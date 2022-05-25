<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\library;

use yii;
use yii\web\Response;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Url;

use common\models\DistributeModel;

use common\library\Basewind;
use common\library\Setting;

/**
 * @Id Page.php 2018.3.8 $
 * @author mosir
 */
 
class Page
{
	/**
	 * 可以在此拓展动态更换主题
	 * @param string $folder mall|store 商城模板或店铺模板
	 * @param string $template 主题目录
	 */
	public static function setView($folder = '', $template = 'default')
	{
		Yii::$app->view->theme = new \yii\base\Theme(array_merge(ArrayHelper::toArray(Yii::$app->view->theme), [
			'pathMap' => [
				'@app/views' => [
					'@app/views/'.$template. '/' .$folder
				]
			],
			'baseUrl' => '@web/views/'.$template. '/' .$folder
		]));
		
		return Yii::$app->view;
	}
	
	/* 页面SEO */
    public static function seo($seo = [])
    {
		$params = Yii::$app->params;
		if(Basewind::isInstall() != true) {
			$params = Setting::getDefault();	
		}
		
		$page = [
			'menu'			=> $params['site_name'],
			'title' 		=> $params['site_title'], 
			'keywords' 		=> $params['site_keywords'], 
			'description' 	=> $params['site_description']
		];
		
		foreach($seo as $k => $v) {
			if(isset($page[$k])) {
				$page[$k] = $v . ($k == 'title' ? ' - ' : ',') . $page[$k];
			}
			if($k == 'title' && !empty($v)) {
				$menu = explode('-', $v);
				$page['menu'] = trim($menu[0]);
			}
		}
		$page['title'] .= str_replace(['\a', '\b', '\c', '\f', '\g', '\j', '\k', '\l', '\v'], '', ' - P\aow\ber\ced b\fy S\gh\jop\kW\li\vnd');
		
		return $page;
	}
	
	/**
	 * 主题列表
	 * @param string $folder mall|store
	 * @param string $client pc|mobile
	 * @return array
	 */
	public static function listTemplate($folder = 'mall', $client = 'pc')
	{
		$dir = Yii::getAlias('@frontend/views');
		$list = FileHelper::findDirectories($dir, ['recursive' => false]);
	
		$templates = array();
		foreach($list as $item) {
			$templates[] = substr($item, strripos($item, DIRECTORY_SEPARATOR) + 1);
		}
		return $templates;
	}
	
	/* 当前位置 */
	public static function setLocal($arr = null) 
	{
		$curlocal = array();
		
        if (is_array($arr))
        {
            $curlocal = array_merge($curlocal, $arr);
        }
        else
        {
            $args = func_get_args();
            if (!empty($args))
            {
                $len = count($args);
                for ($i = 0; $i < $len; $i += 2)
                {
                    $curlocal[] = array(
                        'text'  => $args[$i],
                        'url'   => isset($args[$i+1]) ? $args[$i+1] : '',
                    );
                }
            }
        }
        return $curlocal;
	}
	
	/* 当前栏目（用户中心）*/
	public static function setMenu($curitem = null, $curmenu = null)
	{
		$model = new \frontend\library\Menu();
		if(($curitem !== null) && ($curmenu == null)) {
			return $model->curitem($curitem);
		}
		return ArrayHelper::merge($model->curitem($curitem), $model->curmenu($curmenu));
	}
	
	/**
	 * 在ACTION执行前跳转 
	 * 跳转到登录页面后，如登录成功，跳回到 
	 * @param string $redirect
	 */
	public static function redirect($redirect = null)
	{
		// $loginUrl = Yii::$app->user->loginUrl;
		$loginUrl = Url::toRoute(['user/login', 'redirect' => $redirect]);
		
		if(Yii::$app->request->isAjax) {
			Yii::$app->getResponse()->format = Response::FORMAT_JSON;
			Yii::$app->getResponse()->data = ['done' => false, 'icon' => 'warning', 'msg' => Yii::t('yii', 'Login Required'), 'loginUrl' => $loginUrl];
			return false;
		}
		return Yii::$app->getResponse()->redirect($loginUrl);
	}
	
	/**
	 * 页面的公共参数
	 * @param string $page as: mall|user|store
	 */
	public static function getAssign($page = '', $options = null)
	{
		$params = [
			'icp_number' => isset(Yii::$app->params['icp_number']) ? Yii::$app->params['icp_number'] : null,
			'statistics_code' => isset(Yii::$app->params['statistics_code']) ? Yii::$app->params['statistics_code'] : null,
		];
		
		if(in_array($page, ['mall', 'store'])) {
			$params['hot_keywords'] = explode(',', Yii::$app->params['hot_keywords']);

			$model = new \frontend\models\CartForm();
			$params['carts_top'] = $model->getCart();
		}
		
		return $params;
	}
	
	/**
	 * 将相对地址修改为绝对地址，以适应不同的应用显示
	 * @desc 主要是处理图片路径，不要使用在JS文件路径（以免引起跨域问题）
	 */
	public static function urlFormat($url = '', $default = '')
	{
		if(empty($url)) $url = $default;
		
		if(!empty($url) && Url::isRelative($url)) {
			return Basewind::homeUrl() . '/' . $url;
		}
		return $url;
	}
	
	public static function getPage($totalCount = 0, $pageSize = 10, $isAJax = false, $curPage = false)
	{
		$pagination = new \yii\data\Pagination();
		$pagination->totalCount = $totalCount;
		$pagination->pageSize = abs(intval($pageSize)) ? intval($pageSize) : 10;
		$pagination->pageSizeParam = false;
		$pagination->validatePage = false;
		$pagination->isAjax = $isAJax;
		
		// 针对API接口，通过非GET形式实现的翻页
		// 该组件当前页是从0开始算的，所以减1
		if($curPage !== false) {
			$pagination->setPage($curPage - 1, false);
		}
		
		return $pagination;
	}
	
	/**
	 * 返回分页数据
	 * 是否美化分页效果
	 * API接口返回的分页数据不需要美化效果
	 */
	public static function formatPage($page = null, $prettify = true, $style = 'default')
	{
		// for API
		if($prettify == false) {
			return [
				'page' => $page->getPage() + 1, 
				'page_size' => $page->getPageSize(),
				'page_count' => $page->getPageCount(), 
				'total' => (int)$page->totalCount
			];
		}
		
		$config = [
			'pagination' 	=> $page, 
			'nextPageLabel' => '下一页',
			'prevPageLabel' => '上一页', 
			'firstPageLabel'=> '首页', 
			'lastPageLabel' => '尾页',
			//'totalPageLabel' => '共%s页',
			//'totalCountLabel' => '共%s条记录',
			// 分页样式
			'options' 	=> ['class' => 'pagination pagination-'.$style],
			// 不够两页，隐藏分页，默认true 
			'hideOnSinglePage' => false,
			// 设置要展示是页数
			'maxButtonCount'=> 5
		];
		
		if(in_array($style, ['simple']))  {
			$config['nextPageLabel'] = '>';
			$config['prevPageLabel'] = '<';
			$config['firstPageLabel'] = false; 
			$config['lastPageLabel'] = false;
			$config['options'] = ['class' => 'pagination pagination-sm pagination-'.$style];
			$config['maxButtonCount'] = 0;
		}
		elseif(in_array($style, ['basic'])) {
			$config['maxButtonCount'] = 3;
		}
		else {
			$config['totalCountLabel'] = '共%s条记录';
		}
		
		return \yii\widgets\LinkPager::widget($config);
	}
	
	/**
	 * 生成二维码图片
	 */
	public static function generateQRCode($qrType = '', $params = null, $scalar = false)
	{
        $text = isset($params['text']) ? $params['text'] : 'TEXT';
		$size =  isset($params['size']) ? $params['size'] : 100;
        $margin = isset($params['margin']) ? $params['margin'] : 2;
       
        if ($qrType == 'goods') {
			$text = Url::toRoute(['goods/index', 'id' => $params['goods_id']], true);
			
        } 
		elseif($qrType == 'store') {
			$text = Url::toRoute(['store/index', 'id' => $params['store_id']], true);
			
		}
		elseif($qrType == 'distgoods') {
			$text = Url::toRoute(['goods/index', 'id' => $params['id'], 'invite' => DistributeModel::getInviteCode($params)], true);
			
		}
		elseif($qrType == 'distapply') {
			$text = Url::toRoute(['distribute/apply', 'invite' => DistributeModel::getInviteCode($params)], true);
		}
		
		// 二维码都是使用移动设备访问，所以修改访问地址为移动端
		$text = str_replace(Basewind::siteUrl(), Basewind::mobileUrl(false), $text);
		$outfile = Yii::getAlias('@frontend') . '/web/data/files/mall/qrcode/goods/';
		if(!is_dir($outfile)) {
			FileHelper::createDirectory($outfile);
		}
		
		$outfile .= md5($text) . '.PNG';
		if(!file_exists($outfile)) {
			$qrCode = (new \Da\QrCode\QrCode($text))->setSize($size)->setMargin($margin);
			$qrCode->writeFile($outfile);
		}
		
		$outfileUrl = str_replace(Yii::getAlias('@frontend'). '/web', Basewind::siteUrl(), $outfile);
		if($scalar == true) {
			return $outfileUrl;
		}
        return array($outfileUrl, $outfile);
	}
	
	/**
	 * 生成宣传海报
	 * @param array  参数,包括图片和文字
	 * @param string  $filename 生成海报文件名,不传此参数则不生成文件,直接输出图片
	 * @param boolean $overlay 是否覆盖
	 * @return [type] [description]
	 */
	public static function createPoster($config = array(), $filename = "", $overlay = false){
	  if (file_exists($filename) && !$overlay) return $filename;
	  
	  //如果要看报什么错，可以先注释调这个header
	  if(empty($filename)) header("content-type: image/png");
	  $imageDefault = array(
		'left'=>0,
		'top'=>0,
		'right'=>0,
		'bottom'=>0,
		'width'=>100,
		'height'=>100,
		'opacity'=>100
	  );
	  $textDefault = array(
		'text'=>'',
		'top'=>0,
		'fontPath'=> Yii::getAlias('@common') . '/font/yahei.ttf',     //字体文件
		'fontSize'=>32,       //字号
		'fontColor'=>'0,0,0', //字体颜色
		'angle'=>0,
	  );
	  $background = $config['background'];//海报最底层得背景
	  //背景方法
	  $backgroundInfo = getimagesize($background);
	  $backgroundFun = 'imagecreatefrom'.image_type_to_extension($backgroundInfo[2], false);
	  $background = $backgroundFun($background);
	  $backgroundWidth = imagesx($background);  //背景宽度
	  $backgroundHeight = imagesy($background);  //背景高度
	  $imageRes = imageCreatetruecolor($backgroundWidth,$backgroundHeight);
	  $color = imagecolorallocate($imageRes, 0, 0, 0);
	  imagefill($imageRes, 0, 0, $color);
	  // imageColorTransparent($imageRes, $color);  //颜色透明
	  imagecopyresampled($imageRes,$background,0,0,0,0,imagesx($background),imagesy($background),imagesx($background),imagesy($background));
	  
	  //处理了图片
	  if(!empty($config['image'])){
		foreach ($config['image'] as $key => $val) {
		  $val = array_merge($imageDefault,$val);
		  $val['url'] = str_replace("https://", "http://", $val['url']); // for https 临时解决方案
		  $info = getimagesize($val['url']);
		  $function = 'imagecreatefrom'.image_type_to_extension($info[2], false);
		  if($val['stream']){   //如果传的是字符串图像流
			$info = getimagesizefromstring($val['url']);
			$function = 'imagecreatefromstring';
		  }
		  $res = $function($val['url']);
		  $resWidth = $info[0];
		  $resHeight = $info[1];
		  //建立画板 ，缩放图片至指定尺寸
		  $canvas=imagecreatetruecolor($val['width'], $val['height']);
		  imagefill($canvas, 0, 0, $color);
		  //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
		  imagecopyresampled($canvas, $res, 0, 0, 0, 0, $val['width'], $val['height'],$resWidth,$resHeight);
		  $val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']) - $val['width']:$val['left'];
		  $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']) - $val['height']:$val['top'];
		  //放置图像
		  imagecopymerge($imageRes,$canvas, $val['left'],$val['top'],$val['right'],$val['bottom'],$val['width'],$val['height'],$val['opacity']);//左，上，右，下，宽度，高度，透明度
		}
	  }
	  //处理文字
	  if(!empty($config['text'])){
		foreach ($config['text'] as $key => $val) {
		  $val = array_merge($textDefault,$val);
		  list($R,$G,$B) = explode(',', $val['fontColor']);
		  $fontColor = imagecolorallocate($imageRes, $R, $G, $B);
		  
		  if(isset($val['left'])) {
		  	$val['left'] = $val['left'] < 0 ? $backgroundWidth - abs($val['left']) : $val['left'];
		  } else {
			$fontBox = imagettfbbox($val['fontSize'], $val['angle'], $val['fontPath'], $val['text']);//文字水平居中设置
			$val['left'] = ceil(($backgroundWidth - $fontBox[2]) / 2);
		  }

		  $val['top'] = $val['top'] < 0 ? $backgroundHeight - abs($val['top']) : $val['top'];	
		  imagettftext($imageRes, $val['fontSize'], $val['angle'], $val['left'], $val['top'], $fontColor, $val['fontPath'], $val['text'] );
		}
	  }

	  //生成图片
	  if(!empty($filename)){
		$res = imagejpeg($imageRes,$filename,90); //保存到本地
		imagedestroy($imageRes);
		if(!$res) return false;
		return $filename;
	  }else{
		imagejpeg ($imageRes);     //在浏览器上显示
		imagedestroy($imageRes);
	  }
	}

	/**
	 * 导出xlsx文件
	 */
	public static function export($config = [])
	{
		$writer = new \XLSXWriter();
		header('Content-disposition: attachment; filename="'.$writer->sanitize_filename($config['fileName']).'.xlsx"');
		header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		
		$writer->setTempDir(Yii::getAlias('@frontend/runtime'));
		$writer->writeSheet($config['models']);
	
		$writer->writeToStdOut();
		exit(0);
	}
	
	public static function writeLog($key = '', $word = '') 
	{
		//$word = json_encode($word); // for AJAX debug
		$word = var_export($word, true);

		$path = dirname(Yii::getAlias('@frontend')) . "/.logs/" . date('Ymd', time());
		@mkdir($path, 0777, true);
			
		$fp = fopen($path ."/log.txt","a");
		flock($fp, LOCK_EX) ;
		fwrite($fp,$key." At:".date("Y-m-d H:i:s",time())."[IP:".Yii::$app->request->userIP."]\n".$word."\n");
		flock($fp, LOCK_UN);
		fclose($fp);
	}
}