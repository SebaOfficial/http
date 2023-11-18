<?php

/**
 * Request Class.
 * This class provides methods for accepting HTTP requests.
 *
 * @package Seba\HTTP
 * @author Sebastiano Racca
*/

declare(strict_types=1);

namespace Seba\HTTP;

use Seba\HTTP\Exceptions\InvalidContentTypeException;
use Seba\HTTP\Exceptions\InvalidBodyException;

final class IncomingRequestHandler
{
    private ?array $body;
    private ?string $method;
    private ?array $headers;
    private ?string $defaultContentType;

    public function __construct(string $defaultContentType = null)
    {
        $this->body = null;
        $this->method = $_SERVER['REQUEST_METHOD'] ?? null;
        $this->headers = $this->getAllHeaders();
        $this->defaultContentType = $defaultContentType;
    }

    /**
     * Parses the incoming request body to an associative array based on the Content-Type header.
     *
     * @return void
     *
     * @throws InvalidContentTypeException   When the content type header is invalid.
     * @throws InvalidBodyException          When the JSON body is invalid.
     */
    private function parseBody(): void
    {
        // Retrieve the Content-Type header from the request.
        $contentType = empty($_SERVER['CONTENT_TYPE']) ? ($this->defaultContentType ?? "") : $_SERVER['CONTENT_TYPE'];

        // Determine the content type and process accordingly.
        if (str_starts_with($contentType, "application/x-www-form-urlencoded")) {
            // Parse data for URL-encoded form submissions.
            $this->body = ($_SERVER["REQUEST_METHOD"] === "POST") ? $_POST : $_GET;
        } elseif (str_starts_with($contentType, "multipart/form-data")) {
            // Parse data for multipart form submissions.
            $this->body = $_POST; // Initialize with POST data

            // Add support for boundaries in multipart/form-data.
            if (preg_match('/boundary=(.*)$/', $contentType, $matches)) {
                $boundary = $matches[1];
                $this->parseMultipartFormData($boundary);
            } else {
                throw new InvalidContentTypeException("Invalid Content-Type header for multipart/form-data");
            }
        } elseif (str_starts_with($contentType, "application/json")) {
            // Parse data for JSON content.
            $this->body = json_decode(file_get_contents("php://input"), true);

            // Check for invalid JSON.
            if ($this->body === null) {
                throw new InvalidBodyException("Invalid JSON in the body.");
            }
        } else {
            // Invalid Content-Type header.
            throw new InvalidContentTypeException("Invalid Content-Type header");
        }
    }

    /**
     * Parses multipart/form-data and populates the body with form field values.
     *
     * @param string $boundary The boundary string for parsing multipart data.
     *
     * @return void
     */
    private function parseMultipartFormData(string $boundary): void
    {
        // Retrieve raw input data.
        $rawData = file_get_contents("php://input");

        // Initialize an array to store form field values.
        $formData = [];

        // Parse multipart/form-data.
        $parts = explode("--$boundary", $rawData);

        foreach ($parts as $part) {
            if (!empty($part)) {
                // Extract content-disposition header to identify form field name and value.
                if (preg_match('/Content-Disposition:.*?name="(.*?)".*?(?:\r\n\r\n|\n\n)(.*)/s', $part, $matches)) {
                    $name = $matches[1];
                    $value = $matches[2];
                    $formData[$name] = $value;
                }
            }
        }

        // Merge the parsed form data with the existing body.
        $this->body = array_merge($this->body, $formData);
    }

    /**
     * Returns the parsed body.
     *
     * @return array The parsed body.
     */
    public function getBody(): array
    {

        if(!isset($this->body)){
            $this->parseBody();
        }

        return $this->body;
    }

    /**
     * Gets the HTTP method of the request.
     *
     * @return string|null The HTTP method or null if not available.
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Gets all the headers in the request.
     *
     * @return array The headers.
     */
    private function getAllHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];

        foreach ($_SERVER as $name => $value) {

            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }

        }

        return $headers;
    }

    /**
     * Gets all the headers of the request.
     *
     * @return array The headers.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Gets a specific header value by its name.
     *
     * @param string $name   The name of the header.
     *
     * @return string|null   The value of the header or null if not found.
     */
    public function getHeader(string $name): string|null
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Returns an array of missing parameters.
     *
     * @param array $params   The expected parameters.
     *
     * @return array          The array of missing parameters.
     */
    private function getMissingParams(array $params): array
    {
        return array_diff_key(array_flip($params), $this->getBody());
    }

    /**
     * Checks if all the required parameters are set.
     *
     * @param array $params   The parameters that need to be checked.
     *
     * @return array|false    An associative array with the required parameters or false if they are not set.
     */
    public function getRequiredParams(array $params): array|false
    {
        $missingParams = $this->getMissingParams($params);

        if (empty($missingParams)) {
            return array_intersect_key($this->getBody(), array_flip($params));
        }

        return false;
    }

    /**
     * Checks if the optional parameters are set.
     *
     * @param array $params      The parameters that need to be checked.
     * @param int $minimumKeys   The minimum parameters that need to be sent in the body.
     *
     * @return array|false       An associative array with the optional parameters or false if they are not set.
     */
    public function getOptionalParams(array $params, int $minimumKeys = 0): array|false
    {
        $missingParams = $this->getMissingParams($params);

        if (count($this->getBody()) < $minimumKeys) {
            return false;
        }

        return array_intersect_key($this->getBody(), array_flip($params));
    }

    /**
     * Sets the alowed Headers.
     *
     * @param array $headers   The allowed headers.
     *
     * @return array           A list of unallowed headers.
     */
    public function allowHeaders(?array $headers): array
    {
        header("Access-Control-Allow-Headers: " . implode(", ", $headers));
        return array_diff($this->getHeaders(), $headers);
    }

    /**
     * Sets the alowed HTTP Methods.
     *
     * @param array $methods   The allowed methods.
     *
     * @return bool            Wheter the method is allowed or not.
     */
    public function allowMethods(array $methods): bool
    {
        header("Access-Control-Allow-Methods: " . implode(", ", $methods));
        return in_array($_SERVER['REQUEST_METHOD'], $methods);
    }

    /**
     * Sets the Access-Control-Allow-Origin header.
     *
     * @param array|null $origins   The allowed origins.
     *
     * @return bool                 Wheter the origin is allowed or not.
     */
    public function allowOrigin(?array $origins): bool
    {

        if(empty($origins)){
            header("Access-Control-Allow-Origin: null");
            return false;
        }

        if(in_array("*", $origins)){
            header("Access-Control-Allow-Origin: *");
            return true;
        }

        if(!in_array($_SERVER['HTTP_ORIGIN'] ?? null, $origins))
            return false;

        header("Access-Control-Allow-Origin: " . ($_SERVER['HTTP_ORIGIN'] ?? "*"));
        return true;
    }

    /**
     * Checks if the request has uploaded files.
     *
     * @return bool True if there are uploaded files, false otherwise.
     */
    public function hasFiles(): bool
    {
        return !empty($_FILES);
    }

    /**
     * Gets information about an uploaded file.
     *
     * @param string $name The name attribute of the file input field.
     *
     * @return array|null Information about the uploaded file or null if not found.
     */
    public function getFile(string $name): ?array
    {
        return $_FILES[$name] ?? null;
    }

    /**
     * Gets the IP address of the client making the request.
     *
     * @return string|null The client's IP address or null if not available.
     */
    public function getIp(): ?string
    {
        return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Gets the user agent string from the request headers.
     *
     * @return string|null The user agent string or null if not available.
     */
    public function getUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Gets the protocol used by the request (HTTP/1.1, HTTP/2, etc.).
     *
     * @return string|null The protocol or null if not available.
     */
    public function getProtocol(): ?string
    {
        return $_SERVER['SERVER_PROTOCOL'] ?? null;
    }

    /**
     * Checks if the request is secure (HTTPS).
     *
     * @return bool True if the request is secure, false otherwise.
     */
    public function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    }

    /**
     * Gets the uri of the request.
     *
     * @return string|null The uri.
     */
    public function getUri(): ?string
    {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Parses the uri of the request into an associative array.
     *
     * @return array The parsed uri
     */
    public function getParsedUri(): array
    {
        return parse_url($this->getUri());
    }
}
