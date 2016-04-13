<?php

namespace App\Utilities;

use Hash;
use App\ApiConsumer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Repositories\ApiConsumer\ApiConsumerRepositoryInterface;

class ApiTokenManager
{

    protected $apiConsumerRepo;

    public function __construct(ApiConsumerRepositoryInterface $apiConsumerRepo)
    {
        $this->apiConsumerRepo = $apiConsumerRepo;
    }

    /**
     * Generate a random (invalid) starter token for creating new API Consumers.
     *
     * @return string
     */
    public function generateApiTokenStarter()
    {
        $string = str_random(9) . '_' . str_random(7) . '_' . str_random(9);

        return $string;
    }

    /**
     * Generate a valid, human-readable API token by appending the Consumer ID to the end of the starter token.
     *
     * @param ApiConsumer $apiConsumer
     * @return string
     */
    public function generateValidApiToken(ApiConsumer $apiConsumer)
    {
        $starterToken = $apiConsumer->api_token;

        // Make sure we are dealing with a starter token
        if ($this->getTokenStatus($starterToken) == 'starter') {
            $token = $starterToken . '_' . $apiConsumer->id;
        } else {
            $token = $this->generateApiTokenStarter() . '_' . $apiConsumer->id;
        }

        return $token;
    }

    /**
     * Hash and return a valid API Token to make it active.
     *
     * @param string $validToken (Starter Token which includes the appended UserID)
     * @return string
     */
    public function generateActiveApiToken($validToken)
    {
        return Hash::make($validToken);
    }

    /**
     * Generate and return a Reset Key to be used during the Token Refresh process.
     *
     * @return string
     */
    public function generateResetKey()
    {
        $resetKey = str_random(5) . '_' . str_random(5) . '_' . str_random(5);

        return $resetKey;
    }

    /**
     * Get an ApiConusmer from the api_access_token in a request.
     *
     * @param $token
     * @return ApiConsumer|bool
     */
    public function getApiConsumerFromToken($token)
    {
        // Get the info array from the token
        $tokenInfo = $this->getTokenInfoArray($token);

        // Attempt to find the ApiConsumer by their ID
        try {
            $apiConsumer = $this->apiConsumerRepo->findById($tokenInfo['id']);
        } catch (ModelNotFoundException $e) {
            $apiConsumer = false;
        }

        return $apiConsumer;
    }

    /**
     * Verify the validity of an API Consumer Token.
     *
     * @param string $token
     * @return bool
     */
    public function verifyApiToken($token)
    {
        $apiConsumer = $this->getApiConsumerFromToken($token);

        // If we cannot locate an ApiConsumer, return false, otherwise verify the token and return true or false
        $verified = !$apiConsumer ? false : Hash::check($token, $apiConsumer->api_token);

        return $verified;
    }

    /**
     * Verify that the api_access_token provided is valid and belongs to the system super-admin.
     *
     * @param string $token
     * @return bool
     */
    public function verifyAdminToken($token)
    {
        // Verify that we have received an ApiConsumer from the token
        if (!$apiConsumer = $this->getApiConsumerFromToken($token)) {
            return false;
        }
        // Verify that the ApiConsumer's email address matches the admin email address
        if ($apiConsumer->email !== env('ADMIN_EMAIL')) {
            return false;
        }

        // Verify the validity of the token
        return Hash::check($token, $apiConsumer->api_token);
    }

    /**
     * Determine whether an api_token column on an ApiConsumer contains a starter token or an active token.
     *
     * @param string $token
     * @return string ('starter'/'active')
     */
    public function getTokenStatus($token)
    {
        $status = strlen($this->generateApiTokenStarter()) === strlen($token) ? 'starter' : 'active';

        return $status;
    }

    /**
     * Transform an Api Token provided by an ApiConsumer in a form into an array to be used by the Custom ApiConsumer
     * Validator.
     *
     * @param string $token
     * @return array
     */
    public function getTokenInfoArray($token)
    {
        // Break the token up at the '_'s
        $tokenParts = explode('_', $token);
        // Pop the ApiConsumer ID off the end of the array
        $id = array_pop($tokenParts);
        // Instantiate the empty starter token string
        $starterToken = '';
        // Put the token back together again without the ApiConsumer ID
        foreach ($tokenParts as $tokenPart) {
            $starterToken .= $tokenPart . '_';
        }

        // Return the ApiConsumer ID and the Starter Token in an array
        return ['id' => $id, 'starter_token' => rtrim($starterToken, '_')];
    }

    /**
     * Determine whether or not we are in the API subdomain, and retrieve and return the API Access Token accordingly,
     * if it exists - otherwise, return false.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool|mixed
     */
    public function getTokenFromRequest($request)
    {
        // Get the token from the session or query, depending on which subdomain we are on
        if (getSubdomain($request) != 'api') {
            // Get the token from the session - the user is on the web app
            $token = session()->has('api_consumer_token') ? session('api_consumer_token') : false;
            // We need to do a verify check in this case, because we are not nested under the api.access middleware
            if (!$token || !$this->verifyApiToken($token)) {
                return false;
            }
        } else {
            // Get the token from the query string - the user is accessing the API
            $token = $request->has('api_access_token') ? $request->get('api_access_token') : false;
        }

        return $token;
    }
}