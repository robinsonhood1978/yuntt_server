<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @Id UserPrivModel.php 2018.7.27 $
 * @author mosir
 */

class UserPrivModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_priv}}';
    }
	
	// 关联表
	public function getUser()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'userid']);
	}
	
	/* 判断是否为系统管理员 */
	public static function isAdmin($id = 0)
	{
		if(is_numeric($id)) $id = array($id);
		elseif(!is_array($id)) $id = explode(',', $id);
		
		$query = self::find()->where(['and', ['in', 'userid', $id], ['store_id' => 0, 'privs' => 'all']])->one();
		return $query ? true : false;
	}
	
	/* 判断是否为管理员 */
	public static function isManager($id = 0, $store_id = 0)
	{
		if(is_numeric($id)) $id = array($id);
		elseif(!is_array($id)) $id = explode(',', $id);
		
		$query = self::find()->where(['and', ['in', 'userid', $id], ['store_id' => $store_id]])->one();
		
		return $query ? true : false;		
	}
	
	/* 判断是否有权限访问指定页面 */
	public static function accessPage($controller, $action, $userid = 0, $store_id = 0)
	{
		// 不需要做权限判断的页面
		$noPriv = ['default|index', 'default|welcome', 'default|clearCache', 'default|getipinfo', 'order|trend', 'user|trend', 'store|trend'];
		if(in_array($controller.'|'.$action, $noPriv) || $action == 'jslang') {
			return true;
		}

		$query = self::find()->where(['userid' => $userid, 'store_id' => $store_id])->one();
		if(!$query || empty($query->privs)) {
			return false;
		}
		if($query->privs == 'all') {
			return true;
		}
		else 
		{
			$privs = explode(',', $query->privs);
	
			// 合并依赖权限
			$privs = array_unique(self::getDependPrivs($privs, $userid, $store_id));
			
			// 例外处理
			if($controller == 'plugin') {
				$controller = $controller.'|'.Yii::$app->request->get('instance');
			}
			
			if(in_array($controller.'|all', $privs) || in_array($controller.'|'.$action, $privs)) {
				return true;
			}
		}
		return false;
	}
	
	/* 取得权限控制项 */
	public static function getPrivs($userid = 0, $store_id = 0)
	{
		if($userid && ($admin = UserPrivModel::find()->select('privs')->where(['userid' => $userid, 'store_id' => $store_id])->one())) {
			$checked = explode(',', $admin->privs);
		}
		
		// 仅后台，如有需要前台权限，可在此拓展
		$list = \backend\library\Menu::getMenus();
		unset($list['dashboard']);
		
		$privs = array();
		foreach($list as $key => $val)
		{
			foreach($val['children'] as $k => $v) {
				$selected = ($checked && in_array($v['priv']['key'], $checked)) ? true : false;
				if($v['priv']) $privs[$key][] = array_merge(['label' => $v['text'], 'selected' => $selected], $v['priv']);
			}
		}
		return $privs;
	}
	
	/* 获取依赖的权限 */
	private static function getDependPrivs($privs, $userid = 0, $store_id = 0)
	{
		$list = self::getPrivs($userid, $store_id);
		foreach($list as $key => $val) {
			foreach($val as $k => $v) {
				if(in_array($v['key'], $privs) && $v['depends']) {
					foreach(explode(',', $v['depends']) as $v1) {
						$privs[] = $v1;
					}
				}
			}
		}
		
		return $privs;
	}
}
