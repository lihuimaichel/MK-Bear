<?php
/**
 * @desc ebay产品价格管理
 * @author yangsh
 * @since 2016-08-31
 */
class EbayProductPriceManage extends EbayModel {

	const EVENT_NAME      		= 'revise_price';//修改价格

	/** @var string 异常信息*/
	protected $_Exception   	= null;

    /**@var 状态 */
    const STATUS_DEFAULT		= 0;//默认
    const STATUS_SUCCESS      	= 1;//成功
    const STATUS_FAILURE   		= 2;//失败
    const STATUS_INVALID        = 3;//无需改价

	public static function model($className = __CLASS__) {
		return parent::model ( $className );
	}

    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_price_manage';
    }	
		
	/**
	 * 设置异常信息
	 * @param string $message        	
	 */
	public function setExceptionMessage($message) {
		$this->_Exception = $message;
		return $this;
	}

	/**
	 * 获取异常信息
	 * @return string
	 */
	public function getExceptionMessage() {
		return $this->_Exception;
	}		

	/**
	 * [getFormatKey description]
     * @param  string $itemID    
     * @param  string $onlineSku
     * @return string
	 */
	public static function getFormatKey($itemID, $onlineSku) {
		return $itemID.'_'.$onlineSku;
	} 	

    /**
     * 需要自动改价的海外仓location与仓库ID关系
     * 虚拟仓  location（ UK, United Kingdom  更改至 Dortmund, Deutschland）
       乐宝仓  14 location （Derby, United Kingdom 更改至Bremen, Deutschland）
       万邑通仓  58 location （Manchester, United Kingdom更改至Markgröningen，Deutschland）
       4PX仓    34 location (London, United Kingdom 更改至Bruchsal, Deutschland）
     * @return array
     */
    public static function getOverseaLocationWhIdMap() {
        return array(
			'Derby'      	=>14,//乐宝
			'London'     	=>34,//4PX 
			'Manchester' 	=>58,//万邑通 
        );    	
    }

    /**
     * @desc 获取UK站待清仓SKU
     * @return array
     */
    public static function getEbayClearanceProductForUK() {
    	return array('95584','88949','81083','81708','16636','96212.03','96212.07','86723','85521','89753.01','94405','93924','98460','62067','92766','92101','95634','52726','78389.08','95615','74760','98802','88934','92554.01','92554.02','97093','92082','95397','77946.04','95542.01','92773','96002','93374','90222','71624','98626','98041','89313','89989.01','96625.03','71787.01','86975.02','88093','2063','91229','43195','92106','96138','89983.01','76767.02','94804.01','95414.02','96664.06','65917','96601.06','81515','98354','98189.01','95420.08','92237','76930','95828.01','96951','95111','52620','76769.02','89778','76774','94414','95870','89772','89777','76769.01','76808.02','92282','86725.02','94408','97076','96678.02','96678.01','98074','97208','2182B','72182','76808.01','81800','92287','95442','90540.01','92464','76767.01','86271','92457.01','83602','92340','93795.01','93108','95261.03','95414.05','90454.04','62405.05','94214.01','95420.01','97366','86300','95542.02','96664.09','90482','82328','81169.03','95399.02','71663','80061.02','84122.02','92457.02','92284.06','86273','88568.02','93946.01','81170','81174.03','81543','55069','95420.02','95833.02','90603.01','90603.03','81198.02','79986.03','94804.02','96882','96881','78207','90603.02','70406.07','80449','81281','86356','78939','78557','79617','92298.01','90673.03','95043','93946.02','97624','81034.02','63129','81078','91890','84745.06','61497.02','96168','72840','95056','97099','97622.01','90454.07','98280.03','98987','99219','90176','93428','95261.02','98132.08','95171','97871','90454.02','97550','98901','97852.02','97405','100123','95737.02','99801.02','99801.01','99305','94912','97259','100447','100293','101389','101533','100612','100968','100970','101022','81595.01','92088.03','96619.03','99819','100250','93933','99344','95933','94235.03','101765','101678','99346','100668.04','100668.03','96183.01','92938.06','91174.03','101834','101928','101070','101627','102291','88264','103165','103601.03','103191','99351','103597.01','95815','103601.02','103601.01','103597.02','104277.01','104277.02','95541','105982','110428','105880','52966.02','104949.02','105968','103694','101905','100727.03','100727.02','112010','114389','84745.05','94634','95896','77712','94315.02','75276','93890.02','94315.01','94294','93890.01','94668','95900','96573','95883','97162','96241','99701','99408','99133','99052','98728','98718','97096','97094','97062','96095','99946','100468.02','100468.03','100468.01','100854','100733','96619.01','100997','101584','101585','102676','103763','101466','103768','99735','107622','106018','95313','94923','94914','94716.03','94572','94553','94545','94543','94236.02','93363.03','93346.03','93346.01','92800.02','92800.01','92254','91117','90673.02','90622','90619','90596.01','90565','90562','90554','90450','90447','90433','89525','89514','89283.04','88941','88925','88924','88868','86319.01','84070.01','83485.03','83476.04','83476.03','83472.01','81893','81196.03','81162','79986.01','79832.02','79832.01','78514','78241','78126','77098.03','77098.01','77085','76482','76231.02','74610','62718','62153.02','55384.06','44134','74763','76772','76775','81282','89502','89509','89517','89527','90567','90596.02','91847.02','91873','93890.04','94311.01','94311.02','94431','94538','94542','94546','94549');
    }

    /**
     * @desc 每次准备数据把状态从0改成4
     * @param int $accountID 
     * @return boolean
     */
    public function setDefaultForPrepare($accountID) {
        return $this->dbConnection->createCommand()->update($this->tableName(),array('status'=> self::STATUS_INVALID ), "account_id='{$accountID}' and status=0 ");
    }    

    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

    /**
     * [getListByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        //echo $cmd->Text;
        return $cmd->queryAll();
    }	

	/**
	 * @desc 获取最近一次更新listing价格成功的记录
	 * @param  string $itemID    
	 * @param  string $onlineSku 
	 * @return string
	 */
	public function getLastSuccessRecord($itemID, $onlineSku) {
        $cmd = $this->dbConnection->createCommand()
	            ->select('*')
	            ->from($this->tableName())
	            ->where("item_id='{$itemID}' and online_sku='{$onlineSku}'")
	            ->andWhere("listing_price>0")
	            ->andWhere("updated_price_at>'0000-00-00 00:00:00'")
	        	->order("updated_price_at desc")
	        	->limit(1);
	    return $cmd->queryRow();
	}   

	/**
     * @desc 获取sku可用库存
     * @param  string $sku 
     * @return int
     */
	public function getOverseaSkuStockQty($sku) {
		$whIds = self::getOverseaLocationWhIdMap();
		$warehouseSkuMap = WarehouseSkuMap::model();
    	$res = $warehouseSkuMap->getDbConnection()->createCommand()
    	                ->select("SUM(available_qty) AS stock_qty")
				    	->from($warehouseSkuMap->tableName())
				    	->where("sku='{$sku}'")
				    	->andWhere("warehouse_id in(".implode(',', $whIds ).")")
				    	->queryRow();
        return empty($res) ? 0 : $res['stock_qty'];		
	}	

    /**
     * [getOverseaAccountIDs description]
     * @return array
     */
    public function getOverseaAccountIDs() {
    	$locArr = array_keys(self::getOverseaLocationWhIdMap());
		$ebayProductModel = EbayProduct::model();
		return $ebayProductModel->dbConnection->createCommand()
		            ->selectDistinct('account_id')
		            ->from($ebayProductModel->tableName())
		            ->where("location in('". implode("','", $locArr) ."')")
		        	->queryColumn();
    } 

	/**
	 * @desc 获取累计销量最高的一条listing
	 * @param  int $accountID 
	 * @param  int  $siteID  
	 * @param  array $locArr
	 * @return string
	 */
    public function getBestSaleListing($accountID=null,$siteID=null,$locArr=array()) {
		$ebayProductModel = EbayProduct::model();
		$cmd = $ebayProductModel->dbConnection->createCommand()
		            ->select('account_id,item_id')
		            ->from($ebayProductModel->tableName())
		            ->where("listing_type in('FixedPriceItem', 'StoresFixedPrice') AND item_status=1")
		            ->order("quantity_sold desc")
		            ->limit(1);
		if (!empty($accountID)) {
			$cmd->andWhere("account_id='{$accountID}'");
		}		            
        if ($siteID !== '' && $siteID !== null) {
        	$cmd->andWhere("site_id='{$siteID}'");
        }  		            
		if (!empty($locArr)) {
			$cmd->andWhere("location in('". implode("','", $locArr) ."')");
		}
		$cmd->andWhere("account_id in(".implode(',',EbayAccount::$OVERSEAS_ACCOUNT_ID).")");
		$res = $cmd->queryRow();
		return empty($res) ? '' : $res['item_id'];
    }	

    /**
     * @desc 检测是否唯一
	 * @param  string $sku 
	 * @param  int  $siteID  
	 * @param  array $locArr
	 * @return boolean
     */
    public function checkItemIDs($sku,$siteID,$locArr=array()) {
		$ebayProductModel = new EbayProduct();
		$ebayProductVariantModel = new EbayProductVariation();
		$cmd = $ebayProductModel->dbConnection->createCommand()
		            ->selectDistinct('p.item_id')
		            ->from($ebayProductModel->tableName().' as p')
		            ->leftJoin($ebayProductVariantModel->tableName().' as v',"p.id=v.listing_id" )
		            ->where("p.listing_type in('FixedPriceItem', 'StoresFixedPrice') AND p.item_status=1")
		            ->andWhere("v.sku='{$sku}'");	            
        if ($siteID !== '' && $siteID !== null) {
        	$cmd->andWhere("p.site_id='{$siteID}'");
        }  		            
		if (!empty($locArr)) {
			$cmd->andWhere("p.location in('". implode("','", $locArr) ."')");
		}
		//echo $cmd->Text;
		return $cmd->queryColumn();
    }

	/**
	 * @desc 准备数据
     * @param  integer $accountID 
     * @param  int     $siteID    
     * @param  string $itemID    
     * @param  string $onlineSku
     * @param  array $locArr
     * @return boolean   
	 */
	public function prepareData($accountID,$siteID=null,$itemID=null,$onlineSku=null,$locArr=array()) {
		$nowTime = date("Y-m-d H:i:s");
		//每次准备数据把状态从'默认'改成'无效'
		$this->setDefaultForPrepare($accountID);		
		$dbTransaction = $this->dbConnection->getCurrentTransaction();
        if( !$dbTransaction ){
            $dbTransaction = $this->dbConnection->beginTransaction();
        }
        try {
			//1.获取UK站海外仓在线listing数据
			$ebayProductModel = EbayProduct::model();
			$ebayProductVariantModel = EbayProductVariation::model();
			$where = "p.account_id={$accountID} and p.item_status=1 and v.sku_online != '' and v.sku != '' and p.listing_type in('FixedPriceItem', 'StoresFixedPrice') ";
	        $cmd = $ebayProductVariantModel->dbConnection->createCommand()
	                    ->select("count(*) as total")
	                    ->from($ebayProductVariantModel->tableName().' as v')
	                    ->join($ebayProductModel->tableName().' as p', "v.listing_id=p.id")
	                    ->where($where);
	        if ($siteID !== '' && $siteID !== null) {
	        	$cmd->andWhere("p.site_id='{$siteID}'");
	        }            	                    
	        if($itemID){
	            $cmd->andWhere("v.item_id = '{$itemID}'");
	        }
	        if ($onlineSku) {
	            $cmd->andWhere("v.sku_online = '{$onlineSku}'");
	        }
			if (!empty($locArr)) {
				$cmd->andWhere("p.location in('".implode("','", $locArr)."')");
			}	 
			//MHelper::writefilelog('my.txt',$cmd->Text."\r\n");       
	        $res = $cmd->queryRow();
	        if(empty($res)) {
	        	$this->setExceptionMessage('listing数据为空');
	        	return false;
	        }
	        $total = $res['total'];
	        $pageSize = 1000;
	        $pageCount = ceil($total/$pageSize);
	        $datalist = array();
	        for ($page=1; $page <= $pageCount; $page++) { 
	        	$offset = ($page - 1) * $pageSize;
		        $cmd = $ebayProductVariantModel->dbConnection->createCommand()
		                    ->select("p.account_id,v.item_id,v.sku_online,v.sku")
		                    ->from($ebayProductVariantModel->tableName().' as v')
		                    ->join($ebayProductModel->tableName().' as p', "v.listing_id=p.id")
		                    ->where($where)
		                    ->order("v.id asc")
		                    ->limit($pageSize,$offset);
		        if ($siteID !== '' && $siteID !== null) {
		        	$cmd->andWhere("p.site_id='{$siteID}'");
		        }   		                    
			    if($itemID){
		            $cmd->andWhere("v.item_id = '{$itemID}'");
		        }
		        if ($onlineSku) {
		            $cmd->andWhere("v.sku_online = '{$onlineSku}'");
		        }
				if (!empty($locArr)) {
					$cmd->andWhere("p.location in('".implode("','", $locArr)."')");
				}	
				$cmd->andWhere("p.account_id in(".implode(',',EbayAccount::$OVERSEAS_ACCOUNT_ID).")");	        
		        $res = $cmd->queryAll();
		        if(!empty($res)) {
			        foreach ($res as $v) {
			        	//获取最近一次改价成功的记录
			        	$lastSuc = $this->getLastSuccessRecord($v['item_id'], $v['sku_online']);
			        	//获取sku海外仓总的可用库存
			        	$totalQty = $this->getOverseaSkuStockQty($v['sku']);
			        	$datalist[] = array(
							'account_id'        => $v['account_id'],
							'item_id'           => $v['item_id'],
							'online_sku'        => $v['sku_online'],
							'sku'               => $v['sku'],
							'stock_qty'         => (int)$totalQty,//总可用库存
							'listing_price'     => 0,//listing现价
							'listing_price_old' => empty($lastSuc) ? 0 : $lastSuc['listing_price'],//上次listing价格
							'price'             => 0,//要修改的价格
							'status'            => 0,
							'created_at'        => $nowTime,
			        	);
			        }
		        }
	        }
	    	if ($datalist) {
		    	$groupDataList = MHelper::getGroupData($datalist,500);
		    	foreach ($groupDataList as $groupData) {
		    		$this->batchInsert($this->tableName(),array_keys($groupData[0]),$groupData);
		    	}
	    	}
	    	$dbTransaction->commit();
	    	return true;
        } catch (Exception $e) {
        	$dbTransaction->rollback();
        	echo $e->getMessage()."<br>";
        	$this->setExceptionMessage('准备数据时异常退出');
        	return false;
        }
	}

	/**
	 * @desc 批量更新listing
     * @param  integer $accountID
     * @param  int     $siteID   
     * @param  string $itemID    
     * @param  string $onlineSku
     * @param  array $locArr
     * @return boolean  
	 */
    public function updateItemSimpleInfo($accountID,$siteID=null,$itemID=null,$onlineSku=null,$locArr=array()) {
        $ebayProductModel = EbayProduct::model();
        $ebayProductVariantModel = EbayProductVariation::model();
        $cmd = $ebayProductModel->getDbConnection()->createCommand()
                                ->select("t.account_id,t.item_id,t.sku_online")
                                ->from($ebayProductModel->tableName() . " p")
                                ->join($ebayProductVariantModel->tableName() . " as t", "p.id=t.listing_id")
                                ->join($this->tableName() . " as m", "m.item_id=t.item_id and m.online_sku=t.sku_online")
                                ->where("t.account_id='{$accountID}'")
                                ->andWhere("m.status=0")
                                ->andWhere("p.listing_type in('FixedPriceItem', 'StoresFixedPrice')");
        if ($siteID !== '' && $siteID !== null) {
        	$cmd->andWhere("p.site_id='{$siteID}'");
        }                                 
        if($itemID){
            $cmd->andWhere("t.item_id = '{$itemID}'");
        }
        if ($onlineSku) {
            $cmd->andWhere("t.sku_online = '{$onlineSku}'");
        }
        if (!empty($locArr)) {
        	$cmd->andWhere("p.location in('". implode("','", $locArr) ."')");
        }
        $cmd->andWhere("p.account_id in(".implode(',',EbayAccount::$OVERSEAS_ACCOUNT_ID).")");
		//echo $cmd->Text."<br>";
        $res = $cmd->queryAll();
        if (empty($res)) {
			$this->setExceptionMessage('没有可更新的listing');
        	return false;
        }
        try {
	        foreach ($res as $v) {
	        	$ebayProductNew = new EbayProduct();
	        	$ebayProductNew->updateItemSimpleInfo($v['item_id'],$v['account_id']);
	        }
	        return true;
        } catch (Exception $e) {
        	$this->setExceptionMessage('更新listing时异常退出');
        	return false;
        }
    }

    /**
     * @desc 更新sku的价格
     * 	限制条件：
              对象：UK站点，海外仓（4PX、乐宝、万邑通）的listing价格
              规则：1.如果 海外仓（4PX、乐宝、万邑通）的总库存<=10，那么累计销量最高的listing价格不变，
                       其他的英国站listing价格+20英镑；
 					2.如果 海外仓（4PX、乐宝、万邑通）的总库存>10，即通过调库存，总库存超过10，
 					    那么恢复原来调高的英国站listing价格到原价，即-20英镑；
     * @param  integer $accountID 
     * @param  int     $siteID 
     * @param  string  $itemID    
     * @param  string  $onlineSku
     * @param  array   $locArr
     * @param  boolean $isUpdatePrice
     * @return boolean            
     */
    public function updateSkuPrice($accountID,$siteID=null,$itemID=null,$onlineSku=null,$locArr=array(),$isUpdatePrice=true) {
    	$isUniqueItems = array();
		$nowTime = date("Y-m-d H:i:s");
		$bestSaleID = $this->getBestSaleListing(null,$siteID,$locArr);//累计销量最好的itemid
        $ebayProductModel = EbayProduct::model();
        $ebayProductVariantModel = EbayProductVariation::model();
        $cmd = $ebayProductVariantModel->getDbConnection()->createCommand()
                                ->select("m.id,m.stock_qty,m.listing_price_old,m.price,
		                                	t.item_id,t.sku_online,t.sku,t.sale_price,p.item_status")
                                ->from($ebayProductModel->tableName() . " p")
                                ->join($ebayProductVariantModel->tableName() . " as t", "p.id=t.listing_id")
                                ->join($this->tableName() . " as m", "m.item_id=t.item_id and m.online_sku=t.sku_online")
                                ->where("t.account_id='{$accountID}'")
                                ->andWhere("m.status=0")
                                ->andWhere("p.listing_type in('FixedPriceItem', 'StoresFixedPrice')");
        if ($siteID !== '' && $siteID !== null) {
        	$cmd->andWhere("p.site_id='{$siteID}'");
        }        
        if($itemID){
            $cmd->andWhere("t.item_id = '{$itemID}'");
        }
        if ($onlineSku) {
            $cmd->andWhere("t.sku_online = '{$onlineSku}'");
        }
        if (!empty($locArr)) {
        	$cmd->andWhere("p.location in('". implode("','", $locArr) ."')");
        }
        $res = $cmd->queryAll();
    	if (empty($res)) {
    		$this->setExceptionMessage('没有可更新的price记录');
    		return false;
    	}
    	try {
	     	foreach ($res as $v) {
				$row                      = array();
				$sku                      = $v['sku'];
				$itemStatus               = $v['item_status'];//listing在线状态
				$stockQty                 = $v['stock_qty'];//总可用库存
				$listingPrice             = $v['sale_price'];//当前listing价格
				$pricePriceOld            = $v['listing_price_old'] == 0 ? $listingPrice : $v['listing_price_old'];//上次listing价格
				$row['listing_price']     = $listingPrice;
				$row['listing_price_old'] = $pricePriceOld;				
				if ($itemStatus != 1) {//listing已下架，则不修改价格				
					$row['price']  = $listingPrice;//保持当前价格
					$row['status'] = 3;//无需改价
					$row['note']   = 'listing已下架';
				} else {
					//加价逻辑：海外仓（4PX、乐宝、万邑通）的总库存<=10，累计销量最高的listing价格不变，
					//          其他的英国站listing价格+20英镑
					//恢复原价逻辑：当前listing价格(C),上次listing价格 (L), 
					//          差值(D) = 当前在线listing价格 (N) - 20 ;  如果D <= L , 则 C= L, 否则 C=D. 
					//一个sku对应只有一条listing或属于清仓sku不修改价格
					$ebayClearanceProductSkus = self::getEbayClearanceProductForUK();
					if ( in_array($sku, $ebayClearanceProductSkus ) ) {
						$row['price']  = $listingPrice;//保持当前价格
						$row['status'] = 3;
						$row['note']   = '清仓sku,不需要修改价格';
					} else {
						$isUniqueItem = false;
						if ( in_array($sku,$isUniqueItems) ) {
							$isUniqueItem = true;
						} else {
							$itemidArr = EbayProductPriceManage::model()->checkItemIDs($sku,$siteID,$locArr);
							if (!empty($itemidArr) && count($itemidArr) == 1 ) {
								$isUniqueItem = true;
							}
						}
						if ( $isUniqueItem ) {
							$isUniqueItems[] = $sku;
							$row['price']  = $listingPrice;//保持当前价格
							$row['status'] = 3;
							$row['note']   = $sku.'只有一条listing '. implode(',',$itemidArr) .',不需要修改价格';
						} else {
							$isAddedPrice = $v['listing_price_old'] > 0 && $listingPrice > $pricePriceOld;//是否加过价
							if ( $isAddedPrice ) {//加过价
								if ( $stockQty <= 10 ) {//action:加价
									$row['price']  = $listingPrice;//保持当前价格
									$row['status'] = 3;
									$row['note']   = '已经加过价,不需要改价';
								} else {//action:恢复原价
									$newPrice = $listingPrice - 20;
									if ( $newPrice <= $pricePriceOld ) {
										$newPrice = $pricePriceOld;
									}
									$row['price']  = $newPrice;
									$row['status'] = 0;
									$row['note']   = '新价格:'.$newPrice;
								}
							} else {//未加过价
								if ( $stockQty <= 10 ) {//action:加价
									if ($v['item_id'] != $bestSaleID) {
										$row['price']  = $listingPrice + 20;//保持当前价格
										$row['status'] = 0;
										$row['note']   = '加价20,bestSaleID:'.$bestSaleID;
									} else {
										$row['price']  = $listingPrice;
										$row['status'] = 3;
										$row['note']   = '销量最好的listing不需改价';
									}
								} else {//action:恢复原价
									$row['price']  = $listingPrice;//保持当前价格
									$row['status'] = 3;
									$row['note']   = '未加过价,不需要恢复原价';
								}
							}
						}
					}
				}
				if ($isUpdatePrice) {
					$row['updated_price_at'] = $nowTime;
				}
				$this->getDbConnection()->createCommand()->update($this->tableName(),$row,'id='.$v['id']);
	    	}
	    	return true;
    	} catch (Exception $e) {
    		$this->setExceptionMessage('更新的count记录时异常退出');
    		return false;
    	}
    }

    /**
     * @desc 售出后补库存
     * @param  integer $accountID 
     * @param  int     $siteID 
     * @param  string $itemID    
     * @param  string $onlineSku
     * @param  array $locArr
     * @return boolean   
     */
    public function startModifyPrice($accountID,$siteID=null,$itemID=null,$onlineSku=null,$locArr=array()) {
    	try {
    		$nowTime = date("Y-m-d H:i:s");
    		$calcTime = date("Y-m-d H:i:s", time() - 5*60);//北京时间，5分钟内有更新listing的进行操作
    		//1.准备数据
    		$flag = $this->prepareData($accountID,$siteID,$itemID,$onlineSku,$locArr);
    		if (!$flag) {
	    		echo $this->getExceptionMessage()."<br>";
	    		return false;
    		}
    		//2.更新listing数据
    		$flag = $this->updateItemSimpleInfo($accountID,$siteID,$itemID,$onlineSku,$locArr);
    		if (!$flag ) {
    			echo $this->getExceptionMessage()."<br>";
	    		return false;
    		}
    		//3.更新price
    		$flag = $this->updateSkuPrice($accountID,$siteID,$itemID,$onlineSku,$locArr,true);
    		if (!$flag) {
	    		echo $this->getExceptionMessage()."<br>";
	    		return false;
    		}
    		//4.改价
	        $cmd = $this->getDbConnection()->createCommand()
	                                ->select("id,item_id,online_sku,price,note")
	                                ->from($this->tableName())
	                                ->where("account_id='{$accountID}'")
		                            ->andWhere("status=0")
		                            ->andWhere("listing_price>0 ")
		                            ->andWhere("price>0")
		                            ->andWhere("updated_price_at>'{$calcTime}'");
	        if($itemID){
	            $cmd->andWhere("item_id = '{$itemID}'");
	        }
	        if ($onlineSku) {
	            $cmd->andWhere("online_sku = '{$onlineSku}'");
	        }
	        $res = $cmd->queryAll();
	    	if (empty($res)) {
	    		return false;
	    	}
	    	foreach ($res as $v) {
	    		$data = array('item_id'=>$v['item_id'],'sku_online'=>$v['online_sku'],'price'=>$v['price']);
	    		$res2 = EbayProduct::model()->reviseEbayListing($accountID, $data);
	    		$status = $res2['errorCode'] == 200 ? 1 : 2;
	    		$note = $res2['errorCode'] != 200 ? $res2['errorMsg'] : 'ok';
				$this->getDbConnection()->createCommand()->update($this->tableName(),array('modified_at'=>$nowTime, 'status'=>$status, 'note'=>$note.'@'.$v['note']),'id='.$v['id'] );
	    	}
	    	return true;
    	} catch (Exception $e) {
    		echo $e->getMessage()."<br>";
    		$this->setExceptionMessage('售出补库存时出现异常');
    		return false;
    	}
    }

}