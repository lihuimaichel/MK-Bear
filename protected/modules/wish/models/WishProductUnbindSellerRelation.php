<?php
/**
 * @desc Wish产品绑定
 * @author lihy
 * @since 2016-03-28
 */
class WishProductUnbindSellerRelation extends WishProductSellerRelation{
	
    public function tableName(){
    	return "ueb_listing_variants";
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
    	$cdbcriteria->select = 't.id, t.product_id, t.sku, t.online_sku, t.account_id, s.seller_id';
    	$cdbcriteria->join = 'left join ueb_wish_product_seller_relation s ON s.account_id=t.account_id and s.online_sku=t.online_sku and s.item_id=t.product_id';
    	$cdbcriteria->addCondition("ISNULL(s.seller_id) and t.enabled=1 and t.product_id IS NOT NULL");
		    	
    	return $cdbcriteria;
    }
    
    private function _additions($datas){
    	if(!empty($datas)){
    		$wishAccountList = UebModel::model("WishAccount")->getIdNamePairs();
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
    					'name'=>'online_sku',
    					'type'=>'text',
    					'search'=>'LIKE',
    					'htmlOption' => array(
    							'size' => '22',
    							'style'	=>	'width:260px'
    					),
    					'alias'=>'t'
    			),
    		
    			 
    			array(
    					'name'=>'product_id',
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
    					'data'		=>	UebModel::model("WishAccount")->getIdNamePairs(),
    					'htmlOption'=>array(
    							'size'=>'22'
    					),
    					'alias'=>'t'
    			),
    	);
    }
    
    
    public function attributeLabels(){
    	return array(
    			'sku'			=>	'SKU',
    			 
    			'online_sku'	=>	'在线SKU',
    			 
    			'product_id'	=>	'Product ID',
    			 
    			'account_id'	=>	'账号',
    			 
    			'site_id'		=>	'站点',
    			 
    			'seller_id'		=>	'销售人员',
    			
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
                        ->select('t.product_id, t.sku, t.online_sku, t.account_id, l.sku as parent_sku, l.warehouse_id')
                        ->from($this->tableName() . ' AS t')
                        ->leftJoin(WishProductSellerRelation::model()->tableName() . ' AS s', 's.account_id=t.account_id and s.online_sku=t.online_sku and s.item_id=t.product_id')
                        ->leftJoin(WishListing::model()->tableName() . ' AS l', 'l.id = t.listing_id')
                        ->where("t.account_id='{$accountId}' and ISNULL(s.seller_id) and t.enabled=1")
                        ->limit($limit)
                        ->queryAll();

        return $result;
    }
}