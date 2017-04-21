<?php
/**
 * @desc Aliexpress 属性值子属性model
 * @author wx
 * @since 2015-09-11
 */
class AliexpressAttributeValueMap extends AliexpressModel{
    
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
        return 'ueb_aliexpress_attribute_value_map';
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
     * @desc 根据条件  查询属性值子属性映射
     * @param integer $attributeId,integer $attributeValueId,integer $attributeValueChildrenId
     */
    public function getAttrValMapByCondition( $attributeId = '',$attributeValueId = '',$attributeValueChildrenId = '' ){
    	if(!$attributeId || !$attributeValueId || !$attributeValueChildrenId) return false;
    	$ret = $this->dbConnection->createCommand()
    			->select('*')
    			->from(self::tableName())
    			->where('attribute_id = "'.$attributeId.'"')
    			->andWhere('attribute_value_id = "'.$attributeValueId.'"')
    			->andWhere('attribute_value_children_id = "'.$attributeValueChildrenId.'"')
    			->queryRow();
    	return $ret;
    }
    
}