<?php
/**
 * Created by PhpStorm.
 * User: sergiu
 * Date: 1/25/19
 * Time: 12:43 AM
 */

namespace API\Auth;

use API\Auth\Middleware\JWTAuthMiddleware;
use API\Cache\Item;
use API\Exception\AuthException;
use API\Http\Handler\HttpHandlerFactory;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

class JWTAuthorization implements AuthInterface
{

    protected $name          = 'JWT Authorization Library';
    protected $cacheKey      = '_jwtToken';
    protected $configuration = [
        'login_uri'    => '/api/login_check',
        'method'       => 'POST',
        '_username'    => '',
        '_password'    => '',
        'cache_token'  => true,
        'token_prefix' => 'Bearer ',
    ];
    /** @var string */
    protected $token;
    /** @var null|ClientInterface */
    protected $http = null;
    /** @var CacheItemPoolInterface */
    protected $cache;
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(array $configuration = [])
    {
        $this->configuration = array_merge($this->configuration, $configuration);
    }

    public function authorize(ClientInterface $http = null): ClientInterface
    {
        $this->http = $http;

        dump($this->getToken());

        if (!$this->hasToken()) {
            $this->getAuthenticatedToken();
            $this->storeToken();
        }

        $config = $http->getConfig();
        $config['handler']->push(new JWTAuthMiddleware("{$this->configuration['token_prefix']}{$this->token}"));

        return new Client($config);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setCache(CacheItemPoolInterface $cacheItemPool): AuthInterface
    {
        $this->cache = $cacheItemPool;

        return $this;
    }

    public function setLogger(LoggerInterface $logger): AuthInterface
    {
        $this->logger = $logger;

        return $this;
    }

    protected function getAuthenticatedToken()
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
            throw new AuthException("Wrong auth process response code [{$response->getStatusCode()}]");
        } catch (\Exception $e) {
            throw new AuthException($e->getMessage());
        }

    }

    protected function getCredentialRequest()
    {
        /** @var UriInterface $uriInterface */
        $uriInterface = $this->http->getConfig('base_uri');

        $url     = "{$uriInterface->getScheme()}://{$uriInterface->getHost()}{$this->configuration['login_uri']}";
        $headers = [
            'User-Agent'    => $this->getName(),
            'Cache-Control' => 'no-store',
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ];

        return new Psr7\Request($this->configuration['method'], $url, $headers, Psr7\build_query([
                                                                                                     '_username' => $this->configuration['_username'],
                                                                                                     '_password' => $this->configuration['_password'],
                                                                                                 ]));

    }

    protected function storeToken()
    {
        if ($this->configuration['cache_token']) {
            $item = new Item($this->cacheKey);
            $item->set($this->token);
            $item->expiresAfter(10);

            $this->cache->save($item);
        }
    }

    protected function getToken(){
        if($this->token){
            return $this->token;
        }elseif ($this->cache->hasItem($this->cacheKey)){
            $this->token = $this->cache->getItem($this->cacheKey)->get();
        }

        return $this->token;
    }

    protected function hasToken()
    {

        return $this->token != null;
    }

}
