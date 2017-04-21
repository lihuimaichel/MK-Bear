<?php
/**
 * @desc Ebay刊登产品运费模板model
 * @author lihy
 * @since 2016-03-28
 */
class EbayProductShippingTemplate extends EbayModel{
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_shipping_template';
    }

    public function getShippingTemplateInfoByid($id){
    	return $this->getDbConnection()->createCommand()->from($this->tableName())->where("id={$id}")->queryRow();
    }
    
    public function getShippingTemplateInfoByPid($pid){
    	return $this->getDbConnection()->createCommand()->from($this->tableName())->where("pid={$pid}")->queryRow();
    }
    
    public function getShippingTemplateListByPid($pid){
    	return $this->getDbConnection()->createCommand()->from($this->tableName())->where("pid={$pid}")->queryAll();
    }
    
    
    public function saveShippingTemplateData($data){
    	$res = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    	if($res){
    		return $this->getDbConnection()->getLastInsertID();
    	}
    	return false;
    }
    
    public function delShippingTemplateByID($id){
    	return $this->getDbConnection()->createCommand()->delete($this->tableName(), "id=".$id);
    }
    
    public function delShippingTemplateByPIDs($pids){
    	if(is_array($pids)) $pids = implode(",", $pids);
    	return $this->getDbConnection()->createCommand()->delete($this->tableName(), "pid in(".$pids.")");
    }
    
}