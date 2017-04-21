<?php
/**
 * @desc Amazon产品绑定
 * @author hanxy
 * @since 2016-08-23
 */
class AmazonProductUnbindSellerRelation extends AmazonProductSellerRelation{

    public $item_id;

    public function tableName(){
    	return "ueb_amazon_listing";
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
    	$cdbcriteria->select = 't.id, t.asin1 as item_id, t.asin1, t.sku, t.seller_sku, t.account_id, 0 as site_id, s.seller_id as seller_name';
    	$cdbcriteria->join = 'left join ueb_amazon_product_seller_relation s ON s.account_id=t.account_id and s.online_sku=t.seller_sku and s.item_id=t.asin1';
    	$cdbcriteria->addCondition("ISNULL(s.seller_id) and t.seller_status=1 and t.asin1 IS NOT NULL");
		    	
    	return $cdbcriteria;
    }
    
    private function _additions($datas){
    	if(!empty($datas)){
    		$amazonAccountList = UebModel::model("AmazonAccount")->getIdNamePairs();
    		foreach ($datas as &$data){
    			$data['account_name'] = isset($amazonAccountList[$data['account_id']]) ? $amazonAccountList[$data['account_id']] : '';
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
    					'name'=>'seller_sku',
    					'type'=>'text',
    					'search'=>'LIKE',
    					'htmlOption' => array(
    							'size' => '22',
    							'style'	=>	'width:260px'
    					),
    					'alias'=>'t'
    			),
    		
    			array(
    					'name'=>'asin1',
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
    					'data'		=>	UebModel::model("AmazonAccount")->getIdNamePairs(),
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
    			 
    			'seller_sku'	=>	'在线SKU',
    			 
    			'asin1'	        =>	'Product ID',
    			 
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
    public function getUnbindSkuByAccountId($accountId,$limit){
        if(!is_numeric($accountId)) return false;
        if(empty($limit)) $limit = 10000;
        $result =  $this->dbConnection->createCommand()
                        ->select('t.asin1 as item_id, t.sku, t.seller_sku, t.account_id, s.seller_id')
                        ->from($this->tableName() . ' AS t')
                        ->leftJoin(AmazonProductSellerRelation::model()->tableName() . ' AS s', 's.account_id=t.account_id and s.online_sku=t.seller_sku and s.item_id=t.asin1')
                        ->where("t.account_id='{$accountId}' and ISNULL(s.seller_id) and t.seller_status=1 and t.asin1 IS NOT NULL")
                        ->limit($limit)
                        ->queryAll();

        return $result;
    }
}