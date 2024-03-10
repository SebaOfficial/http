<?php

declare(strict_types=1);

namespace Seba\HTTP;

/**
 * Response Class.
 * This class provides methods for sending HTTP responses.
 *
 * @package Seba\HTTP
 * @author Sebastiano Racca
*/
class ResponseHandler
{
    private int $httpCode;
    private array|object|string|null $body;

    /**
     * Response constructor.
     * Initialises the response with default values.
     */
    public function __construct(int $defaultHTTPCode = 204)
    {
        $this->body = null;
        $this->httpCode = $defaultHTTPCode;
    }

    /**
     * Sets the HTTP Code.
     *
     * @param int $code The code to be set.
     *
     * @return self Returns the current instance.
     */
    public function setHttpCode(int $code): self
    {
        $this->httpCode = $code;
        return $this;
    }

    /**
     * Sets the body.
     *
     * @param array|object|string $body   The body to be set.
     *
     * @return self       Returns the current instance.
     */
    public function setBody(array|object|string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Sets response headers.
     *
     * @param array   A list of headers to be set.
     *
     * @return self   Returns the current instance.
     */
    public function setHeaders(array $headers): self
    {
        foreach($headers as $header) {
            header($header, true);
        }
        return $this;
    }

    /**
     * Sends an response to the HTTP Request.
     *
     * @param bool $exit Wheter to exit the program or not.
     *                   Default is set to true.
     */
    public function send(bool $exit = true): void
    {
        http_response_code($this->httpCode);

        if(isset($this->body)) {
            echo is_string($this->body) ? $this->body : json_encode($this->body);
        }

        if($exit) {
            exit;
        }
    }
}
