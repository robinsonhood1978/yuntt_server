<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;
	

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    public static $user = [];
    public static $uuid = [];
	public static $uuser = [];
	
	public static $gateway = 'http://127.0.0.1/shopwind/frontend/web';
	
    public static function onWorkerStart($businessWorker)
    {   
		//服务准备就绪
        echo "Worker_socket_ready\n";
    }

    public static function onConnect($client_id)
    {
       //当客户端链接上时触发，这里可以做 session  域名来源排除 ，安全过滤等
	   echo "$client_id \n";
    }

    public static function onMessage($client_id, $message)
    {   
		echo "$client_id $message\n";
		
        /*监听事件，需要把客户端发来的json转为数组*/
        $data = json_decode($message, true);
        switch ($data['type']) {

            //当有用户上线时
            case 'reg':
			
                //绑定uid 用于数据分发
                Gateway::bindUid($client_id, $data['content']['id']);
				
				// 发现在Linux系统下无效
				self::$user[$data['content']['id']] = $client_id;
               	self::$uuid[$data['content']['id']] = $data['content']['id'];
				self::$uuser[$data['content']['id']] = $data['content'];
					
				// Linux系统下兼容处理（$user,$uuid, $uuser 数组不会累加，原因不明（通过数据库形式来获取在线用户信息（兼容Win））
				if(strpos(strtolower(PHP_OS), 'win') !== 0)
        		{
					$onlineuser = self::getAllOnlineUser($client_id, $data['content']['id']);
					if($onlineuser)
					{
						extract($onlineuser);
						
						self::$user = $f_user;
               	 		self::$uuid = $f_uuid;
						self::$uuser= $f_uuser;
					}
        		}

                //给当前客户端 发送当前在线人数，以及当前在线人的资料
                //$reg_data['uuser'] = self::$uuid;
				$reg_data['uuser'] = self::$uuser;
                $reg_data['num'] = count(self::$user);
                $reg_data['type'] = "regUser";
				
				//echo "$client_id currentClient " . json_encode($reg_data). "\n";
				
                Gateway::sendToClient($client_id, json_encode($reg_data));

                //将当前在线用户数量，和新上线用户的资料发给所有人 但把排除自己，否则会出现重复好友
                $all_data['type'] = "addList";
                $all_data['content'] = $data['content'];
                $all_data['content']['type'] = 'friend';
                $all_data['content']['groupid'] = 1;
                $all_data['num'] = count(self::$user);
				
				//echo "$client_id allClient" . json_encode($all_data). "\n";
				
                Gateway::sendToAll(json_encode($all_data), '', $client_id);
                break;


            case 'chatMessage':
			
                //处理聊天事件
                $msg['username'] = $data['content']['mine']['username'];
                $msg['avatar'] = $data['content']['mine']['avatar'];
                $msg['id'] = $data['content']['mine']['id'];
                $msg['content'] = htmlspecialchars($data['content']['mine']['content']);
                $msg['type'] = $data['content']['to']['type'];
				
				$chatMessage['type'] = 'getMessage';
                $chatMessage['content'] = $msg;
				
				if(self::userImForbid($msg['id'])) {
					$sayDisabled['type'] = 'sayDisabled';
					Gateway::sendToClient($client_id, json_encode($sayDisabled));
				}
				else
				{
					
					// 将聊天记录保存到本地数据库
					$saveResult = intval(self::saveTalk($data));
					
					//echo "$client_id allClient" . $saveResult. "\n";
					
					// 确保保存成功后，才发送到客户端
					if($saveResult > 0)
					{
						$chatMessage['content']['logid'] = $saveResult;
						
						//echo "$client_id allClient" . json_encode($data). "\n";
								
						//处理单聊
						if ($data['content']['to']['type'] == 'friend') {
		
							//if (isset(self::$uuid[$data['content']['to']['id']])) {
							if (isset($data['content']['to']['id'])) {
								//Gateway::sendToUid(self::$uuid[$data['content']['to']['id']], json_encode($chatMessage));
								//echo "$client_id allClient" . json_encode($chatMessage). "\n";
								Gateway::sendToUid($data['content']['to']['id'], json_encode($chatMessage));
							} else {
								//处理离线消息
								$noonline['type'] = 'noonline';
								Gateway::sendToClient($client_id, json_encode($noonline));
							}
						} else {
							//处理群聊
							//$chatMessage['content']['id'] = $data['content']['to']['id'];
							//Gateway::sendToAll(json_encode($chatMessage), '', $client_id);
						}
					}
				}
   			break;
        }
    }
    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {    
        // 有用户离线时触发 并推送给全部用户
        $data['type'] = "out";
        $data['id'] = array_search($client_id, self::$user);
        unset(self::$user[$data['id']]);
        unset(self::$uuid[$data['id']]);
		unset(self::$uuser[$data['id']]);
        $data['num'] = count(self::$user);
        Gateway::sendToAll(json_encode($data));
		
		// 加密签名
		$token = 'abcdefghijklmn1234556789';
		$uid   = $data['id'];
		$sign  = md5($uid.$token);
		
		return file_get_contents(self::$gateway . "/webim/logout.html?uid={$uid}&sign={$sign}");
    }
	
	public static function userImForbid($uid = 0)
	{
		return (file_get_contents(self::$gateway . "/webim/imforbid.html?uid={$uid}") == 'true') ? true : false;
	}
	
	public static function saveTalk($data = array())
	{
		$from = $data['content']['mine']['id'];
		$to   = $data['content']['to']['id'];
		$type = $data['content']['to']['type'];
		$content = trim($data['content']['mine']['content']);
		$formatContent = trim($data['content']['mine']['formatContent']);
		
		// 加密签名
		$token = 'abcdefghijklmn1234556789';
		
		$sign = md5($from.$to.$type.$content.$token);
		$params = array(
			'from' 			=> $from, 
			'fromName' 		=> $data['content']['mine']['username'], 
			'to' 			=> $to, 
			'toName' 		=> $data['content']['to']['username'],
			'type' 			=> $type, 
			'content' 		=> $content, 
			'formatContent' => serialize($formatContent), 
			'sign' 			=> $sign
		);
		
		$result = self::getHttpResponsePOST(self::$gateway . '/webim/talk.html', 'post', $params);
		$result = json_decode($result, true);
		return $result;
	}
	public static function getAllOnlineUser($client_id = '', $uid = '')
	{
		// 加密签名
		$token = 'abcdefghijklmn1234556789';
		
		$result = array();
		
		if($client_id && $uid) {
			$sign = md5($uid.$client_id.$token);
			$result = file_get_contents(self::$gateway . "/webim/online.html?client_id={$client_id}&uid={$uid}&sign={$sign}");
			$result = json_decode($result, true);
		}
		return $result;
	}
	
	public static function getHttpResponsePOST($url, $method = 'GET', $post = array(), $postJSON = false, $cacert_url = '')
	{
		//初始化curl
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		if($cacert_url) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
			curl_setopt($ch, CURLOPT_CAINFO, $cacert_url);//证书地址
		}
		
		// 以JSON数组的形式提交
		if($postJSON == true) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(  
				"Content-Type: application/json; charset=utf-8",
				"Content-Length: " . strlen($post))  
			); 
		}
		
		//设置超时
		//curl_setopt($ch, CURLOP_TIMEOUT, $this->curl_timeout);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if(strtoupper($method) == 'POST'){
			curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
			curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($post) ? http_build_query($post) : $post);
		}
		
		//运行curl，结果以jason形式返回
		$res = curl_exec($ch);
		//var_dump( curl_error($ch) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
		curl_close($ch);
		return $res;
	}
}
