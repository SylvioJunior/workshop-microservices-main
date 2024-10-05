# Usando Serviços

A estrutura base se chama Services e serve para criar uma camada de serviços (regra de negócios) na aplicação. Os métodos da classe podem ser chamados via Controladores ou entre a própria camada de serviços. Esta documentação visa fornecer uma visão geral sobre como os serviços devem ser criados, configurados e utilizados na aplicação.

## Estrutura da Classe Service

A classe base Service se encontra no arquivo `core/base/service.class.php`. Esta classe fornece a funcionalidade de manipulação de dados, validações e tratamento de exceções.

### Métodos Comuns

Os serviços geralmente possuem métodos comuns para manipulação de dados, como:

- `get()`: Obtém um item específico.
- `list()`: Lista os itens com filtros e paginação opcionais.
- `create()`: Cria um novo item.
- `update()`: Atualiza um item existente.
- `delete()`: Deleta um item.

## Exemplo de Uso

Abaixo está um exemplo de como um serviço pode ser escrito e utilizado:

### CompanyService

```php
<?php

declare(strict_types=1);

/**
 * Company Service Class
 *
 * PHP Version 8
 *
 * @category Service
 * @package  App\Companies
 *
 * @author  Pedro Henrique Rosa <pedrohenriquerb@gmail.com>
 * @license http://no.tld Proprietary License
 * @link    http://no.tld
 */

namespace App\Companies;

use Hidehalo\Nanoid\Client as NanoId;

use Core\Base\Service;
use Core\Exceptions\ItemNotFoundException;
use Core\Exceptions\ValidationException;

use App\BusinessUnits\BusinessUnitService;
use app\companies\dto\CompanyGetCompactDto;
use App\Workspaces\WorkspaceService;
use App\Utils\Cache\CacheService;
use App\Utils\Dto\FilterIdDto;

use App\Companies\Dto\CompanyListDto;
use App\Companies\Dto\CompanyNewDto;
use App\Companies\Dto\CompanyUpdateDto;
use App\Companies\Dto\CompanyGetDto;

/**
 * Company Service Class
 *
 * This class provides services related to company operations.
 */

abstract class CompanyService extends Service
{
    /**
     * Retrieve a company based on the provided filter.
     *
     * @param mixed $filter The filter to search for the company.
     * @return CompanyGetDto|false The company data or false if not found.
     */
    public static function get($filter)
    {
        $workspaceId = self::getWorkspaceId();
        $userId = self::getUserId();

        // Capture key type and value for the search
        ['key' => $key, 'keyType' => $keyType] = (new FilterIdDto([
            'filter' => [
                'keyTypes' => ['id', 'taxId'],
                'condition' => $filter
            ]
        ]))->getFilterData();

        $cachePrefix = self::$workspace->type === 'Shared'
            ? "workspace:{$workspaceId}:{$userId}:company"
            : "workspace:{$workspaceId}:company";

        $cache = CacheService::get("{$cachePrefix}:{$keyType}:{$key}", true);

        if ($cache === '') {
            return false;
        } elseif ($cache) {
            $cacheItem = json_decode($cache, true);
            BusinessUnitService::setContext(self::$workspace, self::$user);

            $businessUnit = BusinessUnitService::get(['id' => $cacheItem['businessUnitId']]);
            $cacheItem['businessUnitId'] = $businessUnit->id;
            $cacheItem['businessUnitName'] = $businessUnit->name;

            return new CompanyGetDto($cacheItem, true);
        }

        $connection = WorkspaceService::dbConnect(self::$workspace);
        $namedParams = [':key' => $key];
        $userCondition = self::$workspace->type === 'Shared' ? ' AND co."userUuid" = :userUuid' : '';
        if ($userCondition) {
            $namedParams[':userUuid'] = self::getUserId();
        }

        // Determine the key for searching
        $sqlKey = $keyType === 'taxId' ? ' co."taxId" = :key ' : ' co."nanoid" = :key ';

        $selectStatement = "
            SELECT
                co.\"nanoid\" as id,
                co.\"name\",
                co.\"status\",
                co.\"legalName\",
                co.\"taxId\",
                co.\"website\",
                co.\"socialLinkedin\",
                co.\"city\",
                co.\"state\",
                co.\"logo\",
                co.\"email\",
                co.\"phone\",
                co.\"yearOfBirth\",
                bu.\"nanoid\" as \"businessUnitId\",
                bu.\"name\" as \"businessUnitName\",
                co.\"createdOn\",
                co.\"modifiedOn\",
                co.\"userUuid\"
            FROM
                \"company\" AS co
                INNER JOIN \"business-unit\" AS bu ON bu.\"id\" = co.\"businessUnitId\"
            WHERE
                {$sqlKey} {$userCondition}";

        $result = $connection->query($selectStatement, $namedParams);
        $item = $result->fetch();

        CacheService::set("{$cachePrefix}:{$keyType}:{$key}", $item ? json_encode($item) : null);

        return $item ? new CompanyGetDto($item, true) : false;
    }

    /**
     * List companies based on search criteria.
     *
     * @param string|null $search Search term.
     * @param int $page Current page number.
     * @param array $filters Filters to apply.
     * @param string $format Format of the response.
     * @param int $rowsPerPage Number of rows per page.
     * @return array List of companies.
     */
    public static function list(
        ?string $search = null,
        int $page = 1,
        array $filters = [],
        string $format = 'full',
        int $rowsPerPage = 10
    ): array {

        $workspaceId = self::getWorkspaceId();
        $userId = self::getUserId();

        if (self::$workspace->type === 'Shared') {
            $filters['userId'] = self::getUserId();
        } else {
            $filters['userId'] = null;
        }

        $cachePrefix = self::$workspace->type === 'Shared'
            ? "workspace:{$workspaceId}:{$userId}:company"
            : "workspace:{$workspaceId}:company";

        $dto = new CompanyListDto(compact('search', 'page', 'filters', 'rowsPerPage', 'format'));

        $formatMap = [
            'full' => CompanyGetDto::class,
            'compact' => CompanyGetCompactDto::class
        ];

        $cacheHash = hash('sha256', json_encode($dto));
        [$cacheList, $cacheTime, $lastUpdate] = CacheService::mget([
            "{$cachePrefix}:list:{$cacheHash}:data",
            "{$cachePrefix}:list:{$cacheHash}:cacheTime",
            "{$cachePrefix}:list:lastUpdate"
        ]);

        if ($cacheList && json_decode($cacheList) && (!$lastUpdate || strtotime($cacheTime) >= strtotime($lastUpdate))) {
            $result = [];
            $cacheListDecoded = json_decode($cacheList, true);

            foreach ($cacheListDecoded as $item) {
                $result[] = new $formatMap[$dto->format]($item, true);
            }

            return $result;
        }

        $connection = WorkspaceService::dbConnect(self::$workspace);
        $namedParams = [];
        $filterCondition = [];

        // Field filters
        if (!empty($dto->filters->userId)) {
            $filterCondition[] = 'c."userUuid" = :userUuid';
            $namedParams[':userUuid'] = $dto->filters->userId;
        }

        if (!empty($dto->filters->status)) {
            $filterCondition[] = 'c."status" = :filter_status';
            $namedParams[":filter_status"] = $dto->filters->status;
        }

        if (!empty($dto->filters->businessUnitId)) {
            $filterCondition[] = 'b."nanoid" = :filter_businessUnitId';
            $namedParams[":filter_businessUnitId"] = $dto->filters->businessUnitId;
        }

        $filterCondition = !empty($filterCondition) ? ' AND ' . implode(' AND ', $filterCondition) : '';

        // Search filter
        $searchCondition = null;
        if (!empty($dto->search)) {
            $searchCondition = ' AND (c."name" LIKE :search_name OR c."legalName" LIKE :search_name OR c."taxId" LIKE :search_name OR c."status" LIKE :search_status)';
            $namedParams[":search_name"] = $dto->search;
            $namedParams[":search_status"] = $dto->search;
        }

        // Pagination
        $pagination = " LIMIT :limit OFFSET :offset";
        $namedParams[":limit"] = $dto->rowsPerPage;
        $namedParams[":offset"] = !empty($dto->page) ? $rowsPerPage * ($dto->page - 1) : 0;

        $selectStatement = "
            SELECT
                c.\"nanoid\" as id,
                c.\"name\",
                c.\"status\",
                c.\"legalName\",
                c.\"taxId\",
                c.\"website\",
                c.\"socialLinkedin\",
                c.\"city\",
                c.\"cityCode\",
                c.\"state\",
                c.\"stateCode\",
                c.\"logo\",
                c.\"email\",
                c.\"phone\",
                c.\"yearOfBirth\",
                b.\"nanoid\" as \"businessUnitId\",
                b.\"name\" as \"businessUnitName\",
                c.\"createdOn\",
                c.\"modifiedOn\"
            FROM
                \"company\" AS c
                INNER JOIN \"business-unit\" AS b ON b.\"id\" = c.\"businessUnitId\"
            WHERE
                1 = 1
                {$filterCondition}
                {$searchCondition}
                {$pagination}";

        $query = $connection->query($selectStatement, $namedParams);
        $result = [];

        while (($item = $query->fetch()) !== false) {
            $result[] = new $formatMap[$dto->format]($item, true);
        }

        CacheService::mset([
            "{$cachePrefix}:list:{$cacheHash}:data" => json_encode($result),
            "{$cachePrefix}:list:{$cacheHash}:cacheTime" => date("c")
        ]);

        return $result;
    }

    /**
     * Create a new company.
     *
     * @param array $data Company data.
     * @param bool $transaction Whether to use a transaction.
     * @return CompanyGetDto The created company data.
     */
    public static function create(array $data, bool $transaction = true): CompanyGetDto
    {
        $workspaceId = self::getWorkspaceId();
        $userId = self::getUserId();

        $dto = new CompanyNewDto($data);
        $cachePrefix = self::$workspace->type === 'Shared'
            ? "workspace:{$workspaceId}:{$userId}:company"
            : "workspace:{$workspaceId}:company";

        $connection = WorkspaceService::dbConnect(self::$workspace);

        try {
            if ($transaction) {
                $connection->beginTransaction();
            }

            BusinessUnitService::setContext(self::$workspace, self::$user);
            $businessUnit = BusinessUnitService::get(['id' => $dto->businessUnitId]);

            if (!$businessUnit) {
                throw new ValidationException(json_encode(['businessUnitId' => 'Business unit not found.']));
            }

            $newNanoid = (new NanoId())->generateId(21, NanoId::MODE_DYNAMIC);

            $sqlStatement = '
                INSERT INTO "company" (
                    "nanoid",
                    "name",
                    "status",
                    "legalName",
                    "taxId",
                    "website",
                    "socialLinkedin",
                    "city",
                    "state",
                    "email",
                    "phone",
                    "yearOfBirth",
                    "businessUnitId",
                    "userUuid",
                    "modifiedOn"
                ) VALUES (
                    :nanoid,
                    :name,
                    :status,
                    :legalName,
                    :taxId,
                    :website,
                    :socialLinkedin,
                    :city,
                    :state,
                    :email,
                    :phone,
                    :yearOfBirth,
                    (SELECT b."id" FROM "business-unit" AS b WHERE b."nanoid"= :businessUnitId),
                    :userUuid,
                    CURRENT_TIMESTAMP
            )';

            $connection->query($sqlStatement, [
                ":nanoid" => $newNanoid,
                ":name" => $dto->name,
                ":status" => $dto->status,
                ":legalName" => $dto->legalName,
                ":taxId" => $dto->taxId,
                ":website" => $dto->website,
                ":socialLinkedin" => $dto->socialLinkedin,
                ":city" => $dto->city,
                ":state" => $dto->state,
                ":email" => $dto->email,
                ":phone" => $dto->phone,
                ":yearOfBirth" => $dto->yearOfBirth,
                ':userUuid' => self::getUserId(),
                ":businessUnitId" => $businessUnit->id
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
            "{$cachePrefix}:id:{$newNanoid}",
            "{$cachePrefix}:taxId:{$dto->taxId}"
        ]);

        CacheService::set("{$cachePrefix}:list:lastUpdate", date("c"));

        return self::get(['id' => $newNanoid]);
    }

    /**
     * Update an existing company.
     *
     * @param array $data Company data.
     * @param mixed $filter The filter to find the company.
     * @param bool $transaction Whether to use a transaction.
     * @return CompanyGetDto The updated company data.
     */
    public static function update(array $data, $filter, bool $transaction = true): CompanyGetDto
    {
        $workspaceId = self::getWorkspaceId();
        $userId = self::getUserId();

        // Capture key type and value for the search
        ['key' => $key, 'keyType' => $keyType] = (new FilterIdDto([
            'filter' => [
                'keyTypes' => ['id', 'taxId'],
                'condition' => $filter
            ]
        ]))->getFilterData();

        $cachePrefix = self::$workspace->type === 'Shared'
            ? "workspace:{$workspaceId}:{$userId}:company"
            : "workspace:{$workspaceId}:company";

        $company = self::get([$keyType => $key]);
        if (!$company) {
            throw new ItemNotFoundException('Company not found');
        }

        $dataToUpdate = array_merge(json_decode(json_encode($company), true), $data ?? []);

        $dto = new CompanyUpdateDto($dataToUpdate);
        $connection = WorkspaceService::dbConnect(self::$workspace);

        try {
            if ($transaction) {
                $connection->beginTransaction();
            }

            BusinessUnitService::setContext(self::$workspace, self::$user);
            $businessUnit = BusinessUnitService::get(['id' => $dto->businessUnitId]);

            if (!$businessUnit) {
                throw new ValidationException(json_encode(['businessUnitId' => 'Business unit not found.']));
            }

            $connection->query('
                UPDATE "company" SET
                    "name" = :name,
                    "status" = :status,
                    "legalName" = :legalName,
                    "taxId" = :taxId,
                    "website" = :website,
                    "socialLinkedin" = :socialLinkedin,
                    "city" = :city,
                    "state" = :state,
                    "email" = :email,
                    "phone" = :phone,
                    "yearOfBirth" = :yearOfBirth,
                    "businessUnitId" = (SELECT b."id" FROM "business-unit" AS b WHERE b."nanoid"= :businessUnitId),
                    "modifiedOn" = CURRENT_TIMESTAMP
                WHERE "nanoid" = :id', [
                ":id" => $company->id,
                ":name" => $dto->name,
                ":status" => $dto->status,
                ":legalName" => $dto->legalName,
                ":taxId" => $dto->taxId,
                ":website" => $dto->website,
                ":socialLinkedin" => $dto->socialLinkedin,
                ":city" => $dto->city,
                ":state" => $dto->state,
                ":email" => $dto->email,
                ":phone" => $dto->phone,
                ":yearOfBirth" => $dto->yearOfBirth,
                ":businessUnitId" => $dto->businessUnitId
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
            "{$cachePrefix}:id:{$company->id}",
            "{$cachePrefix}:taxId:{$dto->taxId}"
        ]);

        if ($company->taxId !== $dto->taxId) {
            CacheService::del("{$cachePrefix}:taxId:{$company->taxId}");
        }

        CacheService::set("{$cachePrefix}:list:lastUpdate", date("c"));

        return self::get(['id' => $company->id]);
    }

    /**
     * Delete a company based on the provided filter.
     *
     * @param mixed $filter The filter to find the company.
     * @param bool $transaction Whether to use a transaction.
     * @return CompanyGetDto The deleted company data.
     */
    public static function delete($filter, bool $transaction = true): CompanyGetDto
    {
        $workspaceId = self::getWorkspaceId();
        $userId = self::getUserId();

        // Capture key type and value for the search
        ['key' => $key, 'keyType' => $keyType] = (new FilterIdDto([
            'filter' => [
                'keyTypes' => ['id', 'taxId'],
                'condition' => $filter
            ]
        ]))->getFilterData();

        $cachePrefix = self::$workspace->type === 'Shared'
            ? "workspace:{$workspaceId}:{$userId}:company"
            : "workspace:{$workspaceId}:company";

        $connection = WorkspaceService::dbConnect(self::$workspace);

        try {
            if ($transaction) {
                $connection->beginTransaction();
            }

            $company = self::get([$keyType => $key]);
            if (!$company) {
                throw new ItemNotFoundException('Company not found');
            }

            $namedParams = [':nanoid' => $company->id];
            $userCondition = self::$workspace->type === 'Shared' ? ' AND "userUuid" = :userUuid' : '';
            if ($userCondition) {
                $namedParams[':userUuid'] = self::getUserId();
            }

            $associateQuery = $connection->query('
                SELECT
                    COUNT(DISTINCT t."id") as num_transactions,
                    COUNT(DISTINCT c2."id") as num_closings
                FROM "company" AS c1
                LEFT JOIN "transaction" AS t ON t."companyId" = c1."id"
                LEFT JOIN "closing" AS c2 ON c2."companyId" = c1."id"
                WHERE
                    c1."nanoid" = :companyUuid', [
                ':companyUuid' => $company->id
            ]);

            $associates = $associateQuery->fetch();
            $errors = [];

            if ($associates['num_transactions'] > 0) {
                $errors['num_transactions'] = 'Company has registered transactions. Remove them first.';
            }

            if ($associates['num_closings'] > 0) {
                $errors['num_closings'] = 'Company has completed closings. Remove them first.';
            }

            if (!empty($errors)) {
                throw new ValidationException(json_encode($errors));
            }

            $connection->query('
                DELETE FROM "company"
                WHERE "nanoid" = :nanoid ' . $userCondition, $namedParams);

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
            "{$cachePrefix}:id:{$company->id}",
            "{$cachePrefix}:taxId:{$company->taxId}"
        ]);

        CacheService::set("{$cachePrefix}:list:lastUpdate", date("c"));

        return $company;
    }
}

```
