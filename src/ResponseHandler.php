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
    private array $headers;
    private array|object|string|null $body;

    /**
     * Response constructor.
     * Initialises the response with default values.
     *
     * @param int $defaultHTTPCode The default HTTP Status code to send if none provided.
     */
    public function __construct(int $defaultHTTPCode = 204)
    {
        $this->body = null;
        $this->httpCode = $defaultHTTPCode;
        $this->headers = [];
    }

    /**
     * Sets the HTTP Code.
     *
     * @param int $code   The code to be set.
     *
     * @return static     Returns the current instance.
     */
    public function setHttpCode(int $code): static
    {
        $this->httpCode = $code;
        return $this;
    }

    /**
     * Sets the body.
     *
     * @param array|object|string $body   The body to be set.
     *
     * @return static                     Returns the current instance.
     */
    public function setBody(array|object|string $body): static
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Sets a response header.
     *
     * @param string $key     The header name.
     * @param string $value   The hader value.
     *
     * @return static         Returns the current instance.
     */
    public function setHeader(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Sets response headers.
     *
     * @param array     A list of headers to be set.
     *
     * @return static   Returns the current instance.
     */
    public function setHeaders(array $headers): static
    {
        foreach($headers as $key => $value) {
            $this->setHeader($key, $value);
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

        foreach($this->headers as $key => $value) {
            header("$key: $value", true);
        }

        if(isset($this->body)) {
            echo is_string($this->body) ? $this->body : json_encode($this->body);
        }

        if($exit) {
            exit;
        }
    }
}
