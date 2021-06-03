<?php

namespace Apiato\Core\Traits\TestsTraits\PhpUnit;

use Apiato\Core\Exceptions\MissingTestEndpointException;
use Apiato\Core\Exceptions\UndefinedMethodException;
use Apiato\Core\Exceptions\WrongEndpointFormatException;
use Apiato\Core\Foundation\Facades\Apiato;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Vinkla\Hashids\Facades\Hashids;

trait TestsRequestHelperTrait
{
    /**
     * property to be set on the user test class.
     */
    protected string $endpoint = '';

    /**
     * property to be set on the user test class.
     */
    protected bool $auth = true;

    protected TestResponse $response;

    protected string $responseContent;

    protected ?array $responseContentArray = null;

    protected ?stdClass $responseContentObject = null;

    /**
     * Allows users to override the default class property `endpoint` directly before calling the `makeCall` function.
     */
    protected ?string $overrideEndpoint = null;

    /**
     * Allows users to override the default class property `auth` directly before calling the `makeCall` function.
     */
    protected bool $overrideAuth;

    /**
     * @throws UndefinedMethodException
     */
    public function makeCall(array $data = [], array $headers = []): TestResponse
    {
        // Get or create a testing user. It will get your existing user if you already called this function from your
        // test. Or create one if you never called this function from your tests "Only if the endpoint is protected".
        $this->getTestingUser();

        // read the $endpoint property from the test and set the verb and the uri as properties on this trait
        $endpoint = $this->parseEndpoint();
        $verb     = $endpoint['verb'];
        $url      = $endpoint['url'];

        // validating user http verb input + converting `get` data to query parameter
        switch ($verb) {
            case 'get':
                $url = $this->dataArrayToQueryParam($data, $url);
                break;
            case 'post':
            case 'put':
            case 'patch':
            case 'delete':
                break;
            default:
                throw new UndefinedMethodException('Unsupported HTTP Verb (' . $verb . ')!');
        }

        $httpResponse = $this->json($verb, $url, $data, $headers);

        $this->logResponseData($httpResponse);

        return $this->setResponseObjectAndContent($httpResponse);
    }

    /**
     * @throws UndefinedMethodException
     */
    public function makeUploadCall(array $files = [], array $params = [], array $headers = []): TestResponse
    {
        // Get or create a testing user. It will get your existing user if you already called this function from your
        // test. Or create one if you never called this function from your tests "Only if the endpoint is protected".
        $this->getTestingUser();

        // read the $endpoint property from the test and set the verb and the uri as properties on this trait
        $endpoint = $this->parseEndpoint();
        $verb     = $endpoint['verb'];
        $url      = $endpoint['url'];

        // validating user http verb input + converting `get` data to query parameter
        switch ($verb) {
            case 'post':
                break;
            default:
                throw new UndefinedMethodException('Unsupported HTTP Verb (' . $verb . ')!');
        }

        $headers = array_merge([
            'Accept' => 'application/json',
        ], $headers);

        $server  = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        $httpResponse = $response = $this->call($verb, $url, $params, $cookies, $files, $server);

        $this->logResponseData($httpResponse);

        return $this->setResponseObjectAndContent($httpResponse);
    }

    public function setResponseObjectAndContent($httpResponse): TestResponse
    {
        $this->setResponseContent($httpResponse);

        return $this->response = $httpResponse;
    }

    public function getResponseContentArray()
    {
        return $this->responseContentArray ?: $this->responseContentArray = json_decode($this->getResponseContent(), true);
    }

    public function getResponseContent(): string
    {
        return $this->responseContent;
    }

    public function setResponseContent($httpResponse)
    {
        return $this->responseContent = $httpResponse->getContent();
    }

    public function getResponseContentObject()
    {
        return $this->responseContentObject ?: $this->responseContentObject = json_decode($this->getResponseContent(), false);
    }

    /**
     * Inject the ID in the Endpoint URI before making the call by
     * overriding the `$this->endpoint` property
     * Example: you give it ('users/{id}/stores', 100) it returns 'users/100/stores'.
     *
     * @param        $id
     * @param bool   $skipEncoding
     * @param string $replace
     */
    public function injectId($id, $skipEncoding = false, $replace = '{id}'): self
    {
        // In case Hash ID is enabled it will encode the ID first
        $id       = $this->hashEndpointId($id, $skipEncoding);
        $endpoint = str_replace($replace, $id, $this->getEndpoint());

        return $this->endpoint($endpoint);
    }

    /**
     * Override the default class endpoint property before making the call
     * to be used as follow: $this->endpoint('verb@uri')->makeCall($data).
     *
     * @param $endpoint
     */
    public function endpoint($endpoint): self
    {
        $this->overrideEndpoint = $endpoint;

        return $this;
    }

    public function getEndpoint(): string
    {
        return !is_null($this->overrideEndpoint) ? $this->overrideEndpoint : $this->endpoint;
    }

    /**
     * Override the default class auth property before making the call.
     *
     * to be used as follow: $this->auth('false')->makeCall($data);
     */
    public function auth(bool $auth): self
    {
        $this->overrideAuth = $auth;

        return $this;
    }

    public function getAuth(): bool
    {
        return !is_null($this->overrideAuth) ? $this->overrideAuth : $this->auth;
    }

    /**
     * Transform headers array to array of $_SERVER vars with HTTP_* format.
     */
    protected function transformHeadersToServerVars(array $headers): array
    {
        return collect($headers)->mapWithKeys(function ($value, $name) {
            $name = strtr(strtoupper($name), '-', '_');

            return [$this->formatServerHeaderKey($name) => $value];
        })->all();
    }

    private function buildUrlForUri($uri): string
    {
        return Config::get('apiato.api.url') . Apiato::getApiPrefix() . ltrim($uri, '/');
    }

    /**
     * Attach Authorization Bearer Token to the request headers
     * if it does not exist already and the authentication is required
     * for the endpoint `$this->auth = true`.
     *
     * @param $headers
     *
     * @return mixed
     */
    private function injectAccessToken(array $headers = []): array
    {
        // if endpoint is protected (requires token to access it's functionality)
        if ($this->getAuth() && !$this->headersContainAuthorization($headers)) {
            // append the token to the header
            $headers['Authorization'] = 'Bearer ' . $this->getTestingUser()->token;
        }

        return $headers;
    }

    private function headersContainAuthorization($headers): bool
    {
        return Arr::has($headers, 'Authorization');
    }

    private function dataArrayToQueryParam($data, $url): string
    {
        return $data ? $url . '?' . http_build_query($data) : $url;
    }

    private function getJsonVerb($text): string
    {
        return Str::replaceFirst('json:', '', $text);
    }

    private function hashEndpointId($id, bool $skipEncoding = false): string
    {
        return (Config::get('apiato.hash-id') && !$skipEncoding) ? Hashids::encode($id) : $id;
    }

    /**
     * read `$this->endpoint` property from the test class (`verb@uri`) and convert it to usable data.
     */
    private function parseEndpoint(): array
    {
        $this->validateEndpointExist();

        $separator = '@';

        $this->validateEndpointFormat($separator);

        // convert the string to array
        $asArray = explode($separator, $this->getEndpoint(), 2);

        // get the verb and uri values from the array
        extract(array_combine(['verb', 'uri'], $asArray));
        /** @var string $verb */
        /** @var string $uri */

        return [
            'verb' => $verb,
            'uri'  => $uri,
            'url'  => $this->buildUrlForUri($uri),
        ];
    }

    private function validateEndpointExist(): void
    {
        if (!$this->getEndpoint()) {
            throw new MissingTestEndpointException();
        }
    }

    /**
     * @throws WrongEndpointFormatException
     */
    private function validateEndpointFormat($separator): void
    {
        // check if string contains the separator
        if (!strpos($this->getEndpoint(), $separator)) {
            throw new WrongEndpointFormatException();
        }
    }

    /**
     * Let's pass an string (get_object_vars) as the first argument to get nice output in our laravel.log.
     */
    private function logResponseData(TestResponse | JsonResponse $httpResponse): void
    {
        $responseLoggerEnabled = Config::get('debugger.tests.response_logger');

        if ($responseLoggerEnabled) {
            Log::notice(var_export(get_object_vars($httpResponse->getData()), true));
        }
    }
}
