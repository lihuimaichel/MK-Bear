<?php
/**
 * @desc ebay产品绑定
 * @author hanxy
 * @since 2016-08-23
 */
class EbayProductUnbindSellerRelation extends EbayProductSellerRelation{

    public function tableName(){
    	return "ueb_ebay_product";
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
    	$cdbcriteria->select = 'v.id, v.item_id, v.sku, v.sku_online, t.account_id, t.site_id, s.seller_id as seller_name, a.short_name, ss.site_name';
    	$cdbcriteria->join = '
            LEFT JOIN ueb_ebay_product_variation v on v.listing_id=t.id
            LEFT JOIN ueb_ebay_product_seller_relation s ON s.account_id=t.account_id and s.online_sku=v.sku_online and s.site_id=t.site_id and s.item_id=v.item_id
            LEFT JOIN ueb_ebay_account a on a.id=t.account_id
            LEFT JOIN ueb_ebay_site_config ss on ss.site_id=t.site_id
        ';
        $cdbcriteria->addCondition("ISNULL(s.seller_id) and t.item_status=1");

    	return $cdbcriteria;
    }
    
    private function _additions($datas){
    	if(!empty($datas)){
    		$ebayAccountList = UebModel::model("EbayAccount")->getIdNamePairs();
    		foreach ($datas as &$data){
    			$data['account_name'] = isset($ebayAccountList[$data['account_id']]) ? $ebayAccountList[$data['account_id']] : '';
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
    					'htmlOptions' => array(),
    					'alias'=>'t'
    			),
    			array(
    					'name'=>'online_sku',
    					'type'=>'text',
    					'search'=>'LIKE',
    					'alias'=>'t'
    			),
    		
    			 
    			array(
    					'name'=>'item_id',
    					'type'=>'text',
    					'search'=>'=',
    					'alias'=>'t'
    			),
    			
    			
                array(
                        'name'      =>  'site_id',
                        'type'      =>  'dropDownList',
                        'search'    =>  '=',
                        'data'      =>  UebModel::model('EbaySite')->getSiteList(),
                        'alias'=>'t',
                        'value'     =>  Yii::app()->request->getParam('site_id'),
                ),


    			array(
    					'name'		=>	'account_id',
    					'type'		=>	'dropDownList',
    					'search'	=>	'=',
    					'data'		=>	UebModel::model("EbayAccount")->getIdNamePairs(),
    					'alias'=>'t'
    			),

    	);
    }
    
    
    public function attributeLabels(){
    	return array(
    			'sku'			=>	'SKU',
    			 
    			'online_sku'	=>	'在线SKU',
    			 
    			'item_id'	    =>	'Product ID',
    			 
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
    public function getUnbindSkuByAccountId($accountId, $limit,$itemId = null,$sku = null){
        if(!is_numeric($accountId)) return false;
        if(empty($limit)) $limit = 10000;
        $result =  $this->dbConnection->createCommand()
                        ->select('v.id, v.item_id, v.sku, v.main_sku, v.sku_online as online_sku, t.account_id, t.site_id, e.site_name, s.seller_id, t.sku_online as seller_sku, t.is_multiple')
                        ->from($this->tableName() . ' AS t')
                        ->leftJoin(EbaySite::model()->tableName() . ' AS e', 'e.site_id=t.site_id')
                        ->leftJoin(EbayProductVariation::model()->tableName() . ' AS v', 'v.listing_id=t.id')
                        ->leftJoin(EbayProductSellerRelation::model()->tableName() . ' AS s', 's.account_id=t.account_id and s.online_sku=v.sku_online and s.site_id=t.site_id and s.item_id=v.item_id')
                        ->where("t.account_id='{$accountId}' and ISNULL(s.seller_id) and t.item_status=1")
                        ->limit($limit);
		if ($itemId != null) {
			$result->andWhere("v.item_id='{$itemId}'");
		}
		if ($sku != null) {
			$result->andWhere("v.sku='{$sku}'");
		}

        return $result->queryAll();
    }
}