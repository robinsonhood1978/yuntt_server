<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\models;

use Yii;
use yii\base\Model; 

use common\models\UserModel;
use common\library\Basewind;
use common\library\Language;

/**
 * @Id UserPhoneForm.php 2018.3.28 $
 * @author mosir
 */
class UserPhoneForm extends Model
{
	public $password;
	public $phone_mob;
	
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
			['password', 'required', 'message' => Language::get('password_required')],
			['phone_mob', 'required', 'message' => Language::get('phone_mob_required')],
			[['password', 'phone_mob'], 'trim'],
			['password', 'validatePassword'],
			
			['phone_mob', 'isPhone'], // It can also be used regular
            ['phone_mob', 'string', 'min' => 11, 'max' => 11],
            ['phone_mob', 'checkUniqueExcludeSelf'],
        ];
    } 
	
	/**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = UserModel::find()->where(['userid' => Yii::$app->user->id])->one();
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, Language::get('orig_pass_not_right'));
            }
        }
    }
	
	/**
	 * Check the Phone
	 * exclude oneself
	 */
	public function checkUniqueExcludeSelf($attribute, $params) 
	{
		if (!$this->hasErrors()) {
            $user = UserModel::find()->where(['phone_mob' => $this->phone_mob])
				->andWhere(['<>', 'userid', Yii::$app->user->id])->one();
            if ($user) {
                $this->addError($attribute, Language::get('phone_mob_existed'));
            }
        }
	}
	
	public function isPhone($attribute, $params)
	{
		if (!$this->hasErrors()) {
            if (Basewind::isPhone($this->phone_mob) == false) {
                $this->addError($attribute, Language::get('phone_mob_invalid'));
            }
        }
	}
	
	/**
     * Resets phone.
     *
     * @return bool if phone was reset.
     */
    public function resetPhone()
    {
        $user = UserModel::find()->where(['userid' => Yii::$app->user->id])->one();
        $user->phone_mob = $this->phone_mob;

        return $user->save(false);
    }
}
