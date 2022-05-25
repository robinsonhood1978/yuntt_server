/**
 * PC端可接入网站支付宝登录，详见 网站支付宝登录 介绍；
 * 无线端可接入app支付宝登录，详见APP支付宝登录产品 介绍。
 * 注：目前H5网页端暂不支持支付宝第三方登录，只能通过支付宝客户端或 支付宝生活号（或H5）发起登录
 * 接口文档：https://opendocs.alipay.com/open/263/105809/
 * 取消授权：https://openauth.alipay.com/auth/tokenManage.htm
 */

/**
 * 以下为官方原文
 * @Link: https://opensupport.alipay.com/support/knowledge/27589/201602150001
 * 参考其他客户反馈：
 * 1）https://www.gznotes.com/wap-h5-alipay-app-startup-load-oauth-url/
 * 2）https://opensupport.alipay.com/support/helpcenter/168/201602482996#
 */

网站支付宝登录产品介绍
支付宝授权登录也就是网站支付宝登录，其开发者可以通过国际标准的OAuth2.0授权机制，在用户授权的情况下，得到用于换取支付宝用户信息的令牌。在拿到用户的授权令牌后，通过调用用户信息共享接口，获取用户的公开信息（如支付宝用户ID、昵称等）。
注：电脑网站和手机网站（H5登录）都可使用该产品进行第三方支付宝登录，但是手机网站支付宝登录访问授权链接时，必须在支付宝钱包内进行授权登录。
更多详见网站支付宝登录在线文档。