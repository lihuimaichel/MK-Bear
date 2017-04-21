<?php
/**
 * @desc Lazada产品绑定
 * @author hanxy
 * @since 2016-08-23
 */
class LazadaProductUnbindSellerRelation extends LazadaProductSellerRelation{

    public $item_id;
	
    public function tableName(){
    	return "ueb_lazada_product";
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
    	$cdbcriteria->select = 't.id, t.product_id as item_id, t.sku, t.seller_sku, t.account_id, t.site_id, s.seller_id';
    	$cdbcriteria->join = 'left join ueb_lazada_product_seller_relation s ON s.account_id=t.account_id and s.online_sku=t.seller_sku and s.site_id=t.site_id and s.item_id=t.product_id';
    	$cdbcriteria->addCondition("ISNULL(s.seller_id) and t.status=1 and t.product_id IS NOT NULL");
		    	
    	return $cdbcriteria;
    }
    
    private function _additions($datas){
    	if(!empty($datas)){
            $siteList = LazadaSite::$siteList;
    		$lazadaAccountList = UebModel::model("LazadaAccount")->getAccountList();
    		foreach ($datas as &$data){
                $data['site_name'] = isset($siteList[$data['site_id']]) ? $siteList[$data['site_id']] : '';
    			$data['account_name'] = isset($lazadaAccountList[$data['account_id']]) ? $lazadaAccountList[$data['account_id']] : '';
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
    					'data'		=>	UebModel::model("LazadaAccount")->getAccountList(),
    					'htmlOption'=>array(
    							'size'=>'22'
    					),
    					'alias'=>'t'
    			),

                array(
                        'name'      =>  'site_id',
                        'type'      =>  'dropDownList',
                        'search'    =>  '=',
                        'data'      =>  LazadaSite::$siteList,
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
     * @param integer $siteId
     * @return array 
     */
    public function getUnbindSkuByAccountId($accountId,$siteId,$limit){
        if(!is_numeric($accountId)) return false;
        if(empty($limit)) $limit = 10000;
        $result =  $this->dbConnection->createCommand()
                        ->select('t.product_id, t.sku, t.seller_sku, t.account_id, t.site_id')
                        ->from($this->tableName() . ' AS t')
                        ->leftJoin(LazadaProductSellerRelation::model()->tableName() . ' AS s', 's.account_id=t.account_id and s.online_sku=t.seller_sku and s.site_id=t.site_id and s.item_id=t.product_id')
                        ->where("t.account_id='{$accountId}' and t.site_id='{$siteId}' and ISNULL(s.seller_id) and t.status=1 and t.product_id IS NOT NULL")
                        ->limit($limit)
                        ->queryAll();

        return $result;
    }
}