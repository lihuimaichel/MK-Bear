<?php
/**
 * @desc Ebay刊登拍卖信息model
 * @author lihy
 * @since 2016-03-28
 */
class EbayProductSellerRelation extends EbayModel{
	public $site_name;
	public $account_name;
    public $product_id;
    public $online_sku;
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_seller_relation';
    }

    /**
     * 获取销售员ID
     * @author yangsh
     * @since 2016/08/10
     */
    public function getItemSellerID($itemID, $onlineSku) {
        if ($itemID == '') {
            $rtn = array('errorCode'=>'100','errorMsg'=>'itemID is empty');
            return $rtn;
        }
        if ($onlineSku == '') {
            $rtn = array('errorCode'=>'101','errorMsg'=>'onlineSku is empty');
            return $rtn;
        }
        $info = $this->getOneByCondition(
            'seller_id',
            "item_id='{$itemID}' and online_sku='{$onlineSku}'"
        );
        $sellerID = empty($info) ? 0 : $info['seller_id'];
        $rtn = array('errorCode'=>'0','errorMsg'=>'ok', 'data'=>array('sellerID'=>$sellerID));
        return $rtn;
    }    

    /**
     * @desc   getOneByCondition
     * @param  string $fields 
     * @param  string $conditions  
     * @param  array $params  
     * @param  mixed $order 
     * @return array        
     * @author yangsh
     */
    public function getOneByCondition($fields='*', $conditions, $params=array(), $order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName());
        if (!empty($params)) {
            $cmd->where($conditions, $where);
        } else {
            $cmd->where($conditions);
        }
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

    /**
     * @desc   getListByCondition
     * @param  string $fields 
     * @param  string $conditions  
     * @param  array $params  
     * @param  mixed $order 
     * @return array        
     * @author yangsh
     */
    public function getListByCondition($fields='*', $conditions, $params=array(), $order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName());
        if (!empty($params)) {
            $cmd->where($conditions, $where);
        } else {
            $cmd->where($conditions);
        }
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }       

    public function getProductSellerRelationById($id){
    	return $this->getDbConnection()->createCommand()->from($this->tableName())->where("id=:id", array(":id"=>$id))->queryRow();
    }
   
    /**
     * @desc 保存数据
     * @param unknown $data
     * @return Ambigous <number, boolean>
     */
    public function saveData($data){
    	$res = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    	if($res){
    		$this->writeProductSellerRelationLog(array(
    				'item_id'		=>	$data['item_id'],
    				'sku'			=>	$data['sku'],
    				'seller_id'		=>	$data['seller_id'],
    				'online_sku'	=>	isset($data['online_sku']) ? $data['online_sku'] : '',
    				'account_id'	=>	$data['account_id'],
    				'site_id'		=>	$data['site_id']
    		));
    	}
    	return $res;
    }

    public function updateDataById($id, $data){
    	$res = $this->getDbConnection()->createCommand()->update($this->tableName(), $data, "id=:id", array(":id"=>$id));
    	if($res){
    		$info = $this->getProductSellerRelationById($id);
    		if($info){
    			$this->writeProductSellerRelationLog(array(
    													'item_id'		=>	$info['item_id'],
									    				'sku'			=>	$info['sku'],
									    				'seller_id'		=>	$info['seller_id'],
									    				'online_sku'	=>	$info['online_sku'],
    													'site_id'		=>	$info['site_id'],
    													'account_id'	=>	$info['account_id'],
    												));
    		}
    	}
    	return $res;
    }
    
    public function updateSellerIdByItemIdAndSku($newSellerId, $itemId, $sku, $onlineSKU, $accountID, $siteID){
    	$conditions = "item_id=:item_id and sku=:sku";
    	$params = array(
    			":item_id"		=>	$itemId,
    			":sku"			=>	$sku,
    			":account_id"	=>	$accountID,
    			":site_id"		=>	$siteID
    	);
    	if($onlineSKU){
    		$conditions .= " AND online_sku=:online_sku";
    		$params[":online_sku"]	=	$onlineSKU;
    	}
    	$nowtime = date("Y-m-d H:i:s");
    	$updateData = array(
    						'seller_id'		=>	$newSellerId,
    						'update_time'	=>	$nowtime
    					);
    	
    	$res = $this->getDbConnection()->createCommand()->update($this->tableName(), $updateData, $conditions, $params);
    	if($res){
    		$this->writeProductSellerRelationLog(array(
    				'item_id'		=>	$itemId,
    				'sku'			=>	$sku,
    				'seller_id'		=>	$newSellerId,
    				'online_sku'	=>	$onlineSKU ? $onlineSKU : '',
    				'site_id'		=>	$siteID,
    				'account_id'	=>	$accountID
    		));
    	}
    	return $res;
    }
    
    public function checkUniqueRow($itemId, $sku, $onlineSku, $accountID, $siteID){
    	$info = $this->getDbConnection()->createCommand()
    				->select('id')
    				->from($this->tableName())
    				->where("item_id=:item_id and sku=:sku and online_sku=:online_sku and account_id=:account_id and site_id=:site_id", 
    						array(":item_id"=>$itemId, ":sku"=>$sku, ":online_sku"=>$onlineSku, ":account_id"=>$accountID, ":site_id"=>$siteID))
    				->queryRow();
    	return empty($info) ? 0 : $info['id'];
    }
    
    public function writeProductSellerRelationLog($data){
    	$nowtime = date("Y-m-d H:i:s");
    	$userId = (int)Yii::app()->user->id;
    	$data['create_time'] = $nowtime;
    	$data['create_user_id'] = $userId;
    	$logtableName = $this->tableName() . "_log";
    	return $this->getDbConnection()->createCommand()->insert($logtableName, $data);
    }
    
	/**
	 * @desc 获取销售产品信息
	 * @param unknown $itemId
	 * @param unknown $sku
	 * @param unknown $onlineSku
	 * @return mixed
	 */
    public function getProductSellerRelationInfoByItemIdandSKU($itemId, $sku, $onlineSku){
    	$info = $this->getDbConnection()->createCommand()
			    	->select('*')
			    	->from($this->tableName())
			    	->where("item_id=:item_id and sku=:sku and online_sku=:online_sku ",
    						array(":item_id"=>$itemId, ":sku"=>$sku, ":online_sku"=>$onlineSku))
    				->queryRow();
    	return $info;
    }


    /**
     * @desc 删除
     * @param unknown $ids
     * @return boolean|Ambigous <number, boolean>
     */
    public function deleteById($ids){
        if(empty($ids)) return false;
        if(!is_array($ids)){
            $ids = array($ids);
        }
        return $this->getDbConnection()
            ->createCommand()
            ->delete($this->tableName(), array("IN", 'id', $ids));
    }


    /**
     * @desc 批量更改对应站点和账号下的销售人员
     * @param unknown $oldSiteId
     * @param unknown $oldAccountId
     * @param unknown $oldSellerId
     * @param unknown $newSellerId
     * @return Ambigous <number, boolean>
     */
    public function batchChangeSellerToOtherSeller($oldSiteId, $oldAccountId, $oldSellerId, $newSellerId){
        $dbtransaction = EbayProductSellerRelationLog::model()->getDbConnection()->beginTransaction();
        try {
            $resustInfo = $this->getDbConnection()->createCommand()
                    ->select('*')
                    ->from($this->tableName())
                    ->where('account_id=:account_id AND seller_id=:seller_id AND site_id=:site_id', array(':account_id'=>$oldAccountId,':seller_id'=>$oldSellerId, ':site_id'=>$oldSiteId))
                    ->queryAll();
            if($resustInfo){
                foreach ($resustInfo as $info) {
                    $this->writeProductSellerRelationLog(array(
                                'item_id'       =>  $info['item_id'],
                                'sku'           =>  $info['sku'],
                                'seller_id'     =>  $newSellerId,
                                'online_sku'    =>  $info['online_sku'],
                                'site_id'       =>  $info['site_id'],
                                'account_id'    =>  $info['account_id'],
                                'error_msg'     =>  '原销售人员:'.$oldSellerId
                        ));
                }

                $this->getDbConnection()->createCommand()
                     ->update(
                        $this->tableName(), 
                        array('seller_id'=>$newSellerId, 'update_time'=>date('Y-m-d H:i:s')), 
                        "account_id='{$oldAccountId}' and seller_id='{$oldSellerId}' and site_id='{$oldSiteId}'"
                    );
            }

            $dbtransaction->commit();
            return true;
        } catch (Exception $e) {
            $dbtransaction->rollback();
            return false;
        }
    }


    /**
     * @desc 批量设置账号给销售人员
     * @param unknown $siteID
     * @param unknown $accountID
     * @param unknown $sellerID
     * @return boolean|Ambigous <boolean, number, unknown>
     */
    public function batchSetAccountListingToSeller($siteID, $accountID, $sellerID){
        if(empty($accountID) || empty($sellerID)) return false;
        //获取对应账号未绑定产品信息
        $res = false;
        $indexNum = 0;
        $limit = 2000;
        do{
            $productList = $this->getDbConnection()->createCommand()
            ->from(EbayProductVariation::model()->tableName() . " as v")
            ->leftJoin(EbayProduct::model()->tableName()." as p", "v.listing_id=p.id")
            ->leftJoin($this->tableName() . " as s", "s.account_id=p.account_id and s.online_sku=v.sku_online and s.site_id=p.site_id and s.item_id=v.item_id")
            ->select("v.item_id, v.sku, v.sku_online as online_sku, p.account_id, p.site_id")
            ->where("p.account_id='{$accountID}' and p.site_id='{$siteID}' and ISNULL(s.seller_id) and p.item_status=1")
            ->limit($limit)
            ->queryAll();
             
            if(!empty($productList)){
                foreach ($productList as &$val){
                    $val['seller_id'] = $sellerID;

                    $conditions = 'site_id = :site_id AND item_id = :item_id AND account_id = :account_id AND sku = :sku AND online_sku = :online_sku';
                    $params = array(':site_id'=>$val['site_id'], ':item_id'=>$val['item_id'], ':account_id'=>$val['account_id'], ':sku'=>$val['sku'], ':online_sku'=>$val['online_sku']);
                    $relationModel = $this->getBindSellerListByCondition($conditions,$params);
                    if($relationModel){
                        $deleteRelateinArr = array();
                        foreach ($relationModel as $rkey => $rvalue) {
                            $deleteRelateinArr[] = $rvalue['id'];
                        }
                        $deleteRelateinString = implode(',', $deleteRelateinArr);
                        $sql = 'DELETE FROM '.$this->tableName().' WHERE id IN('.$deleteRelateinString.')';
                        $this->getDbConnection()->createCommand($sql)->execute();
                    }

                }
                $res2 = $this->batchInsert($this->tableName(), array('item_id', 'sku', 'online_sku', 'account_id', 'site_id', 'seller_id'), $productList);
                if($res2){
                    $res = $res2;//只置为真
                }
            }
            $indexNum++;
            if($indexNum > 3){
                break;
            }
        }while ($productList);
        return $res;
    }


    /**
     * @desc 批量设置SKU给销售人员
     * @param unknown $ids
     * @param unknown $sellerID
     * @return boolean|Ambigous <boolean, number, unknown>
     */
    public function batchSetSkuListingToSeller($ids, $sellerID){
        if(empty($ids) || empty($sellerID)) return false;
        //获取对应账号未绑定产品信息
        $res = false;
        $limit = 2000;

        $productList = $this->getDbConnection()->createCommand()
                        ->from(EbayProductVariation::model()->tableName() . " as v")
                        ->leftJoin(EbayProduct::model()->tableName()." as p", "v.listing_id=p.id")
                        ->leftJoin($this->tableName() . " as s", "s.account_id=p.account_id and s.online_sku=v.sku_online and s.site_id=p.site_id and s.item_id=v.item_id")
                        ->select("v.item_id, v.sku, v.sku_online as online_sku, p.account_id, p.site_id")
                        ->where("ISNULL(s.seller_id) and p.item_status=1")
                        ->andWhere(array("IN", "v.id", $ids))
                        ->limit($limit)
                        ->queryAll();
        if(!empty($productList)){
            foreach ($productList as &$val){
                $val['seller_id'] = $sellerID;

                $conditions = 'site_id = :site_id AND item_id = :item_id AND account_id = :account_id AND sku = :sku AND online_sku = :online_sku';
                $params = array(':site_id'=>$val['site_id'], ':item_id'=>$val['item_id'], ':account_id'=>$val['account_id'], ':sku'=>$val['sku'], ':online_sku'=>$val['online_sku']);
                $relationModel = $this->getBindSellerListByCondition($conditions,$params);
                if($relationModel){
                    $deleteRelateinArr = array();
                    foreach ($relationModel as $rkey => $rvalue) {
                        $deleteRelateinArr[] = $rvalue['id'];
                    }
                    $deleteRelateinString = implode(',', $deleteRelateinArr);
                    $sql = 'DELETE FROM '.$this->tableName().' WHERE id IN('.$deleteRelateinString.')';
                    $this->getDbConnection()->createCommand($sql)->execute();
                }

            }

            $res2 = $this->batchInsert($this->tableName(), array('item_id', 'sku', 'online_sku', 'account_id', 'site_id', 'seller_id'), $productList);
            if($res2){
                $res = $res2;//只置为真
            }
        }
        return $res;
    }


    /**
     * @desc 页面的跳转链接地址
     */
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/ebay/ebayproductsellerrelation/list');
    }


    /**
     * @desc 未绑定页面设置账号销售的跳转链接地址
     */
    public static function getUnbindsellerNavTabId() {
        return Menu::model()->getIdByUrl('/ebay/ebayproductsellerrelation/unbindseller');
    }


    /**
     * @desc 获取产品与绑定人员的绑定数据
     * @param unknown $conditions
     * @param unknown $params
     * param unknown $limit
     * param unknown $offset
     */
    public function getBindSellerListByCondition($conditions, $params){
        return $this->getDbConnection()->createCommand()
                        ->select('id, item_id, sku, online_sku, account_id, seller_id, site_id')
                        ->from($this->tableName())
                        ->where($conditions, $params)
                        ->order('sku DESC, id DESC')
                        // ->limit($limit, $offset)
                        ->queryAll();                   
    }


    /**
     * @desc 获取产品与绑定人员的未绑定数据
     * @param unknown $conditions
     * @param unknown $params
     */
    public function getUnBindSellerListByCondition($conditions, $params){
        return $this->getDbConnection()->createCommand()
                    ->select('v.item_id,v.sku,v.sku_online,p.account_id,p.site_id, s.seller_id')
                    ->from(EbayProductVariation::model()->tableName(). ' as v')
                    ->leftJoin(EbayProduct::model()->tableName(). ' as p', 'v.listing_id=p.id')
                    ->leftJoin(EbayProductSellerRelation::model()->tableName(). ' as s', 's.account_id=p.account_id and s.online_sku=v.sku_online and s.site_id=p.site_id and s.item_id=v.item_id')
                    ->where($conditions, $params)
                    ->order('p.sku DESC')
                    ->queryAll();        
    }


    // ============================= search ========================= //
    
    public function search(){
    	$sort = new CSort();
    	$sort->attributes = array('defaultOrder'=>'t.sku');
    	$dataProvider = parent::search($this, $sort, '', $this->_setdbCriteria());
    	$dataProvider->setData($this->_additions($dataProvider->data));
    	return $dataProvider;
    }
    /**
     * @desc  设置条件
     * @return CDbCriteria
     */
    private function _setdbCriteria(){
    	$cdbcriteria = new CDbCriteria();
    	$cdbcriteria->select = 't.*';
    	
    	return $cdbcriteria;
    }
    
    private function _additions($datas){
    	if(!empty($datas)){
    		$siteLists = UebModel::model('EbaySite')->getSiteList();
    		$accountLists = UebModel::model('EbayAccount')->getIdNamePairs();
    		foreach ($datas as &$data){
    			//获取站点
    			$data['site_name'] = isset($siteLists[$data['site_id']]) ?  $siteLists[$data['site_id']] : '';
                //获取账号名称
    			$data['account_name'] = isset($accountLists[$data['account_id']]) ? $accountLists[$data['account_id']] : ''; 
    		}
    	}
    	return $datas;
    }
    
    
    public function filterOptions(){
    	// $site = Yii::app()->request->getParam('site_id');
    	return array(
    			array(
    					'name'=>'sku',
    					'type'=>'text',
    					'search'=>'LIKE',
    					'htmlOption' => array(
    							'size' => '22',
    					)
    			),
    			array(
    					'name'=>'online_sku',
    					'type'=>'text',
    					'search'=>'LIKE',
    					//'rel'=>true,
    					'htmlOption' => array(
    							'size' => '22',
    							'style'	=>	'width:260px'
    					)
    			),
    		
    			 
    			array(
    					'name'=>'item_id',
    					'type'=>'text',
    					'search'=>'=',
    					'htmlOption'=>array(
    							'size'=>'22'
    					)
    			),
    			
    			array(
    					'name' 			=> 'site_id',
						'type' 			=> 'dropDownList',
						'search' 		=> '=',
						'data' 			=> UebModel::model('EbaySite')->getSiteList(),
                        'value'         =>  Yii::app()->request->getParam('site_id'),
    					'htmlOption'	=>	array(
    											'size'=>'22'
    										)
    			),
    			
    			
    			array(
    					'name' 			=> 'account_id',
    					'type' 			=> 'dropDownList',
    					'search' 		=> '=',
    					'data' 			=> UebModel::model('EbayAccount')->getIdNamePairs(),
    					'htmlOptions' 	=> array(
    							'id'=>'search_account_id'
    					),
    			),
    			
    			array(
    					'name'		=>	'seller_id',
    					'type'		=>	'dropDownList',
    					'data'		=>	EbayProductAdd::model()->getUserNameList(),
    					'search'	=>	'=',
    			
    			),

    	);
    }
    
    
    public function attributeLabels(){
    	
    	
    	return array(
    			'sku'			=>	'SKU',
    			 
    			'online_sku'	=>	'在线SKU',
    			 
    			'item_id'		=>	'Item ID',
    			 
    			'account_id'	=>	'账号',
    			 
    			'site_id'		=>	'站点',
    			 
    			'seller_id'		=>	'销售人员',
    			 
    	);
    }
    
    // ============================= end search ====================//
}