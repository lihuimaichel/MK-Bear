<?php
class EbayProductStatistic extends UebModel {
	
	/** @var string 产品英文名称 **/
	public $en_title = null;
	//产品中文标题
	public $cn_title = null;

	public $account_id = null;
	public $title = null;
	
	/**
	 * @desc 设置表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_product';
	}
	
	/**
	 * @desc 设置连接的数据库名
	 * @return string
	 */
	public function getDbKey() {
		return 'db_oms_product';
	}
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see CActiveRecord::relations()
	 */
	public function relations() {
		return array(
		);
	}
	
	/**
	 * @desc 获取状态列表
	 * @param string $status
	 */
	public static function getStatusList($status = null){
		$statusArr = array(
				self::UPLOAD_STATUS_DEFAULT     => Yii::t('aliexpress', 'UPLOAD STATUS DEFAULT'),
				self::UPLOAD_STATUS_RUNNING     => Yii::t('aliexpress', 'UPLOAD STATUS RUNNING'),
				self::UPLOAD_STATUS_IMGFAIL     => Yii::t('aliexpress', 'UPLOAD STATUS IMGFAIL'),
				self::UPLOAD_STATUS_IMGRUNNING  => Yii::t('aliexpress', 'UPLOAD STATUS IMGRUNNING'),
				self::UPLOAD_STATUS_SUCCESS     => Yii::t('aliexpress', 'UPLOAD STATUS SUCCESS'),
				self::UPLOAD_STATUS_FAILURE     => Yii::t('aliexpress', 'UPLOAD STATUS FAILURE'),
		);
		if($status===null){
			return $statusArr;
		}else{
			return $statusArr[$status];
		}
	}
	/**
	 * @desc 属性翻译
	 */
	public function attributeLabels() {
		return array(
				'sku'					=> Yii::t('aliexpress_product_statistic', 'Sku'),
				'en_title' 				=> Yii::t('aliexpress_product_statistic', 'Product Title'),
				'title'					=> Yii::t('aliexpress_product_statistic', 'Product Title'),
				'product_cost' 			=> Yii::t('aliexpress_product_statistic', 'Product Cost'),
				'product_category_id' 	=> Yii::t('aliexpress_product_statistic', 'Product Category'),
				'account_id' 			=> Yii::t('aliexpress_product_statistic', 'Account'),
				'product_status' 		=> Yii::t('aliexpress_product_statistic', 'Product Status'),
				'online_number' 		=> Yii::t('aliexpress_product_statistic', 'Online Number'),
				'product_is_bak' 		=> Yii::t('aliexpress_product_statistic', 'If Stock Up'),
				'is_online' 			=> Yii::t('aliexpress_product_statistic', 'Is Online'),
				'site_id'				=> Yii::t('ebay', 'Site Id'),
				'is_special_attribute'	=> Yii::t('ebay', 'Special Attribute'),	
				'warehouse_id'			=> Yii::t('ebay', 'Warehouse ID'),	
				'product_stock'			=> Yii::t('ebay', 'Available Product Stock'),
				'sale_price'			=> Yii::t('ebay', 'Product Price'),
				'online_date'			=> Yii::t('ebay', 'Online Date'),
				'create_time'			=> Yii::t('ebay', 'Create Time'),
				'product_is_multi'		=> Yii::t('ebay', 'Product Is Multi'),
				'online_category_id'	=> Yii::t('ebay', 'Online Category ID'),
				'listing_duration'		=> Yii::t('ebay', 'Listing Duration')."(做刊登周期设置)",
				'listing_type'			=> Yii::t('ebay', 'Listing Type')."(做刊登设置)",
				'auction_status'		=> Yii::t('ebay', 'Auction Status')."(做刊登设置)",
				'auction_plan_day'		=> Yii::t('ebay', 'Auction Plan Day')."(做刊登设置)",
				'auction_rule'			=> Yii::t('ebay', 'Auction Rule')."(做刊登设置)",
				'config_type'			=> Yii::t('ebay', 'Config Type Name')."(做刊登设置)",	
				'is_display_variation'  => Yii::t('ebay', 'Is Display Variation'),
		);
	}
	
	/**
	 * @return array search filter (name=>label)
	 */
	public function filterOptions() {
		$site = Yii::app()->request->getParam("site_id");
		$isMulti = Yii::app()->request->getParam("product_is_multi");
		$classId = Yii::app()->request->getParam("product_category_id");
		$onlineCategoryId = Yii::app()->request->getParam("online_category_id");
		$listingType = Yii::app()->request->getParam("listing_type");
		$isDisplayVariation = Yii::app()->request->getParam("is_display_variation");
		$result = array(
			array(
					'name'		 	=> 'title',
					'search' 		=> '=',
					'type' 			=> 'text',
					//'alias'			=>	't',
					'rel' 			=> 'selectedTodo',
					'htmlOptions'	=> array(),
			),
			array(
				'name'		 	=> 'sku',
				'search' 		=> 'IN',
				'type' 			=> 'text',
				//'alias'			=>	't',
				'rel' 			=> 'selectedTodo',
				'htmlOptions'	=> array(),
			),
			array(
				'name' 			=> 'product_category_id',
				'type'			=> 'dropDownList',
				'data'		    => ProductClass::model()->getProductClassPair(),
				'search'		=> '=',
				'rel'			=> 'selectedTodo',
				'htmlOptions' 	=> array(
										'onchange'=>'getProductOnlineCategory(this)',
									),
			),
			array(
					'name' 			=> 'online_category_id',
					'type'			=> 'dropDownList',
					'data'		    => ProductCategoryOnline::model()->getProductOnlineCategoryPairByClassId($classId),
					'search'		=> '=',
					'value'			=>	$onlineCategoryId,
					'htmlOptions' 	=> array(
							'id'=>'search_online_category_id'
					),
			),
			array(
					'name' 			=> 'site_id',
					'type' 			=> 'dropDownList',
					'search' 		=> '=',
					'data' 			=> UebModel::model('EbaySite')->getSiteList(),
					'htmlOptions' 	=> array(),
					'value'			=>	$site,
					'rel' 			=> 'selectedTodo',
					'htmlOptions' 	=> array(
											'onchange'=>'getAccountList(this)'
										),
			),
			array(
				'name' 			=> 'account_id',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'data' 			=> CHtml::listData(UebModel::model('EbayAccountSite')->getAbleAccountListBySiteID($site), "id", "short_name"),
				'htmlOptions' 	=> array(
										'id'=>'search_account_id'
									),
				'rel' 			=> 'selectedTodo',
			),
			array(
				'name' 			=> 'product_status',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'alias'			=> 't',
				'data' 			=> Product::getProductStatusConfig(),
				'htmlOptions' 	=> array(),		
			),
			array(
				'name' 			=> 'product_is_bak',
				'type' 			=> 'dropDownList',
				'alias'			=>	't',
				'value' 		=> isset($_REQUEST['product_is_bak']) ? $_REQUEST['product_is_bak'] : '',
				'data' 			=> Product::getStockUpStatusList(),
				'search' 		=> '=',
				'htmlOptions' 	=> array(
				),
			),				
			array(
				'name' 			=> 'product_cost',
				'type' 			=> 'text',
				'search' 		=> 'RANGE',
				'alias'			=>	't',
				'rel'			=> 'selectedTodo',
				'htmlOptions'	=> array(
					'size' => 4
				),
			),
			array(
				'name'		 	=> 'product_is_multi',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'value'			=> $isMulti,
				'data' 			=> array('2' => Yii::t('system', 'Yes'), '0' => Yii::t('system', 'No')),
				'htmlOptions' 	=> array(),
				'rel'			=> 'selectedToDo'
								
			),
			array(
					'name'		 	=> 'is_online',
					'type' 			=> 'dropDownList',
					'search' 		=> '=',
					'data' 			=> array('1' => Yii::t('system', 'Yes'), '2' => Yii::t('system', 'No')),
					'htmlOptions' 	=> array(),
					'rel' 			=> 'selectedTodo',
			
			),
			/* array(
					'name'		 	=> 'is_special_attribute',
					'type' 			=> 'dropDownList',
					'search' 		=> '=',
					'data' 			=> array('1' => Yii::t('system', 'Yes'), '2' => Yii::t('system', 'No')),
					'htmlOptions' 	=> array(),
					'rel' 			=> 'selectedTodo',
			
			), */

			array(
					'name' 			=> 'warehouse_id',
					'type' 			=> 'dropDownList',
					'search' 		=> '=',
					'data' 			=> Warehouse::model()->getWarehousePairs(),
					'htmlOptions' 	=> array(),
					'rel' 			=> 'selectedTodo',
			),
				
			array(
					'name' 			=> 'product_stock',
					'type' 			=> 'text',
					'search' 		=> 'RANGE',
					'rel'			=> 'selectedTodo',
					'htmlOptions'	=> array(
							'size' => 4
					),
			),
			
			/* array(
					'name' 			=> 'sale_price',
					'type' 			=> 'text',
					'search' 		=> 'RANGE',
					'rel'			=> 'selectedTodo',
					'htmlOptions'	=> array(
							'size' => 4
					),
			), */
				
			array(
					'name' 			=> 'create_time',
					'type' 			=> 'text',
					'search' 		=> 'RANGE',
					'alias'			=>	't',
					'htmlOptions'	=> array(
							'size' => 4,
							'class'=>'date',
							'style'=>'width:80px;'
					),
			),
				
				
			
			array(
					'name'		 	=> 'is_display_variation',
					'type' 			=> 'dropDownList',
					'search' 		=> '=',
					'data' 			=> array(
											'不显示','显示'
										),
					'value'			=> $isDisplayVariation,
					'htmlOptions' 	=> array(
							
					),
					'rel' 			=> 'selectedTodo',
			),
				
			array(
			 		'name'		 	=> 'listing_duration',
					'type' 			=> 'dropDownList',
					'search' 		=> '=',
					'data' 			=> $this->getListingDuration($listingType),
					'value'			=> 'GTC',
					'htmlOptions' 	=> array(
											'id'=>'search_listing_duration'
										),
					'rel' 			=> 'selectedTodo',
						
			),
				
			array(
					'name'		 	=> 'listing_type',
					'type' 			=> 'dropDownList',
					'search' 		=> '=',
					'data' 			=> array(
											EbayProductAdd::LISTING_TYPE_FIXEDPRICE	=>	'一口价',
											EbayProductAdd::LISTING_TYPE_AUCTION	=>	'拍卖'	
										),
					'value'			=> EbayProductAdd::LISTING_TYPE_FIXEDPRICE,
					'htmlOptions' 	=> array(
							'id'=>'search_listing_type',
							'onchange'=>'getListingTypeOption(this)'
					),
					'rel' 			=> 'selectedTodo',
			),
			
				array(
						'name'		 	=> 'auction_status',
						'type' 			=> 'dropDownList',
						'search' 		=> '=',
						'data' 			=> array(
										0 => '不循环刊登',
										1 => '循环刊登'
						),
						'htmlOptions' 	=> array(
								'id'=>'search_auction_status',
						),
						'rel' 			=> 'selectedTodo',
				),
				
				array(
						'name'		 	=> 'auction_rule',
						'type' 			=> 'dropDownList',
						'search' 		=> '=',
						'data' 			=> EbayProductAdd::model()->getAuctionType(),
						'htmlOptions' 	=> array(
								'id'=>'search_auction_rule',
						),
						'rel' 			=> 'selectedTodo',
				),
				
				array(
						'name'		 	=> 'auction_plan_day',
						'type' 			=> 'text',
						'search' 		=> '=',
						'htmlOptions' 	=> array(
								'id'=>'search_auction_plan_day',
						),
						'rel' 			=> 'selectedTodo',
				),
				
				array(
						'name'		 	=> 'config_type',
						'type' 			=> 'dropDownList',
						'search' 		=> '=',
						'data'			=>	EbayProductAdd::getConfigType(),
						'htmlOptions' 	=> array(
								'id'=>'search_config_type',
						),
						'rel' 			=> 'selectedTodo',
				),
		);
	
		return $result;
	}
	
	
	public function getListingDuration($listingType){
		if($listingType == EbayProductAdd::LISTING_TYPE_AUCTION){
			$listingDurations = array(
					'Days_5'	=>	'5天',
					'Days_3'	=>	'3天',
					'Days_7'	=>	'7天',
					'Days_10'	=>	'20天',
			);
		}else{
			
			
			$listingDurations = array(
					'GTC'		=>	'GTC',
					'Days_3'	=>	'3天',
					'Days_5'	=>	'5天',
					'Days_7'	=>	'7天',
					'Days_10'	=>	'10天',
					'Days_30'	=>	'30天',
			);
		}
		return $listingDurations;
	}
	/**
	 * search SQL
	 * @return $array
	 */
	protected function _setCDbCriteria() {
		$criteria = new CDbCriteria();
		$criteriaSku = new CDbCriteria();
		$skuArr = array();
		$notInSkuArr = array();
		$inSkuArr = array();
		$isSKU = false;
		$saleAccountID = Yii::app()->user->id;

		$whereRel = '1';
		$userAccountSite = SellerUserToAccountSite::model()->getAccountSiteByCondition(Platform::CODE_EBAY,$saleAccountID);
		if($userAccountSite){//如果是销售人员
			$whereRel .= " and seller_user_id = {$saleAccountID}";
		}
		
		//联接sku销售关系表
		if(!AuthAssignment::model()->checkPlatformByUserIdAndPlatformCode($saleAccountID, Platform::CODE_EBAY) && !UserSuperSetting::model()->checkSuperPrivilegeByUserId($saleAccountID)){
			//$criteria->join = "join ueb_product_to_seller_relation ps on (ps.sku = t.sku and ps.seller_id={$saleAccountID}) ";
		}
		
		if(isset($_REQUEST['title']) && !empty($_REQUEST['title']) && empty($_REQUEST['sku'])){
			$skus = Productdesc::model()->getSkuByAllTitle($_REQUEST['title']);
			if($skus){
				$criteria->addInCondition("t.sku", $skus);
			}else{
				$criteria->addCondition("0=1");
			}
		}
		
		if(isset($_REQUEST['sku']) && !empty($_REQUEST['sku'])){
			$sku = trim($_REQUEST['sku']);
			$criteriaSku->addCondition("p.sku = '" . $sku . "'");
			$criteria->addCondition("t.sku='".$sku."'");
			$skuArr = array($sku);
		}
		//product_category_id
		if(isset($_REQUEST['product_category_id']) && !empty($_REQUEST['product_category_id']) && empty($_REQUEST['online_category_id'])){
			$classId = trim($_REQUEST['product_category_id']);
			//获取所有品类
			$onlineCateIds = ProductCategoryOnline::model()->getProductOnlineCategoryIDsClassId($classId);
			if($onlineCateIds){
				if(!is_array($onlineCateIds)){
					$onlineCateIds = array($onlineCateIds);
				}
				$criteria->addInCondition("t.online_category_id", $onlineCateIds);
			}else{
				$criteria->addCondition("0=1");
			}
		}

		if (isset($_REQUEST['site_id']) && $_REQUEST['site_id'] !== ''){
			$criteriaSku->addCondition("p.site_id = " . (int)$_REQUEST['site_id']);
			$isSKU = true;
			$site_name = EbaySite::getSiteName($_REQUEST['site_id']);
			$whereRel .= " and site = '{$site_name}'";
		}

		if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])){
			$criteriaSku->addCondition("p.account_id = " . (int)$_REQUEST['account_id']);
			$isSKU = true;
			$whereRel .= " and account_id = {$_REQUEST['account_id']}";

			$tableName = 'ueb_product_to_account_seller_platform_eb_' . $_REQUEST['account_id'];
			$skuListRel = ProductToAccount::model()->getDbConnection()->createCommand()
				->select("sku")
				->from($tableName)
				->where($whereRel)
				->queryColumn();
			$criteria->addInCondition("t.sku", $skuListRel);
		}
		$isOnline = isset($_REQUEST['is_online']) ? $_REQUEST['is_online'] : null;
		if($isOnline){
			$isSKU = true;
		}
		if($isSKU){
			$ebayProduct = new EbayProduct();
			$criteriaSku->addCondition("p.listing_type <> 1");
			$criteriaSku->addCondition("p.item_status = 1");
			$skuList = $ebayProduct->getDbConnection()->createCommand()
							->select("p.sku")
							->from("ueb_ebay_product as p")
							//->join("ueb_ebay_product_variation as pv", "p.id = pv.listing_id")
							->where($criteriaSku->condition)
							->group("p.sku")
							->queryColumn();
		}else{
			//联接sku销售关系表
			//$criteria->join = "join ueb_product_to_seller_relation ps on (ps.sku = t.sku and ps.seller_id={$saleAccountID}) ";
		}

		//var_dump($skuList);
		$isMulti = Yii::app()->request->getParam("product_is_multi");
		$isDisplayVariation = Yii::app()->request->getParam("is_display_variation");
		$productMulti = array();
		if($isMulti === ''){
			//$criteria->addInCondition("t.product_is_multi", array(Product::PRODUCT_MULTIPLE_NORMAL, Product::PRODUCT_MULTIPLE_MAIN));
			$productMulti[] = Product::PRODUCT_MULTIPLE_NORMAL;
			if($isDisplayVariation){
				$productMulti[] = Product::PRODUCT_MULTIPLE_VARIATION;
			}else{
				$productMulti[] = Product::PRODUCT_MULTIPLE_MAIN;
			}
		}else{
			//$criteria->addInCondition("t.product_is_multi", array($isMulti));
			if($isMulti == Product::PRODUCT_MULTIPLE_MAIN && $isDisplayVariation){
				$productMulti[] = Product::PRODUCT_MULTIPLE_VARIATION;
			}else{
				$productMulti[] = $isMulti;
			}
		}
		
		
		$criteria->addInCondition("t.product_is_multi", $productMulti);

		if ($isOnline == 1){//在线
			if($skuList){
				$criteria->addInCondition("t.sku", $skuList);
			}else{
				$criteria->addCondition("1=0"); 
			}
		}else if ($isOnline == 2) {//不在线
			if($skuList){
				//if(!$skuArr)
					$criteria->addNotInCondition("t.sku", $skuList);
			}else{
				/* if(!$skuArr){
					$criteria->addCondition("1=0");
				} */
			}
		}
		
		//过滤分类
		/* if (isset($_REQUEST['product_category_id']) && !empty($_REQUEST['product_category_id'])) {
			$criteria->join = "join ueb_product_category_sku_old t1 on (t1.sku = t.sku) join ueb_product_category_old t2 on (t1.classid = t2.id)";
			$criteria->addCondition("t2.id = " . (int)$_REQUEST['product_category_id']);
		} */
		//过滤掉特殊属性
		if(!empty($_REQUEST['is_special_attribute'])){
			//@todo
		}

		//库存数量过滤
		if( (!empty($_REQUEST['product_stock'][0]) || !empty($_REQUEST['product_stock'][1])) && !empty($_REQUEST['warehouse_id'])  ){
			//@todo 海外仓库
			$warehouseId = $_REQUEST['warehouse_id'];
			$criteria->join = "join ueb_warehouse.ueb_warehouse_sku_map t2 on (t2.sku = t.sku and t2.warehouse_id={$warehouseId}) ";
			$productStock = $_REQUEST['product_stock'];
			$minStock = (int)$_REQUEST['product_stock'][0];
			$maxStock = (int)$_REQUEST['product_stock'][1];
			if(!empty($_REQUEST['product_stock'][0])){
				$criteria->addCondition("t2.available_qty >= {$minStock} ");
			}
			
			if(!empty($_REQUEST['product_stock'][1])){
				$criteria->addCondition("t2.available_qty <= {$maxStock} ");
			}
			//$criteria->addCondition("t2.available_qty between {$minStock} and {$maxStock}");
		}

		//产品成本
		if(!empty($_REQUEST['product_cost'][0]) || !empty($_REQUEST['product_cost'][1])){
			$minCost = floatval($_REQUEST['product_cost'][0]);
			$maxCost = floatval($_REQUEST['product_cost'][1]);
			$maxCost += 0.0001;
			if(!empty($_REQUEST['product_cost'][0])){
				$criteria->addCondition("t.product_cost >= {$minCost} ");
			}
			if(!empty($_REQUEST['product_cost'][1])){
				$criteria->addCondition("t.product_cost <= {$maxCost} ");
			}
			//$criteria->addCondition("t.product_cost between {$minCost} and {$maxCost}");
		}
		return $criteria;
	}
	
	/**
	 * @return $array
	 */
	public function search(){
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'  => 'id',
		);
		$criteria = null;
		$criteria = $this->_setCDbCriteria();
		$dataProvider = parent::search(get_class($this), $sort,array(),$criteria);
	
		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}
	
	/**
	 * @desc 附加查询条件
	 * @param unknown $data
	 */
	public function addition($data){
		foreach ($data as $key => $val) {
			$sku = $val['sku'];
			$title = Productdesc::model()->getTitleBySku($val['sku']);
			$data[$key]->en_title = empty($title['english']) ? '' : $title['english'];
			$data[$key]->cn_title = empty($title['Chinese']) ? '' : $title['Chinese'];
			/* if(empty($title['Chinese']) && empty($title['english'])) {
				//中英文标题都为空，如果是子sku情况，取父sku标题
				if(strpos($val['sku'], '.') !== false) {
					//子sku，取父sku标题
					$skuParent = (int)$val['sku'];
					$titleNew = Productdesc::model()->getTitleBySku($skuParent);
					if($titleNew){
						$data[$key]->en_title = empty($titleNew['english']) ? '' : $titleNew['english'];
						$data[$key]->cn_title = empty($titleNew['Chinese']) ? '' : $titleNew['Chinese'];
					}
				}
			} */
		}
		return $data;
	}
	
	
}