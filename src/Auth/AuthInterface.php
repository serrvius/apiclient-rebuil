<?php
/**
 * Created by PhpStorm.
 * User: sergiu
 * Date: 1/30/19
 * Time: 12:15 AM
 */

namespace API\Auth;

use GuzzleHttp\ClientInterface;

interface AuthInterface
{

    public function __construct(array $configuration = []);

    public function authorize(ClientInterface $client = null);

}
