<?php

class JdProduct extends JdModel {
	const EVENT_GET_PRODUCT = 'get_product';
	//1.在售中,2.仓库中,3违规,4.删除
	const WARE_STATUS_ON_SALE = 1;
	const WARE_STATUS_ON_STORE = 2;
	const WARE_STATUS_VIOLATION = 3;
	const WARE_STATUS_DELETE = 4;
	
	public $oprator = '';
	public $ware_status_text;
	public $account_name;
	public $detail;
	public $supply_price;
	public $sub_sku;
	
	private static $_accountList = array();
	
	private $_errMsg = array();
	public function tableName(){
		return 'ueb_jd_product';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
	
	public function setErrorMsg($message){
		$this->_errMsg[] = $message;
	}
	public function getErrorMsg($glue = "<br/>"){
		return implode($glue, $this->_errMsg);
	}
	
	public function saveProductData($accountId, $datas){
		$skuEncrypt = new encryptSku();
		$queryWareSkuRequest = new QueryWareSkuRequest;
		$getWareRequest = new GetWareRequest;
		$this->_errMsg = array();
		foreach ($datas as $data){
			$wareId = $data['wareId'];
			//获取当前产品下面对应的所有sku信息
			$queryWareSkuRequestNum = 0;
			$queryWareSkuRequestMaxNum = 4;
			$lastQueryResult = false;
			do{
				$queryWareSkuRequest->setAccount($accountId);
				$queryWareSkuRequest->setWareId($wareId);
				$response = $queryWareSkuRequest->setRequest()->sendRequest()->getResponse();
				if($queryWareSkuRequest->getIfSuccess()){
					$lastQueryResult = true;
					$queryWareSkuRequestNum = $queryWareSkuRequestMaxNum;
				}else{
					$lastQueryResult = false;
					++$queryWareSkuRequestNum;
					$this->setErrorMsg($wareId . ":{$queryWareSkuRequestNum}th>>". $queryWareSkuRequest->getErrorMsg());
				}
			}while($queryWareSkuRequestNum < $queryWareSkuRequestMaxNum);
			if($lastQueryResult){
				$response = json_decode(json_encode($response), true);

				if(!isset($response['jingdong_ept_warecenter_outapi_waresku_query_responce']['queryskuinfo_result']['skuList'])){
					$this->setErrorMsg($wareId . ":Not Found SKU");
					continue;
				}
				$skuList = $response['jingdong_ept_warecenter_outapi_waresku_query_responce']['queryskuinfo_result']['skuList'];
				if(empty($skuList)) {
					$this->setErrorMsg($wareId . ":Not Found SKU");
					continue;
				}
			}else{
				$this->setErrorMsg($wareId . ":Not Found SKU");
				continue;
			}
			
			//取出第一个进行主sku查找
 			$skuList = $skuList[0]['skus'];
// 			$sku = $skuList[0]['skuId'];
// 			$localSku = $skuEncrypt->getRealSku($sku);
// 			//查找对应的sku信息
// 			$productInfo = Product::model()->getDbConnection()
// 							->createCommand()
// 							->from(Product::tableName())
// 							->select('id,product_is_multi')
// 							->where('sku=:sku', array(':sku'=>$localSku))->queryRow();
// 			if(empty($productInfo)) {
// 				/* $this->setErrorMsg($wareId . ":Not Found the Product Info");
// 				continue; */
// 			}
// 			if($productInfo && $productInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_VARIATION){
// 				$productSelAttr = new ProductSelectAttribute;
// 				$localParentSku = $productSelAttr->getMainSku($productInfo['id'], $localSku);
// 			}else{
// 				$localParentSku = $localSku;
// 			}
			$sku = isset($data['itemNum'])?$data['itemNum']:0;
			$localParentSku = $skuEncrypt->getRealSku($sku);
			$productData = array(
								'account_id'		=>	$accountId,
								'ware_id'			=>	$wareId,
								'sku'				=>	$localParentSku,
								'title'				=>	$data['title'],
								'min_supply_price'	=>	$data['minSupplyPrice'],
								'max_supply_price'	=>	$data['maxSupplyPrice'],
								'item_num'			=>	isset($data['itemNum'])?strlen($data['itemNum'])>40?substr($data['itemNum'],0,40):$data['itemNum']:0,
								'online_time'		=>	isset($data['onlineTime']) && date("Y-m-d H:i:s", $data['onlineTime']/1000) != '1970-01-01 08:00:00' ? date("Y-m-d H:i:s", $data['onlineTime']/1000) : '0000-00-00 00:00:00',
								'transport_id'		=>	isset($data['transportId'])?$data['transportId']:0,
								'ware_status'		=>	isset($data['wareStatus'])?$data['wareStatus']:0,
								'recommend_tpid'	=>	isset($data['recommendTpid']) ? $data['recommendTpid'] : 0,
								'update_lasttime'	=>	isset($data['update_lasttime']) ? $data['update_lasttime'] : date("Y-m-d H:i:s"),
						);
			//获取详细表
			$extendData = array();
			$wareSkuList = array();
			$getwareSkuRepeatNum = 0;
			$getwareSkuRepeatMaxNum = 4;
			$getWareRequest->setAccount($accountId);
			$getWareRequest->setWareId($wareId);
			do{
				$wareInfo = $getWareRequest->setRequest()->sendRequest()->getResponse();
				$wareInfo = json_decode(json_encode($wareInfo), true);
				++$getwareSkuRepeatNum;
				if($getWareRequest->getIfSuccess()){
					if(!empty($wareInfo['jingdong_ept_warecenter_ware_get_responce']['getwareinfobyid_result'])){
						$getwareSkuRepeatNum = $getwareSkuRepeatMaxNum;
						$wareInfoResulte 			= $wareInfo['jingdong_ept_warecenter_ware_get_responce']['getwareinfobyid_result'];
						$productData['weight'] 		= isset($wareInfoResulte['weight'])?$wareInfoResulte['weight']:0;
						$productData['net_weight'] 	= isset($wareInfoResulte['netWeight'])?$wareInfoResulte['netWeight']:0;
						$productData['cubage']		= isset($wareInfoResulte['cubage'])?$wareInfoResulte['cubage']:'';
						$productData['brand_id'] 	= isset($wareInfoResulte['brandId'])?$wareInfoResulte['brandId']:0;
						$productData['delivery_days'] = isset($wareInfoResulte['deliveryDays'])?$wareInfoResulte['deliveryDays']:0;
						$productData['stock'] 		= isset($wareInfoResulte['stock'])?$wareInfoResulte['stock']:0;
						$productData['offline_time']	= isset($wareInfoResulte['offlineTime'])?date("Y-m-d H:i:s", $wareInfoResulte['offlineTime']/1000):'0000-00-00 00:00:00';
						$productData['pack_long'] 	= isset($wareInfoResulte['packLong'])?$wareInfoResulte['packLong']:0;
						$productData['pack_wide'] 	= isset($wareInfoResulte['packWide'])?$wareInfoResulte['packWide']:0;
						$productData['pack_height'] = isset($wareInfoResulte['packHeight'])?$wareInfoResulte['packHeight']:0;
						$productData['pack_info'] 	= isset($wareInfoResulte['packInfo'])?$wareInfoResulte['packInfo']:'';
						$productData['category_id'] = isset($wareInfoResulte['categoryId'])?$wareInfoResulte['categoryId']:0;
						$productData['custom_tpid'] = isset($wareInfoResulte['customTpid'])?$wareInfoResulte['customTpid']:0;
						$productData['imguri'] 		= isset($wareInfoResulte['imgUri'])?$wareInfoResulte['imgUri']:'';
						$productData['attributes'] 	= isset($wareInfoResulte['attributes'])?$wareInfoResulte['attributes']:'';
						$productData['category_id'] = isset($wareInfoResulte['categoryId'])?$wareInfoResulte['categoryId']:0;
						$extendData = array(
								'keywords'		=>	isset($wareInfoResulte['keywords'])?$wareInfoResulte['keywords']:'',
								'description'	=>	isset($wareInfoResulte['description'])?$wareInfoResulte['description']:'',
						);
						if(!empty($wareInfoResulte['wareSkus'])){
							foreach ($wareInfoResulte['wareSkus'] as $_waresku){
								$wareSkuList[$_waresku['skuId']] = $_waresku['rfId'];
							}
						}
					}
				}
			}while($getwareSkuRepeatNum < $getwareSkuRepeatMaxNum);
			//保存或者更新主表数据
			$checkExists = $this->find('ware_id=:ware_id', array(':ware_id'=>$wareId));
			$addId = 0;
			if($checkExists){//update
				$this->getDbConnection()->createCommand()->update($this->tableName(), $productData, 'id=:id', array(':id'=>$checkExists->id));
				$addId = $checkExists->id;
			}else{//add
				$this->getDbConnection()->createCommand()->insert($this->tableName(), $productData);
				$addId = $this->getDbConnection()->lastInsertID;
			}
			if($extendData){
				$extendData['listing_id'] = $addId;
				$checkExtend = self::model('JdProductExtend')->find('listing_id=:listing_id', array(':listing_id'=>$addId));
				if($checkExtend){
					$this->getDbConnection()->createCommand()
										->update(self::model('JdProductExtend')->tableName(), $extendData, 'id=:id', array(':id'=>$checkExtend->id));
				}else{
					$this->getDbConnection()->createCommand()->insert(self::model('JdProductExtend')->tableName(), $extendData);
				}
			}
			//保存或者更新从表数据
			if($skuList){
				foreach ($skuList as $variant){
					$onlineSku = isset($wareSkuList[$variant['skuId']])?$wareSkuList[$variant['skuId']]:$sku;
					$skuId = isset($variant['skuId'])?$variant['skuId']:0;
					$variantData = array(
										'listing_id'	=>	$addId,
										'account_id'	=>	$accountId,
										'parent_sku'	=>	$localParentSku,
										'sku'			=>	$skuEncrypt->getRealSku($onlineSku),
										'online_sku'	=>	$onlineSku,
										'sku_id'		=>	$skuId,
										'ware_id'		=>	$variant['wareId'],
										'stock'			=>	$variant['stock'],
										'sale_stock_amount'	=>	isset($variant['saleStockAmount'])?$variant['saleStockAmount']:0,
										'amount_count'		=>	isset($variant['amountCount'])?$variant['amountCount']:0,
										'supply_price'		=>	isset($variant['supplyPrice'])?$variant['supplyPrice']:0,
										'imguri'			=>	isset($variant['imgUri'])?$variant['imgUri']:'',
										'lock_count'		=>	isset($variant['lockCount'])?$variant['lockCount']:0,
										'lock_start_time'	=>	isset($variant['lockStartTime'])?date("Y-m-d H:i:s", $variant['lockStartTime']/1000):'0000-00-00 00:00:00',
										'lock_end_time'		=>	isset($variant['lockEndTime'])?date("Y-m-d H:i:s", $variant['lockEndTime']/1000):'0000-00-00 00:00:00',
										'attributes'		=>	isset($variant['attributes'])?$variant['attributes']:'',
										'status'			=>	isset($variant['status'])?$variant['status']:0,
										'hscode'			=>	isset($variant['hsCode'])?$variant['hsCode']:'',
									);
					$variantInfo = self::model('JdProductVariant')->find('sku_id=:sku_id AND listing_id=:id AND account_id=:account_id', array(':sku_id'=>$skuId,':id'=>$addId, ':account_id'=>$accountId));
					if($variantInfo){
						//update
						self::model('JdProductVariant')->getDbConnection()
														->createCommand()
														->update(JdProductVariant::tableName(), $variantData, 'id=:id', array(':id'=>$variantInfo->id));
					}else{
						//add
						self::model('JdProductVariant')->getDbConnection()
														->createCommand()
														->insert(JdProductVariant::tableName(), $variantData);
					}
				}
			}
			
		}
		flush();
	}
	/**
	 * @desc 上架
	 * @param unknown $wireIds
	 * @param unknown $accountID
	 * @return boolean|Ambigous <multitype:multitype: , string>
	 */
	public function shelveWare($wireIds, $accountID){
		if(empty($wireIds)) return false;
		if(!is_array($wireIds))
			$wireIds = array($wireIds);
		$shelveWareRequest = new ShelveWareRequest;
		$result = array(
							'success'	=>	array(),
							'fail'		=>	array(),
							'errorMsg'	=>	array(),
						);
		foreach ($wireIds as $wareId){
			$shelveWareRequest->setAccount($accountID);
			$shelveWareRequest->setWareId($wareId);
			$reponse = $shelveWareRequest->setRequest()->sendRequest()->getResponse();
			if($shelveWareRequest->getIfSuccess()){
				if($reponse->jingdong_ept_warecenter_ware_shelve_response->result->success){
					$result['success'][] = $wareId;
					continue;
				}
			}
			$result['fail'][] = $wareId;
			$result['errorMsg'][$wareId] = $shelveWareRequest->getErrorMsg();
		}
		//更新本地数据
		if($result['success']){
			$this->getDbConnection()->createCommand()
									->update($this->tableName(), 
										array('ware_status'=>self::WARE_STATUS_ON_SALE), 
										array('IN', 'ware_id', $result['success']));
		}
		return $result;
	}
	/**
	 * @desc 下架
	 * @param unknown $wareIds
	 * @param unknown $accountID
	 * @return boolean|Ambigous <multitype:multitype: , string>
	 */
	public function unshelveWare($wareIds, $accountID){
		if(empty($wareIds)) return false;
		if(!is_array($wareIds))
			$wareIds = array($wareIds);
		$shelveWareRequest = new UnshelveWareRequest;
		$result = array(
				'success'	=>	array(),
				'fail'		=>	array(),
				'errorMsg'	=>	array(),
		);
		foreach ($wareIds as $wareId){
			$shelveWareRequest->setAccount($accountID);
			$shelveWareRequest->setWareId($wareId);
			$reponse = $shelveWareRequest->setRequest()->sendRequest()->getResponse();
			if($shelveWareRequest->getIfSuccess()){
				if($reponse->jingdong_ept_warecenter_ware_unshelve_responce->result->success){
					$result['success'][] = $wareId;
					continue;
				}
			}
			$result['fail'][] = $wareId;
			$result['errorMsg'][$wareId] = $shelveWareRequest->getErrorMsg();
		}
		//更新本地数据
		if($result['success']){
			$this->getDbConnection()->createCommand()
								->update($this->tableName(),
										array('ware_status'=>self::WARE_STATUS_ON_STORE),
										array('IN', 'ware_id', $result['success'])
										);
		}
		return $result;
	}

	/**
	 * @desc 删除掉商品
	 * @param unknown $wareIds
	 * @param unknown $accountID
	 * @return boolean|Ambigous <multitype:multitype: , string>
	 */
	public function deleteWare($wareIds, $accountID){
		if(empty($wareIds)) return false;
		if(!is_array($wareIds))
			$wareIds = array($wareIds);
		$delWareRequest = new DeleteWareRequest;
		$result = array(
				'success'	=>	array(),
				'fail'		=>	array(),
				'errorMsg'	=>	array(),
		);
		foreach ($wareIds as $wareId){
			$delWareRequest->setAccount($accountID);
			$delWareRequest->setWareId($wareId);
			$reponse = $delWareRequest->setRequest()->sendRequest()->getResponse();
			if($delWareRequest->getIfSuccess()){
				if($reponse->jingdong_ept_warecenter_ware_delete_responce->result->success){
					$result['success'][] = $wareId;
					continue;
				}
			}
			$result['fail'][] = $wareId;
			$result['errorMsg'][$wareId] = $delWareRequest->getErrorMsg();
		}
		//更新本地数据
		if($result['success']){
			$this->getDbConnection()->createCommand()
			->update($this->tableName(),
					array('ware_status'=>self::WARE_STATUS_DELETE),
					array('IN', 'ware_id', $result['success'])
			);
		}
		return $result;
	}
	// =================== Start:Jd Product Search Part =================
	
	public function search(){
		$sort = new CSort();
		$sort->attributes = array(
							'defaultOrder'=>'id'
					);
		$cdbCriteria = $this->_setCriteria();
		$dataProvider = parent::search($this, $sort, '', $cdbCriteria);
		$data = $this->_additions($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}
	private function _setCriteria(){
		$cdbCriteria = new CDbCriteria();
		$cdbCriteria->select = "t.*";
		//$cdbCriteria->group = "t.sku";
		$variantCon = array();
		$variantParam = array();
		if(isset($_REQUEST['online_sku']) && $_REQUEST['online_sku']){
			$variantCon[] = 'online_sku=:online_sku';
			$variantParam[':online_sku'] = $_REQUEST['online_sku'];
		}
		if(isset($_REQUEST['sub_sku']) && $_REQUEST['sub_sku']){
			$variantCon[] = 'sku=:sku';
			$variantParam[':sku'] = $_REQUEST['sub_sku'];
		}
		
		if($variantCon){
			//减少获取数据量，优先把排除条件加入
			if(isset($_REQUEST['sku']) && $_REQUEST['sku']){
				$variantCon[] = 'parent_sku=:parent_sku';
				$variantParam[':parent_sku'] = $_REQUEST['sku'];
			}
			if(isset($_REQUEST['account_id']) && $_REQUEST['account_id']){
				$variantCon[] = 'account_id=:account_id';
				$variantParam[':account_id'] = $_REQUEST['account_id'];
			}
			$conditions = implode(" AND ", $variantCon);
			$listingIds = $this->getDbConnection()->createCommand()
									->from(JdProductVariant::tableName())
									->where($conditions, $variantParam)
									->select('listing_id')
									->queryAll();
			if($listingIds){
				$idstr = '';
				foreach ($listingIds as $id){
					$idstr .= $id['listing_id'].",";
				}
				$idstr = trim($idstr, ",");
				$cdbCriteria->addCondition("id IN({$idstr})");
			}else{
				$cdbCriteria->addCondition("1=0 and id=-1");
			}
		}
		return $cdbCriteria;
	}
	/**
	 * @desc 添加额外数据
	 * @param unknown $datas
	 * @return unknown
	 */
	public function _additions($datas){
		if(empty($datas)) return $datas;
		foreach ($datas as &$data){
			$accountList = $this->getAccountPairsList();
			//账号
			$data->account_name = isset($accountList[$data['account_id']])?$accountList[$data['account_id']]:'';
			//状态
			$data->ware_status_text = $this->getWareStatusText($data['ware_status']);
			$data->supply_price = "min:".$data['min_supply_price']."<br/>".
								  "max:".$data['max_supply_price'];
			$data->oprator = $this->getOpratorText($data['id'], $data['ware_status']);
			//操作栏
			$data->detail = array();
			
			//根据sku获取列表
			$skuList = $this->getVariantListByParentSku($data['sku'], $data['id']);
			if(!$skuList){
				continue;
			}
			foreach ($skuList as $variant){
				$variant['account_name'] = isset($accountList[$variant['account_id']])?$accountList[$variant['account_id']]:'';
				$variant['sub_sku'] = $variant['sku'];
				$variant['is_stock'] = $variant['stock']?"Y":"N";
				$data->detail[] = $variant;
			}
		}
		return $datas;
	}
	
	public function filterOptions(){
		return array(
				array(
						'name'		=>	'sku',
						'type'		=>	'text',
						'search'	=>	'=',
						'htmlOption'=>	array(
											'size'=>22		
											)
					),
				array(
						'name'		=>	'online_sku',
						'type'		=>	'text',
						'search'	=>	'=',
						'rel'		=>	true,
						'htmlOption'=>	array(
								'size'=>22
						)
				),
				array(
						'name'		=>	'sub_sku',
						'type'		=>	'text',
						'search'	=>	'=',
						'rel'		=>	true,
						'htmlOption'=>	array(
								'size'=>22
						)
				),
				array(
						'name'	=>	'account_id',
						'type'	=>	'dropDownList',
						'search'=>	'=',
						'data'	=>	$this->getAccountPairsList()
					),
				array(
						'name'	=>	'ware_status',
						'type'	=>	'dropDownList',
						'search'=>	'=',
						'data'	=>	$this->getWareStatusOptions()		
					),
				
		);
	}
	/**
	 * @desc 根据主sku获取子sku
	 * @param unknown $parentSku
	 */
	public function getVariantListByParentSku($parentSku, $listingId){
		$conditions = null;
		$variantCon = array();
		$variantParam = array();
		$variantCon[] = 'listing_id=:listing_id';
		$variantParam[':listing_id'] = $listingId;
		if(isset($_REQUEST['online_sku']) && $_REQUEST['online_sku']){
			$variantCon[] = 'online_sku=:online_sku';
			$variantParam[':online_sku'] = $_REQUEST['online_sku'];
		}
		if(isset($_REQUEST['sub_sku']) && $_REQUEST['sub_sku']){
			$variantCon[] = 'sku=:sku';
			$variantParam[':sku'] = $_REQUEST['sub_sku'];
		}
		if($variantCon){
			$conditions = implode(" AND ", $variantCon);
		}
		$skuList = self::model('JdProductVariant')->getVariantListByParentSku($parentSku, $conditions, $variantParam);
		return $skuList;
	}
	/**
	 * @desc 获取账户信息
	 * @return multitype:
	 */
	public function getAccountPairsList(){
		if(!self::$_accountList){
			self::$_accountList = self::model("JdAccount")->getAccountPairs();	
		}
		return self::$_accountList;
	}
	public function attributeLabels(){
		return array(
					'sku'	=>	Yii::t('jd_product', 'SKU'),
					'account_id'	=>	Yii::t('jd_product', 'Account Name'),
					'ware_status'	=>	Yii::t('jd_product', 'Ware Status'),
					'oprator'		=>	Yii::t('system', 'Oprator'),
					'title'			=>	Yii::t('system', 'Title'),
					'account_name'	=>	Yii::t('jd_product', 'Account Name'),
					'online_sku'	=>	Yii::t('jd_product', 'Online SKU'),
					'amount_count'	=>	Yii::t('jd_product', 'Amount Count'),
					'stock'			=>	Yii::t('jd_product', 'Stock'),
					'is_stock'		=>	Yii::t('jd_product', 'Has Stock'),
					'sale_stock_amount'	=>	Yii::t('jd_product', 'Sale Stock Amount'),
					'lock_count'		=>	Yii::t('jd_product', 'Lock Count'),
					'lock_start_time'	=>	Yii::t('jd_product', 'Lock Start Time'),
					'lock_end_time'		=>	Yii::t('jd_product', 'Lock End Time'),
					'supply_price'		=>	Yii::t('jd_product', 'Supply Price'),
					'sub_sku'			=>	Yii::t('jd_product', 'Sub SKU'),
					'ware_id'			=>	Yii::t('jd_product', 'Ware ID'),
					'item_num'			=>	Yii::t('jd_product', 'Item Num'),
				);
	}
	/**
	 * @desc 获取操作文本
	 * @param unknown $listingId
	 * @param unknown $wareStatus
	 * @return string
	 */
	public function getOpratorText($listingId, $wareStatus){
		$str = "<select style='width:75px;' onchange = 'offLine(this,".$listingId.")' >
				<option>".Yii::t('system', 'Please Select')."</option>";
		if($wareStatus == self::WARE_STATUS_ON_SALE){
			$str .= '<option value="offline">'.Yii::t('wish_listing', 'Product Disabled').'</option>';
		}
		$str .="</select>";
		return $str;
	}
	/**
	 * @desc 获取商品状态选项列表
	 * @return multitype:NULL Ambigous <string, string, unknown>
	 */
	public function getWareStatusOptions(){
		return array(
				self::WARE_STATUS_DELETE => Yii::t('jd_product', 'Ware Status Delete'),
				self::WARE_STATUS_ON_SALE => Yii::t('jd_product', 'Ware Status on Sale'),
				self::WARE_STATUS_ON_STORE => Yii::t('jd_product', 'Ware Status on Store'),
				self::WARE_STATUS_VIOLATION => Yii::t('jd_product', 'Ware Status Violation')
				
		);
	}
	/**
	 * @desc  获取商品状态文案
	 * @param unknown $wareStatus
	 * @return string
	 */
	public function getWareStatusText($wareStatus){
		$color = "red";
		$msg = "";
		switch ($wareStatus){
			case self::WARE_STATUS_DELETE:
				$msg = Yii::t('jd_product', 'Ware Status Delete');
				break;
			case self::WARE_STATUS_ON_SALE:
				$color = "green";
				$msg = Yii::t('jd_product', 'Ware Status on Sale');
				break;
			case self::WARE_STATUS_ON_STORE:
				$color = "blue";
				$msg = Yii::t('jd_product', 'Ware Status on Store');
				break;
			case self::WARE_STATUS_VIOLATION:
				$msg =  Yii::t('jd_product', 'Ware Status Violation');
				break;
		}
		return "<font color='{$color}'>{$msg}</font>";
	}
	// =================== End:Jd Product Search Part ==================
	
	/**
	 * 获取在线listing
	 * @param unknown $sku
	 * @param unknown $accountID
	 * @return Ambigous <multitype:, mixed>
	 */
	public function getOnlineListingBySku($sku, $accountID = null) {
		$command = $this->getDbConnection()->createCommand()
		->from(self::tableName() . " t")
		->select("t.*, t1.sku")
		->join(JdProductVariant::tableName() . " as t1", "t.id = t1.listing_id")
		->where("t1.sku = :sku", array(':sku' => $sku));
		if (!is_null($accountID))
			$command->andWhere("t.account_id = :account_id", array(':account_id' => $accountID));
		return $command->queryAll();		
	}
}
?>