<?php
/**
 * @desc Ebay Product Model
 * @author lihy
 */
class ProductEbay extends ProductsModel {
    
   
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product';
    }
    /**
     * @desc  根据条件获取产品列表
     * @param unknown $conditions
     * @param unknown $params
     * @param string $limits
     * @param string $select
     * @return mixed
     */
    public function getProductListByCondition($conditions, $params = array(), $limits = "", $select = "*"){
    	$command = $this->getDbConnection()->createCommand()
				    	->from($this->tableName())
				    	->where($conditions, $params)
				    	->select($select);
    	if($limits){
    		$limitsarr = explode(",", $limits);
    		$limit = isset($limitsarr[1]) ? trim($limitsarr[1]) : 0;
    		$offset = isset($limitsarr[0]) ? trim($limitsarr[0]) : 0;
    		$command->limit($limit, $offset);
    	}
    	return $command->queryAll();
    }
	/**
	 * @desc 获取产品信息
	 * @param unknown $sku
	 */ 
	public function getProductInfoByCondition($conditions, $params = array()){
		return $this->dbConnection->createCommand()
					->select('*')
					->from(self::tableName())
					->where($conditions, $params)
					->queryRow();
	}

}