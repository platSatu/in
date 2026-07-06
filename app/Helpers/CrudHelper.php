<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class CrudHelper
{
    protected static string $userColumn = 'user_id';
    protected static string $parentColumn = 'parent_id';
    protected static int $cacheMinutes = 60;
    protected static int $defaultPerPage = 10;

    /**
     * Generate cache key unik
     */
    private static function generateCacheKey(string $prefix, array $params = []): string
    {
        $userId = Auth::id() ?? 'guest';
        $paramsString = !empty($params) ? '_' . implode('_', $params) : '';
        
        return "crud_{$prefix}_{$userId}{$paramsString}";
    }

    /**
     * 1. getAll: Menampilkan semua data dengan paginate & search
     */
    public static function getAll(
        string $model, 
        array $with = [], 
        ?string $search = null, 
        ?array $searchColumns = null,
        ?int $perPage = null
    ): mixed
    {
        $perPage = $perPage ?? self::$defaultPerPage;
        $table = (new $model())->getTable();
        
        // Cache key includes page dan search query
        $cacheKey = self::generateCacheKey("{$table}_all", [
            'page' => request('page', 1),
            'search' => $search,
            'perPage' => $perPage
        ]);

        return Cache::remember($cacheKey, self::$cacheMinutes, function () use ($model, $with, $search, $searchColumns, $perPage) {
            $query = (new $model())->newQuery();

            // Apply search jika ada
            if ($search && !empty($searchColumns)) {
                $query->where(function ($q) use ($search, $searchColumns) {
                    foreach ($searchColumns as $column) {
                        $q->orWhere($column, 'like', "%{$search}%");
                    }
                });
            }

            // Apply eager loading
            if (!empty($with)) {
                $query->with($with);
            }

            // Return sebagai LengthAwarePaginator
            return $query->orderByDesc('created_at')->paginate($perPage);
        });
    }

    /**
     * 2. getByUser: Data milik user login & parent_id dengan paginate & search
     */
    public static function getByUser(
        string $model, 
        array $with = [], 
        ?string $search = null, 
        ?array $searchColumns = null,
        ?int $perPage = null
    ): mixed
    {
        $userId = Auth::id();
        $perPage = $perPage ?? self::$defaultPerPage;
        $table = (new $model())->getTable();
        
        $cacheKey = self::generateCacheKey("{$table}_my_data", [
            'page' => request('page', 1),
            'search' => $search,
            'perPage' => $perPage
        ]);

        return Cache::remember($cacheKey, self::$cacheMinutes, function () use ($model, $table, $with, $search, $searchColumns, $perPage, $userId) {
            $query = (new $model())->newQuery()
                ->where(function ($q) use ($table, $userId) {
                    $q->where("{$table}." . self::$userColumn, $userId)
                        ->orWhere("{$table}." . self::$parentColumn, $userId);
                });

            // Apply search jika ada
            if ($search && !empty($searchColumns)) {
                $query->where(function ($q) use ($search, $searchColumns) {
                    foreach ($searchColumns as $column) {
                        $q->orWhere($column, 'like', "%{$search}%");
                    }
                });
            }

            // Apply eager loading
            if (!empty($with)) {
                $query->with($with);
            }

            return $query->orderByDesc('created_at')->paginate($perPage);
        });
    }

    /**
     * 3. Store: Insert data langsung dengan relasi
     */
    public static function store(string $model, array $data, array $with = []): Model
    {
        $userId = Auth::id();

        return DB::transaction(function () use ($model, $data, $userId, $with) {
            DB::table('users')
                ->lockForUpdate()
                ->find($userId);

            $data[self::$userColumn] = $userId;

            $modelInstance = new $model();
            $modelInstance->fill($data);
            $modelInstance->save();

            if (!empty($with)) {
                $modelInstance->load($with);
            }

            self::clearCache($model);

            return $modelInstance;
        });
    }

    /**
     * 4. findById: Cari data berdasarkan ID
     */
    public static function findById(string $model, int $id, array $with = []): Model
    {
        $userId = Auth::id();
        $table = (new $model())->getTable();
        $cacheKey = self::generateCacheKey("{$table}_detail", [$id]);

        return Cache::remember($cacheKey, self::$cacheMinutes, function () use ($model, $id, $table, $with, $userId) {
            $query = $model::where("{$table}.id", $id);

            $query->where(function ($q) use ($table, $userId) {
                $q->where("{$table}." . self::$userColumn, $userId)
                    ->orWhere("{$table}." . self::$parentColumn, $userId);
            });

            if (!empty($with)) {
                $query->with($with);
            }

            return $query->firstOrFail();
        });
    }

    /**
     * 5. Update: Update data langsung dengan relasi
     */
    public static function update(string $model, int $id, array $data, array $with = []): Model
    {
        return DB::transaction(function () use ($model, $id, $data, $with) {
            $table = (new $model())->getTable();
            $record = self::findById($model, $id);

            DB::table($table)
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            $record->fill($data)->save();

            if (!empty($with)) {
                $record->load($with);
            }

            self::clearCache($model, $id);

            return $record;
        });
    }

    /**
     * 6. Delete: Hapus data
     */
    public static function delete(string $model, int $id): bool
    {
        $table = (new $model())->getTable();
        $record = self::findById($model, $id);

        DB::table($table)
            ->where('id', $id)
            ->lockForUpdate()
            ->first();

        $result = $record->delete();

        if ($result) {
            self::clearCache($model, $id);
        }

        return $result;
    }

    /**
     * Clear Cache
     */
    public static function clearCache(string $model, ?int $id = null): void
    {
        $userId = Auth::id() ?? 'guest';
        $table = (new $model())->getTable();

        Cache::forget("crud_{$table}_all_{$userId}");
        Cache::forget("crud_{$table}_my_data_{$userId}");

        if ($id) {
            Cache::forget("crud_{$table}_detail_{$id}_{$userId}");
        }
    }
}