<?php
class AmazonList extends UebModel {
	/**
	 *  已取消商品状态
	 * @var unknown
	 */
	const SELLER_STATUS_CANCEL = 3;
	/**
	 * 亚马逊发货（下架和上架）FBA
	 * @var unknown
	 */
	const SELLER_STATUS_FULFILLMENT_AMAZON = 4;
	/**
	 * 销售状态，下架状态为2
	 * @var unknown
	 */
	const SELLER_STATUS_OFFLINE = 2;
	/**
	 * 销售状态，上架状态为1
	 * @var unknown
	 */
	const SELLER_STATUS_ONLINE = 1;

	/**
	 * 配送状态，卖家配送（FBM，默认为1）
	 * @var unknown
	 */
	const FULFILLMENT_STATUS_MERCHANT = 1;

	/**
	 * 配送状态，amazon配送（FBA）
	 * @var unknown
	 */
	const FULFILLMENT_STATUS_AMAZON = 2;
	
	const EVENT_NAME = 'getorder';
	const EVENT_DETECT_SUBMISSION_NAME = 'detect_offline_submission';//检测下架时提交的submission状态事件
	
	/** @var object 拉单返回信息*/
	public $orderResponse = null;
	
	/** @var int 账号ID*/
	public $_accountID = null;
	
	/** @var string 异常信息*/
	public $exception = null;
	
	/** @var int 日志编号*/
	public $_logID = 0;
	
	public $_errorMsg;
	public $detail = null;
	public $account_name = null;
	public $opreator = null;
	public $seller_status_text = null;
	public $fulfillment_type_text = null;
	public $seller_name;
	public $send_warehouse;
	public $title;
	public $product_weight;
	public $product_cost;
	public $available_qty;
	public $product_img;
	public $currency_code;
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName() {
		return 'ueb_amazon_listing';
	}
	
	public function getDbKey() {
		return 'db_amazon';
	}

    /**
     * @desc filterByCondition
     * @param  string $fields 
     * @param  [type] $where 
     * @return [type]  
     */
    public function filterByCondition($fields="*",$where) {
        $res = $this->dbConnection->createCommand()
                    ->select($fields)
                    ->from($this->tableName())
                    ->where($where)
                    ->queryAll();
        return $res;
    }	

	/**
	 * @desc 保存产品数据
	 * @param unknown $datas
	 * @param string $isCancel
	 * @return boolean
	 */
	public function saveAmazonList($datas, $isCancel = false) {
		if (!$datas || !is_array($datas)) return false;
		$encryptSku = new encryptSku();
		foreach ($datas as $k=> $data) {
			// $dbTransaction = $this->getDbConnection()->getCurrentTransaction();
			// if (empty($dbTransaction))
			// 	$dbTransaction = $this->getDbConnection()->beginTransaction();
			// try {
	  		try{			
				$params = array();
				
	            $accountInfo = AmazonAccount::model()->getAccountInfoById($this->_accountID);
	            if(!$accountInfo) return false;
	            
	            //针对日本账号listing，切换不同的KEY来取值
				if ($accountInfo['country_code'] == 'jp'){
					//日本账号listing，没有提供相应的asin码，使用product_id代替
					$info = array();
					// $data = array_values($data);	//删除无法识别的键名
					foreach($data as $key=>$val){
						$key = mb_convert_encoding($key, "UTF-8","Shift_JIS");	//把日文编码转换识别
						$val = mb_convert_encoding($val, "UTF-8","Shift_JIS");
						$info[$key] = $val;
					}
					if ($info){
						$data = array();
						$data = $info;
					}
					$amazonListingID = $data['出品ID'];
					$sku = $encryptSku->getAmazonRealSku2($data['出品者SKU']);
					$params['account_id'] = $this->_accountID;

					if (isset($data['出品ID']))
						$params['amazon_listing_id'] = $data['amazon_listing_id'] = $data['出品ID'];	//出品ID
					if (isset($data['商品名']))
						$params['item_name'] = $data['item_name'] = (string)$data['商品名'];	//商品名
					if (isset($data['出品者SKU']))
						$params['seller_sku'] = $data['seller_sku'] = $data['出品者SKU'];	//出品者SKU
					if (isset($sku))
						$params['sku'] = $data['sku'] = $sku;
					if (isset($data['価格']))
						$price = 0;
						$price = (float)$data['価格'];	//価格
						if ($price > 1000000){
							$params['price'] = $data['price'] = 1000000;
						}else{
							$params['price'] = $data['price'] = $price;
						}
					if (isset($data['出品日']))
						$data['open_date'] = $this->setDateTimeFormat($data['出品日'],'jp');	//出品日
						if (strtotime($data['open_date'])) $params['open_date'] = $data['open_date'];
					if (isset($data['商品IDタイプ']))
						$params['product_id_type'] = $data['product_id_type'] = $data['商品IDタイプ'];	//商品IDタイプ
					if (isset($data['商品ID']))
						$params['asin1'] = $params['product_id'] = $data['product_id'] = (string)$data['商品ID'];	//商品ID
					if (isset($data['フルフィルメント・チャンネル']))
						$data['fulfillment-channel'] = trim(substr($data['フルフィルメント・チャンネル'], 0, 3));//フルフィルメント・チャンネル				
					if (isset($data['数量']))
						$data['quantity'] = (int)$data['数量'];	//数量
					if (isset($data['コンディション説明']))
						$data['item-description'] = (string)$data['コンディション説明'];	//コンディション説明									
				}else{
					$amazonListingID = (string)$data['listing-id'];
					$sku = $encryptSku->getAmazonRealSku2($data['seller-sku']);
					$params['account_id'] = $this->_accountID;
					if (isset($data['listing-id']))
						$params['amazon_listing_id'] = (string)$data['listing-id'];
					if (isset($data['item-name'])){
						//编码转换
						$encode = mb_detect_encoding($data['item-name'], array("ASCII","UTF-8","GB2312","GBK","BIG5","ISO-8859-1")); 
						if ($encode){
							$data['item-name'] = iconv($encode,"UTF-8",$data['item-name']);
						}else{
							$data['item-name'] = iconv("UTF-8","UTF-8//IGNORE",$data['item-name']);	//识别不了的编码就截断输出
						}
						$params['item_name'] = $data['item-name'];
					}
					if (isset($data['seller-sku']))
						$params['seller_sku'] = trim(addslashes((string)$data['seller-sku']));
					if (isset($sku))
						$params['sku'] = trim(addslashes((string)$sku));
					if (isset($data['price']))
						$price = 0;
						$price = (float)$data['price'];
						if ($price > 1000000){
							$params['price'] = 1000000;
						}else{
							$params['price'] = $price;
						}					
					if (isset($data['open-date']))
						$openDate = '';
						$openDate = $this->setDateTimeFormat($data['open-date']);
						if (strtotime($openDate)) $params['open_date'] = $openDate;					
					if (isset($data['image-url']))
						$params['image_url'] = (string)$data['image-url'];
					if (isset($data['item-is-marketplace']))
						$params['item_is_marketplace'] = (string)$data['item-is-marketplace'];
					if (isset($data['product-id-type']))
						$params['product_id_type'] = (int)$data['product-id-type'];
					if (isset($data['zshop-shipping-fee']))
						$params['zshop_shipping_fee'] = (float)$data['zshop-shipping-fee'];
					if (isset($data['item-condition']))
						$params['item_condition'] = (int)$data['item-condition'];
					if (isset($data['item-note'])){
						if($this->_accountID == 72){
							$itemNote = '';
						}else{
							$itemNote = trim($data['item-note']);
						}
						//mysql编码格式utf-8格式，不支持带四字节的字符串插入
			        	if ($itemNote != '' && preg_match('/[\x{10000}-\x{10FFFF}]/u',$itemNote) ) {
				            $itemNote = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $itemNote);
				        }
			        	$params['item_note'] = $itemNote;
			        }
					if (isset($data['zshop-category1']))
						$params['zshop_category1'] = (string)$data['zshop-category1'];
					if (isset($data['zshop-browse-path']))
						$params['zshop_browse_path'] = (string)$data['zshop-browse-path'];
					if (isset($data['zshop-storefront-feature']))
						$params['zshop_storefront_feature'] = (string)$data['zshop-storefront-feature'];											
					if (isset($data['asin1']))
						$params['asin1'] = (string)$data['asin1'];
					if (isset($data['asin2']))
						$params['asin2'] = (string)$data['asin2'];
					if (isset($data['asin3']))
						$params['asin3'] = (string)$data['asin3'];
					if (isset($data['product-id']))
						$params['product_id'] = (string)$data['product-id'];
						//d-fr法国账号，没有asin数据，但product_id可能就是asin数据
						if ($this->_accountID == 48 || $this->_accountID == 77 || $this->_accountID == 72){
							$params['asin1'] = $params['product_id'];
						}					
					if (isset($data['will-ship-internationally']))
						$params['will_ship_internationally'] = (int)$data['will-ship-internationally'];
					if (isset($data['expedited-shipping']))
						$params['expedited_shipping'] = (int)$data['expedited-shipping'];
					if (isset($data['zshop-boldface']))
						$params['zshop_boldface'] = (string)$data['zshop-boldface'];
					if (isset($data['bid-for-featured-placement']))
						$params['bid_for_featured_placement'] = (string)$data['bid-for-featured-placement'];
					if (isset($data['add-delete']))
						$params['add_delete'] = (string)$data['add-delete'];
					if (isset($data['pending-quantity']))
						$params['pending_quantity'] = (int)$data['pending-quantity'];
				}
				//判断配送状态
				if(isset($data['fulfillment-channel'])){
					$params['fulfillment_channel'] = trim(substr($data['fulfillment-channel'], 0, 3));
					if($params['fulfillment_channel'] == 'AMA'){
						$params['fulfillment_type'] = self::FULFILLMENT_STATUS_AMAZON;	//amazon配送状态(2)
					}else{
						$params['fulfillment_type'] = self::FULFILLMENT_STATUS_MERCHANT;	//卖家配送状态(默认1)
					}
				}
				//只针对卖家配送状态记录，更新销售状态（通过库存是否为0）和写入库存量
				//如果是amazon配送（FBA），因为库存量获取不到，暂时默认为上架状态。
				//在此接口不更新amazon配送的销售状态！另一接口获取FBA库存量和确认销售状态。
				if ($params['fulfillment_type'] == self::FULFILLMENT_STATUS_MERCHANT){
					if (isset($data['quantity']) && ((int)$data['quantity'] > 0)) {
						$params['quantity'] = $data['quantity'];
						$params['seller_status'] = self::SELLER_STATUS_ONLINE;
					}else{ 
						$params['seller_status'] = self::SELLER_STATUS_OFFLINE;
					}
				}

				$params['warehouse_id'] = 41;	//默认光明本地仓
				
                //通过listingID获取海外仓ID，并入库
                if (!empty($params['seller_sku']) && !empty($params['asin1'])){  
	                // $params['warehouse_id'] = 41;	默认光明本地仓
	                $warehouseInfo = AmazonAsinWarehouse::model()->getWarehouseInfoByAsin($params['asin1'],$params['seller_sku']);
	                if($warehouseInfo) $params['warehouse_id'] = (int)$warehouseInfo['overseas_warehouse_id'];	
                }			

				$time = date("Y-m-d H:i:s");
				$params['modify_time'] = $time;
				$params['confirm_status'] = 1;	//标识此在线listing接口数据有更新

				//检查该listing是否已经存在
	 			$amazonList = self::find("amazon_listing_id = :amazon_listing_id and account_id = :account_id", array(':amazon_listing_id' => $amazonListingID, ':account_id' => $this->_accountID));
				if (!empty($amazonList)) {
					$listingID = $amazonList->id;
					//如果存在更新数据
					$res = $this->getDbConnection()->createCommand()
							->update(self::tableName(), 
										$params, 
										"amazon_listing_id = :amazon_listing_id and account_id = :account_id", 
										array(':amazon_listing_id' => $amazonListingID, ':account_id' => $this->_accountID)
									);
					if (!$res)
						throw new Exception(Yii::t('amazon_product', 'Upload Product Info Failure'));
				} else {
					$params['create_time'] = $time;
					$res = $this->getDbConnection()->createCommand()->insert(self::model()->tableName(), $params);
					if (!$res){
						throw new Exception(Yii::t('amazon_product', 'Save Product Info Failure'));
					}
					$listingID = $this->getDbConnection()->getLastInsertID();									
				}

				//查找和更新刊登表的asin等字段数据
				$amazonProductAddVariationModel = new AmazonProductAddVariation();
				$variationInfo = $amazonProductAddVariationModel->getProductAddVariationInfoBysellerSKU($params['seller_sku']);
				if ($variationInfo){
					//更新刊登数据
					if ($variationInfo['listing_id'] == 0){	
						$conditions = 'id = ' .$variationInfo['id'];	
						$update_data = array(
							'listing_id' => $listingID,
							'asin' => $params['asin1'],
						);			    				
						$amazonProductAddVariationModel->updateProductAddVariation($conditions, $update_data);
					}
				}

				if(isset($data['item-description']) && $listingID > 0){
					$description = $data['item-description'];
					$encode = mb_detect_encoding($description, array("ASCII","UTF-8","GB2312","GBK","BIG5","ISO-8859-1")); 
					if ($encode){
						$description = iconv($encode,"UTF-8",$description);
					}else{
						$description = iconv("ASCII","UTF-8//IGNORE",$description);	//识别不了的编码就截断输出				
					}

					$descriptionData = array(
						'listing_id' => $listingID,
						'description' => $description,
					);
					//检查listing 描述是否存在
					$amazonListExtend = $this->getDbConnection()->createCommand()->select("id")
						->from("ueb_amazon_listing_extend")
						->where("listing_id = :listing_id", array('listing_id' => $listingID))
						->queryRow();
					if (!$amazonListExtend) {
						$flag = $this->getDbConnection()->createCommand()->insert("ueb_amazon_listing_extend", $descriptionData);

						if (!$flag)
							throw new Exception(Yii::t('amazon_product', 'Save Product Description Failure'));
					}
				}
			} catch (Exception $e) {
				// MHelper::printvar($e->getMessage());
				$this->setErrorMsg($e->getMessage());
				return false;
			}			
		}
		return true;
	}

	/**
	 * @desc 更新产品数据和销售状态（仅更新库存/价格、销售状态(针对卖家销售状态)，不新增）
	 * @param array $listData 只包括了ASIN码、seller_sku、库存数量、price数据
	 * @return boolean
	 */
	public function updateQuantityPriceList($listData) {
		if (!$this->_accountID) return false;

		foreach ($listData as $k=> $data) {
/*			
			//特殊处理日本账号62
			if ($this->_accountID == 62){
				$newsdata = array_values($data);	//删除无法识别的键名
				$data['sku']      = $newsdata[0];
				$data['asin']     = $newsdata[1];
				$data['price']    = $newsdata[2];
				$data['quantity'] = $newsdata[3];
			}
*/
			if(!isset($data['sku']) || empty($data['sku']) || !isset($data['asin']) || empty($data['asin'])) continue;	
			//检查该listing是否已经存在，有则只更新数据 			
            $info = $this->getDbConnection()->createCommand()
                    ->from(self::tableName())
                    ->select('id,price,quantity,seller_status,fulfillment_type')
                    ->where("seller_sku = '".$data['sku']."' AND asin1 = '".$data['asin']."' AND account_id = ".$this->_accountID)
                    ->queryRow();
			if ($info) {
				if(isset($data['price'])) $params['price'] = $data['price'];

				//如果是卖家配送（卖家配送有库存数据，amazon配送库存数据为空），则更新库存和销售状态。此接口只更新amazon配送的价格字段
				if ($info['fulfillment_type'] == self::FULFILLMENT_STATUS_MERCHANT){
					if (isset($data['quantity'])){
						$params['quantity'] = (int)$data['quantity'];												
						if ($params['quantity'] > 0){
							$params['seller_status'] = self::SELLER_STATUS_ONLINE;
						}else{
							$params['seller_status'] = self::SELLER_STATUS_OFFLINE;
						}
					}
				}

				$time = date("Y-m-d H:i:s");
				$params['modify_time'] = $time;

				//有参数变化才执行更新
				if ($info['fulfillment_type'] == self::FULFILLMENT_STATUS_MERCHANT){
					//卖家配送：如果价格或是库存量有变化
					if ($params['price'] != $info['price'] || $params['quantity'] != $info['quantity']){
						$res = $this->getDbConnection()->createCommand()->update(self::tableName(), $params, "id = " .$info['id']);
						if (!$res) throw new Exception(Yii::t('amazon_product', 'Upload Product Info Failure'));
					}
				}else{
					//amazon配送：如果价格变化
					if ($params['price'] != $info['price']){
						$res = $this->getDbConnection()->createCommand()->update(self::tableName(), $params, "id = " .$info['id']);
						if (!$res) throw new Exception(Yii::t('amazon_product', 'Upload Product Info Failure'));
					}
				}
			}			
		}
	}	


	/**
	 * @desc 更新FBA产品数据（仅更新FBA的库存和销售状态）
	 * @param array $listData 只包括了ASIN码、seller_sku、库存数量
	 * @return boolean
	 */
	public function saveFBAInventoryList($listData) {
		if (!$this->_accountID) return false;

		foreach ($listData as $k=> $data) {
			if(!isset($data['seller-sku']) || empty($data['seller-sku']) || !isset($data['asin']) || empty($data['asin'])) continue;	
			//检查该listing是否已经存在，有则只更新数据 			
            $info = $this->getDbConnection()->createCommand()
                    ->from(self::tableName())
                    ->select('id,quantity,seller_status')
                    ->where("seller_sku = '".$data['seller-sku']."' AND asin1 = '".$data['asin']."' AND fulfillment_type = ".self::FULFILLMENT_STATUS_AMAZON." AND account_id = ".$this->_accountID)
                    ->queryRow();
			if ($info) {
				if (isset($data['Quantity Available'])){
					$quantity_able = isset($data['Warehouse-Condition-code']) ? $data['Warehouse-Condition-code'] : '';
					//确认为可销售使用的库存（存在UNSELLABLE的状态）
					if ($quantity_able == 'SELLABLE'){
						$params['quantity'] = (int)$data['Quantity Available'];					
						if ($params['quantity'] > 0){
							$params['seller_status'] = self::SELLER_STATUS_ONLINE;
						}else{
							$params['seller_status'] = self::SELLER_STATUS_OFFLINE;
						}
					}
				}
				$time = date("Y-m-d H:i:s");
				$params['modify_time'] = $time;

				//不做是否更新判断，否则如果没有写入confirm_status=1，会理解为已删除数据
				$res = $this->getDbConnection()->createCommand()->update(self::tableName(), $params, "id = " .$info['id']);
				if (!$res) throw new Exception(Yii::t('amazon_product', 'Upload Product Info Failure'));
			}			
		}
	}		
	
	public function setAccountID($accountID) {
		$this->_accountID = $accountID;
	}
	
	//================ START CURD操作 ========
	/**
	 * @desc 获取亚马孙产品列表
	 * @param unknown $condition
	 * @return mixed
	 */
	public function getAmazonProductList($condition = array(), $attributes = array()){
		if(!$condition){
			return array();	
		}
		$conditions = array();
		$conditionp = array();
		foreach ($condition as $key=>$val){
			$conditions[] = "{$key}=:{$key}";
			$conditionp[":{$key}"] = $val;
		}
		$conditionstr = implode(" AND ", $conditions);
		$criteria = new CDbCriteria();
		$criteria->condition = $conditionstr;
		$criteria->params = $conditionp;
		if($attributes)
			$result = self::model()->findAllByAttributes($attributes);
		else 
			$result = self::model()->findAll($criteria);
		return $result;
	}
	/**
	 * @desc 根据主键id数组来批量更新数据
	 * @param unknown $id
	 * @param unknown $updata
	 */
	public function updateAmazonProductByPks($id, $updata){
		if(empty($id) || empty($updata)) return false;
		return $this->getDbConnection()->createCommand()
										->update(self::tableName(), $updata, array('in', 'id', $id));		
	}
	/**
	 * @desc 更新亚马逊产品数据
	 * @param unknown $condition
	 * @param unknown $updata
	 * @return boolean|Ambigous <number, boolean>
	 */
	public function updateAmazonProduct($conditions, $updata){
		if(empty($conditions) || empty($updata)) return false;
		return $this->getDbConnection()->createCommand()
						->update(self::tableName(), $updata, $conditions);
	}
	//================ END CURD操作==========

	/**
	 * @desc 获取amazon产品状态选项
	 * @return multitype:NULL Ambigous <string, string, unknown>
	 */
	public function getAmazonSellerStatusOptions(){
		return array(
				self::SELLER_STATUS_OFFLINE=>Yii::t('amazon_product', 'Offline Status'),
				self::SELLER_STATUS_ONLINE=>Yii::t('amazon_product', 'Online Status'),
				self::SELLER_STATUS_CANCEL=>Yii::t('amazon_product', 'Cancel Status')
		);
	}

	/**
	 * @desc 获取amazon产品状态选项
	 * @return multitype:NULL Ambigous <string, string, unknown>
	 */
	public function getAmazonFufillmentTypeOptions(){
		return array(
				self::FULFILLMENT_STATUS_MERCHANT =>Yii::t('amazon_product', 'FufillmentType Seller'),
				self::FULFILLMENT_STATUS_AMAZON =>Yii::t('amazon_product', 'FufillmentType Amazon'),			
		);
	}	
	
	/**
	 * @desc 设置筛选条件
	 * @return multitype:
	 */
	public function filterOptions(){
		return array(
				array(
						'name' => 'sku',
						'type' => 'text',
						'search' => 'LIKE',
						'htmlOption' => array(
								'size' => '22',
						),
				),
				array(
						'name' => 'seller_sku',
						'type' => 'text',
						'search' => 'LIKE',
						'htmlOption' => array(
								'size' => '22',
						),
				),
				array(
						'name'	=>	'asin1',
						'type'	=>	'text',
						'search'	=>	'=',
						'htmlOption' => array(
								'size' => '22',
						),
				
				),				
				array(
						'name' => 'account_id',
						'type' => 'dropDownList',
						'data' => $this->getAmazonAccountPairs(),
						'search' => '=',
				),
				array(
						'name'	=>	'seller_status',
						'type'	=>	'dropDownList',
						'data'	=>	$this->getAmazonSellerStatusOptions(),
						'search'	=>	'='
				
				),
				array(
						'name'	=>	'fulfillment_type',
						'type'	=>	'dropDownList',
						'data'	=>	$this->getAmazonFufillmentTypeOptions(),
						'search'	=>	'='
				
				),	
                array(
                        'name'=>'send_warehouse',
                        'type'=>'dropDownList',
                        'search'=>'=',
                        'rel'   =>  true,
						'data'=>AmazonAsinWarehouse::model()->getWarehouseList()
                ),
                array(
						'name'   =>  'seller_id',
						'type'   =>  'dropDownList',
						'rel'    =>  true,
						'data'   =>  User::model()->getAmazonUserList(),
						'search' =>  '=',                
                ),  
                array(
                        'name'          => 'open_date',
                        'type'          => 'text',
                        'search'        => 'RANGE',
                        'htmlOptions'   => array(
                                'class'   => 'date',
                                'dateFmt' => 'yyyy-MM-dd HH:mm:ss',
                                'style'   => 'width:120px;',
                                'width'   => '300px'
                        ),
                ),                              				
			);
	}

	/**
	 * 存储亚马孙账号数据
	 * @var unknown
	 */
	private static $amazonAccountPairs;
	private function getAmazonAccountPairs(){
		if(!self::$amazonAccountPairs)
		self::$amazonAccountPairs = AmazonAccount::getIdNamePairs();
		return self::$amazonAccountPairs;
	}
	/**
	 * @desc 设置格外的数据处理
	 * @param unknown $datas
	 * @return unknown
	 */
	public function addtions($datas){
		$accountList = AmazonAccount::model()->getAllIdNamePairs();	//账号列表
		$amazonWebsiteList = AmazonAccount::model()->getWebsiteByCountryCode();	//各国家网站
		$currencyList = AmazonAccount::model()->getCurrencyCodeByCountryCode();	//各国家货币
		$sellerUserList = User::model()->getPairs();	//销售人员
        $warehouseList = AmazonAsinWarehouse::model()->getWarehouseList();	//仓库列表
        $amazonProductSellerRelationModel = new AmazonProductSellerRelation();
		foreach ($datas as $key=>$data){
			//获取每个对应sku下所有账号的数据列表
			if (!empty($data['sku'])){
				$_datas = $this->getAmazonProductListBySearch($data['sku']);
				if(!$_datas) unset($datas[$key]);
				$datas[$key]->detail = array();
				foreach ($_datas as $k=>$row){

					//销售人员查看限制
					$accountIdArr = array();
					$accountSellerList = AmazonProductSellerRelation::model()->getListByCondition('account_id','seller_id = '.Yii::app()->user->id,'','','account_id');
		            if ($accountSellerList){
			            foreach ($accountSellerList as $acc){
			            	$accountIdArr[] = $acc['account_id'];
			            }
			        }            
		            if($accountIdArr){
		                if (!in_array($row['account_id'],$accountIdArr)) continue;		                
		            }

					//销售人员查询
					if(isset($_REQUEST['seller_id']) && $_REQUEST['seller_id']){ 
			            $sellerID = (int)$_REQUEST['seller_id'];			            
			            $sellerInfo = $amazonProductSellerRelationModel->getItemSellerID($row['asin1'],$row['seller_sku']);
			            if ($sellerInfo){
			            	if ((int)$sellerInfo['data']['sellerID'] != $sellerID) {
			            		continue;
			            	}
			            }else{
			            	continue;
			            }
					}		

					//上架时间过滤
			        if( (isset($_REQUEST['open_date'][0]) && !empty($_REQUEST['open_date'][0])) || isset($_REQUEST['open_date'][1]) && !empty($_REQUEST['open_date'][1]) ){
			        	$currentOpenDate = strtotime($row['open_date']);
			            $openDate1 = !empty($_REQUEST['open_date'][0]) ? strtotime($_REQUEST['open_date'][0]) : 0;
			            $openDate2 = !empty($_REQUEST['open_date'][1]) ? strtotime($_REQUEST['open_date'][1]) : 0;
			            if ($openDate1 > 0 && $openDate2 == 0){
			            	if($openDate1 > $currentOpenDate) continue;
			            }elseif($openDate1 == 0 && $openDate2 > 0){
			            	if($openDate2 < $currentOpenDate) continue;
			            }elseif($openDate1 > 0 && $openDate2 > 0){
			            	if($openDate1 > $currentOpenDate || $openDate2 < $currentOpenDate) continue;
			            }
			        }

                    //获取sku的信息
                    $skuInfo = Product::model()->getProductBySku($row['sku']);
                    $row['product_weight'] = isset($skuInfo['product_weight'])?$skuInfo['product_weight']:'';
                    $row['product_cost'] = isset($skuInfo['product_cost'])?$skuInfo['product_cost']:'';
                    $row['available_qty'] = WarehouseSkuMap::model()->getAvailableBySkuAndWarehouse($row['sku']);      

                    //获取第一张主图片
                    $firstImg = '';
			        $skuImg = ProductImageAdd::getImageUrlFromRestfulBySku($row['sku'], $typeAlisa = null, $type = 'normal', $width = 450, $height = 450, $platform = Platform::CODE_AMAZON);
			        if (isset($skuImg['ft']) && !isset($skuImg['zt'])) {
			            $skuImg['zt'] = $skuImg['ft'];
			        }
			        if (isset($skuImg['zt']) && $skuImg['zt']) $firstImg = array_shift($skuImg['zt']);
			        $row['product_img'] = "<img src='{$firstImg}' style='cursor:pointer;'' width='60' height='60'/>";

					$row['account_name'] = $accountList[$row['account_id']];
					$row['opreator'] = $this->getOprationList($row['seller_status'], $row['id']);
					$row['seller_status_text'] = $this->getSellerStatusText($row['seller_status'], $row['fulfillment_channel']);
					$productSellerRelationInfo = AmazonProductSellerRelation::model()->getProductSellerRelationInfoByItemIdandSKU($row['asin1'], $row['sku'], $row['seller_sku']);
					$sellerName = $productSellerRelationInfo && isset($sellerUserList[$productSellerRelationInfo['seller_id']]) ? $sellerUserList[$productSellerRelationInfo['seller_id']] : '-';
					$row['seller_name'] = $sellerName;

					//如果是日本或是法国账号，用PID搜索，其它账号用asin打开
					$title = $row['item_name'];
					$accountInfo = AmazonAccount::model()->getAccountInfoById($row['account_id']);
					if($accountInfo){
						$countryCode = $accountInfo['country_code'];
					}else{
						$countryCode = 'us';
					}
					if (isset($row['asin1']) && !empty($row['asin1'])){
						if (is_numeric($row['asin1'])){
							$title = "<a href='https://www.".$amazonWebsiteList[$countryCode]."/s?field-keywords=".$row['asin1']."' target='_blank' title='点击进入商城'>".$row['item_name']."</a>";
						}else{
							$title = "<a href='https://www.".$amazonWebsiteList[$countryCode]."/gp/product/".$row['asin1']."' target='_blank' title='点击进入商城'>".$row['item_name']."</a>";
						}
					}
					$row['title'] = $title;
					$row['currency_code'] = $currencyList[$countryCode];

					$row['open_date'] = ($row['open_date'] != '0000-00-00 00:00:00') ? $row['open_date'] : '';
					$row['fulfillment_type_text'] = $this->getFulfillmentTypeText($row['fulfillment_type']);					
					$row['send_warehouse'] = ($warehouseList && isset($warehouseList[$row['warehouse_id']])) ? $warehouseList[$row['warehouse_id']] : '-'; //发货仓库

					$datas[$key]->detail[$k] = $row;
				}
			}
		}		
		return $datas;
	}

	/**
	 * @desc 获取销售状态文本
	 * @param unknown $status
	 * @return string
	 */
	public function getSellerStatusText($status, $fulfillmentChannel = 'DEF'){
		$options = $str = "";
		$color = "red";
		//做判断
		switch ($status){
			case self::SELLER_STATUS_ONLINE:
				$color = "green";
				$str = Yii::t('amazon_product', 'Online Status');
				break;
			case self::SELLER_STATUS_OFFLINE:
				$str = Yii::t('amazon_product', 'Offline Status');
				break;
			case self::SELLER_STATUS_FULFILLMENT_AMAZON:
				$str .= Yii::t('amazon_product', 'Fulfilled by Amazon');
				break;
			case self::SELLER_STATUS_CANCEL:
				$str = Yii::t('amazon_product', 'Cancel Status');
				break;
		}
		$options = "<font color='{$color}'>".$str."</font>";
		return $options;
	}

	/**
	 * @desc 获取配送状态文本
	 * @param unknown $status
	 * @return string
	 */
	public function getFulfillmentTypeText($type){
		$options = "";
		$color = "";
		switch ($type){
			case self::FULFILLMENT_STATUS_MERCHANT:
				$options = Yii::t('amazon_product', 'FufillmentType Seller');
				break;
			case self::FULFILLMENT_STATUS_AMAZON:
				$color = "blue";
				$options = Yii::t('amazon_product', 'FufillmentType Amazon');
				break;
		}
		$options = "<font color='{$color}'>".$options."</font>";
		return $options;
	}

	/**
	 * @desc 获取操作列的html
	 * @param unknown $status
	 * @param unknown $id
	 * @return string
	 */
	public function getOprationList($status, $id){
		$str = "<select style='width:75px;' onchange = 'offLine(this,".$id.")' >
				<option>".Yii::t('system', 'Please Select')."</option>";
		if($status == self::SELLER_STATUS_ONLINE){
			$str .= '<option value="offline">'.Yii::t('amazon_product', 'Offline Status').'</option>';
		}elseif($status == self::SELLER_STATUS_OFFLINE){
			//$str .= '<option value="online">'.Yii::t('amazon_product', 'Online Status').'</option>';
		}
		$str .="</select>";
		return $str;
	}
	/**
	 * @desc 根据搜索条件获取产品列表
	 * @param unknown $sku
	 * @return multitype:|Ambigous <mixed, multitype:, CActiveRecord, NULL, multitype:unknown Ambigous <CActiveRecord, NULL> , unknown, multitype:unknown Ambigous <unknown, NULL> , multitype:unknown >
	 */
	private function getAmazonProductListBySearch($sku){
		if(empty($sku)) return array();
		$condition = array(
				'sku'=>$sku	
		);
		if (isset($_REQUEST['seller_sku']) && !empty($_REQUEST['seller_sku']))
			$condition["seller_sku"] = $_REQUEST['seller_sku'];
		if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id']))
			$condition["account_id"] = (int)$_REQUEST['account_id'];
		if (isset($_REQUEST['seller_status']) && (int)$_REQUEST['seller_status'])
			$condition["seller_status"] = (int)$_REQUEST['seller_status'];
		if (isset($_REQUEST['asin1']) && $_REQUEST['asin1'])
			$condition["asin1"] = $_REQUEST['asin1'];
		if (isset($_REQUEST['fulfillment_type']) && $_REQUEST['fulfillment_type'])
			$condition["fulfillment_type"] = $_REQUEST['fulfillment_type'];	
		if (isset($_REQUEST['send_warehouse']) && $_REQUEST['send_warehouse'])
			$condition["warehouse_id"] = $_REQUEST['send_warehouse'];

		return $this->getAmazonProductList($condition);
	}
	/**
	 * @desc 提供数据
	 * @see UebModel::search()
	 */
	public function search(){
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder'=>'t.sku'
		);
		$dataProvider = parent::search($this, $sort, '', $this->setSearchDbCriteria());
		$datas = $this->addtions($dataProvider->data);
		// MHelper::printvar($datas);
		$dataProvider->setData($datas);
		// MHelper::printvar($dataProvider);
		return $dataProvider;
	}
	/**
	 * @desc 设置搜索条件
	 * @return CDbCriteria
	 */
	public function setSearchDbCriteria(){
		$cdbcriteria = new CDbCriteria();
		$cdbcriteria->select = 'sku';
		$cdbcriteria->group = 'sku';

        //发货仓库查询
        if(isset($_REQUEST['send_warehouse']) && $_REQUEST['send_warehouse']){
            $warehouse_id = (int)$_REQUEST['send_warehouse'];
            $condition[] = 'warehouse_id=:warehouse_id';
            $params[':warehouse_id'] = $warehouse_id;
            $cdbcriteria->addCondition('t.warehouse_id='.$warehouse_id);

        }	

        //销售人员只能看指定分配的账号数据
        $accountIdArr = array();
        if(isset(Yii::app()->user->id)){
            $accountList = AmazonProductSellerRelation::model()->getListByCondition('account_id','seller_id = '.Yii::app()->user->id,'','','account_id');
            if ($accountList){
	            foreach ($accountList as $acc){
	            	$accountIdArr[] = $acc['account_id'];
	            }
	        }            
            if($accountIdArr){
                $ids = implode(',', array_unique($accountIdArr));
                $cdbcriteria->addCondition('t.account_id in('.$ids.')');
            }
        }

        //查询主表销售人员关联的listing
        if(isset($_REQUEST['seller_id']) && $_REQUEST['seller_id']){  
            $sellerID = (int)$_REQUEST['seller_id'];
            $amazonProductSellerRelationModel = new AmazonProductSellerRelation();
            $amazonRelationTable = $amazonProductSellerRelationModel->tableName();
            $tempSql = " (select seller_id,item_id from {$amazonRelationTable} group by item_id) ";
            $cdbcriteria->join = " left join {$tempSql} ps on ps.item_id = t.asin1 ";
            $cdbcriteria->addCondition("ps.seller_id = {$sellerID}");
        }

        //刊登时间
        if( (isset($_REQUEST['open_date'][0]) && !empty($_REQUEST['open_date'][0])) && isset($_REQUEST['open_date'][1]) && !empty($_REQUEST['open_date'][1]) ){
            $cdbcriteria->addCondition("t.open_date >= '" . addslashes($_REQUEST['open_date'][0]) . "' AND t.open_date <= '" . addslashes($_REQUEST['open_date'][1]) . "'");
        }        

		return $cdbcriteria;
	}
	/**
	 * @desc 设置对应的字段标签名称
	 * @see CModel::attributeLabels()
	 */
	public function attributeLabels(){
		return array(
					'id'                    => '',
					'account_name'          => Yii::t('amazon_product', 'Account Id'),
					'account_id'            => Yii::t('amazon_product', 'Account Id'),
					'sku'                   => Yii::t('amazon_product', 'Sku'),
					'seller_sku'            => Yii::t('amazon_product', 'Seller Sku'),
					'item_name'             => Yii::t('amazon_product', 'Item Name'),
					'product_id'            => Yii::t('amazon_product', 'Product Id'),
					'amazon_listing_id'     => Yii::t('amazon_product', 'Listing Id'),
					'price'                 => Yii::t('amazon_product', 'Price'),
					'quantity'              => Yii::t('amazon_product', 'Quantity'),
					'asin1'                 => Yii::t('amazon_product', 'Asin1'),		
					'seller_status_text'    => Yii::t('amazon_product', 'Seller Status'),
					'seller_status'         => Yii::t('amazon_product', 'Seller Status'),
					'opreator'              => Yii::t('system', 'Opration'),
					'seller_name'           => Yii::t('common', 'Seller Name'),
					'fulfillment_type'      => Yii::t('amazon_product', 'Fulfillment Type'),
					'fulfillment_type_text' => Yii::t('amazon_product', 'Fulfillment Type'),	
					'send_warehouse'        => Yii::t('amazon_product', 'Send Warehouse'),
					'open_date'             => Yii::t('amazon_product', 'Listing Create Time'),
					'product_cost'          => Yii::t('product', 'Product Cost'),
					'product_weight'        => Yii::t('product', 'Product Weight'),
					'available_qty'         => Yii::t('product', 'Available Qty'),	
					'product_img'           => Yii::t('amazon_product', 'Image'),	
					'seller_id'				=> Yii::t('amazon_product', 'Seller'),
					'currency_code'		    => Yii::t('amazon_product', 'Currency Code'),
														
				);
	}
	//================ END 提供搜索产品 ========

    /**
     * order field options
     * @return $array
     */
    // public function orderFieldOptions() {
    //     return array(
    //     	'price',
    //         'quantity',
    //         'open_date',
    //     );
    // }
	
	/**
	 * @desc 批量更改发货周期
	 * @param unknown $accountId
	 * @param unknown $itemData <sku, fulfillmentLatency>
	 * @throws Exception
	 * @return Ambigous <string, unknown>|boolean
	 */
	public function amazonChangeFulfillmentLatency($accountId, $itemData){
		try{
			$submitFeedRequest = new SubmitFeedRequest();
			$submitFeedRequest->setFeedType(SubmitFeedRequest::FEEDTYPE_POST_INVENTORY_AVAILABILITY_DATA)
								->setAccount($accountId);
			$merchantId = $submitFeedRequest->getMerchantID();
			$submitFeedRequest->setFeedContent($this->getXmlData($itemData, $merchantId));
			$submitFeedId = $submitFeedRequest->setRequest()
											->sendRequest()
											->getResponse();
			$feedProcessingStatus = $submitFeedRequest->getFeedProcessingStatus();
			if($submitFeedRequest->getIfSuccess() &&
			$feedProcessingStatus && $feedProcessingStatus != SubmitFeedRequest::FEED_STATUS_CANCELLED){
				$scheduled = 1;
				if ($feedProcessingStatus == SubmitFeedRequest::FEED_STATUS_DONE){
					$scheduled = 2;
				}
				//收集sku
				$sku = array();
				foreach ($itemData as $item){
					$sku[] = $item['sku'];
				}
				//写入请求日报表
				$startDate = $endDate = date("Y-m-d H:i:s",time()-8*3600);
				$params = array(
						'account_id'               => $accountId,
						'report_request_id'        => $submitFeedId,
						'report_type'              => SubmitFeedRequest::FEEDTYPE_POST_INVENTORY_AVAILABILITY_DATA,
						'start_date'               => $this->transactionUTCTimeFormat($startDate),
						'end_date'                 => $this->transactionUTCTimeFormat($endDate),
						'submitted_date'           => $this->transactionUTCTimeFormat($endDate),
						'scheduled'                =>	$scheduled,
						'report_processing_status' => $feedProcessingStatus,
						'report_skus'              =>implode(",", $sku)
				);
				$requestReport = new AmazonRequestReport();
				$requestReport->addRequestReport($params);
				return $submitFeedId; // modify by lihy 2016-02-06
			}
			throw new Exception($submitFeedRequest->getErrorMsg());
		}catch(Exception $e){
			$this->setErrorMsg($e->getMessage());
			return false;
		}
	}
	
	
	/**
	 * @desc Amazon产品下架接口
	 * @param unknown $accountId
	 * @param unknown $itemData
	 * @throws Exception
	 * @return boolean
	 */
	public function  amazonProductOffline($accountId, $itemData){
		try{
			// $submitFeedRequest = new SubmitFeedRequest();
			$submitFeedRequest = new CommonSubmitFeedRequest();
			$submitFeedRequest->setFeedType(SubmitFeedRequest::FEEDTYPE_POST_INVENTORY_AVAILABILITY_DATA)
								->setAccount($accountId);
			$merchantId = $submitFeedRequest->getMerchantID();
			$submitFeedRequest->setFeedContent($this->getXmlData($itemData, $merchantId));
			$submitFeedId = $submitFeedRequest->setRequest()
										->sendRequest()
										->getResponse();
			$feedProcessingStatus = $submitFeedRequest->getFeedProcessingStatus();
			if($submitFeedRequest->getIfSuccess() &&
				$feedProcessingStatus && $feedProcessingStatus != SubmitFeedRequest::FEED_STATUS_CANCELLED){
				$scheduled = 1;
				if ($feedProcessingStatus == SubmitFeedRequest::FEED_STATUS_DONE){
					$scheduled = 2;
				}
				//收集sku
				$sku = array();
				foreach ($itemData as $item){
					$sku[] = $item['sku'];
				}				
				//写入请求日报表
				$startDate = $endDate = date("Y-m-d H:i:s",time()-8*3600);
				$params = array(
						'account_id'               => $accountId,
						'report_request_id'        => $submitFeedId,
						'report_type'              => SubmitFeedRequest::FEEDTYPE_POST_INVENTORY_AVAILABILITY_DATA,
						'start_date'               => $this->transactionUTCTimeFormat($startDate),
						'end_date'                 => $this->transactionUTCTimeFormat($endDate),
						'submitted_date'           => $this->transactionUTCTimeFormat($endDate),
						'scheduled'                =>	$scheduled,
						'report_processing_status' => $feedProcessingStatus,
						'report_skus'              =>implode(",", $sku)
				);
				$requestReport = new AmazonRequestReport();
				$requestReport->addRequestReport($params);
				return $submitFeedId; // modify by lihy 2016-02-06
			}
			throw new Exception($submitFeedRequest->getErrorMsg());
		}catch(Exception $e){
			$this->setErrorMsg($e->getMessage());
			return false;
		}
	}
	
	/**
	 * @desc 获取提交的xml代码
	 */
	public function getXmlData( $itemData,$merchantId ){
		$feedHeader = '<?xml version="1.0" encoding="UTF-8"?>
				<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
				    <Header>
				        <DocumentVersion>1.01</DocumentVersion>
				        <MerchantIdentifier>'.$merchantId.'</MerchantIdentifier>
				    </Header>
				    <MessageType>Inventory</MessageType>';
	
		$feedMain = '';
		foreach ($itemData as $k=>$item){
			$feedMain .= '<Message>
						<MessageID>'.($k+1).'</MessageID>
						<OperationType>Update</OperationType>
						<Inventory>';
			if (isset($item['sku'])){
				$feedMain .= '<SKU>'.$item['sku'].'</SKU>';
			}
			if (isset($item['quantity'])){
				$feedMain .= '<Quantity>'.$item['quantity'].'</Quantity>';
			}
			if(isset($item['fulfillmentLatency'])){
				$feedMain .= '<FulfillmentLatency>'.$item['fulfillmentLatency'].'</FulfillmentLatency>';
			}			
			$feedMain .= '</Inventory></Message>';
				
		}
		$feedFoot = '</AmazonEnvelope>';
		return $feedHeader.$feedMain.$feedFoot;
	}

	/**
	 * @desc Amazon feed submission请求接口
	 * @param unknown $accountId
	 * @param unknown $newreport
	 * @return boolean
	 */
	public function amazonFeedSubmissionRequest($accountId, $newreport, $reportSkuList){
		static $amazonRequestReport, $request;
		if(!$amazonRequestReport)
			$amazonRequestReport = new AmazonRequestReport();
		if(!$request)
			$request = new GetFeedSubmissionListRequest();
		$request->setAccount($accountId)->setFeedSubmissionId($newreport);
		$reponse = $request->setReqType(GetFeedSubmissionListRequest::REQ_TYPE_ONLY_FEED_SUBMISSION_LIST)->setRequest()->sendRequest()->getResponse();
		if(isset($reponse[SubmitFeedRequest::FEED_STATUS_CANCELLED]) && $reponse[SubmitFeedRequest::FEED_STATUS_CANCELLED]){
			$amazonRequestReport->batchUpdateRequestReport($reponse[SubmitFeedRequest::FEED_STATUS_CANCELLED],
						SubmitFeedRequest::FEEDTYPE_POST_INVENTORY_AVAILABILITY_DATA,
						array('scheduled'=>AmazonRequestReport::SCHEDULED_YES, 'report_processing_status'=>SubmitFeedRequest::FEED_STATUS_CANCELLED));
		}
		if(isset($reponse[SubmitFeedRequest::FEED_STATUS_DONE]) && $reponse[SubmitFeedRequest::FEED_STATUS_DONE]){
			$amazonRequestReport->batchUpdateRequestReport($reponse[SubmitFeedRequest::FEED_STATUS_DONE],
					SubmitFeedRequest::FEEDTYPE_POST_INVENTORY_AVAILABILITY_DATA,
					array('scheduled'=>AmazonRequestReport::SCHEDULED_YES, 'report_processing_status'=>SubmitFeedRequest::FEED_STATUS_DONE));
			foreach ($reponse[SubmitFeedRequest::FEED_STATUS_DONE] as $val){
				$skus = $reportSkuList[$val];
				if(!$skus) continue;
				$skuarr = explode(",", $skus);
				if(!$skuarr) continue;
				//拉取单个商品到本地
				//更新本地商品库
				$conditions = "seller_sku in('".implode("','", $skuarr)."') AND account_id={$accountId}";
				$updata = array('seller_status'=>AmazonList::SELLER_STATUS_OFFLINE);
			 	AmazonList::model()->updateAmazonProduct($conditions, $updata);
			}
		}
		return $reponse ? true : false;
	}
	
	/**
	 * @desc 根据asin码获取对应数据
	 * @param array $ASIN
	 * @return multitype:
	 */
	public function getListingByASIN($accountID, $asin = array()){
		if(empty($asin)) return array();
		$getMatchingProductRequest = new GetMatchingProductRequest();
		$getMatchingProductRequest->setAccount($accountID);
		$getMatchingProductRequest->setAsinID($asin);
		$result = $getMatchingProductRequest->setRequest()->sendRequest()->getResponse();
	}

	/**
	 * @desc 获取feed的报告结果（跟踪号上传专用）
	 * @param unknown $accountID
	 * @param unknown $feedSubmissionId
	 * @return number
	 */
	public function getFeedSubmissionResult($accountID, $feedSubmissionId){
		$feedSubmissionResult = new GetFeedSubmissionResultRequest();
		$feedSubmissionResult->setAccount($accountID);
		$feedSubmissionResult->setFeedSubmissionId($feedSubmissionId);
		$response = $feedSubmissionResult->setRequest()->sendRequest()->getResponse();
		return $response;
	}

	/**
	 * @desc 获取feed的报告结果（公共方法）
	 * @param unknown $accountID
	 * @param unknown $feedSubmissionId
	 * @return number
	 */
	public function GetCommonFeedSubmissionResult($accountID, $feedSubmissionId){
		$feedSubmissionResult = new GetCommonFeedSubmissionResultRequest();
		$feedSubmissionResult->setAccount($accountID);
		$feedSubmissionResult->setFeedSubmissionId($feedSubmissionId);
		$response = $feedSubmissionResult->setRequest()->sendRequest()->getResponse();
		return $response;
	}	
	
	/**
	 *  
	 * @desc 设置错误消息
	 * @param unknown $msg
	 */
	
	private function setErrorMsg($msg){
		$this->_errorMsg = $msg;
	}
	/**
	 * @desc 获取错误消息
	 * @return unknown
	 */
	public function getErrorMsg(){
		return $this->_errorMsg;
	}

	/**
	 * @desc 根据SKU获取listing记录
	 * @param string $SKU
	 */
	public function getListingBySku($SKU){
		if(!$SKU) return false;
		$ret = $this->dbConnection->createCommand()
				->select('*')
				->from(self::tableName())
				->where('sku="'.$SKU.'"')
				->queryAll();
		return $ret;
	}	

	/**
	 * 根据条件获取Listing数据
	 */
	public function getListingInfoByCondition($where) {
		if (empty($where)) return false;
        return $this->getDbConnection()->createCommand()
					->select('*')
					->from($this->tableName())
					->where($where)
					->limit(1)
                    ->queryRow();
	}	

	/**
	 * @desc 根据判断获取listing的Ids，分组批量更新操作
	 * @param string $SKU
	 */
	public function updateListingGroupByIds($data,$update_data){		
		$result = array();
    	$conditions = " 1 ";

    	//设置条件
    	if(is_array($data)){
    		foreach ($data as $key=>$val){
    			if (!empty($val)){
    				$conditions .= " AND {$key}='{$val}' ";
    			}    			
    		}
    	}else {
    		$conditions = $data;
    	}

    	//超过五天没有更新的，就认为是已删除的listing
    	$last_update_date = date('Y-m-d H:i:s',time()-86400*5);
    	$conditions .= " AND modify_time < '" .$last_update_date. "'";

    	//获取条件下所有记录的ID
		$ret = $this->dbConnection->createCommand()
				->select('id')
				->from(self::tableName())
				->where($conditions)
				->queryAll();

		if ($ret){
			$ids_arr = $this->getGroupIds($ret);	//分组，并且为ID字符串组
			if ($ids_arr){
				foreach($ids_arr as $ids){
					if ($ids){							
						$result = $this->updateAll($update_data,"id in ( {$ids} )");				
					}
					
				}
			}			
		}
		return $result;
	}

	/**
	 * @desc 把IDS分组并转化为in使用的字符串（ID用逗号分隔）
	 * @param string $SKU
	 */
	public function getGroupIds($ret, $num = 1000){		
		if (!$ret) return false;
		if ($num > 2000) $num = 1000;

		$group = MHelper::getGroupData($ret,$num);
		$ids_arr = array();
		foreach ($group as $item){
			$ids = '';
			$tmp = array();
			foreach ($item as $val){
				$tmp[] = $val['id'];
			}
			$ids = implode(',', array_values($tmp));
			$ids_arr[] = $ids;
		}
		return $ids_arr;
	}

	/**
     * @desc UTC时间格式转换
     * @param unknown $UTCTime
     * @return mixed
     */
    public function transactionUTCTimeFormat($UTCTime){
    	$newUTCTime = '';
    	if (!empty($UTCTime)){
    	$UTCTime = strtoupper($UTCTime);
    	$newUTCTime = str_replace("T", " ", $UTCTime);
    	$newUTCTime = str_replace("Z", "", $UTCTime);
    	}
		return $newUTCTime;
    }	

	/**
     * @desc 时间格式转换（不改变时间，不计算时区，拼接格式化日期）
     * @param $datetime
     * @param $type 类型：jp的特殊日期处理
     * @return string
     */
    public function setDateTimeFormat($datetime,$type = ''){
    	if (empty($datetime)) return false;
		$mydate  = '';
		$mytime  = '';
		$myyear  = '';
		$mymonth = '';
		$myday   = '';
    	$date_arr = explode(" ",$datetime);
    	if ($date_arr){
    		if (isset($date_arr[0]) && $date_arr[0]) $mydate = $date_arr[0];
    		if (isset($date_arr[1]) && $date_arr[1]) $mytime = $date_arr[1];
    	}
    	if ($mydate){
    		$mydate_arr = explode("/",$mydate);
    		if (count($mydate_arr) > 1){
    			if ($type == 'jp'){
					$myyear  = $mydate_arr[0];
					$mymonth = $mydate_arr[1];
					$myday   = $mydate_arr[2];
    			}else{
					$myyear  = $mydate_arr[2];
					$mymonth = $mydate_arr[1];
					$myday   = $mydate_arr[0];
				}
				$mydate  = $myyear.'-'.$mymonth.'-'.$myday;
    		}
    	}
    	$formatDate = $mydate.' '.$mytime;
    	if (strtotime($formatDate)){
    		return $formatDate;
    	}
    	return false;
    }



    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return [type]         [description]
     * @author yangsh
     */
    public function getOneByCondition($fields='*', $where='1',$order='')
    {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }



}