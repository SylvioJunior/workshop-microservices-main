<?php

declare(strict_types=1);

namespace App\User;

use App\User\Dto\UserGetViewDto;
use Core\Base\Request;
use Core\Exceptions\ItemNotFoundException;

use App\User\UserService;

/**
 * Class UserController
 *
 * Controller for user-related operations.
 */
abstract class UserController
{
    /**
     * List users with optional filters and pagination.
     *
     * @return array
     */
    public static function list(): array
    {
        Request::validateTypes([
            'search' => ['string', 'null'],
            'page' => ['integer', 'null'],
            'filters' => ['array', 'null'],
            'rowsPerPage' => ['integer', 'null']
        ]);

        $payload = Request::$payload ?? [];
        $search = $payload['search'] ?? null;
        $page = (int) ($payload['page'] ?? 1);
        $filters = $payload['filters'] ?? [];
        $rowsPerPage = (int) ($payload['rowsPerPage'] ?? 10);

        $list = UserService::list([
            'search' => $search,
            'page' => $page,
            'filters' => $filters,
            'rowsPerPage' => $rowsPerPage
        ], UserGetViewDto::class);

        return [
            'status' => 200,
            'meta' => [
                'rows_per_page' => $rowsPerPage,
                'page' => $page
            ],
            'data' => $list
        ];
    }

    /**
     * Get a specific user.
     *
     * @return array
     * @throws ItemNotFoundException
     */
    public static function get(): array
    {
        Request::validateTypes([
            'id' => ['string', 'null'],
            'email' => ['string', 'null'],
            'externalId' => ['string', 'null']
        ]);

        extract(Request::$data ?? [], EXTR_SKIP);

        $filter = array_filter(@compact('id', 'email', 'externalId'));

        $user = UserService::get($filter, UserGetViewDto::class);

        if (!$user) {
            throw new ItemNotFoundException("User not found.");
        }

        return [
            'status' => 200,
            'data' => $user
        ];
    }

    /**
     * Create a new user.
     *
     * @return array
     */
    public static function create(): array
    {
        Request::validateTypes([]);

        $user = UserService::create(Request::$payload);

        return [
            'status' => 200,
            'data' => $user
        ];
    }

    /**
     * Update an existing user.
     *
     * @return array
     */
    public static function update(): array
    {
        Request::validateTypes([
            'id' => ['string', 'null']
        ]);

        $id = Request::$data['id'] ?? null;

        $user = UserService::update(Request::$payload, $id);

        return [
            'status' => 200,
            'data' => $user
        ];
    }

    /**
     * Delete a user.
     *
     * @return array
     * @throws ItemNotFoundException
     */
    public static function delete(): array
    {
        Request::validateTypes([
            'id' => ['string', 'null']
        ]);

        $id = Request::$data['id'] ?? null;

        $user = UserService::delete($id);

        if (!$user) {
            throw new ItemNotFoundException("User not found.");
        }

        return [
            'status' => 200,
            'data' => $user
        ];
    }
}
