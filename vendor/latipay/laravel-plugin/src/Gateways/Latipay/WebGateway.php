<?php

namespace Latipay\LaravelPlugin\Gateways\Latipay;


use Latipay\LaravelPlugin\Contracts\GatewayInterface;
use Latipay\LaravelPlugin\Exceptions\BusinessException;
use Latipay\LaravelPlugin\Kernel\Supports\Arr;
use Symfony\Component\HttpFoundation\Response;

class WebGateway implements GatewayInterface
{

    public function pay($endpoint, array $payload)
    {

        if (isset($payload['amount'])) {
            $payload['amount'] = sprintf("%.2f",round($payload['amount'],2));
        }

        $postData = array(
            'user_id' => isset($payload['user_id']) ? $payload['user_id'] : '',
            'wallet_id' => isset($payload['wallet_id']) ? $payload['wallet_id'] : '',
            'amount' => isset($payload['amount']) ? $payload['amount'] : '',
            'payment_method' => isset($payload['payment_method']) ? strtolower($payload['payment_method']) : '',
            'return_url' => isset($payload['return_url']) ? $payload['return_url'] : '',
            'callback_url' => isset($payload['callback_url']) ? $payload['callback_url'] : '',
            'merchant_reference' => isset($payload['merchant_reference']) ? $payload['merchant_reference'] : '',
            'ip' => isset($payload['ip']) ? $payload['ip'] : '127.0.0.1',
            'product_name' => isset($payload['product_name']) ? $payload['product_name'] : '',
            'version' => '2.0',
        );

        if ($postData['payment_method'] == "wechat") {
            $postData['present_qr'] = isset($payload['present_qr']) ? $payload['present_qr'] : 1;
        }

        ksort($postData);
        $item = array();
        foreach ($postData as $key => $value) {
            $item[] = $key . "=" . $value;
        }
        $_prehash =  join("&", $item);

        $api_key = $payload['api_key'];
        $signature = hash_hmac('sha256', $_prehash . $api_key, $api_key);
        $postData['signature'] = $signature;

        $return = [];
        try {
            $options = [];
            $options['headers'] = [
                "Content-Type" => "application/json"
            ];

            $payment = Support::requestApi($postData, $endpoint, $options);
            if ($payment['host_url'] != '') {
                $response_signature = hash_hmac('sha256', $payment['nonce'].$payment['host_url'], $payload['api_key']);
                if ($response_signature == $payment['signature']) {
                    $redirect_url         = $payment['host_url'].'/'.$payment['nonce'];
                    $return['status']      = 'success';
                    $return['redirect_url'] = $redirect_url;
                }
            } else {
                throw new BusinessException($payment['message']);
            }
        } catch (\Exception $e) {
            throw new BusinessException($e->getMessage());
        }

        return $return;
    }

    /**
     * Find.
     *
     * @author mamba <me@mamba.cn>
     *
     * @param $order
     *
     * @return array
     */
    public function find($order)
    {
        return [
            'method'      => 'latipay.trade.query',
            'biz_content' => json_encode(is_array($order) ? $order : ['out_trade_no' => $order]),
        ];
    }

    /**
     * Build Html response.
     *
     * @author mamba <me@mamba.cn>
     *
     * @param string $endpoint
     * @param array  $payload
     * @param string $method
     *
     * @return Response
     */
    protected function buildPayHtml($endpoint, $payload, $method = 'POST')
    {

    }

    /**
     * Get method config.
     *
     * @author mamba <me@mamba.cn>
     *
     * @return string
     */
    protected function getMethod()
    {
        return 'latipay.trade.page.pay';
    }

    /**
     * Get productCode config.
     *
     * @author mamba <me@mamba.cn>
     *
     * @return string
     */
    protected function getProductCode()
    {
        return 'FAST_INSTANT_TRADE_PAY';
    }
}
