<?php
/**
 * @desc aliexpress产品绑定
 * @author hanxy
 * @since 2016-08-22
 */
class AliexpressProductUnbindSellerRelation extends AliexpressProductSellerRelation{
	
    public $account_id;
    public $online_sku;
    public $item_id;
    public function tableName(){
        return "ueb_aliexpress_product_variation";
    }

    // ============================= search ========================= //
    
    public function search(){
    	$sort = new CSort();
    	$sort->attributes = array('defaultOrder'=>'t.sku');
    	$dataProvider = UebModel::search($this, $sort, '', $this->_setdbCriteria());
    	$dataProvider->setData($this->_additions($dataProvider->data));
    	return $dataProvider;
    }
    
    /**
     * @desc  设置条件
     * @return CDbCriteria
     */
    private function _setdbCriteria(){
    	$cdbcriteria = new CDbCriteria();
    	$cdbcriteria->select = 't.id, t.aliexpress_product_id as item_id, t.product_id, t.sku, t.sku_code as online_sku,p.account_id,0 as site_id, s.seller_id';
        $cdbcriteria->join = 'LEFT JOIN ueb_aliexpress_product p on p.id=t.product_id 
                              LEFT JOIN ueb_aliexpress_product_seller_relation s ON s.account_id=p.account_id and s.online_sku=t.sku_code and s.item_id=t.aliexpress_product_id';
    	$cdbcriteria->addCondition("ISNULL(s.seller_id) and t.sku_code != '' and p.product_status_type='onSelling' and t.aliexpress_product_id IS NOT NULL");
		    	
    	return $cdbcriteria;
    }
    
    private function _additions($datas){
    	if(!empty($datas)){
    		$wishAccountList = UebModel::model("AliexpressAccount")->getIdNamePairs();
    		foreach ($datas as &$data){
    			$data['account_name'] = isset($wishAccountList[$data['account_id']]) ? $wishAccountList[$data['account_id']] : '';
    		}
    	}
    	return $datas;
    }
    
    
    public function filterOptions(){
    	return array(
    			array(
    					'name'=>'sku',
    					'type'=>'text',
    					'search'=>'LIKE',
    					'htmlOption' => array(
    							'size' => '22',
    					),
    					'alias'=>'t'
    			),
    			array(
    					'name'=>'sku_code',
    					'type'=>'text',
    					'search'=>'LIKE',
    					'htmlOption' => array(
    							'size' => '22',
    							'style'	=>	'width:260px'
    					),
    					'alias'=>'t'
    			),
    		
    			 
    			array(
    					'name'=>'aliexpress_product_id',
    					'type'=>'text',
    					'search'=>'=',
    					'htmlOption'=>array(
    							'size'=>'22'
    					),
    					'alias'=>'t'
    			),
    			
    			
    			array(
    					'name'		=>	'account_id',
    					'type'		=>	'dropDownList',
    					'search'	=>	'=',
    					'data'		=>	UebModel::model("AliexpressAccount")->getIdNamePairs(),
    					'htmlOption'=>array(
    							'size'=>'22'
    					),
    					'alias'=>'p'
    			),
    	);
    }
    
    
    public function attributeLabels(){
    	return array(
    			'sku'			=>	'SKU',
    			 
    			'sku_code'	    =>	'在线SKU',
    			 
    			'item_id'	    =>	'Item ID',
    			 
    			'account_id'	=>	'账号',
    			 
    			'site_id'		=>	'站点',
    			 
    			'seller_id'		=>	'销售人员',

                'aliexpress_product_id'=>'Product ID',
    			
    	);
    }
    
    // ============================= end search ====================//
    

    /**
     * @desc 通过账号查询未绑定的sku
     * @param integer $accountId
     * @return array 
     */
    public function getUnbindSkuByAccountId($accountId, $limit){
        if(!is_numeric($accountId)) return false;
        if(empty($limit)) $limit = 10000;
        $result =  $this->dbConnection->createCommand()
                        ->select('t.aliexpress_product_id as item_id, t.sku, t.sku_code as online_sku, p.account_id, s.seller_id, p.sku as parent_sku')
                        ->from($this->tableName() . ' AS t')
                        ->leftJoin(AliexpressProduct::model()->tableName() . ' AS p', 'p.id = t.product_id')
                        ->leftJoin(AliexpressProductSellerRelation::model()->tableName() . ' AS s', 's.account_id=p.account_id and s.online_sku=t.sku_code and s.item_id=t.aliexpress_product_id')
                        ->where("p.account_id='{$accountId}' and ISNULL(s.seller_id) and t.sku_code != '' and p.product_status_type='onSelling' and t.aliexpress_product_id IS NOT NULL")
                        ->limit($limit)
                        ->queryAll();

        return $result;
    }
}