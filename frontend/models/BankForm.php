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

use common\models\BankModel;

use common\library\Language;

/**
 * @Id BankForm.php 2018.4.17 $
 * @author mosir
 */
class BankForm extends Model
{
	public $bank_name;
	public $short_name;
	public $account_name;
	public $open_bank;
	public $type;
	public $num;
	public $captcha;
	
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
			['short_name', 'required', 'message' => Language::get('short_name_empty')],
			['short_name', 'checkShortName'],
			['num', 'required', 'message' => Language::get('num_empty')],
			['account_name', 'required', 'message' => Language::get('account_name_error')],
			['account_name', 'string', 'length' => [2, 120]],
			['type', 'in', 'range' => ['debit','credit'], 'message' => Language::get('type_error')],
			['open_bank', 'string'],
			['captcha', 'captcha', 'captchaAction' => 'default/captcha', 'message' => Language::get('captcha_failed')],
        ];
    }
	
	/**
     * [scenarios : different validation conditions under different business logic.]
     * @return [type] [description]
     */
    public function scenarios()
    {
        return [
            'default' => ['short_name', 'account_name', 'open_bank', 'type', 'num', 'captcha'],
			//'add' => [], 
			//'update' = [],
        ];
    }
	
	public function checkShortName($attribute, $params)
	{
		if (!$this->hasErrors()) {
			
			$bankList = self::getBankList();
			if(!$bankList) $bankList = array();
			
			$check = false;
			foreach($bankList as $key => $bank) {
				if(strtoupper($key) == strtoupper($this->short_name))  {
					$check = true;
					break;
				}
			}
            if ($check == false) {
                $this->addError($attribute, Language::get('short_name_error'));
            }
        }
	}

    public function save($validate = true)
    {
        if ($validate && !$this->validate()) {
            return false;
        }
		
		$bankList = self::getBankList();
		foreach($bankList as $key => $bank) {
			if(strtoupper($key) == strtoupper($this->short_name))  {
				$this->bank_name = $bank;
				break;
			}
		}
		// add or edit
		$bid = intval(Yii::$app->request->get('bid'));
		if(!$bid || !($bank = BankModel::find()->where(['bid' => $bid, 'userid' => Yii::$app->user->id])->one())) {
			$bank = new BankModel();
		}
		
		$bank->userid = Yii::$app->user->id;
		$bank->bank_name = $this->bank_name;
		$bank->short_name = strtoupper($this->short_name);
		$bank->account_name = $this->account_name;
		$bank->open_bank = $this->open_bank;
		$bank->type = $this->type;
		$bank->num = $this->num;
		
		return $bank->save(false) ? $bank : null;
	}
	
	public static function getBankList()
	{
		return array (
			'ICBC' 		=> '??????????????????',
			'CCB' 		=> '??????????????????',
			'ABC' 		=> '??????????????????',
			'POSTGC' 	=> '????????????????????????',
			'COMM' 		=> '????????????',
			'CMB' 		=> '????????????',
			'BOC' 		=> '????????????',
			'CEBBANK' 	=> '??????????????????',
			'CITIC' 	=> '????????????',
			'SPABANK' 	=> '??????????????????',
			'SPDB' 		=> '????????????????????????',
			'CMBC' 		=> '??????????????????',
			'CIB' 		=> '????????????',
			'GDB' 		=> '??????????????????',
			'SHRCB'  	=> '????????????????????????',
			'SHBANK' 	=> '????????????',
			'NBBANK' 	=> '????????????',
			'HZCB' 		=> '????????????',
			'BJBANK'  	=> '????????????',
			'BJRCB'	  	=> '????????????????????????',
			'FDB'	  	=> '????????????',
			'WZCB'   	=> '????????????',
			'CDCB'    	=> '????????????',
			'HXBANK'	=> '????????????',
		);
	}
}
