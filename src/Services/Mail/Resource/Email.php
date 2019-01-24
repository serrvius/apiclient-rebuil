<?php
/**
 * Created by PhpStorm.
 * User: sergiu
 * Date: 1/25/19
 * Time: 12:08 AM
 */

namespace API\Services\Mail\Resource;

use API\Resource;
use API\Services\Mail\Providers;

class Email extends Resource
{

    public function __construct($service, $serviceName, $resourceName, $resource)
    {
        parent::__construct($service, $serviceName, $resourceName, $resource);
    }

    public function providers($optParams = [])
    {
        $params = [];
        $params = array_merge($params, $optParams);

        return $this->call('providers', array($params), Providers::class);
    }

}
