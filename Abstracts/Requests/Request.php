<?php

namespace Apiato\Core\Abstracts\Requests;

use Apiato\Core\Abstracts\Models\UserModel as User;
use Apiato\Core\Abstracts\Transporters\Transporter;
use Apiato\Core\Exceptions\UndefinedTransporterException;
use Apiato\Core\Traits\HashIdTrait;
use Apiato\Core\Traits\SanitizerTrait;
use Apiato\Core\Traits\StateKeeperTrait;
use Illuminate\Foundation\Http\FormRequest as LaravelRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

/**
 * Class Request.
 *
 * A.K.A (app/Http/Requests/Request.php)
 */
abstract class Request extends LaravelRequest
{
    use HashIdTrait;
    use SanitizerTrait;
    use StateKeeperTrait;

    /**
     * The transporter to be "casted" to.
     */
    protected ?string $transporter = null;

    /**
     * To be used mainly from unit tests.
     */
    public static function injectData(array $parameters = [], ?User $user = null, array $cookies = [], array $files = [], array $server = []): self
    {
        // if user is passed, will be returned when asking for the authenticated user using `\Auth::user()`
        if ($user) {
            $app = App::getInstance();
            $app['auth']->guard($driver = 'api')->setUser($user);
            $app['auth']->shouldUse($driver);
        }

        // For now doesn't matter which URI or Method is used.
        $request = parent::create('/', 'GET', $parameters, $cookies, $files, $server);

        $request->setUserResolver(static fn () => $user);

        return $request;
    }

    /**
     * Check if a user has permission to perform an action.
     * User can set multiple permissions (separated with "|") and if the user has
     * any of the permissions, he will be authorize to proceed with this action.
     */
    public function hasAccess(?User $user = null): bool
    {
        // if not in parameters, take from the request object {$this}
        $user = $user ?: $this->user();

        if ($user) {
            $autoAccessRoles = Config::get('apiato.requests.allow-roles-to-access-all-routes');
            // there are some roles defined that will automatically grant access
            if (!empty($autoAccessRoles)) {
                $hasAutoAccessByRole = $user->hasAnyRole($autoAccessRoles);

                if ($hasAutoAccessByRole) {
                    return true;
                }
            }
        }

        // check if the user has any role / permission to access the route
        $hasAccess = array_merge(
            $this->hasAnyPermissionAccess($user),
            $this->hasAnyRoleAccess($user)
        );

        // allow access if user has access to any of the defined roles or permissions.
        return empty($hasAccess) ? true : in_array(true, $hasAccess);
    }

    /**
     * @param $user
     */
    private function hasAnyPermissionAccess($user): array
    {
        if (!array_key_exists('permissions', $this->access) || !$this->access['permissions']) {
            return [];
        }

        $permissions = is_array($this->access['permissions']) ? $this->access['permissions'] :
            explode('|', $this->access['permissions']);

        return array_map(static fn ($permission) => $user->hasPermissionTo($permission), $permissions);
    }

    /**
     * @param $user
     */
    private function hasAnyRoleAccess($user): array
    {
        if (!array_key_exists('roles', $this->access) || !$this->access['roles']) {
            return [];
        }

        $roles = is_array($this->access['roles']) ? $this->access['roles'] :
            explode('|', $this->access['roles']);

        return array_map(static fn ($role) => $user->hasRole($role), $roles);
    }

    /**
     * Maps Keys in the Request.
     * For example, ['data.attributes.name' => 'firstname'] would map the field [data][attributes][name] to [firstname].
     * Note that the old value (data.attributes.name) is removed the original request - this method manipulates the request!
     * Be sure you know what you do!
     */
    public function mapInput(array $fields): void
    {
        $data = $this->all();

        foreach ($fields as $oldKey => $newKey) {
            // the key to be mapped does not exist - skip it
            if (!Arr::has($data, $oldKey)) {
                continue;
            }

            // set the new field and remove the old one
            Arr::set($data, $newKey, Arr::get($data, $oldKey));
            Arr::forget($data, $oldKey);
        }

        // overwrite the initial request
        $this->replace($data);
    }

    /**
     * Overriding this function to modify the any user input before
     * applying the validation rules.
     *
     * @param null $keys
     */
    public function all($keys = null): array
    {
        $requestData = parent::all($keys);

        $requestData = $this->mergeUrlParametersWithRequestData($requestData);

        return $this->decodeHashedIdsBeforeValidation($requestData);
    }

    /**
     * apply validation rules to the ID's in the URL, since Laravel
     * doesn't validate them by default!
     * Now you can use validation rules like this: `'id' => 'required|integer|exists:items,id'`.
     */
    private function mergeUrlParametersWithRequestData(array $requestData): array
    {
        if (isset($this->urlParameters) && !empty($this->urlParameters)) {
            foreach ($this->urlParameters as $param) {
                $requestData[$param] = $this->route($param);
            }
        }

        return $requestData;
    }

    /**
     * This method mimics the $request->input() method but works on the "decoded" values.
     *
     * @param $key
     * @param $default
     */
    public function getInputByKey($key = null, $default = null)
    {
        return data_get($this->all(), $key, $default);
    }

    /**
     * Used from the `authorize` function if the Request class.
     * To call functions and compare their bool responses to determine
     * if the user can proceed with the request or not.
     */
    protected function check(array $functions): bool
    {
        $orIndicator = '|';
        $returns     = [];

        // iterate all functions in the array
        foreach ($functions as $function) {

            // in case the value doesn't contains a separator (single function per key)
            if (!strpos($function, $orIndicator)) {
                // simply call the single function and store the response.
                $returns[] = $this->{$function}();
            } else {
                // in case the value contains a separator (multiple functions per key)
                $orReturns = [];

                // iterate over each function in the key
                foreach (explode($orIndicator, $function) as $orFunction) {
                    // dynamically call each function
                    $orReturns[] = $this->{$orFunction}();
                }

                // if in_array returned `true` means at least one function returned `true` thus return `true` to allow access.
                // if in_array returned `false` means no function returned `true` thus return `false` to prevent access.
                // return single boolean for all the functions found inside the same key.
                $returns[] = in_array(true, $orReturns) ? true : false;
            }
        }

        // if in_array returned `true` means a function returned `false` thus return `false` to prevent access.
        // if in_array returned `false` means all functions returned `true` thus return `true` to allow access.
        // return the final boolean
        return in_array(false, $returns) ? false : true;
    }

    /**
     * Returns the Transporter (if correctly set).
     *
     * @throws UndefinedTransporterException
     */
    public function getTransporter(): string
    {
        if ($this->transporter === null) {
            throw new UndefinedTransporterException();
        }

        return $this->transporter;
    }

    /**
     * Transforms the Request into a specified Transporter class.
     *
     * @psalm-return Transporter
     */
    public function toTransporter()
    {
        $transporterClass = $this->getTransporter();

        return new $transporterClass($this);
    }
}
