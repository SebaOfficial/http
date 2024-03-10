<?php

declare(strict_types=1);

namespace Seba\HTTP\Router;

/**
 * Available Request Methods.
 *
 * @package Seba\HTTP\Router
 * @author Sebastiano Racca
 */
class RequestedMethods
{
    public const GET = 1;
    public const POST = 2;
    public const PUT = 4;
    public const DELETE = 8;
    public const OPTIONS = 16;
    public const PATCH = 32;
    public const HEAD = 64;

    private static array $methodStrings = [
        'GET' => self::GET,
        'POST' => self::POST,
        'PUT' => self::PUT,
        'DELETE' => self::DELETE,
        'OPTIONS' => self::OPTIONS,
        'PATCH' => self::PATCH,
        'HEAD' => self::HEAD,
    ];

    public static function getStrings(int $methods): array {
        $strings = [];
        foreach (self::$methodStrings as $method => $value) {
            if ($methods & $value) {
                $strings[] = $method;
            }
        }
        return $strings;
    }
}

