<?php
/**
 * Created by PhpStorm.
 * User: sergiu
 * Date: 1/30/19
 * Time: 12:15 AM
 */

namespace API\Auth;

use GuzzleHttp\ClientInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

interface AuthInterface
{

    public function __construct(array $configuration = []);

    public function authorize(ClientInterface $http = null): ClientInterface;

    public function getName(): string;

    public function setLogger(LoggerInterface $logger): AuthInterface;

    public function setCache(CacheItemPoolInterface $logger): AuthInterface;

}
