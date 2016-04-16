<?php

namespace App\Http\Controllers;

use Exception;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Helpers;
use App\Utilities\JwTokenManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use League\Fractal\TransformerAbstract as Transformer;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BaseController extends Controller
{

    use Helpers;

    protected $jwTokenManager;

    public function __construct(JwTokenManager $jwTokenManager)
    {
        $this->jwTokenManager = $jwTokenManager;
    }

    /**
     * Get the properly formatted (Dingo Dispatcher) GET request with an API Token query string.
     *
     * @param $path (route URL - relative, i.e., '/my-route')
     * @param string $v (add 'v2' for admin paths, defaults to 'v1')
     * @return mixed (Dingo dispatcher call with api_access_token in query string)
     */
    public function apiGetRequest($path, $v = 'v1')
    {
        try {
            $response = $this->api->version($v)->get($this->setAccessTokenPath($path, $v));
        } catch (Exception $e) {
            return $this->getWebExceptionResponse($e);
        }

        return $response;
    }

    /**
     * Get the properly formatted (Dingo Dispatcher) GET request with JWT in the header and API Token query string.
     *
     * @param $path (route URL - relative, i.e., '/my-route')
     * @param string $v (add 'v2' for admin paths, defaults to 'v1')
     * @return mixed (Dingo dispatcher call with header)
     */
    public function apiGetRequestWithJwt($path, $v = 'v1')
    {
        $jwt = $this->setJwtHeader();

        try {
            $response = $this->api->version($v)->header('Authorization', $jwt)->get($this->setAccessTokenPath($path, $v));
        } catch (Exception $e) {
            return $this->getWebExceptionResponse($e);
        }

        return $response;
    }

    /**
     * Get the properly formatted (Dingo Dispatcher) POST request with an API Token query string.
     *
     * @param $path (route URL - relative, i.e., '/my-route')
     * @param $data (Form Request (all()) )
     * @param string $v (add 'v2' for admin paths, defaults to 'v1')
     * @return mixed (Dingo dispatcher call with api_access_token in query string)
     */
    public function apiPostRequest($path, $data, $v = 'v1')
    {
        try {
            $response = $this->api->version($v)->post($this->setAccessTokenPath($path, $v), $data);
        } catch (Exception $e) {
            return $this->getWebExceptionResponse($e);
        }

        return $response;
    }

    /**
     * Get the properly formatted (Dingo Dispatcher) POST request with JWT in the header and API Token query string.
     *
     * @param $path (route URL - relative, i.e., '/my-route')
     * @param $data (Form Request (all()) )
     * @param string $v (add 'v2' for admin paths, defaults to 'v1')
     * @return mixed (Dingo dispatcher call with header)
     */
    public function apiPostRequestWithJwt($path, $data, $v = 'v1')
    {
        $jwt = $this->setJwtHeader();

        try {
            $response = $this->api->version($v)->header('Authorization', $jwt)->post($this->setAccessTokenPath($path, $v), $data);
        } catch (Exception $e) {
            return $this->getWebExceptionResponse($e);
        }

        return $response;
    }

    /**
     * Get the properly formatted (Dingo Dispatcher) PUT request with an API Token query string.
     *
     * @param $path (route URL - relative, i.e., '/my-route')
     * @param $data (Form Request (all()) )
     * @param string $v (add 'v2' for admin paths, defaults to 'v1')
     * @return mixed (Dingo dispatcher call with api_access_token in query string)
     */
    public function apiPutRequest($path, $data, $v = 'v1')
    {
        try {
            $response = $this->api->version($v)->put($this->setAccessTokenPath($path, $v), $data);
        } catch (Exception $e) {
            return $this->getWebExceptionResponse($e);
        }

        return $response;
    }

    /**
     * Get the properly formatted (Dingo Dispatcher) PUT request with JWT in the header and API Token query string.
     *
     * @param $path (route URL - relative, i.e., '/my-route')
     * @param $data (Form Request (all()) )
     * @param string $v (add 'v2' for admin paths, defaults to 'v1')
     * @return mixed (Dingo dispatcher call with header)
     */
    public function apiPutRequestWithJwt($path, $data, $v = 'v1')
    {
        $jwt = $this->setJwtHeader();

        try {
            $response = $this->api->version($v)->header('Authorization', $jwt)->put($this->setAccessTokenPath($path, $v), $data);
        } catch (Exception $e) {
            return $this->getWebExceptionResponse($e);
        }

        return $response;
    }

    /**
     * Get the properly formatted (Dingo Dispatcher) DELETE request with an API Token query string.
     *
     * @param $path (route URL - relative, i.e., '/my-route')
     * @param string $v (add 'v2' for admin paths, defaults to 'v1')
     * @return mixed (Dingo dispatcher call with api_access_token in query string)
     */
    public function apiDeleteRequest($path, $v = 'v1')
    {
        try {
            $response = $this->api->version($v)->delete($this->setAccessTokenPath($path, $v));
        } catch (Exception $e) {
            return $this->getWebExceptionResponse($e);
        }

        return $response;
    }

    /**
     * Get the properly formatted (Dingo Dispatcher) DELETE request with JWT in the header and API Token query string.
     *
     * @param $path (route URL - relative, i.e., '/my-route')
     * @param string $v (add 'v2' for admin paths, defaults to 'v1')
     * @return mixed (Dingo dispatcher call with header)
     */
    public function apiDeleteRequestWithJwt($path, $v = 'v1')
    {
        $jwt = $this->setJwtHeader();

        try {
            $response = $this->api->version($v)->header('Authorization', $jwt)->delete($this->setAccessTokenPath($path, $v));
        } catch (Exception $e) {
            return $this->getWebExceptionResponse($e);
        }

        return $response;
    }

    /**
     * Determine what kind of object we received from a Model Service and return the appropriate API Response.
     *
     * @param JsonResponse|Collection|Model $returned
     * @param Transformer $transformer
     * @return \Dingo\Api\Http\Response|void
     */
    public function getApiResponse($returned, $transformer)
    {
        if ($returned instanceof JsonResponse) {
            // Return an error or a JsonResponse if we receive a JsonResponse from the Model Service
            $response = $this->getApiResponseFromJson($returned);
        } elseif ($returned instanceof Collection) {
            // Return a collection if we receive an Eloquent collection from the Model Service
            $response = $this->response->collection($returned, $transformer);
        } elseif ($returned instanceof Model) {
            // Return a model if we receive an Eloquent model from the Model Service
            $response = $this->response->item($returned, $transformer);
        } else {
            // Fallback to no content
            $response = $this->response->noContent();
        }

        return $response;
    }

    /**
     * Add a valid System or Admin Api Access Token to the path and return it.
     *
     * @param $path
     * @param string $version ('v2' for admin paths, 'v1' for system paths)
     * @return string
     */
    protected function setAccessTokenPath($path, $version)
    {
        // If an API Consumer is accessing the web app, use their token - otherwise use the system token
        $userToken = session()->has('api_consumer_token') ? session('api_consumer_token') : env('SYSTEM_ACCESS_TOKEN');

        // If the API version is not 'v1', use the admin token, otherwise use the userToken
        $token = $version == 'v1' ? $userToken : env('ADMIN_ACCESS_TOKEN');

        // Append the appropriate token to the path
        return $path . '?api_access_token=' . $token;
    }

    /**
     * Determine whether or not we have an error, and return the properly formatted response.
     *
     * @param JsonResponse $json
     * @return JsonResponse|void
     */
    private function getApiResponseFromJson(JsonResponse $json)
    {
        $info = getJsonInfoArray($json);
        $status = $info['status'];

        if ($status >= 400) {
            // We have received an error JsonResponse from the Model Service
            $message = $info['message'];

            return $this->response->error($message, $status);
        }

        return $json;
    }

    /**
     * Get a properly formatted JsonResponse to return to the WEB APP from a Dingo Generated Error Exception response.
     *
     * @param Exception $e
     * @return JsonResponse
     */
    private function getWebExceptionResponse(Exception $e)
    {
        if ($e instanceof HttpException) {
            $status = $e->getStatusCode();
        } else {
            $status = $e->getCode();
        }

        $webResponse = new JsonResponse(['message' => $e->getMessage()], $status);

        return $webResponse;
    }

    /**
     * Get the JWT if it exists in any of the viable request resources and return it in the Authorization Header
     * format or return null.
     *
     * @return string|null
     */
    private function setJwtHeader()
    {
        $jwt = $this->jwTokenManager->getJwtFromResources();

        return $jwt == null ?: 'Bearer ' . $jwt;
    }

    /**
     * Get the JWT if it exists in any of the viable request resources and return it in the Query String format or
     * return null.
     *
     * @return string|null
     */
    private function setJwtQuery()
    {
        $jwt = $this->jwTokenManager->getJwtFromResources();

        return $jwt == null ?: 'token=' . $jwt;
    }
}