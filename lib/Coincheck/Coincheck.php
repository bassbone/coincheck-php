<?php
namespace Coincheck;

use Guzzle\Common\Event as GuzzleEvent;
use Guzzle\Service\Client as GuzzleClient;
use Guzzle\Service\Description\ServiceDescription;

class Coincheck
{
    /** @var GuzzleClient */
    private $client;

    /** @var Order */
    private $order;
    /** @var Send */
    private $send;
    /** @var Borrow */
    private $borrow;
    /** @var Lend */
    private $lend;
    /** @var Account */
    private $account;

    /**
     * @param array $options API options
     */
    public function __construct($accessKey, $apiSecret, $options = array())
    {
        $apiBase = 'https://coincheck.jp/';
        $this->client = new GuzzleClient($apiBase);
        $this->client->setDefaultOption('headers/Content-Type', "application/json");
        $this->client->setDefaultOption('headers/ACCESS-KEY', $accessKey);
        $description = ServiceDescription::factory(__DIR__ . "/Resource/service_descriptions/concheck.json");
        $this->client->setDescription($description);

        $this->order = new Order($this);
        $this->send = new Send($this);
        $this->lend = new Lend($this);
        $this->borrow = new Borrow($this);
        $this->account = new Account($this);
    }

    public function __get($key)
    {
        $accessors = array('order', 'lend', 'borrow', 'send', 'account');
        if (in_array($key, $accessors) && property_exists($this, $key)) {
            return $this->{$key};
        } else {
            throw new \Exception('Unknown accessor ' . $key);
        }
    }

    public function __set($key, $value)
    {
        throw new \Exception($key . ' is not able to override');
    }

    public function setSignature($url, $apiSecret, $arr = array())
    {
        $nonce = time();
        $message = $nonce.$url.http_build_query($arr);
        $signature = hash_hmac("sha256", $message, $apiSecret);
        $this->client->setDefaultOption('headers/ACCESS-NONCE', $nonce);
        $this->client->setDefaultOption('headers/ACCESS-SIGNATURE', $signature);
    }

    /**
     * Dispatch API request
     *
     * @param string $operation Target action
     * @param object $paramData Request data
     *
     */
    public function request($operation, $paramData)
    {
        $this->setSignature('https://coincheck.jp/'.'api/exchange/orders/transactions', 'SECRET_KEY' ,$paramData);
        $command = $this->client->getCommand($operation, $paramData);
        try {
            $res = $this->client->execute($command);
            echo json_encode($res);
        } catch (\Guzzle\Common\Exception\RuntimeException $e) {
            throw var_dump($e);
        }
    }
}
