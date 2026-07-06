<?php

namespace App\Helpers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AdminCrud
{
    /**
     * Get paginated data with optional user scope, search and eager loading.
     *
     * @param class-string<Model> $modelClass
     * @param string|null $userId
     * @param array<int, string> $searchColumns
     * @param string|null $search
     * @param int $perPage
     * @param array<int, string> $with
     * @param string $latestColumn
     */
    public static function paginate(
        string $modelClass,
        ?string $userId = null,
        array $searchColumns = [],
        ?string $search = null,
        int $perPage = 10,
        array $with = [],
        string $latestColumn = 'created_at'
    ): LengthAwarePaginator {
        $query = $modelClass::query();

        if (!empty($with)) {
            $query->with($with);
        }

        if (
            !is_null($userId) &&
            trim($userId) !== '' &&
            !in_array(strtolower(trim($userId)), ['all', 'superadmin'], true)
        ) {
            $query->where(['user_id' => $userId]);
        }

        if (!empty($search) && !empty($searchColumns)) {
            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        return $query->latest($latestColumn)->paginate($perPage)->withQueryString();
    }

    /**
     * Get all data with optional user scope and eager loading.
     *
     * @param class-string<Model> $modelClass
     * @param string|null $userId
     * @param array<int, string> $with
     */
    public static function all(
        string $modelClass,
        ?string $userId = null,
        array $with = []
    ): Collection {
        $query = $modelClass::query();

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->latest()->get();
    }

    /**
     * Find one row by id with optional user ownership scope.
     *
     * @param class-string<Model> $modelClass
     * @param string $id
     * @param string|null $userId
     * @param array<int, string> $with
     */
    public static function findOrFail(
        string $modelClass,
        string $id,
        ?string $userId = null,
        array $with = []
    ): Model {
        $query = $modelClass::query();

        if (!empty($with)) {
            $query->with($with);
        }

        if (!is_null($userId)) {
            $query->where(['user_id' => $userId]);
        }

        return $query->findOrFail($id);
    }

    /**
     * Create new row.
     *
     * @param class-string<Model> $modelClass
     * @param array<string, mixed> $data
     */
    public static function create(
        string $modelClass,
        array $data
    ): Model {
        return $modelClass::create($data);
    }

    /**
     * Update existing row by id with optional user ownership scope.
     *
     * @param class-string<Model> $modelClass
     * @param string $id
     * @param array<string, mixed> $data
     * @param string|null $userId
     */
    public static function update(
        string $modelClass,
        string $id,
        array $data,
        ?string $userId = null
    ): Model {
        $model = self::findOrFail($modelClass, $id, $userId);
        $model->update($data);

        return $model->refresh();
    }

    /**
     * Delete existing row by id with optional user ownership scope.
     *
     * @param class-string<Model> $modelClass
     * @param string $id
     * @param string|null $userId
     */
    public static function delete(
        string $modelClass,
        string $id,
        ?string $userId = null
    ): bool {
        $model = self::findOrFail($modelClass, $id, $userId);

        return (bool) $model->delete();
    }
}
