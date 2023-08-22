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

    public function __construct()
    {
        $this->body = null;
        $this->method = $_SERVER['REQUEST_METHOD'] ?? null;
        $this->headers = $this->getAllHeaders();
    }

    /**
     * Parses the incoming request body to an associative array.
     *
     * @return void
     *
     * @throws InvalidContentTypeException   When the content type header is invalid.
     * @throws InvalidBodyException          When the JSON body is invalid.
     */
    private function parseBody(): void
    {
        switch ($_SERVER['CONTENT_TYPE'] ?? "application/x-www-form-urlencoded"){
            case "application/x-www-form-urlencoded":
            case "multipart/form-data":
                if($_SERVER["REQUEST_METHOD"] === "POST")
                    $this->body = $_POST;

                else if($_SERVER["REQUEST_METHOD"] === "GET")
                    $this->body = $_GET;

                else
                    parse_str(file_get_contents("php://input"), $this->body);

                break;
            case "application/json":
                $this->body = json_decode(file_get_contents("php://input"), true);

                if($this->body === null){
                    throw new InvalidBodyException("Invalid json in the body.");
                }

                break;
            default:
                throw new InvalidContentTypeException("Invalid Content-Type header");
                break;
        }
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
    public function getMethod(): string|null
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

        if (count($this->getBody()) < $minimumKeys || !empty($missingParams)) {
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
        return array_diff($this->getHeaders(), $allowedHeaders);
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
}