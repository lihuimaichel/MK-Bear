<?php
/**
 * @desc Ebay产品图片配置model
 * @author Gordon
 * @since 2015-07-25
 */
class EbayProductImageConfig extends EbayModel{
    
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_image_config';
    }
    
    // ====================== START 列表页面 =========================
    public function search(){
		$criteria = new CDbCriteria;
		$csort = new CSort();
		$dataPriveder = parent::search($this, $csort, '', $criteria);
		$data = $dataPriveder->data;
		$data = $this->additions($data);
		return $dataPriveder->setData($data);   	
    }
    
    
    public function additions($datas){
    	if($datas){
    		foreach ($datas as $key=>$data){
    			
    		}
    	}
    	return $datas;
    }
    // ====================== END 列表页面 ===========================
}