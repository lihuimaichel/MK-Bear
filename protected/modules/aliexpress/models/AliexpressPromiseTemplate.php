<?php
/**
* @desc Aliexpress Promise Template (服务模板)
* @author	tony
* @since	2015-09-14
*/

class AliexpressPromiseTemplate extends AliexpressModel{

	const EVENT_NAME = 'getpromisetemplate';
	
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
		return 'ueb_aliexpress_promise_template';
	}

	/**
	 * @desc 更新分类
	 */
	public function updatePromiseTemplate(){
		$request = new GetPromiseTemplateRequest();
		$accountID = $this->_accountId;
		$templateID = $this->_templateId;
		
		$request->setTemplateID($templateID);
		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();

		if( isset($response->templateList['0']->name) ){
			$promiseTemplates = $response->templateList;
			foreach ($promiseTemplates as $promiseTemplate){
			$flag = $this->savePromiseTemplate($promiseTemplate);
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
	public function savePromiseTemplate($promiseTemplate){
		if ( isset($promiseTemplate) ){
			$params = array(
					'account_id' => $this->_accountId,
					'template_id' => $promiseTemplate->id,
					'name' => $promiseTemplate->name,
					'timestamp'=>date("Y-m-d H:i:s")
			);
			$flag = $this->saveRecord($params);
		}
		return $flag;
	}
	
	/**
	 * @desc 更新信息
	 */
	public function saveRecord($params){
        if( isset($params['id']) ){
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
			->where('account_id = :accountId AND template_id = 0', array(':accountId'=>$accountID))
			->limit(1);
		return $cmd->queryScalar();
	}


	/**
	 * @desc 通过账号获取模板信息
	 * @param int $accountID
	 */
	public function getTemplateIdInfoByAccountId($accountID){
		$cmd = $this->dbConnection->createCommand();
		$cmd->select('template_id,name')
			->from(self::tableName())
			->where('account_id = :accountId', array(':accountId'=>$accountID));
		return $cmd->queryAll();
	}
}