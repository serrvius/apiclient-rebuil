<?php
/**
 * Created by PhpStorm.
 * User: sergiu
 * Date: 1/25/19
 * Time: 12:43 AM
 */

namespace API\Auth;

use API\Auth\Middleware\JWTAuthMiddleware;
use API\Exception\AuthException;
use API\Http\Handler\HttpHandlerFactory;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7;
use Psr\Http\Message\UriInterface;

class JWTAuthorization implements AuthInterface
{

    protected $configuration = [
        'login_uri'    => '/api/login_check',
        'method'       => 'POST',
        '_username'    => '',
        '_password'    => '',
        'cache_token'  => true,
        'token_prefix' => 'Bearer ',
    ];

    protected $token;

    /** @var null|ClientInterface */
    protected $client = null;

    public function __construct(array $configuration = [])
    {
        $this->configuration = array_merge($this->configuration, $configuration);
    }

    public function authorize(ClientInterface $client = null)
    {
        $this->client = $client;
        if (!$this->hasToken()) {
            $this->askToken();
        }

        $config = $client->getConfig();
        $config['handler']->push(new JWTAuthMiddleware($this->token));

        return new Client($config);
    }

    protected function askToken()
    {

        try {
            $httpHandler = HttpHandlerFactory::build();
            /** @var Psr7\Response $response */
            $response = $httpHandler($this->getCredentialRequest());

            if ($response->getStatusCode() == 200) {
                $body = $response->getBody()->getContents();

                if (!is_string($body)) {
                    throw new AuthException('Wrong response for login process');
                };
                $data = json_decode($body, true);
                if (!is_array($data) || !array_key_exists('token', $data)) {
                    throw new AuthException('Wrong response format on auth process');
                }

                $this->token = $data['token'];

                return $this->token;
            }
        } catch (\Exception $e) {
            throw new AuthException($e->getMessage());
        }

    }

    protected function getCredentialRequest()
    {
        /** @var UriInterface $uriInterface */
        $uriInterface = $this->client->getConfig('base_uri');

        $url     = "{$uriInterface->getScheme()}://{$uriInterface->getHost()}{$this->configuration['login_uri']}";
        $headers = [
            'Cache-Control' => 'no-store',
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ];

        return new Psr7\Request($this->configuration['method'], $url, $headers, Psr7\build_query([
                                                                                                     '_username' => $this->configuration['_username'],
                                                                                                     '_password' => $this->configuration['_password'],
                                                                                                 ]));

    }

    protected function hasToken()
    {
        return $this->token != null;
    }

}
