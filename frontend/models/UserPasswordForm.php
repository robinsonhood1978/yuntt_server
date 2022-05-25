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
 * @Id UserPasswordForm.php 2018.3.28 $
 * @author mosir
 * @desc Password reset request form
 */
class UserPasswordForm extends Model
{
	public $oldPassword;
	public $password;
	public $confirmPassword;
	
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
			[['oldPassword', 'password', 'confirmPassword'], 'required', 'message' => Language::get('passwod_empty')],
			[['oldPassword', 'password', 'confirmPassword'], 'trim'],
			['oldPassword', 'validatePassword'],
            ['password', 'string', 'min' => 6, 'max' => 20],
			['password', 'compare', 'compareAttribute' => 'confirmPassword', 'message' => Language::get('twice_pass_not_match')],
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
            if (!$user || !$user->validatePassword($this->oldPassword)) {
                $this->addError($attribute, Language::get('orig_pass_not_right'));
            }
        }
    }
	
	/**
     * Resets password.
     *
     * @return bool if password was reset.
     */
    public function resetPassword()
    {
        $user = UserModel::find()->where(['userid' => Yii::$app->user->id])->one();
        $user->setPassword($this->password);
        $user->removePasswordResetToken();

        return $user->save(false);
    }
}
