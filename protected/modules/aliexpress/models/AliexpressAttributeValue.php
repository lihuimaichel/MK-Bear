<?php
/**
 * @desc Aliexpress 属性值model
 * @author wx
 * @since 2015-09-11
 */
class AliexpressAttributeValue extends AliexpressModel{
    
    /** @var string 异常信息*/
    protected $exception = null;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 设置账号ID
     */
    public function setAccountID($accountID){
    	$this->_accountID = $accountID;
    }
    
    /**
     * @return array validation rules for model attributes.
     */
    public function rules(){}
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_attribute_value';
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
    
    /**
     * @desc 根据attribute_value_id 查询属性值
     */
    public function getAttrValByAttrValId( $attributeValueId = '' ){
    	if(!$attributeValueId) return false;
    	$ret = $this->dbConnection->createCommand()
    			->select('*')
    			->from(self::tableName())
    			->where('attribute_value_id = "'.$attributeValueId.'"')
    			->queryRow();
    	
    	return $ret;
    }
    
    /**
     * @desc 根据属性ID获取属性值列表
     * @param unknown $attributeID
     * @return Ambigous <multitype:, mixed>
     */
    public function getAttributeValueList($attributeID) {
    	return $this->getDbConnection()->createCommand()
    		->from(self::tableName() . " t")
    		->join("ueb_aliexpress_attribute_map t1", "t.attribute_value_id = t1.attribute_value_id")
    		->where("t1.attribute_id = :attribute_id", array(':attribute_id' => $attributeID))
    		->queryAll();
    }
    
    /**
     * @desc 获取指定属性值ID的信息
     * @param unknown $ids
     * @return Ambigous <multitype:, mixed>
     */
    public function getAttributeValueByIds($ids) {
    	$valueList = array();
    	if (is_array($ids))
    		$ids = implode(',', $ids);
    	$ids = trim($ids, ",");
    	$res = $this->getDbConnection()->createCommand()
    	->from(self::tableName())
    	->where("attribute_value_id in (" . $ids . ")")
    	->queryAll();
    	if (!empty($res)) {
    		foreach ($res as $row) {
    			$valueList[$row['attribute_value_id']] = $row;
    		}
    	}
    	return $valueList;
    }
}