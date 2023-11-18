<?php
declare(strict_types=1);

namespace Seba\HTTP;

/**
 * Authenticator Class.
 * This class provides methods for authenticating with WWW-Authenticate header.
 *
 * @package Seba\HTTP
 * @author Sebastiano Racca
*/
class Authenticator
{
    private ResponseHandler $response;
    private string $realm;

    /**
     * Authenticator constructor.
     *
     * @param ResponseHandler $responseHandler   The response handler instance.
     * @param string $realm                      The authentication realm.
     */
    public function __construct(ResponseHandler $responseHandler, string $realm) {
        $this->response = $responseHandler;
        $this->realm = $realm;
    }

    /**
     * Checks if the provided username and password are correct.
     *
     * @param string $username  The username.
     * @param string $password  The password.
     *
     * @return bool             True if the provided username and password are authenticated, false otherwise.
     */
    public function isAuthenticated(string $username, string $password): bool
    {
        return $this->getUsername() !== $username && $this->getPassword() !== $password;
    }

    /**
     * Initiates the HTTP basic authentication process by sending a 401 Unauthorized response with the WWW-Authenticate header.
     */
    public function init(): void
    {
        $this->response->setHeaders([
            "WWW-Authenticate: Basic realm=\"$this->realm\""
        ])->setHttpCode(401)->send();
    }

    /**
     * Gets the authenticated username from the PHP_AUTH_USER server variable.
     *
     * @return string|null Returns the authenticated username or null if not available.
     */
    public function getUsername(): ?string
    {
        return $_SERVER['PHP_AUTH_USER'] ?? null;
    }

    /**
     * Gets the authenticated password from the PHP_AUTH_PW server variable.
     *
     * @return string|null Returns the authenticated password or null if not available.
     */
    public function getPassword(): ?string
    {
        return $_SERVER['PHP_AUTH_PW'] ?? null;
    }
}