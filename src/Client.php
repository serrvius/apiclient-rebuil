<?php
/**
 * Created by PhpStorm.
 * User: sergiu
 * Date: 1/23/19
 * Time: 12:32 AM
 */

namespace API;

use API\Cache\MemoryCacheItemPool;
use API\Http\REST;
use GuzzleHttp\ClientInterface;
use Monolog\Handler\StreamHandler as MonologStreamHandler;
use Monolog\Handler\SyslogHandler as MonologSysHandler;
use Monolog\Logger;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

class Client
{

    const NAME = 'api-client';

    /** @var array */
    protected $config = [];
    /** @var */
    protected $cache;
    /** @var LoggerInterface|null */
    protected $logger;
    /** @var ClientInterface */
    protected $http;

    public function __construct(array $config)
    {
        $this->config = array_merge([

                                        'application_name' => self::NAME,
                                        'base_path'        => '',
                                        'system_logs'      => true,
                                        'retry'            => [],
                                        'retry_map'        => null,
                                    ], $config);
    }

    /**
     * @param string $basePath
     * @return Client
     */
    public function setBasePath(string $basePath): self
    {
        $this->config['base_path'] = $basePath;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBasePath(): ?string
    {
        return $this->config['base_path'];
    }

    /**
     * @return string|null
     */
    public function getApplicationName(): ?string
    {
        return $this->config['application_name'];
    }

    /**
     * @param string $applicationName
     * @return Client
     */
    public function setApplicationName(string $applicationName): self
    {
        $this->config['application_name'] = $applicationName;

        return $this;
    }

    /**
     * @return CacheItemPoolInterface
     */
    public function getCache(): CacheItemPoolInterface
    {
        if (!isset($this->cache)) {
            $this->cache = new MemoryCacheItemPool();
        }

        return $this->cache;
    }

    /**
     * @param CacheItemPoolInterface $cache
     * @return Client
     */
    public function setCache(CacheItemPoolInterface $cache): self
    {

        $this->cache = $cache;

        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return Client
     */
    public function setConfig($name, $value): self
    {
        $this->config[$name] = $value;

        return $this;
    }

    /**
     * @param      $name
     * @param null $default
     * @return mixed|null
     */
    public function getConfig($name, $default = null)
    {
        return isset($this->config[$name]) ? $this->config[$name] : $default;
    }

    /**
     * @param LoggerInterface $logger
     * @return Client
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return LoggerInterface
     * @throws \Exception
     */
    public function getLogger(): LoggerInterface
    {
        if (!isset($this->logger)) {
            $this->logger = $this->createDefaultLogger();
        }

        return $this->logger;
    }

    /**
     * @return LoggerInterface
     * @throws \Exception
     */
    protected function createDefaultLogger(): LoggerInterface
    {
        $logger = new Logger(self::NAME);
        if ($this->config['system_logs']) {
            $handler = new MonologSysHandler('app', LOG_USER, Logger::NOTICE);
        }
        else {
            $handler = new MonologStreamHandler('php://stderr', Logger::NOTICE);
        }
        $logger->pushHandler($handler);

        return $logger;

    }

    /**
     * @param ClientInterface $http
     * @return Client
     */
    public function setHttpClient(ClientInterface $http): self
    {
        $this->http = $http;

        return $this;
    }

    /**
     * @return ClientInterface
     */
    public function getHttpClient(): ClientInterface
    {
        if (!$this->http) {
            $this->http = $this->createDefaultHttpClient();
        }

        return $this->http;
    }

    /**
     * @return \GuzzleHttp\Client
     */
    protected function createDefaultHttpClient()
    {
        $options = ['exceptions' => false];

        $version = ClientInterface::VERSION;
        if ('5' === $version[0]) {
            $options = [
                'base_url' => $this->config['base_path'],
                'defaults' => $options,
            ];
        }
        else {
            // guzzle 6
            $options['base_uri'] = $this->config['base_path'];
        }

        return new \GuzzleHttp\Client($options);
    }

    /**
     * @param ClientInterface|null $http
     * @return ClientInterface
     */
    public function authorize(ClientInterface $http = null)
    {
        if (null === $http) {
            $http = $this->getHttpClient();
        }

        return $http;
    }

    /**
     * @param RequestInterface $request
     * @param null             $expectedClass
     * @return array
     * @throws Exception\ServiceException
     */
    public function execute(RequestInterface $request, $expectedClass = null)
    {
        $request = $request->withHeader('User-Agent', $this->config['application_name']
        //            . " " . self::USER_AGENT_SUFFIX
        //            . $this->getLibraryVersion()
        );

        // call the authorize method
        // this is where most of the grunt work is done
        $http = $this->authorize();

        return REST::execute($http, $request, $expectedClass, $this->config['retry'], $this->config['retry_map']);
    }

}
