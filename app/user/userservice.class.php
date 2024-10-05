<?php

declare(strict_types=1);

namespace App\User;

use App\Utils\Cache\CacheService;
use App\Utils\Dto\FilterIdDto;
use App\Utils\Parameters\ParameterService;
use App\User\Dto\{
    UserListDto,
    UserCreateDto,
    UserUpdateDto,
    UserGetDto,
    UserGetViewDto
};
use Hidehalo\Nanoid\Client as NanoId;
use Core\Exceptions\ValidationException;
use Core\Base\{Connection, Service};


/**
 * User Service Class
 *
 * This class provides services related to user operations.
 */
abstract class UserService extends Service
{
    private const CACHE_PREFIX = 'identity-provider:user';

    /**
     * Retrieves a user based on the provided filter.
     *
     * @param mixed $filter The filter to search for the user.
     * @return UserGetDto|false The user data or false if not found.
     */
    public static function get($filter, string $template = UserGetDto::class): UserGetDto|UserGetViewDto|false
    {
        $filterDto = new FilterIdDto([
            'filter' => [
                'keyTypes' => ['externalId', 'email', 'username'],
                'condition' => $filter
            ]
        ]);
        ['key' => $key, 'keyType' => $keyType] = $filterDto->getFilterData();

        $cacheKey = self::CACHE_PREFIX . ":{$keyType}:{$key}";
        $cache = CacheService::get($cacheKey, true);

        if ($cache === '') {
            return false;
        } elseif ($cache) {
            return new $template(json_decode($cache, true), true);
        }

        $connection = Connection::open('postgresql', 'env_ip_db');

        $query = $connection->query("
            SELECT *
            FROM \"User\"
            WHERE \"{$keyType}\" = :key
        ", [':key' => $key]);

        $user = $query->fetch();

        if (!$user) {
            CacheService::set($cacheKey, '', 300);
            return false;
        }

        $user['customAttributes'] = json_decode($user['customAttributes'], true);
        $userDto = new $template($user, true);

        CacheService::mset([
            self::CACHE_PREFIX . ":id:{$user['id']}" => json_encode($user),
            self::CACHE_PREFIX . ":email:{$user['email']}" => json_encode($user),
            self::CACHE_PREFIX . ":username:{$user['username']}" => json_encode($user)
        ]);

        return $userDto;
    }

    /**
     * Lists users based on the provided criteria.
     *
     * @param array $data Listing criteria.
     * @return array List of users.
     */
    public static function list(array $data, string $template = UserGetDto::class): array
    {
        $dto = new UserListDto($data);

        $cacheHash = md5(json_encode($dto) . $template);
        $cacheKey = self::CACHE_PREFIX . ":list:{$cacheHash}";

        $cacheList = CacheService::get("{$cacheKey}:data");
        $cacheTime = CacheService::get("{$cacheKey}:cacheTime");

        if ($cacheList && $cacheTime) {
            return array_map(
                fn($item) => new $template($item, true),
                json_decode($cacheList, true)
            );
        }

        $connection = Connection::open('postgresql', 'env_ip_db');
        $namedParams = [];
        $filterCondition = [];

        if (!empty($dto->filters->accountStatus)) {
            $filterCondition[] = '"accountStatus" = :accountStatus';
            $namedParams[":accountStatus"] = $dto->filters->accountStatus;
        }

        $filterCondition = !empty($filterCondition) ? ' AND ' . implode(' AND ', $filterCondition) : '';

        $searchCondition = '';
        if (!empty($dto->search)) {
            $searchCondition = ' AND ("username" LIKE :search OR "email" LIKE :search)';
            $namedParams[":search"] = "%{$dto->search}%";
        }

        $pagination = " LIMIT :limit OFFSET :offset";
        $namedParams[":limit"] = $dto->rowsPerPage;
        $namedParams[":offset"] = !empty($dto->page) ? $dto->rowsPerPage * ($dto->page - 1) : 0;

        $selectStatement = "
            SELECT *
            FROM \"User\"
            WHERE 1 = 1
            {$filterCondition}
            {$searchCondition}
            {$pagination}";

        $query = $connection->query($selectStatement, $namedParams);
        $result = [];

        while (($item = $query->fetch()) !== false) {
            $item['customAttributes'] = json_decode($item['customAttributes'], true);
            $result[] = new $template($item, true);
        }

        CacheService::mset([
            "{$cacheKey}:data" => json_encode($result),
            "{$cacheKey}:cacheTime" => date("c")
        ]);

        return $result;
    }

    /**
     * Creates a new user.
     *
     * @param array $data User data.
     * @param bool $transaction Whether to use a transaction.
     * @return UserGetDto The created user data.
     */
    public static function create(array $data, bool $transaction = true): UserGetDto
    {
        $newNanoid = (new NanoId())->generateId(21, NanoId::MODE_DYNAMIC);
        $data['externalId'] = $newNanoid;

        $dto = new UserCreateDto($data);

        $userFindByEmail = self::get(['email' => $dto->email]);

        if ($userFindByEmail) {
            throw new ValidationException(json_encode([
                'email' => "O email {$dto->email} já está sendo utilizado"
            ]));
        }

        $userFindByUsername = self::get(['username' => $dto->email]);

        if ($userFindByUsername) {
            throw new ValidationException(json_encode([
                'username' => "O username {$dto->username} já está sendo utilizado"
            ]));
        }

        $connection = Connection::open('postgresql', 'env_ip_db');

        try {

            if ($transaction) {
                $connection->beginTransaction();
            }

            $salt = bin2hex(random_bytes(16));
            $passwordHash = password_hash($dto->password . $salt, PASSWORD_ARGON2ID);
            $systemSalt = $_ENV['env_system_salt'];
            $finalHash = hash('sha256', $passwordHash . $systemSalt);

            $sqlStatement = '
                INSERT INTO "User" (
                    "externalId",
                    "username",
                    "email",
                    "phoneNumber",
                    "passwordHash",
                    "passwordSalt",
                    "accountStatus",
                    "emailVerified",
                    "phoneVerified",
                    "mfaEnabled",
                    "customAttributes",
                    "updatedAt"
                ) VALUES (
                    :externalId,
                    :username,
                    :email,
                    :phoneNumber,
                    :passwordHash,
                    :passwordSalt,
                    :accountStatus,
                    :emailVerified,
                    :phoneVerified,
                    :mfaEnabled,
                    :customAttributes,
                    CURRENT_TIMESTAMP
                )';

            $connection->query($sqlStatement, [
                ":externalId" => $newNanoid,
                ":username" => $dto->username,
                ":email" => $dto->email,
                ":phoneNumber" => $dto->phoneNumber,
                ":passwordHash" => $finalHash,
                ":passwordSalt" => $salt,
                ":accountStatus" => 'PENDING',
                ":emailVerified" => 'f',
                ":phoneVerified" => 'f',
                ":mfaEnabled" => 'f',
                ":customAttributes" => json_encode($dto->customAttributes ?? [])
            ]);

            if ($transaction) {
                $connection->commit();
            }
        } catch (\Throwable $t) {
            if ($transaction) {
                $connection->rollback();
            }
            throw $t;
        }

        CacheService::mdel([
            self::CACHE_PREFIX . ":externalId:{$newNanoid}",
            self::CACHE_PREFIX . ":email:{$dto->email}",
            self::CACHE_PREFIX . ":username:{$dto->username}"
        ]);

        CacheService::set(self::CACHE_PREFIX . ":list:lastUpdate", date("c"));

        return self::get(['externalId' => $newNanoid]);
    }

    /**
     * Updates an existing user.
     *
     * @param array $data User data.
     * @param mixed $filter The filter to find the user.
     * @param bool $transaction Whether to use a transaction.
     * @return UserGetDto The updated user data.
     */
    public static function update(array $data, $filter, bool $transaction = true): UserGetDto
    {
        $connection = Connection::open('postgresql', 'env_ip_db');

        $user = self::get($filter);

        if (!$user) {
            throw new ValidationException(json_encode(['id' => 'User not found.']));
        }

        $data = array_merge(json_decode(json_encode($user), true), $data ?? []);
        $dto = new UserUpdateDto($data);

        try {
            if ($transaction) {
                $connection->beginTransaction();
            }

            $updateFields = [];
            $updateParams = [];

            foreach ($dto as $key => $value) {
                if ($value !== null && $key !== 'id') {
                    $updateFields[] = "\"{$key}\" = :{$key}";
                    $updateParams[":{$key}"] = $value;
                }
            }

            if (!empty($updateFields)) {
                $updateFields[] = "\"updatedAt\" = CURRENT_TIMESTAMP";
                $updateStatement = "UPDATE \"User\" SET " . implode(', ', $updateFields) . " WHERE \"externalId\" = :id";
                $updateParams[':id'] = $user->externalId;

                $connection->query($updateStatement, $updateParams);
            }

            if ($transaction) {
                $connection->commit();
            }
        } catch (\Throwable $t) {
            if ($transaction) {
                $connection->rollback();
            }
            throw $t;
        }

        CacheService::mdel([
            self::CACHE_PREFIX . ":externalId:{$user->externalId}",
            self::CACHE_PREFIX . ":email:{$user->email}",
            self::CACHE_PREFIX . ":username:{$user->username}"
        ]);

        CacheService::set(self::CACHE_PREFIX . ":list:lastUpdate", date("c"));

        return self::get(['externalId' => $user->externalId]);
    }

    /**
     * Deletes a user.
     *
     * @param mixed $filter The filter to find the user.
     * @param bool $transaction Whether to use a transaction.
     * @return UserGetDto The deleted user data.
     */
    public static function delete($filter, bool $transaction = true): UserGetDto
    {
        $connection = Connection::open('postgresql', 'env_ip_db');

        $user = self::get($filter);

        if (!$user) {
            throw new ValidationException(json_encode(['id' => 'User not found.']));
        }

        try {
            if ($transaction) {
                $connection->beginTransaction();
            }

            $deleteStatement = "DELETE FROM \"User\" WHERE \"externalId\" = :externalId";
            $connection->query($deleteStatement, [':externalId' => $user->externalId]);

            if ($transaction) {
                $connection->commit();
            }
        } catch (\Throwable $t) {
            if ($transaction) {
                $connection->rollback();
            }
            throw $t;
        }

        CacheService::mdel([
            self::CACHE_PREFIX . ":externalId:{$user->externalId}",
            self::CACHE_PREFIX . ":email:{$user->email}",
            self::CACHE_PREFIX . ":username:{$user->username}"
        ]);

        CacheService::set(self::CACHE_PREFIX . ":list:lastUpdate", date("c"));

        return $user;
    }
}
