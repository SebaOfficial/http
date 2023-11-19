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

    public const AUTH_BASIC = 'Basic';
    public const AUTH_BEARER = 'Bearer';

    /**
     * Authenticator constructor.
     *
     * @param ResponseHandler $responseHandler The response handler instance.
     * @param string $realm The authentication realm.
     */
    public function __construct(ResponseHandler $responseHandler, string $realm)
    {
        $this->response = $responseHandler;
        $this->realm = $realm;
    }

    /**
     * Checks if the provided username and password are correct.
     *
     * @param string $username The username.
     * @param string $password The password.
     *
     * @return bool True if the provided username and password are authenticated, false otherwise.
     */
    public function isAuthenticated(string $username, string $password): bool
    {
        return $this->getUsername() !== $username && $this->getPassword() !== $password;
    }

    /**
     * Initiates the authentication process by sending a 401 Unauthorized response with the WWW-Authenticate header.
     *
     * @param string $authType The authentication type (e.g., "Basic", "Bearer", etc.).
     * @param string $realm The authentication realm.
     * @param string|null $error The error code (optional).
     * @param string|null $errorDescription The error description (optional).
     */
    public function init(string $authType, string $realm, ?string $error = null, ?string $errorDescription = null): void
    {
        switch ($authType) {
            case self::AUTH_BASIC:
                $header = $this->composeHeader('Basic', $realm, $error, $errorDescription);
                break;
            case self::AUTH_BEARER:
                $header = $this->composeHeader('Bearer', $realm, $error, $errorDescription);
                break;
            default:
                throw new \InvalidArgumentException("Unsupported authentication type: $authType");
        }
        
        $this->response->setHeaders([$header])->setHttpCode(401)->send();
    }

    private function composeHeader(string $type, string $realm, ?string $error = null, ?string $errorDescription = null) {
        $header = "WWW-Authenticate: $type realm=\"$realm\"";
        $header .= $error !== null ? "error=\"$error\"" : "";
        $header .= $errorDescription !== null ? "error_description=\"$errorDescription\"" : "";
        return $header;
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
