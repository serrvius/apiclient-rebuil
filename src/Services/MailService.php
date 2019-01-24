<?php
/**
 * Created by PhpStorm.
 * User: sergiu
 * Date: 1/25/19
 * Time: 12:12 AM
 */

namespace API\Services;

use API\Client;
use API\Service;
use API\Services\Mail\Resource\Email;

class MailService extends Service
{

    public $mail;

    public function __construct(Client $client)
    {
        parent::__construct($client);

        $this->rootUrl = '/mail/v2';
        $this->servicePath = '/mail/v2';
        $this->version     = 'v2';

        $this->mail = new Email($this, 'mail', 'mail', [
            'methods' => [
                'providers' => [
                    'path'       => '/providers',
                    'httpMethod' => 'GET',
                ],
            ],
        ]);
    }

}
