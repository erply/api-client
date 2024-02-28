<?php

namespace Erply\ApiClient;

/**
 * Class ApiClient
 *
 * Connects to an Erply account over API and allows to make API calls.
 *
 * @package Erply\ApiClient
 */
class ApiClient
{
    /**
     * API URL, typically "https://{your_account_number}.erply.com/api/"
     * @var string|null
     */
    protected $url;

    /**
     * Erply account number (a three- to six-digit code)
     * @var string|null
     */
    protected $clientCode;

    /**
     * Erply username. We recommend to create a separate user account
     * for each API script, to be able to see which records have been
     * added and which records have been modified by the script.
     * @var string|null
     */
    protected $username;

    /**
     * Erply password.
     * Make sure to generate a strong random password. If you use the
     * username-password combination for the script only, the password
     * does not need to be short or memorable.
     * @var string|null
     */
    protected $password;

    /**
     * API session key. Will be automatically created the first time
     * you call sendRequest().
     * @var string|null
     */
    protected $sessionKey;

    /**
     * Expiry timestamp of the session key.
     * @var int|null
     */
    protected $expiryTimestamp;

    /**
     * CURL connection timeout, in seconds
     * @var int|null
     */
    protected $connectionTimeout;

    /**
     * CURL execution timeout, in seconds.
     * If you encounter timeout issues, raise this value.
     * @var int|null
     */
    protected $executionTimeout;

    /**
     * CURL error code - if the HTTP request to API
     * fails entirely
     * @see https://curl.haxx.se/libcurl/c/libcurl-errors.html
     * @var int|null
     */
    protected $curlErrorCode;

    /**
     * CURL error message
     * @var string|null
     */
    protected $curlErrorText;

    /**
     * HTTP status code. Status code 500, for example, may indicate that there
     * is a temporary problem with the service, or that the call has failed
     * for some reason.
     *
     * Please note that when the API is able to respond, the status code will
     * always be 200, even if API responds with a validation error or a permission
     * error. Always check the field `status.errorCode` in the response: a value 0
     * indicates a successful call.
     * @var int|null
     */
    protected $httpCode;

    /**
     * Raw output received by CURL.
     * @var string|null
     */
    protected $rawOutput;

    /**
     * Send an API call
     *
     * @param string $request    Name of the API call
     * @param array  $parameters Input parameters for the call
     *
     * @return bool|string
     * @throws \Exception
     */
    public function sendRequest(string $request, array $parameters = []): array
    {
        // Check if all necessary parameters are set up
        if (!$this->url) {
            throw new \Exception("API URL has not been defined.");
        }

        // Include client code and request name to POST parameters
        $parameters['request']    = $request;
        $parameters['clientCode'] = $this->clientCode;

        // Get session key
        if ($request != "verifyUser"
            && $request != "createInstallation"
            && (!isset($parameters['sessionKey']) || !$parameters['sessionKey'])
            && (!isset($parameters['serviceKey']) || !$parameters['serviceKey'])
            && (!isset($parameters['applicationKey']) || !$parameters['applicationKey'])
        ) {
            $parameters['sessionKey'] = $this->generateSessionKey($keyRequestResult);

            // Instead of a key we got an array which contains error code, let's return it
            if (!isset($parameters['sessionKey']) || !$parameters['sessionKey']) {
                return $keyRequestResult;
            }
        }

        $this->rawOutput = $this->getPostRequestContent($this->url, $parameters);

        if ($this->hasFailed()) {
            throw new \Exception($this->getCurlOrHttpErrorMessage());
        }

        // return response body
        $structure = json_decode($this->rawOutput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Cannot decode Erply API JSON response: " . json_last_error_msg(), json_last_error());
        }

        return $structure;
    }

    public function setUrl(string $url): self
    {
        // The trailing slash after https://123.erply.com/api/ is required
        if (substr($url, -1) !== '/') {
            $url .= '/';
        }
        $this->url = $url;

        return $this;
    }

    public function setClientCode(string $clientCode): self
    {
        $this->clientCode = $clientCode;

        return $this;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function setConnectionTimeout(int $connectionTimeout): self
    {
        $this->connectionTimeout = $connectionTimeout;
        return $this;
    }

    public function setExecutionTimeout(int $executionTimeout): self
    {
        $this->executionTimeout = $executionTimeout;
        return $this;
    }

    public function setSessionKey(string $sessionKey): self
    {
        $this->sessionKey = $sessionKey;
        return $this;
    }

    public function setExpiryTimestamp(int $expiryTimestamp): self
    {
        $this->expiryTimestamp = $expiryTimestamp;
        return $this;
    }

    public function getPostRequestContent(string $url, array $parameters): string
    {
        // Prepare POST request
        $ch = curl_init();

        curl_setopt_array(
            $ch,
            [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $parameters,
            ]
        );

        if (!is_null($this->connectionTimeout)) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
        }
        if (!is_null($this->executionTimeout)) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->executionTimeout);
        }

        // call POST request
        $response = curl_exec($ch);

        // Data about errors and HTTP code
        $this->curlErrorCode = curl_errno($ch);
        $this->curlErrorText = curl_error($ch);
        $this->httpCode      = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        curl_close($ch);

        return $response === false ? '' : $response;
    }

    public function getSessionKey(): ?string
    {
        return $this->sessionKey;
    }

    public function getExpiryTimestamp(): ?int
    {
        return $this->expiryTimestamp;
    }

    public function getCurlErrorCode(): ?int
    {
        return $this->curlErrorCode;
    }

    public function getCurlErrorText(): ?string
    {
        return $this->curlErrorText;
    }

    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

    public function hasFailed(): bool
    {
        return $this->getCurlErrorCode() || $this->getHttpCode() !== 200;
    }

    public function getCurlOrHttpErrorMessage(): string
    {
        if ($this->curlErrorCode) {
            return sprintf("CURL error %s: %s", $this->curlErrorCode, $this->curlErrorText);
        } elseif ($this->getHttpCode() !== 200) {
            return sprintf("HTTP status code %s", $this->httpCode);
        }

        return '';
    }

    /**
     * Calls API verifyUser to retrieve an API key.
     *
     * @param $response
     *
     * @return null|string
     * @throws \Exception
     */
    protected function generateSessionKey(&$response): ?string
    {
        // Session key is active, return it
        if ($this->sessionKey && $this->expiryTimestamp > time()) {
            return $this->sessionKey;
        } else {
            // Perform API request to get a new session key
            $response = $this->sendRequest(
                "verifyUser",
                ["username" => $this->username, "password" => $this->password]
            );

            // Session key was successfully received
            if (isset($response['records'][0]['sessionKey']) && $response['records'][0]['sessionKey']) {
                // Store the session key and expiry time in class variables
                $this->sessionKey      = $response['records'][0]['sessionKey'];
                $this->expiryTimestamp = time() + $response['records'][0]['sessionLength'] - 30;

                // Return the session key
                return $this->sessionKey;
            } else {
                $this->sessionKey = null;
                return null;
            }
        }
    }
}
