<?php
/**
* @desc Aliexpress Freight Template (运费模板)
* @author	tony
* @since	2015-09-14
*/

class AliexpressFreightTemplate extends AliexpressModel{

	const EVENT_NAME = 'getfreighttemplate';
	const IS_DEFAULT = 1;
	const IS_NOT_DEFAULT = 0;

	/** @var int 账号ID*/
	public $_accountId = null;

	/** @var int 模板ID*/
	public $_templateId = null;

	/** @var string 异常信息*/
	public $exception = null;

	/**
	 * @desc 设置账号ID
	 */
	public function setAccountID($accountID){
		$this->_accountId = $accountID;
	}

	/**
	 * @desc 设置模板ID
	 */
	public function setTemplateID($templateID){
		$this->_templateId = $templateID;
	}

	/**
	 * @desc 获取异常信息
	 * @return string
	 */
	public function getExceptionMessage(){
		return $this->exception;
	}
	
	/**
	 * @desc 设置异常信息
	 * @param string $message
	 */
	public function setExceptionMessage($message){
		$this->exception = $message;
	}

	public static function model($className = __CLASS__) {
		return parent::model($className);
	}

	/**
	 * @desc 数据库表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_aliexpress_freight_template';
	}

	/**
	 * @desc 更新分类
	 */
	public function updateFreightTemplate(){
		$request = new GetFreightTemplateRequest();
		$accountID = $this->_accountId;
		$templateID = $this->_templateId;
		
		$request->setTemplateID($templateID);
		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
		if( isset($response->success) ){
			$promiseTemplates = $response->aeopFreightTemplateDTOList;
			foreach ($promiseTemplates as $promiseTemplate){
			$flag = $this->saveFreightTemplate($promiseTemplate);
			
			}
			if ( $flag ){
				return true;
			}else{
				$this->setExceptionMessage($request->getErrorMsg());
				return false;
			}
		}
	}

	/**
	 * @desc 保存模板信息
	 */
	public function saveFreightTemplate($promiseTemplate){
		if ( isset($promiseTemplate) ){
			$params = array(
					'account_id' => $this->_accountId,
					'template_id' => $promiseTemplate->templateId,
					'is_default' => $promiseTemplate->default?self::IS_DEFAULT:self::IS_NOT_DEFAULT,
					'template_name' => $promiseTemplate->templateName,
					'timestamp'=>date("Y-m-d H:i:s")
			);
			$flag = $this->saveRecord($params);
		}
		return $flag;
	}

	/**
	 * @desc 更新分类信息
	 */
	public function saveRecord($params){
		if( isset($params['template_id']) ){
			return $this->dbConnection->createCommand()->replace(self::tableName(), $params);
		}else{
			return $this->dbConnection->createCommand()->insert(self::tableName(), $params);
		}
	}
	
	/**
	 * @desc 获取模板和模板名的键值对
	 * @param unknown $accountID
	 * @return Ambigous <type, multitype:unknown >
	 */
	public function getTemplateIDNamePairs($accountID) {
		return $this->queryPairs("template_id, name", "account_id = :account_id", array(':id' => $accountID));
	}


	/**
	 * @desc 通过账号获取模板ID
	 * @param int $accountID
	 */
	public function getTemplateIdByAccountId($accountID){
		$cmd = $this->dbConnection->createCommand();
		$cmd->select('template_id')
			->from(self::tableName())
			->where('account_id = :accountId AND is_default = 1', array(':accountId'=>$accountID))
			->limit(1);
		return $cmd->queryScalar();
	}


	/**
	 * @desc 通过账号获取模板信息
	 * @param int $accountID
	 */
	public function getTemplateIdInfoByAccountId($accountID){
		$cmd = $this->dbConnection->createCommand();
		$cmd->select('template_id,template_name')
			->from(self::tableName())
			->where('account_id = :accountId', array(':accountId'=>$accountID));
		return $cmd->queryAll();
	}


	/**
	 * 产品管理，复制刊登取出固定模板
	 * @param float $floatPrice  产品价格
	 * @return int
	 */
	public function getAppointTemplateId($sku, $accountID, $floatPrice){
		//获取默认的运费模板ID
		$freightTemplateId = null;

		$freightTemplateInfo = $this->getTemplateIdInfoByAccountId($accountID);
		$freightTemplateArr = array();
		if($freightTemplateInfo){
			foreach ($freightTemplateInfo as $ftkey => $ftvalue) {
				$replaceStr = str_replace(' ', '', $ftvalue['template_name']);
				$freightTemplateArr[$replaceStr] = $ftvalue['template_id'];
			}
		}else{
			return $this->ReturnMessage(false,'运费模板不能为空');
		}

		//查询是否设置的产品属性
		$attributeValueInfo = ProductSelectAttribute::model()->getAttIdsBySku($sku,ProductAttribute::PRODUCT_FEATURES_CODE);

		//根据价格和sku产品属性判断选择什么模板
		if($floatPrice <= 5){
			//sku有特殊属性
			if($attributeValueInfo){
				$freightTemplateId = $freightTemplateArr['TemplateForSpecialPropertyWithAmountBelow5USD'];
			}else{
				$freightTemplateId = $freightTemplateArr['TemplateForNOSpecialPropertyWithAmountBelow5USD'];
			}

		}else{
			$freightTemplateId = isset($freightTemplateArr['ChinaPostAirMailFreeShippingAbove5USD'])?$freightTemplateArr['ChinaPostAirMailFreeShippingAbove5USD']:$freightTemplateId;
		}

		//取默认模板
		if(!$freightTemplateId){
			$freightTemplateId = $this->getTemplateIdByAccountId($accountID);
		}

		return $freightTemplateId;
	}


	/**
	 * 返回的消息数组
	 * @param bool   $booleans  布尔值
	 * @param string $message   提示的消息
	 * @return array
	 */
	public function ReturnMessage($booleans,$message){
		return array($booleans,$message);
		exit;
	}
}