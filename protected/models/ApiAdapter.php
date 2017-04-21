<?php
/**
 * @package Ueb.modules.api.models
 * @author Gordon
 * @author 2015-07-29
 */
Yii::import('application.components.*');
Yii::import('application.modules.systems.components.*');
Yii::import('application.modules.systems.models.*');
Yii::import('application.modules.users.components.*');
Yii::import('application.modules.users.models.*');
Yii::import('application.modules.products.components.*');
Yii::import('application.modules.products.models.*');
Yii::import('application.modules.warehouses.components.*');
Yii::import('application.modules.warehouses.models.*');
Yii::import('application.modules.common.components.*');
Yii::import('application.modules.common.models.*');
Yii::import('application.modules.ebay.components.*');
Yii::import('application.modules.ebay.models.*');
Yii::import('application.modules.aliexpress.components.*');
Yii::import('application.modules.aliexpress.models.*');
Yii::import('application.modules.amazon.components.*');
Yii::import('application.modules.amazon.models.*');
Yii::import('application.modules.wish.components.*');
Yii::import('application.modules.wish.models.*');
Yii::import('application.modules.lazada.components.*');
Yii::import('application.modules.lazada.models.*');
// Yii::import('application.modules.joom.components.*');
// Yii::import('application.modules.joom.models.*');
// Yii::import('application.modules.jd.components.*');
// Yii::import('application.modules.jd.models.*');
class ApiAdapter extends UebModel { 		

	public function getDbKey() {
		return 'db_ebay';
	}
	
	public function tableName() {
		return 'ueb_ebay_product';
	}

	/**
	 * [_call description]
	 * @param  [type] $method [description]
	 * @param  array  $data   [description]
	 * @return array         ['errCode'=>200,'errMsg'=>'ok','data'=>[]]
	 */
	public function _call($method, $data=array()){
		$ps 		= explode(':', $method);
		$moduleName = $ps[0]; $modelName = $ps[1]; $functionName = $ps[2];
		$paramArr 	= MHelper::objectToArray($data->data);	
		$model 		= UebModel::model($modelName);	
		$result 	= call_user_func_array(array($model, $functionName),$paramArr);
		return array(
			'errorCode' => isset($result['errorCode']) ? $result['errorCode'] : '0',
			'errorMsg' 	=> isset($result['errorMsg']) ? $result['errorMsg'] : '',
			'data'		=> isset($result['data']) ? $result['data'] : $result,
		);
	}

}