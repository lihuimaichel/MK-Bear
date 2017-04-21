<?php
/**
 * @desc lazada 分类属性MODEL
 * @author zhangF
 *
 */
class LazadaCategoryAttributes extends LazadaModel {
	
	/** @var string 异常信息 **/ 
	protected $_exception = null;
	
	/** @var integer 分类ID **/
	protected $_categoryID = null;
	
	/** @var integer 账号ID **/
	protected $_accountID = null;
	
	/** @var integer 站点ID **/
	protected $_siteID = null;

	/** @var integer lazada账号表ID字段 **/
    protected $_apiAccountID = null;
	
	const EVENT_NAME = 'getcategoryattributes';
	const ATTRIBUTE_TYPE = 'category_attribute';
	
	/**
	 * @desc 设置表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_lazada_category_attribute';
	}
	
	/**
	 * @desc 获取模型
	 * @param system $className
	 * @return Ambigous <CActiveRecord, unknown, multitype:>
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @desc 设置异常信息
	 * @param string $message
	 */
	public function setExceptionMessage($message) {
		$this->_exception = $message;
	}

	/**
	 * @desc 获得异常信息
	 * @return string
	 */
	public function getExceptionMessage() {
		return $this->_exception;
	}

	/**
     * @desc 设置lazada账号表ID字段
     * @param unknown $apiAccountID
     */
    public function setApiAccount($apiAccountID) {
        $this->_apiAccountID = $apiAccountID;
    }
	
	/**
	 * @desc 获取分类属性
	 * @return boolean
	 */
	public function getCategoryAttributes() {
		try {
			$GetCategoryAttributes = new GetCategoryAttributesRequest();
			$GetCategoryAttributes->setPrimaryCategory($this->_categoryID);
			$response = $GetCategoryAttributes->setSiteID($this->_siteID)->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();

			//MHelper::writefilelog('lazada/getCategoryAttributes/'.date("Ymd").'/'. date('H') .'/response.txt', implode('--',array($this->_categoryID,$this->_accountID,$this->_siteID)).' ####'. date("Y-m-d H:i:s").' @@@ '.print_r($response,true)."\r\n");//add log for test

			if (!$GetCategoryAttributes->getIfSuccess()) {
				$this->setExceptionMessage($GetCategoryAttributes->getErrorMsg());
				return false;
			}
			$flag = $this->saveCategoryAttributes($response->Body->asXml());
			if (!$flag) {
				$this->setExceptionMessage(Yii::t('lazada_category', 'Save Category Attributes Failure'));
				return false;
			}
			return true;
		} catch (Exception $e) {
			$this->setExceptionMessage(Yii::t('ladaza_category', $e->getMessage()));
			return false;
		}
	}

	/**
	 * @desc 获取分类属性
	 * @return boolean
	 */
	public function getCategoryAttributesNew() {
		try {
			$GetCategoryAttributes = new GetCategoryAttributesRequestNew();
			$GetCategoryAttributes->setPrimaryCategory($this->_categoryID);
			$response = $GetCategoryAttributes->setApiAccount($this->_apiAccountID)->setRequest()->sendRequest()->getResponse();
			
			if (!$GetCategoryAttributes->getIfSuccess()) {
				$this->setExceptionMessage($GetCategoryAttributes->getErrorMsg());
				return false;
			}
			$flag = $this->saveCategoryAttributes(json_encode($response->Body));
			if (!$flag) {
				$this->setExceptionMessage(Yii::t('lazada_category', 'Save Category Attributes Failure'));
				return false;
			}
			return true;
		} catch (Exception $e) {
			$this->setExceptionMessage(Yii::t('ladaza_category', $e->getMessage()));
			return false;
		}
	}
	
	/**
	 * @desc 保存分类属性
	 * @param string $attributes (save category attributes with xml documents)
	 * @return boolean
	 */
	public function saveCategoryAttributes($attributes) {
		//检查当前分类的属性是否有添加，没有则添加，有则更新
/* 		$attributeModel = $this->find("category_id = :category_id and `type` = :type", array(':category_id' => $this->_categoryID, ':type' => self::ATTRIBUTE_TYPE));
		if (!empty($attributeModel)) {
			$attributeModel->setAttribute('value', $attributes);
			if (!$attributeModel->save(false))
				return false;
		} else { */
		//删除之前的属性
		$this->dbConnection->createCommand()->delete(self::tableName(), "category_id = :category_id and `type` = :type", array(':category_id' => $this->_categoryID, ':type' => self::ATTRIBUTE_TYPE));
			$data = array(
				'type' => self::ATTRIBUTE_TYPE,
				'category_id' => $this->_categoryID,
				'value' => $attributes,
			);
			if (!$this->getDbConnection()->createCommand()->insert(self::tableName(), $data))
				return false;
		//}
		return true;
	}
	
	/**
	 * @desc  设置分类ID
	 * @param integer $ID
	 */
	public function setCategoryID($ID) {
		$this->_categoryID = $ID;
	}
	
	/**
	 * @desc 设置账号ID
	 * @param integer $accountID
	 */
	public function setAccountID($accountID) {
		$this->_accountID = $accountID;
	}
	
	/**
	 * @desc 设置站点ID
	 * @param unknown $siteID
	 */
	public function setSiteID($siteID) {
		$this->_siteID = $siteID;
	}
}