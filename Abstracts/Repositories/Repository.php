<?php

namespace Apiato\Core\Abstracts\Repositories;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Prettus\Repository\Contracts\CacheableInterface as PrettusCacheable;
use Prettus\Repository\Eloquent\BaseRepository as PrettusRepository;
use Prettus\Repository\Traits\CacheableRepository as PrettusCacheableRepository;

abstract class Repository extends PrettusRepository implements PrettusCacheable
{
    use PrettusCacheableRepository;

    /**
     * Define the maximum amount of entries per page that is returned.
     * Set to 0 to "disable" this feature.
     */
    protected int $maxPaginationLimit = 0;

    /**
     * This function relies on strict conventions.
     * Conventions:
     *    - Repository name should be same like it's model name (model: Foo -> repository: FooRepository).
     *    - If the container contains Models with names different than the container name, the repository class must
     *          implement model() method and return the FQCN e.g. Role::class
     * Specify Model class name.
     */
    public function model(): string
    {
        // 1_ get the full namespace of the child class who's extending this class.
        // 2_ remove the namespace and keep the class name
        // 3_ remove the word Repository from the class name
        // 4_ check if the container name is set on the repository to indicate that the
        //    model has different name than the container holding it
        // 5_ build the namespace of the Model based on the conventions

        $fullName       = static::class;
        $className      = substr($fullName, strrpos($fullName, '\\') + 1);
        $classOnly      = str_replace('Repository', '', $className);
        $modelNamespace = 'App\\Containers\\' . $this->getCurrentSection() . '\\' . $this->getCurrentContainer() . '\\Models\\' . $classOnly;

        return $modelNamespace;
    }

    private function getCurrentSection(): string
    {
        return explode('\\', static::class)[2];
    }

    private function getCurrentContainer(): string
    {
        return explode('\\', static::class)[3];
    }

    /**
     * Boot up the repository, pushing criteria.
     */
    public function boot()
    {
    }

    /**
     * Paginate the response
     * Apply pagination to the response. Use ?limit= to specify the amount of entities in the response.
     * The client can request all data (skipping pagination) by applying ?limit=0 to the request, if
     * PAGINATION_SKIP is set to true.
     *
     * @param null   $limit
     * @param array  $columns
     * @param string $method
     *
     * @psalm-return mixed
     */
    public function paginate($limit = null, $columns = ['*'], $method = 'paginate')
    {
        // the priority is for the function parameter, if not available then take
        // it from the request if available and if not keep it null.
        $limit = $limit ?: Request::get('limit');

        // check, if skipping pagination is allowed and requested by the user
        if ($limit === '0' && Config::get('repository.pagination.skip')) {
            return $this->all($columns);
        }

        // check for the maximum entries per pagination
        if (is_int($this->maxPaginationLimit)
            && $this->maxPaginationLimit > 0
            && $limit > $this->maxPaginationLimit
        ) {
            $limit = $this->maxPaginationLimit;
        }

        return parent::paginate($limit, $columns, $method);
    }
}
