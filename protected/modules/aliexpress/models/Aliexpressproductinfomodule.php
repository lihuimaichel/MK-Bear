<?php
/**
 * @desc 产品信息模块模型
 * @author zhangf
 *
 */
class Aliexpressproductinfomodule extends AliexpressModel {
	
	
	/** @var integer 账号ID */
	protected $_accountID = '';
	
	/** @var string 错误信息 */
	protected $_errorMessage = '';
	
	const EVENT_NAME = 'get_productinfo_module';
	
	const MODULE_STATUS_TBD		 		 = 'tbd' ;  		   //审核不通过
	const MODULE_STATUS_AUDITING		 = 'auditing';	       //审核中
	const MODULE_STATUS_APPROVED		 = 'approved' ;	       //审核通过
	
	const MODULE_TYPE_CUSTOM			 = 'custom';		   //自定义模块
	const MODULE_TYPE_RELATION			 = 'relation';		   //关联模块
	
	
	/**
	 * (non-PHPdoc)
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_aliexpress_productinfo_module';
	}
	
	
	public static function model($className = __CLASS__) {
		return parent::model($className);		
	}
	
	public function setAccountID($accountID) {
		$this->_accountID = $accountID;
	}
	
	public function rules() {
		return array(
		);
	}
	
	/**
	 * @desc 获产品信息模块
	 * @param unknown $params
	 * @return boolean
	 */
	public function getProductInfoModules($params = array()) {
		try {
			$request = new FindAeProductDetailModuleListByQureyRequest();
			$moduleStatus = FindAeProductDetailModuleListByQureyRequest::MODULE_STATUS_APPROVED;
			if (isset($params['status']) && !empty($params['status']))
				$moduleStatus = $params['status'];
			$page = 1;
			if (isset($params['page']) && !empty($params['page']))
				$page = (int)$params['page'];
			$totalPage = $page;
			$request->setModuleStatus($moduleStatus);
			$hasFinished = false;
			while ($page <= $totalPage) {
				$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
				$page++;
				if ($request->getIfSuccess()) {
					$totalPage = (int)$response->totalPage;
					$flag = $this->saveProductInfoModules($response->aeopDetailModuleList);
					if (!$flag) {
						return false;
					}
				} else {
					$this->_errorMessage = $request->getErrorMsg();
					return false;
				}
			}
			return true;
		} catch (Exception $e) {
			$this->_errorMessage = $e->getMessage();
			return false;
		}
	}
	
	/**
	 * @desc 获取错误信息
	 * @return string
	 */
	public function getErrorMessage() {
		return $this->_errorMessage;
	}
	
	/**
	 * @desc 保存产品信息模块
	 * @param unknown $data
	 * @return boolean
	 */
	public function saveProductInfoModules($data) {
		try {
			$moduleIDs = array();
			foreach ($data as $row) {
				$moduleID = $row->id;
				$moduleIDs[] = $moduleID;
				//查找模块详情
				$displayContent = '';
				$moduleDetail = $this->getModuleDetails($moduleID);
				if (!$moduleDetail) continue;
				$displayContent = $moduleDetail->displayContent;				
				//检查产品信息模块是否存在
				$moduleInfo = $this->find("module_id = :module_id and account_id = :account_id", array(':module_id' => $moduleID, ':account_id' => $this->_accountID));
				if (!empty($moduleInfo))
					$id = $moduleInfo->id;
				else
					$id = null;
				$addData = array(
					'account_id' => $this->_accountID,
					'module_id' => $row->id,
					'name' => $row->name,
					'status' => $row->status,
					'type' => $row->type,
					'display_content' => $displayContent,
					'module_contents' => $row->moduleContents,
				);
				if (!empty($id))
					$this->dbConnection->createCommand()->update(self::tableName(), $addData, "id=" . $id);
				else
					$this->getDbConnection()->createCommand()->insert(self::tableName(), $addData);
			}
			if($moduleIDs){
				$this->getDbConnection()->createCommand()->delete(self::tableName(), "account_id='{$this->_accountID}' AND module_id NOT IN (".MHelper::simplode($moduleIDs).")");
			}
			return true;
		} catch (Exception $e) {
			$this->_errorMessage = $e->getMessage();
			return false;
		}
		return true;
	}
	
	/**
	 * @desc 根据模块ID查询模块详情
	 * @param unknown $moduleID
	 * @return boolean|mixed
	 */
	public function getModuleDetails($moduleID) {
		try {
			$request = new FindAeProductModuleByIdRequest();
			$request->setModuleID($moduleID);
			$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
			if (!$request->getIfSuccess()) {
				$this->_errorMessage = $request->getErrorMsg();
				return false;
			} else {
				return $response;
			}
		} catch (Exception $e) {
			$this->_errorMessage = $e->getMessage();
			return false;
		}
	}
	
	public function search() {
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder' => 'id',
		);
		$dataProvider = parent::search(get_class($this), $sort, '', $this->_setDbCriteria());
		return $dataProvider;
	}
	
	protected function _setDbCriteria() {
		if (isset($_REQUEST['pageNum']))
			$_POST['pageNum'] = $_REQUEST['pageNum'];
		$_REQUEST['numPerPage'] = 10;
		$criteria = new CDbCriteria();
		if (isset($_REQUEST['account_id']))
			$criteria->addCondition("account_id = " . (int)$_REQUEST['account_id']);
		if (isset($_REQUEST['module_name']) && $_REQUEST['module_name'] != '')
			$criteria->addSearchCondition("name", $_REQUEST['module_name']);
		return $criteria;
	}
	
	function filterOptions() {
		return array();
	}
	
	/**
	 * @desc 获取模块类型列表
	 * @param unknown $type
	 * @return Ambigous <string, Ambigous <string, string, unknown>>|multitype:string Ambigous <string, string, unknown>
	 */
	static function getModuleType($type) {
		$typeList = array(
			self::MODULE_TYPE_CUSTOM => Yii::t('aliexpress', 'Custom Module'),
			self::MODULE_TYPE_RELATION => Yii::t('aliexpress', 'Relation Module'),
		);
		if (!is_null($type) && array_key_exists($type, $typeList))
			return $typeList[$type];
		return $typeList;
	}
	
	/**
	 * @desc 获取摸吧的显示内容
	 * @param unknown $accountID
	 * @param unknown $moduleID
	 * @return Ambigous <mixed, string, unknown>
	 */
	function getModuleContents($accountID, $moduleID) {
		return $this->getDbConnection()->createCommand()
			->from(self::tableName())
			->select("display_content")
			->where("module_id = :module_id", array(':module_id' => $moduleID))
			->andWhere("account_id = :account_id", array(':account_id' => $accountID))
			->queryScalar();
	}
	
	/**
	 * @desc 获取模块名称
	 * @param unknown $accountID
	 * @param unknown $moduleID
	 * @return Ambigous <mixed, string, unknown>
	 */
	function getModuleNameAndType($accountID, $moduleID) {
		return $this->getDbConnection()->createCommand()
		->from(self::tableName())
		->select("name,type")
		->where("module_id = :module_id", array(':module_id' => $moduleID))
		->andWhere("account_id = :account_id", array(':account_id' => $accountID))
		->queryRow();
	}


	/**
	 * @desc 获取模块列表
	 * @param unknown $accountID
	 * @return Ambigous <mixed, string, unknown>
	 */
	function getModuleFieldsByAccountId($accountID) {
		return $this->getDbConnection()->createCommand()
		->from(self::tableName())
		->select("module_id,name")
		->where("account_id = :account_id", array(':account_id' => $accountID))
		->queryAll();
	}
}