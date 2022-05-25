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
use common\library\Language;

/**
 * @Id UserEmailForm.php 2018.3.25 $
 * @author mosir
 * @desc Email reset request form
 */
class UserEmailForm extends Model
{
	public $password;
	public $email;
	
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
			['password', 'required', 'message' => Language::get('password_required')],
			[['password', 'email'], 'trim'],
			['password', 'validatePassword'],
			
			['email', 'email'],
            ['email', 'string', 'max' => 50],
            ['email', 'checkUniqueExcludeSelf'],
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
	 * Check the Email
	 * exclude oneself
	 */
	public function checkUniqueExcludeSelf($attribute, $params) 
	{
		if (!$this->hasErrors()) {
            $user = UserModel::find()->where(['email' => $this->email])
				->andWhere(['<>', 'userid', Yii::$app->user->id])->one();
            if ($user) {
                $this->addError($attribute, Language::get('email_exists'));
            }
        }
	}
	
	/**
     * Resets email.
     *
     * @return bool if email was reset.
     */
    public function resetEmail()
    {
        $user = UserModel::find()->where(['userid' => Yii::$app->user->id])->one();
        $user->email = $this->email;

        return $user->save(false);
    }
}
