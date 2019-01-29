<?php
/**
 * Created by PhpStorm.
 * User: sergiu
 * Date: 1/30/19
 * Time: 1:28 AM
 */

namespace API\Auth\Middleware;

use Psr\Http\Message\RequestInterface;

class JWTAuthMiddleware
{

    protected $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            // Requests using "auth"="google_auth" will be authorized.
//            if (!isset($options['auth']) || $options['auth'] !== 'google_auth') {
//                return $handler($request, $options);
//            }

            $request = $request->withHeader('authorization', 'Bearer ' . $this->token);

            return $handler($request, $options);
        };
    }

}
