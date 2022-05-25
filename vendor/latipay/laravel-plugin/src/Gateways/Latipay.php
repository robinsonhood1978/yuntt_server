<?php

namespace Latipay\LaravelPlugin\Gateways;

use Latipay\LaravelPlugin\Exceptions\BusinessException;
use Latipay\LaravelPlugin\Exceptions\InvalidSignException;
use Latipay\LaravelPlugin\Kernel\Supports\Collection;
use Symfony\Component\HttpFoundation\Request;
use Latipay\LaravelPlugin\Contracts\GatewayApplicationInterface;
use Latipay\LaravelPlugin\Contracts\GatewayInterface;
use Latipay\LaravelPlugin\Exceptions\InvalidConfigException;
use Latipay\LaravelPlugin\Exceptions\InvalidGatewayException;
use Latipay\LaravelPlugin\Gateways\Latipay\Support;
use Latipay\LaravelPlugin\Kernel\Supports\Config;
use Latipay\LaravelPlugin\Kernel\Supports\Str;
use Symfony\Component\HttpFoundation\Response;

class Latipay implements GatewayApplicationInterface
{
    const MODE_V2 = 'V2';

    const URL = [
        self::MODE_V2 => 'https://api.latipay.net/v2/transaction',
    ];

    const PAYMENT_METHOD_URL = [
        self::MODE_V2 => 'https://api.latipay.net/v2/detail/',
    ];

    protected $payload;

    protected $gateway;

    protected $paymentMethodUrl;

    public function __construct(Config $config)
    {
        $mySupport = Support::create($config);
        $this->gateway = $mySupport->getBaseUri();
        $this->paymentMethodUrl = $mySupport->getPaymentMethodUri();

        $this->payload = [
            'api_key' => $config->get('api_key'),
            'user_id' => $config->get('user_id'),
            'wallet_id' => $config->get('wallet_id'),
            'payment_method' => $config->get('payment_method'),
            'return_url' => $config->get('return_url'),
            'callback_url' => $config->get('callback_url'),
            'version' => '2.0',
        ];
    }

    public function getPaymentMethods()
    {
        if (!$this->payload['wallet_id']) {
            throw new InvalidConfigException("Latipay Error! Please try later. Latipay wallet_id Not found.");
        }

        $walletId = $this->payload['wallet_id'];
        $userId = $this->payload['user_id'];
        $apiKey = $this->payload['api_key'];

        $preHash = $walletId . $userId;
        $signature = hash_hmac('sha256', $preHash, $apiKey);

        $url = $this->paymentMethodUrl.$walletId;
        $query = [
            'user_id' => $userId,
            'signature' => $signature,
        ];

        $apiData = Support::requestGetApi($url, $query);

        if (is_array($apiData) && isset($apiData['code']) && ($apiData['code'] === 0)) {
            $wallet = $apiData['payment_method'];
        }

        $wallet = isset($wallet) ? $wallet : 'Wechat,Alipay';
        return explode(',', $wallet);
    }

    /**
     * @param $method
     * @param $params
     *
     * @return mixed
     * @throws InvalidGatewayException
     */
    public function __call($method, $params)
    {
        return $this->pay($method, ...$params);
    }

    /**
     * @param       $gateway
     * @param array $params
     *
     * @return mixed
     * @throws InvalidGatewayException
     */
    public function pay($gateway, $params = [])
    {
        $this->payload = array_merge($this->payload, $params);

        $gateway = get_class($this).'\\'.Str::studly($gateway).'Gateway';

        if (class_exists($gateway)) {
            return $this->makePay($gateway);
        }

        throw new InvalidGatewayException("Pay Gateway [{$gateway}] Not Exists");
    }

    public function verify($data = null, $refund = false)
    {
        if (is_null($data)) {
            $request = Request::createFromGlobals();

            $data = $request->request->count() > 0 ? $request->request->all() : $request->query->all();
        }
        

        $paymentMethod = isset($data['payment_method']) ? $data['payment_method'] : '';
        $status = isset($data['status']) ? $data['status'] : '';
        $currency = isset($data['currency']) ? $data['currency'] : '';
        $amount = isset($data['amount']) ? $data['amount'] : '';
        $orderId = isset($data['merchant_reference']) ? $data['merchant_reference'] : '';

        $signatureString = $orderId . $paymentMethod . $status . $currency . $amount;
        $signature = hash_hmac('sha256', $signatureString, $this->payload['api_key']);
        // $signature = hash_hmac('sha256', $signatureString, '111222333');

        $data['signature'] = $signature;
        if (isset($data['signature']) && $signature == $data['signature']) {
            return new Collection($data);
            // return $data;
        }
        return new Collection($data);

        throw new InvalidSignException('Latipay Sign Verify FAILED', $data);
    }

    public function find($orderId, $type = 'web')
    {
        $gateway = get_class($this).'\\'.Str::studly($type).'Gateway';

        if (!class_exists($gateway) || !is_callable([new $gateway(), 'find'])) {
            throw new InvalidGatewayException("{$gateway} Done Not Exist Or Done Not Has FIND Method");
        }

        $signatureString = $orderId.$this->payload['user_id'];
        $signature = hash_hmac('sha256', $signatureString, $this->payload['api_key']);

        $url = $this->gateway.'/'.$orderId;
        $query = [
            'user_id' => $this->payload['user_id'],
            'signature' => $signature,
        ];

        $apiData = Support::requestGetApi($url, $query);
        if (is_array($apiData) && isset($apiData['code']) && ($apiData['code'] === 0)) {
            return $apiData;
        }

        throw new BusinessException('Order query failed!');
    }

    public function refund($order)
    {

    }

    public function cancel($order)
    {

    }

    public function close($order)
    {

    }

    public function success()
    {
        return Response::create('sent');
    }

    /**
     * @param $gateway
     *
     * @return mixed
     * @throws InvalidGatewayException
     */
    protected function makePay($gateway)
    {
        $app = new $gateway();

        if ($app instanceof GatewayInterface) {
            return $app->pay($this->gateway, array_filter($this->payload, function ($value) {
                return $value !== '' && !is_null($value);
            }));
        }

        throw new InvalidGatewayException("Pay Gateway [{$gateway}] Must Be An Instance Of GatewayInterface");
    }
}
