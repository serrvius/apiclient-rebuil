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

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $request->withHeader('Authorization', $this->token);
            return $handler($request, $options);
        };
    }

}
