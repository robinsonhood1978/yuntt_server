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

use common\models\UserModel;

/**
 * @Id DistributeMerchantModel.php 2018.10.22 $
 * @author mosir
 */

class DistributeMerchantModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%distribute_merchant}}';
    }
	
	// 关联表
	public function getUser()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'userid']);
	}

	/**
	 * 获取邀请人的下三级用户
	 * 确保用户是存在的，且还得确保每个下级都是分销商
	 */
	public static function getChilds($inviteUid = 0)
	{
		$result = array();

		// 一级
		$firsts = parent::find()->alias('dm')->select('dm.userid')->joinWith('user', false, 'INNER JOIN')->where(['dm.parent_id' => $inviteUid])->column();
		if(!$firsts) {
			return $result;
		}
		$result = $firsts;

		// 二级
		foreach($firsts as $key => $value) {
			$seconds = parent::find()->alias('dm')->select('dm.userid')->joinWith('user', false, 'INNER JOIN')->where(['dm.parent_id' => $value])->column();
			if($seconds) {
				$result = array_merge($result, $seconds);

				// 三级
				foreach($seconds as $key => $value) {
					$thirds = parent::find()->alias('dm')->select('dm.userid')->joinWith('user', false, 'INNER JOIN')->where(['dm.parent_id' => $value])->column();
					if($thirds) {
						$result = array_merge($result, $thirds);
					}
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * 获取邀请人的上三级用户
	 * 确保用户是存在的，且还得确保每个上级都是分销商
	 */
	public static function getParents($inviteUid = 0)
	{
		$result = array();
		
		// 一级
		$query = parent::find()->alias('dm')->select('dm.userid,dm.parent_id')->joinWith('user', false, 'INNER JOIN')->where(['dm.userid' => $inviteUid])->one();
		if(!$query || !$query->userid) {
			return $result;
		}
		$result[] = $query->userid;
		
		// 二级
		if(!$query->parent_id || !UserModel::find()->select('userid')->where(['userid' => $query->parent_id])->exists()) {
			return $result;
		}
		$result[] = $query->parent_id;
		
		// 三级
		$query = parent::find()->select('parent_id')->where(['userid' => $query->parent_id])->one();
		if(!$query || !$query->parent_id || !parent::find()->where(['userid' => $query->parent_id])->exists()) {
			return $result;
		}
		$result[] = $query->parent_id;

		return $result;
	}
	
	/* 获取三级团队成员总数 */
	public static function getTeams($userid = 0)
	{
		$result = 0;
		if(($query = parent::find()->select('userid,parent_id')->where(['parent_id' => $userid]))) 
		{
			$result += $query->count();
			foreach($query->all() as $item) 
			{
				if(($query1 = parent::find()->select('userid,parent_id')->where(['parent_id' => $item->userid]))) {
					
					$result += $query1->count();
					
					foreach($query1->all() as $val) {
						if(($query2 = parent::find()->select('userid,parent_id')->where(['parent_id' => $val->userid]))) {
							$result += $query2->count();
						}
					}
				}
			}
		}
		return $result;
	}
}
