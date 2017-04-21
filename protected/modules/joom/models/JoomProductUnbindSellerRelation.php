<?php
/**
 * @desc Joom产品绑定
 * @author hanxy
 * @since 2016-09-28
 */
class JoomProductUnbindSellerRelation extends JoomProductSellerRelation{
	
    public function tableName(){
    	return "ueb_joom_listing";
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
    	$cdbcriteria->select = 't.id, t.product_id, t.sku, t.parent_sku, t.account_id, s.seller_id';
    	$cdbcriteria->join = 'left join ueb_joom_product_seller_relation s ON s.account_id=t.account_id and s.online_sku=t.parent_sku and s.item_id=t.product_id';
    	$cdbcriteria->addCondition("ISNULL(s.seller_id) and t.enabled=1 and t.product_id IS NOT NULL");
		    	
    	return $cdbcriteria;
    }
    
    private function _additions($datas){
    	if(!empty($datas)){
    		$joomAccountList = UebModel::model("JoomAccount")->getIdNamePairs();
    		foreach ($datas as &$data){
    			$data['account_name'] = isset($joomAccountList[$data['account_id']]) ? $joomAccountList[$data['account_id']] : '';
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
    					'name'=>'parent_sku',
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
    					'data'		=>	UebModel::model("JoomAccount")->getIdNamePairs(),
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
    			 
    			'parent_sku'	=>	'在线主SKU',
    			 
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
                        ->select('t.product_id, t.sku, t.parent_sku, t.account_id')
                        ->from($this->tableName() . ' AS t')
                        ->leftJoin(JoomProductSellerRelation::model()->tableName() . ' AS s', 's.account_id=t.account_id and s.online_sku=t.parent_sku and s.item_id=t.product_id')
                        ->where("t.account_id='{$accountId}' and ISNULL(s.seller_id) and t.enabled=1 and t.product_id IS NOT NULL")
                        ->limit($limit)
                        ->queryAll();

        return $result;
    }
}