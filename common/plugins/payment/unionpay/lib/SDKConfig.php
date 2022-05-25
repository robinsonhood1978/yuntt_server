<?php

namespace common\plugins\payment\unionpay\lib;

// 商户的私钥证书
define( 'SDK_SIGN_CERT_PATH', str_replace("\\","/", dirname(dirname(__FILE__))) . '/certs/ywaj0341.pfx');

// 签名证书密码
define( 'SDK_SIGN_CERT_PWD',  '070901');

// 银联公钥证书（这条自个加的，备忘用）
//define( 'SDK_VERIFY_CERT_PATH',  str_replace("\\","/", dirname(dirname(__FILE__))) . '/certs/acp_prod_verify_sign.cer');

// 密码加密证书（这条一般用不到的请随便配）
// 银联的敏感信息加密证书，网关支付用不到，代收，代付等后台产品　银联配置了敏感信息加密的时候用的到
define( 'SDK_ENCRYPT_CERT_PATH',  str_replace("\\","/", dirname(dirname(__FILE__))) . '/certs/acp_prod_enc.cer');

// 验签证书路径（请配到文件夹，不要配到具体文件）
define( 'SDK_VERIFY_CERT_DIR', str_replace("\\","/", dirname(dirname(__FILE__))) .'/certs/');

// 前台请求地址
define( 'SDK_FRONT_TRANS_URL', 'https://gateway.95516.com/gateway/api/frontTransReq.do');

// 后台请求地址
define( 'SDK_BACK_TRANS_URL', 'https://gateway.95516.com/gateway/api/backTransReq.do');

//日志 目录 
define( 'SDK_LOG_FILE_PATH', str_replace("\\","/", dirname(dirname(__FILE__))) . '/logs/');

//日志级别，关掉的话改PhpLog::OFF
//define( 'SDK_LOG_LEVEL', PhpLog::DEBUG);
define( 'SDK_LOG_LEVEL', PhpLog::OFF);