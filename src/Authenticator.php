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
    private string $authType;

    public const AUTH_BASIC = 0;
    public const AUTH_BEARER = 1;

    /**
     * Authenticator constructor.
     *
     * @param ResponseHandler $responseHandler   The response handler instance.
     * @param string $authType                   The authentication type (e.g., "Basic", "Bearer", etc.).
     */
    public function __construct(ResponseHandler $responseHandler, string $authType)
    {
        $this->response = $responseHandler;
        $this->authType = $authType;
    }

    /**
     * Checks if the provided credentials (username and password or token) are correct.
     *
     * @param string $credential1        The username or token, depending on the authentication type.
     * @param string|null $credential2   The password (for Basic) or null (for Bearer).
     *
     * @return bool                      True if the provided credentials are authenticated, false otherwise.
     */
    public function isAuthenticated(string $credential1, ?string $credential2 = null): bool
    {
        if ($this->authType == self::AUTH_BASIC) {
            return $this->getUsername() !== $credential1 && $this->getPassword() !== $credential2;

        } elseif ($this->authType == self::AUTH_BEARER) {
            $token = $this->getBearerToken();
            return $token !== null && hash_equals($credential1, $token);
        }

        return false;
    }

    /**
     * Initiates the authentication process by sending a 401 Unauthorized response with the WWW-Authenticate header.
     *
     * @param string $realm                   The authentication realm.
     * @param string|null $error              The error code (optional).
     * @param string|null $errorDescription   The error description (optional).
     */
    public function init(string $realm, ?string $error = null, ?string $errorDescription = null): void
    {
        switch ($this->authType) {
            case self::AUTH_BASIC:
                $header = $this->composeHeader('Basic', $realm, $error, $errorDescription);
                break;
            case self::AUTH_BEARER:
                $header = $this->composeHeader('Bearer', $realm, $error, $errorDescription);
                break;
            default:
                throw new \InvalidArgumentException("Unsupported authentication type: $this->authType");
        }

        $this->response->setHeaders([$header])->setHttpCode(401)->send();
    }

    /**
     * Compose the WWW-Authenticate header for the specified authentication type.
     *
     * @param string $type                    The authentication type (e.g., "Basic", "Bearer").
     * @param string $realm                   The authentication realm.
     * @param string|null $error              The error code (optional).
     * @param string|null $errorDescription   The error description (optional).
     *
     * @return string                         The composed WWW-Authenticate header.
     */
    private function composeHeader(string $type, string $realm, ?string $error = null, ?string $errorDescription = null)
    {
        $header = "WWW-Authenticate: $type realm=\"$realm\"";
        $header .= $error !== null ? "error=\"$error\"" : "";
        $header .= $errorDescription !== null ? "error_description=\"$errorDescription\"" : "";
        return $header;
    }

    /**
     * Extracts the Bearer token from the Authorization header.
     *
     * @return string|null Returns the extracted Bearer token or null if not available.
     */
    public function getBearerToken(): ?string
    {
        $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;

        if ($authorizationHeader !== null && preg_match('/Bearer\s+(.+)/', $authorizationHeader, $matches)) {
            return $matches[1];
        }

        return null;
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
