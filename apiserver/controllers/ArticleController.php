<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 * Robin modified 05/03/2022
 */

namespace apiserver\controllers;

use Yii;
use yii\web\Controller;

use common\models\ArticleModel;
use common\models\AcategoryModel;
use common\models\DepositTradeModel;
use common\models\BindModel;

use common\library\Basewind;
use common\library\Timezone;
use common\library\Page;

use apiserver\library\Respond;
use Latipay\LaravelPlugin\Pay;
// use yii\httpclient\Client;
// use GuzzleHttp\Client;
use linslin\yii2\curl;
use common\library\Plugin;
use common\library\Def;
use common\library\Setting;


/**
 * @Id ArticleController.php 2018.10.15 $
 * @author yxyc
 */

class ArticleController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;

	protected $config = [
        'api_key' => 'wg32ef44',
        'user_id' => 'U000043109',
        'wallet_id' => 'W000046384', //钱包ID
        'version' => '2.0',//default
    ];

	public function actionCurrency()
	{
		$respond = new Respond();
		$setting = Setting::getInstance()->getAll();
		return $respond->output(true, null, $setting);
	}

	public function actionAutocomplete()
    {
        $respond = new Respond();
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$address = urlencode($post->value);
		

		$result = Basewind::curl('https://maps.googleapis.com/maps/api/place/autocomplete/json?input='.$address.'&strictbounds=true&location=-41.3341175%2C172.6017639&radius=846569&key=AIzaSyBZulG_3lw3l33ZK_WEk5kzRKqcuYueZk8', 'get', null, true);
		$response = json_decode($result);

		$predictions = $response->predictions;

		$addr = array();

		foreach ($predictions as $key => $value) {
			$addr[] = $value->description;
		}

		// for ($i = 0; $i < count($predictions); ++$i) {
		// 	$addr[] = $predictions[$i] = $arr[$i] . '_i';
		// }

		// print_r($addr);
		// exit;

		$model = new \apiserver\models\TestForm();	
		
		$content = [
			'content' => json_encode($addr),
		];
		
		if(($record = $model->save($content, false)) === false) {
			return $respond->output(Respond::CURD_FAIL, 'add fail');
		}

		return $respond->output(true, null, json_encode($addr));
    }

	public function actionLatipaywechat()
    {
        $respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['userId']);

		$tradeOrderId = (array)$post->orderId;
		$tod = $tradeOrderId[0];

		$userId = $post->userId;

		// 获取交易数据
		list($errorMsg, $orderInfo) = DepositTradeModel::checkAndGetTradeInfo($tod, $userId);
		if ($errorMsg !== false) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}

		// echo($orderInfo['amount']);
		// exit;

		$amount = $orderInfo['amount'];
		$product_name = $post->bizOrderId;
		$open_id = BindModel::getOpenid($userId);
		
		$data = [
			"user_id" => "U000043109",
			"wallet_id" => "W000046384",
			"amount" => $amount,
			"notify_url" => "https://cnstore.edisonwang.cn/api/article/notify",
			"merchant_reference" => $tod,
			"app_id" => "wx18c7c6c6d8e4c3f5",
			"open_id" => $open_id,
			"product_name" => $product_name
			// "signature" => "41dd7accf4b9d663266525124c83de792815889a16df24b75093e525b37b3d62"
		];

		$signature = Basewind::createWechatPaySignWithHash($data, 'wg32ef44');

		$data['signature'] = $signature;

		// $curl = new curl\Curl();
		// $response = json_decode($curl->setGetParams($data)
		// 	->get('https://api.weixin.qq.com/sns/jscode2session', true));

		$result = Basewind::curl('https://api.latipay.net/v2/miniapppay', 'post', json_encode($data), true);
		$response = json_decode($result);

		// $response = [
		// 	"code" => 0,
		// 	"message" => "SUCCESS",
		// 	"messageCN" => "\u64cd\u4f5c\u6210\u529f",
		// 	"payment" => {
		// 	   "timeStamp":"1643429837",
		// 	   "nonceStr":"nowqgpUMdVEYyUAh",
		// 	   "packageStr":"prepay_id=wx291217173212954047de1a599507010000",
		// 	   "signType":"MD5",
		// 	   "paySign":"1132e5bdb72ad2c4aefa59257a4a5e51"
		// 	},
		// 	"paydata" => {
		// 	   "order_id":"2022012900004152",
		// 	   "nonce":"7d8d1d20220129001716c008b1bbb0054d3da81910804ab657",
		// 	   "payment_method":"wechat",
		// 	   "amount":0.01,
		// 	   "amount_cny":0.04,
		// 	   "currency":"NZD",
		// 	   "product_name":"International Freight",
		// 	   "organisation_id":42713,
		// 	   "organisation_name":"AION TECHNOLOGIES LIMITED",
		// 	   "user_id":"U000043109",
		// 	   "user_name":"Mr Bo Lin",
		// 	   "wallet_id":"W000046384",
		// 	   "wallet_name":"AION TECH",
		// 	   "qr_code":null,
		// 	   "qr_code_url":null,
		// 	   "currency_rate":"4.16220",
		// 	   "merchant_reference":"robin_test",
		// 	   "signature":"55298f7f8285f52a2428aeaf3aa7a86e27cff583f8e6662851cd199a449ca4c1"
		// 	},
		// 	"gatewaydata" => {
		// 	   "return_code":"SUCCESS",
		// 	   "return_msg":"OK",
		// 	   "appid":"wx41b857f53090e44c",
		// 	   "mch_id":"1303797501",
		// 	   "sub_appid":"wx18c7c6c6d8e4c3f5",
		// 	   "sub_mch_id":"484622892",
		// 	   "device_info":null,
		// 	   "nonce_str":"nowqgpUMdVEYyUAh",
		// 	   "sign":"2E664A4863E2FD2C1AA06D2C56384E9E",
		// 	   "result_code":"SUCCESS",
		// 	   "err_code":null,
		// 	   "err_code_des":null,
		// 	   "trade_type":"JSAPI",
		// 	   "prepay_id":"wx291217173212954047de1a599507010000",
		// 	   "code_url":null
		// 	}
		// ];
		// $response = json_decode($response);
		
		$payment = (array)$response->payment;
		$payment['package'] = $payment['packageStr'];
		unset($payment['packageStr']);

		$paydata = (array)$response->paydata;
		$latipayOrderId = $paydata['order_id'];
		// $latipayOrderId作为payTradeNo更新到交易表
		if (DepositTradeModel::updatePayTradeNo($tod, $latipayOrderId) === false) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('payment_save_trade_fail'));
		}


		$arr = [
			'code' => 0,
			'req' => $data,
			'post' => $post,
			'result' => $payment,
		];

		$arr2 = [
			'req' => $data,
			'result' => (array)$response,
		];

		$model = new \apiserver\models\TestForm();	
		
		$content = [
			'content' => json_encode($arr2),
		];
		
		if(($record = $model->save($content, false)) === false) {
			return $respond->output(Respond::CURD_FAIL, 'add fail');
		}

		return $respond->output(true, null, $arr);
    }

	public function actionWechatopenid()
    {
        $respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		
		$data = [
            'appid' => 'wx18c7c6c6d8e4c3f5', 
            'secret' => 'f7945c071f33f01815760c38de2e3384',
            'js_code' => $post->code,
            'grant_type' => 'authorization_code', 
        ];

		$curl = new curl\Curl();
		$response = json_decode($curl->setGetParams($data)
			->get('https://api.weixin.qq.com/sns/jscode2session', true));

		// $client = new Client();
		// $response = $client->createRequest()
		// 	->setMethod('POST')
		// 	->setUrl('https://api.weixin.qq.com/sns/jscode2session')
		// 	->setData($data)
		// 	->send();

		// $client = new Client(['baseUrl' => 'https://api.weixin.qq.com/sns/']);

		// $response = $client->get('jscode2session', $data)->send();

		// $client = new Client(['base_uri' => 'https://api.weixin.qq.com/sns/']);
		// Send a GET request to /get?foo=bar
		// $response = $client->request('GET', 'jscode2session', ['query' => $data]);


        //$result = Pay::latipay($this->config)->web($order);
		//$result = $order;
		$arr = [
			'code' => 0,
			'req' => $data,
			'result' => $response,
		];
				

        /** result数据，redirect_url为支付url  
        * Array(
       *        [status] => success
        *       [redirect_url] => https://api.latiproduct.net/v2/gatewaydata_inapp/abcde
         *  )
        **/
        //return redirect($result['redirect_url']);
		return $respond->output(true, null, $arr);
    }

	//下单并返回支付url
    public function actionLatipay()
    {
        $respond = new Respond();
		$order = [
            'merchant_reference' => 'YTT' . time(), //商户订单号 ,当payment_method为moneymore时，每次发起支付订单id不能相同
            'amount' => '0.01',
            'product_name' => '测试支付',
            'return_url' => 'return_url', //支付完成页面返回地址
            'callback_url' => 'callback_url', //异步通知回调地址
            'payment_method' => 'wechat', // wechat, alipay, moneymore
            'present_qr' => '1', // wechat
            'ip' => '127.0.0.1',
        ];

        $result = Pay::latipay($this->config)->web($order);
		//$result = $order;
		$arr = [
			'order' => $order,
			'result' => $result,
		];
				

        /** result数据，redirect_url为支付url  
        * Array(
       *        [status] => success
        *       [redirect_url] => https://api.latiproduct.net/v2/gatewaydata_inapp/abcde
         *  )
        **/
        //return redirect($result['redirect_url']);
		return $respond->output(true, null, $arr);
    }

	//获取支持的支付方式
    //返回数组 Array(
    //    [0] => Alipay
    //    [1] => Wechat
    //    [2] => MoneyMore
    //)
    public function actionMethod()
    {
        $respond = new Respond();
		$arr = Pay::latipay($this->config)->getPaymentMethods();
		// demo for $arr
		// {
		// 	"code": 0,
		// 	"message": "请求成功！",
		// 	"data": [
		// 		"Wechat",
		// 		"Alipay",
		// 		"Polipay",
		// 		"MoneyMore"
		// 	]
		// }
		return $respond->output(true, null, $arr);
    }
    
    //查询订单
    //返回数组内容参考
    //Array(
    //      [code] => 0
    //      [message] => SUCCESS
    //      [messageCN] => 操作成功
    //      [merchant_reference] => 1567568358
    //      [status] => paid
    //      [currency] => NZD
    //      [amount] => 0.02
    //      [amount_cny] => 0.1
    //      [rate] => 0
    //      [signature] => 103600c090f5f0738a2df5c891faf192b46111f0dca3ac5712d6138234054f4b
    //     [payment_method] => wechat
    //      [transaction_id] => 2019090400003370
    //      [order_id] => 2019090400003370
    //      [pay_time] => 2019-09-04 03:39:57
    //  )
    public function actionQueryOrder($orderId)
    {
        $respond = new Respond();
		return Pay::latipay($this->config)->find($orderId);
		return $respond->output(true, null, $arr);
    }

    //支付完成后（成功或失败）浏览器重定向
    public function actionReturnBack()
    {
        $data = Pay::latipay($this->config)->verify(); // 是的，验签就这么简单！
        
        //$data为collection
        //"merchant_reference" => "1567568358"
        //"order_id" => "2019090400003370"
        //"currency" => "NZD"
        //"status" => "paid"
        //"payment_method" => "wechat"
        //"signature" => "103600c090f5f0738a2df054f4b"
        //"createDate" => "2019-09-04 03:39:19"
        //"amount" => "0.02"
        
        //重定向逻辑
    }

    //支付结果异步通知
    public function actionNotify()
    {
        $latipay = Pay::latipay($this->config);
    
        try{
			$data = $latipay->verify(); // 是的，验签就这么简单！
            //data内容同上
			//var_dump($data->toArray());
			$arr = $data->toArray();

			$model = new \apiserver\models\TestForm();	
		
			$content = [
				'content' => json_encode($arr),
				'trade_no' => $arr['merchant_reference'],
				'latipay_order_id' => $arr['order_id'],
			];

			// var_dump($content['content']);
			// exit;
			
			if(($record = $model->save($content, false)) === false) {
				return $respond->output(Respond::CURD_FAIL, 'add fail');
			}
            //回调业务逻辑
			if(empty($arr['order_id'])) {
				return false;
			}
			if(!($orderInfo = DepositTradeModel::getTradeInfoForNotify($arr['order_id']))) {
				return false;
			}

			$payment_code = $orderInfo['payment_code'];

			$payment = Plugin::getInstance('payment')->build($payment_code);

			if(!($payment_info = $payment->getInfo()) || !$payment_info['enabled']) {
				return false;
			}

			// 购物订单（处理购物逻辑）
			if(in_array($orderInfo['bizIdentity'], array(Def::TRADE_ORDER)))
			{
				if($payment->handleOrderAfterNotify($orderInfo, ['target' => Def::ORDER_ACCEPTED]) === false) {
					//return Message::warning($payment->errors);
					return $payment->verifyResult(false);
				}
			}
           
           //异步通知成功：Latipay服务器期望收到 sent 文本
           die('sent');

        } catch (\Exception $e) {
            // $e->getMessage();
        }
    }

	/**
	 * 获取文章详情
	 * @api 接口访问地址: http://api.xxx.com/article/list
	 */
    public function actionList()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['cate_id', 'page', 'page_size']);
		
		$query = ArticleModel::find()->select('article_id, title, add_time')
			->where(['if_show' => 1])
			->orderBy(['sort_order' => SORT_ASC, 'article_id' => SORT_DESC]);

		if($post->cate_id) {
			$allId = AcategoryModel::getDescendantIds($post->cate_id);
			$query->andWhere(['in', 'cate_id', $allId]);
		}

		if(isset($post->items) && !empty($post->items)) {
			$query->andWhere(['in', 'article_id', explode(',', $post->items)]);
		}

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value) {
			$list[$key]['add_time'] = Timezone::localDate('Y-m-d', $value['add_time']);
		}
	
		return $respond->output(true, null, ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
    }
	
	/**
	 * 获取文章详情
	 * @api 接口访问地址: http://api.xxx.com/article/read
	 */
    public function actionRead()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id']);
		
		$record = ArticleModel::find()->select('article_id, title, description, add_time')->where(['article_id' => $post->id, 'if_show' => 1])->asArray()->one();
		$record['add_time'] = Timezone::localDate('Y-m-d', $record['add_time']);
	
		return $respond->output(true, null, $record);
    }
}