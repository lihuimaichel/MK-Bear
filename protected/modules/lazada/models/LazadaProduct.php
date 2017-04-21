<?php
/**
 * @desc lazada listing model
 * @author zhangF
 *
 */
class LazadaProduct extends LazadaModel {
	
	/** @var integer  账号ID **/
	protected $_accountID = null;

	/** @var integer  账号autoID **/
	protected $_accountAutoID = null;	
	
	/** @var integer **/
	protected $_siteID = null;
	
	/** @var integer 日志ID **/
	protected $_logID = null;

	/** @var string 异常信息*/
	protected $_exception = null;
	
	public $detail = array();
	
	public $listing_id = null;
	
	public $offline	= null;

	public $profit_margin = null;
	
	const EVENT_NAME = 'getproduct';
	
	const PRODUCT_STATUS_UNKNOW = 0;						//未知状态
	const PRODUCT_STATUS_ACTIVE = 1;						//在线产品
	const PRODUCT_STATUS_INACTIVE = 2;						//不活跃的产品
	const PRODUCT_STATUS_DELETED = 3;						//已经删除的产品
	
	const PRODUCT_STATUS_TEXT_ACTIVE = 'active';
	const PRODUCT_STATUS_TEXT_INACTIVE = 'inactive';
	const PRODUCT_STATUS_TEXT_DELETED = 'deleted';
	
	public $opration = '';
	public $seller_name;
	
    public $tplParam = array(
            'scheme_name'           => '通用方案',
            'standard_profit_rate'  => 0.25,
            'lowest_profit_rate'    => 0.12,
            'floating_profit_rate'  => 0.02
    );

    public $brandByAccountID = array(
            '36' => 'easygobuy',
            '37' => 'Vktech',
            '38' => 'any4you',
            '39' => 'DomybestShop',
            '44' => 'RBO',
            '61' => 'Not Specified',
            '66' => 'Not Specified',
            '71' => 'Not Specified',
            '76' => 'Not Specified'
    ); 
        
	public static function model($className = __CLASS__) {
	    return parent::model($className);
	}
	
	/**
	 * @desc 设置表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_lazada_product';
	}

	/**
	 * @desc 设置账号ID
	 * @param integer $accountID
	 */
	public function setAccountID($accountID) {
		$this->_accountID = $accountID;
		return $this;
	}

	public function setAccountAutoID($accountAutoID) {
		$this->_accountAutoID = $accountAutoID;
		return $this;
	}
	
	/** @var 设置站点ID **/
	public function setSiteID($siteID) {
		$this->_siteID = $siteID;
		return $this;
	}
	
	/**
	 * @desc 设置日志编号
	 * @param integer $logID
	 */
	public function setLogID($logID) {
		$this->_logID = $logID;
	}
	
	/**
	 * @desc 设置异常信息
	 * @param string $message
	 */
	public function setExceptionMessage($message) {
		$this->_exception = $message;
	}
	
	/**
	 * @desc 获取异常信息
	 * @return string
	 */
	public function getExceptionMessage() {
		return $this->_exception;
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
	 * [getOneByCondition description]
	 * @param  string $fields [description]
	 * @param  string $where  [description]
	 * @param  mixed $order  [description]
	 * @return [type]         [description]
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
	 * @return [type]         [description]
	 * @author yangsh
	 */
	public function getListByCondition($fields='*', $where='1',$order='') {
		$cmd = $this->dbConnection->createCommand();
		$cmd->select($fields)
			->from(self::tableName())
			->where($where);
		$order != '' && $cmd->order($order);
		return $cmd->queryAll();
	}	

	/**
	 * @desc 保存product信息
	 * @param  object $product
	 * @return boolean
	 * @author yangsh
	 * @since 2016-06-23
	 */
	public function saveProductInfo($product) {
		try {
			$nowTime 		= date('Y-m-d H:i:s');
			$onlineSku 		= trim($product->SellerSku);
			$sku 			= encryptSku::getRealSku($onlineSku);
			$sku 			= $sku ? $sku : $onlineSku;
			$productID 		= implode('-',array($this->_siteID,$this->_accountID,$onlineSku));
			$productData 	= array(
				'account_auto_id' 	=> $this->_accountAutoID,
				'account_id' 		=> $this->_accountID,
				'site_id' 			=> $this->_siteID,
				'seller_sku'		=> $onlineSku,
				'sku'               => $sku,
				'shop_sku'          => trim($product->ShopSku),
				'name'              => trim($product->Name),
				'parent_sku'        => trim($product->ParentSku),
				'variation'         => trim($product->Variation),
				'quantity'          => (int)$product->Quantity,
				'available'         => (int)$product->Available,
				'price'	            => floatval($product->Price),
				'sale_price'        => isset($product->SalePrice) ? floatval($product->SalePrice) : 0,
				'sale_start_date'   => trim($product->SaleStartDate) == '' ? '0000-00-00 00:00:00' : trim($product->SaleStartDate),
				'sale_end_date'     => trim($product->SaleEndDate) == '' ? '0000-00-00 00:00:00' : trim($product->SaleEndDate),
				'status'            => self::getProductStatusByStatusText(trim($product->Status)),
				'status_text'       => trim($product->Status),
				'product_id'        => $productID,
				'url'               => trim($product->Url),
				'main_image'        => trim($product->MainImage),
				'tax_class'         => isset($product->TaxClass) ? trim($product->TaxClass) : '',
				'brand'             => isset($product->Brand) ? trim($product->Brand) : '',
				'primary_category'  => isset($product->PrimaryCategory) ? trim($product->PrimaryCategory) : '',
			);
			//检查账号listing是否存在，存在则更新记录，不存在则插入新纪录
			$listingID = self::IfListingExists($this->_siteID, $this->_accountID, $onlineSku);
			if ($listingID) {
				$productData['modify_time'] = $nowTime;
				$this->dbConnection->createCommand()->update(self::tableName(), $productData, "id = $listingID");
			} else {
				$productData['create_time'] = $nowTime;
				$this->dbConnection->createCommand()->insert(self::tableName(), $productData);
				$listingID = $this->dbConnection->getLastInsertID();
			}
			return $listingID;
		} catch (Exception $e) {
			$this->setExceptionMessage($e->getMessage());
			return false;
		}
	}	
	
	/**
	 * @desc 拉取账号LISTING保存到本地
	 * @param string $filter
	 * @param string $limit
	 * @return boolean
	 */
	public function getAccountListing($filter = null, $sellerSkuList = array(), $createdAfter = null, $createdBefore = null, $search = '') {
		try {
/* 			$transaction = $this->getDbConnection()->getCurrentTransaction();
			if (empty($transaction))
				$transaction = $this->getDbConnection()->beginTransaction(); */
			$page = 0;				//当前页数
			$limit = 200;			//每次拉取条数
			$offset = 0;			//拉取listing偏移量
			$getProductRequest = new GetProductsRequest();
			//拉取所有状态的产品
			if (is_null($filter))
				$getProductRequest->setFilter(GetProductsRequest::PRODUCT_STATUS_ALL);
			else 
				$getProductRequest->setFilter($filter);			//拉取指定状态的产品
			//指定要拉取的seller sku
			if (!empty($sellerSkuList)) {
				$getProductRequest->setSkuSellerList($sellerSkuList);
			}
			//设置拉取开始时间
			if (!empty($createdAfter))
				$getProductRequest->setCreatedAfter($createdAfter);
			//设置拉取结束时间
			if (!empty($createdBefore))
				$getProductRequest->setCreatedBefore($createdBefore);
			//设置搜索产品关键字
			if (!empty($search))
				$getProductRequest->setSearch($search);
			//设置一次搜索多少条
			$getProductRequest->setLimit($limit);
			$hasNextPage = true;		//是否有下一页
			//循环直到取完所有的产品
			while ($hasNextPage) {
				$offset = $page * $limit;
				$getProductRequest->setOffset($offset);
				$response = $getProductRequest->setSiteID($this->_siteID)->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
				if (!$getProductRequest->getIfSuccess()) {
					$this->setExceptionMessage($getProductRequest->getErrorMsg());
					return false;
				}
				if (empty($response->Body->Products)) {
					$hasNextPage = false;
					break;
				}
				$page++;
				//保存产品信息
				//$flag = $this->saveListingInfo($response->Body->Products->Product);
				$flag = $this->saveListingInfoNew($response->Body->Products->Product);
				if (!$flag) {
					//$transaction->rollback();
					//$this->setExceptionMessage(Yii::t('lazada_product', 'Save Product Info Failure'));
					return false;
				}
			}
//  			$transaction->commit();
			return true;
		} catch (Exception $e) {
// 			$transaction->rollback();
			$this->setExceptionMessage(Yii::t('lazada_product', $e->getMessage()));
			return false;
		}
	}
	
	
	/**
	 * @desc 保存listing信息
	 * @param object $products
	 * @return boolean
	 */
	public function saveListingInfo($products) {
		if (!empty($products)) {
			foreach ($products as $product) {
				$listingData = array();
				$onlineSku = trim($product->SellerSku);
				$sku = encryptSku::getRealSku($onlineSku);
				$sku = $sku ? $sku : $onlineSku;
				$listingData = array(
					'site_id' => $this->_siteID,
					'account_id' => $this->_accountID,
					'seller_sku' => $onlineSku,
					'sku' => $sku,
					'shop_sku' => trim($product->ShopSku),
					'name' => trim($product->Name),
					'parent_sku' => trim($product->ParentSku),
					'variation' => trim($product->Variation),
					'quantity' => (int)$product->Quantity,
					'available' => (int)$product->Available,
					'price'	=> floatval($product->Price),
					'sale_price' => empty($product->SalePrice) ? null : floatval($product->SalePrice),
					'sale_start_date' => empty($product->SaleStartDate) ? null : $product->SaleStartDate,
					'sale_end_date' => empty($product->SaleEndDate) ? null : $product->SaleEndDate,
					'status' => self::getProductStatusByStatusText(trim($product->Status)),
					'status_text' => trim($product->Status),
					'product_id' => trim($product->ProductId),
					'url' => $product->Url,
					'main_image' => $product->MainImage,
				);
				//检查账号listing是否存在，存在则更新记录，不存在则插入新纪录
				$listingID = self::IfListingExists($this->_siteID, $this->_accountID, $onlineSku);
				if ($listingID) {
					$listingData['modify_time'] = date('Y-m-d H:i:s');
					if (!$this->getDbConnection()->createCommand()->update(self::tableName(), $listingData, "id = $listingID"))
						return false;
				} else {
					$listingData['create_time'] = date('Y-m-d H:i:s');
					if (!$this->getDbConnection()->createCommand()->insert(self::tableName(), $listingData))
						return false;
				}		
			}
			return true;
		} else {
			return false;
		}
	}
	
	
	/**
	 * @desc 检查指定账号下某一SKU是否已经存在，存在返回ID号，不存在返回空
	 * @param string $accountId
	 * @param string $sku
	 * @return miexed
	 */
	public function IfListingExists($siteID = null, $accountId = null, $sku = null) {
		return $this->dbConnection->createCommand()->select("id")
			->from(self::tableName())
			->where("account_id = :account_id and site_id = :site_id and seller_sku = :sku", array(':account_id' => $accountId,':site_id' => $siteID,':sku' => $sku))
			->queryScalar();
	}
	
	/**
	 * @desc 获得产品的状态
	 * @param string $key
	 * @return Ambigous <NULL, Ambigous <string, string, unknown>>|multitype:NULL Ambigous <string, string, unknown>
	 */
	public static function getProductStatusList($key = null) {
		 $list = array(
		 	self::PRODUCT_STATUS_ACTIVE => Yii::t('lazada_product', 'Product Status Active'),
		 	self::PRODUCT_STATUS_INACTIVE => Yii::t('lazada_product', 'Product Status Inactive'),
		 	self::PRODUCT_STATUS_DELETED => Yii::t('lazada_product', 'Product Status Deleted'),
		 );
		 if (!is_null($key) && array_key_exists($key, $list))
		 	return $list[$key];
		 return $list;
	}
	
	/**
	 * @desc 根据产品状态文本值获取状态值
	 * @param string $textValue
	 * @return string
	 */
	public static function getProductStatusByStatusText($textValue = '') {
		switch ($textValue) {
			case self::PRODUCT_STATUS_TEXT_ACTIVE:
				return self::PRODUCT_STATUS_ACTIVE;
			case self::PRODUCT_STATUS_TEXT_INACTIVE:
				return self::PRODUCT_STATUS_INACTIVE;
			case self::PRODUCT_STATUS_TEXT_DELETED:
				return self::PRODUCT_STATUS_DELETED;
			default:
				return self::PRODUCT_STATUS_UNKNOW;
		}
	}

	
	/**
	 * @desc 获取指定sku的在线广告
	 * @param string $sku
	 */
	public function getOnlineListingBySku($sku,$accountID = '', $siteID = ''){
	    $skus = array();
	    if( is_string($sku) ){
	        $skus = array($sku);
	    }else{
	        $skus = $sku;
	    }
	    $where = '';
	    if( $accountID ){
	        $where .= ' AND account_id = '.$accountID;
	    }
	    if ($siteID)
	    	$where .= " and site_id = " . $siteID;
	    return $this->dbConnection->createCommand()
	           ->select('*')
	           ->from(self::tableName())
	           ->where('sku IN ('.MHelper::simplode($skus).')')
	           ->andWhere('status = 1'.$where)
	           ->queryAll();
	}
	
	/**
	 * @desc 根据条件查找在线广告
	 * @param unknown $param
	 */
	public function getProductByParam($param){
	    if( empty($param) ){
	        return array();
	    }else{
	        $sql = $this->dbConnection->createCommand()->select('*')->from(self::tableName())->where('1=1');
	        foreach($param as $col=>$item){
	            $sql = $sql->andWhere($col.' = "'.$item.'"');
	        }
	        return $sql->queryAll();
	    }
	}

	/**
	 * (non-PHPdoc)
	 * @see UebModel::search()
	 */
	public function search() {
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder' => 'create_time',
			'defaultDirection' => 'desc',
		);
		$dataProvider = parent::search(get_class($this), $sort, '', $this->_setDbCriteria());
		$datas = $this->addition($dataProvider->data);
		$dataProvider->setData($datas);
		return $dataProvider;
	}
	
	/**
	 * @desc 设置查询条件
	 * @return CDbCriteria
	 */
	protected function _setDbCriteria() {
		$criteria = new CDbCriteria();
		$account_id = '';
        $accountIdArr = array();
        if(isset(Yii::app()->user->id)){
            $accountIdArr = LazadaAccountSeller::model()->getListByCondition('account_id','seller_user_id = '.Yii::app()->user->id);
        }
        if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id']) ){
            $account_id = (int)$_REQUEST['account_id'];
        }
        if($accountIdArr && !in_array($account_id, $accountIdArr)){
            $account_id = implode(',', $accountIdArr);
        }
        if($account_id){
            $criteria->addCondition("t.account_id IN(".$account_id.")");
        }

		$seller_sku = isset($_POST['seller_sku'])?$_POST['seller_sku']:null;
		if(isset($seller_sku) && !empty($seller_sku)){
			$sellSkuArr = explode(";",$seller_sku);
			$criteria->addInCondition('seller_sku', $sellSkuArr);
		}
                
        //是否有库存
        if (isset($_REQUEST['quantity']) && !empty($_REQUEST['quantity'])){
            if((int)$_REQUEST['quantity'] == 2){
				$criteria->addCondition("quantity > 0" );
            } else {
                $criteria->addCondition("quantity = 0" );
            }
        }
		
        //是否有特价
        if (isset($_REQUEST['has_sale_price']) && !empty($_REQUEST['has_sale_price'])){
            if((int)$_REQUEST['has_sale_price'] == 1){
				$criteria->addCondition("sale_price is not null" );
            } else {
                $criteria->addCondition("sale_price is null" );
            }
        }

        //产品分类
        $categoryLevelOne = Yii::app()->request->getParam('category_level_one');
        if($categoryLevelOne){
        	$minimumCategoryArr = LazadaCategory::model()->getMinimumCategory($categoryLevelOne);
        	$criteria->addInCondition('primary_category', $minimumCategoryArr);
        }

		$criteria->select = "t.sku";
		$criteria->group = "t.sku";
		return $criteria;
	}
	
	/**
	 * @desc 处理数据
	 * @param array $datas
	 * @return array
	 */
	public function addition($datas) {
		$accountList = UebModel::model('LazadaAccount')->queryPairs(array('id', 'seller_name'));
		$sellerUserList = User::model()->getPairs();
		foreach ($datas as $key => $data) {
			//查找每个SKU刊登的所有listing记录
			$listings =  $this->getSkuListingBySearchCondition($data->sku);
			$datas[$key]->detail = array();
			if (!empty($listings)) {
				foreach ($listings as $k => $listing) {
					$accountInfo = LazadaAccount::model()->getApiAccountByIDAndSite($listing['account_id'], $listing['site_id']);
					$currencySymbol = LazadaSite::getSiteCurrencyList($listing['site_id']) . ' ';
					$listingLink = $listing['url'] ? CHtml::link($listing['seller_sku'], $listing['url'], array('target' => '_blank', 'style' => 'color:blue')) : $listing['seller_sku'];
					$shop_sku = $listing['shop_sku'];
                                        //$accountName = isset($accountList[$listing['account_id']]) ? $accountList[$listing['account_id']] : '';
					$accountName = $accountInfo['seller_name'];
					$specialPrice = $listing['sale_price'] !== null ? '<font color="red">' . $currencySymbol . $listing['sale_price'] . '</font>' : '--';
					$price = '<font color="red">' . $currencySymbol . $listing['price'] . '</font>';
					$saleStartDate = $listing['sale_start_date'] ? $listing['sale_start_date'] : '<font>--</font>';
					$saleEndDate = $listing['sale_end_date'] ? $listing['sale_end_date'] : '<font>--</font>';
					$name = $listing['url'] ? CHtml::link($listing['name'], $listing['url'], array('target' => '_blank', 'style' => 'color:blue')) : $listing['name'];
					$status = '';
					if ($listing['status'] == 1) {
						$status = '<font color="green">' . self::getProductStatusList($listing['status']) . '</font>';
					} else if ($listing['status'] == 2) {
						$status = '<font color="gray">' . self::getProductStatusList($listing['status']) . '</font>';
					} else if ($listing['status'] == 3) {
						$status = '<font color="red">' . self::getProductStatusList($listing['status']) . '</font>';
					}
					$productSellerRelationInfo = LazadaProductSellerRelation::model()->getProductSellerRelationInfoByItemIdandSKU($listing['product_id'], $listing['sku'], $listing['seller_sku']);
					$sellerName = $productSellerRelationInfo && isset($sellerUserList[$productSellerRelationInfo['seller_id']]) ? $sellerUserList[$productSellerRelationInfo['seller_id']] : '-';

					//获取利润率
					$currency = LazadaSite::getCurrencyBySite($listing['site_id']);
					$priceCal = new CurrencyCalculate();
			        $priceCal->setCurrency($currency);//币种
			        $priceCal->setPlatform(Platform::CODE_LAZADA);//设置销售平台
			        $priceCal->setSku($listing['sku']);//设置sku
			        $priceCal->setSalePrice($listing['sale_price']);
			        $priceCal->setSiteID($listing['site_id']);//设置站点
			        $profitRate = $priceCal->getProfitRate()*100;

					$datas[$key]->detail[$k] = array(
						'listing_id' => $listing['id'],
						'seller_sku' => $listingLink,
						'shop_sku' => $shop_sku,
						'name' => $name,
						'site_id' => LazadaSite::getSiteList($listing['site_id']),
						'account_name' => $accountName,
						'quantity' => $listing['quantity'],
						'price' => $price,
						'sale_price' => $specialPrice,
						'status' => $status,
						'sale_start_date' => date('Y-m-d',strtotime($saleStartDate)),
						'sale_end_date' => date('Y-m-d',strtotime($saleEndDate)),
						'offline'	=> $this->getOprationList($status = $listing['status'], $listing['id']),
						'seller_name'	=>	$sellerName,
						'primary_category' => LazadaCategory::model()->getBreadcrumbCategory($listing['primary_category']),
						'profit_margin' => round($profitRate,2).'%',
						'create_time' => $listing['create_time'],
					);
				}
			}
		}
		return $datas;
	}
	
	/**
	 * 操作多选项
	 * @param unknown $status
	 * @return string
	 */
	public function getOprationList($status, $id){
		
		if($status != 2){//未下线
                        $str = "<select style='width:75px;' onchange = 'offLine(this,".$id.")' ><option>".Yii::t('system', 'Please Select')."</option>";
			$str .= "<option value='offline'>".Yii::t('system', 'Off Line')."</option>";
                } else {
                        $str = "<select style='width:75px;' onchange = 'onLine(this,".$id.")' ><option>".Yii::t('system', 'Please Select')."</option>";
                        $str .= "<option value='online'>".Yii::t('system', 'OnLine')."</option>";
                }
		$str .="</select>";
		return $str;
	}
	
	/**
	 * @desc 根据搜索条件查询SKU对应listing记录
	 * @param string $sku
	 * @return Ambigous <multitype:, mixed>
	 */
	public function getSkuListingBySearchCondition($sku = '') {
		$criteria = new CDbCriteria();
		$criteria->addCondition("sku = '" . $sku . "'");
		if (isset($_REQUEST['site_id']) && !empty($_REQUEST['site_id']))
			$criteria->addCondition("site_id = " . (int)$_REQUEST['site_id']);
		if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id']))
			$criteria->addCondition("account_id = " . (int)$_REQUEST['account_id']);
		//status
		if (isset($_REQUEST['status']) && !empty($_REQUEST['status']))
			$criteria->addCondition("status = " . (int)$_REQUEST['status']);
                if (isset($_REQUEST['quantity']) && !empty($_REQUEST['quantity'])){
                    if((int)$_REQUEST['quantity'] == 2){
			$criteria->addCondition("quantity > 0" );
                    } else {
                        $criteria->addCondition("quantity = 0" );
                    }
                }
                if (isset($_REQUEST['has_sale_price']) && !empty($_REQUEST['has_sale_price'])){
                    if((int)$_REQUEST['has_sale_price'] == 1){
			$criteria->addCondition("sale_price is not null" );
                    } else {
                        $criteria->addCondition("sale_price is null" );
                    }
                }
                if (isset($_REQUEST['shop_sku']) && !empty($_REQUEST['shop_sku'])){
                        $shop_sku = trim($_REQUEST['shop_sku']);
			$criteria->addCondition("shop_sku like '{$shop_sku}%'" );
                    
                }
		
		$DbCommand = $this->getDbConnection()->createCommand()
			->from(self::tableName())
			->select("*")
			->where($criteria->condition);
		//echo $DbCommand->text;exit;	
		return $DbCommand->queryAll();
	}
	
	/**
	 * @desc 设置属性标签
	 * @see CModel::attributeLabels()
	 */
	public function attributeLabels() {
		return array(
			'sku'                => Yii::t('lazada_product', 'Sku'),
			'name'               => Yii::t('lazada_product', 'Name'),
			'seller_sku'         => Yii::t('lazada_product', 'Seller Sku'),
			'quantity'           => Yii::t('lazada_product', 'Stock Quantity'),
			'price'              => Yii::t('lazada_product', 'price'),
			'sale_price'         => Yii::t('lazada_product', 'Special Price'),
			'sale_start_date'    => Yii::t('lazada_product', 'Sale Start Date'),
			'sale_end_date'      => Yii::t('lazada_product', 'Sale End Date'),
			'status'             => Yii::t('lazada_product', 'Status'),
			'site_id'            => Yii::t('lazada_product', 'Site'),
			'account_id'         => Yii::t('lazada_product', 'Account'),
			'listing_id'         => Yii::t('lazada_product', 'Listing Tab Header Text'),
			'offline'            => Yii::t('lazada_product', 'OffLine Opration'),
			'opration'           => Yii::t('system', 'Oprator'),
			'account_name'       => Yii::t('lazada_product', 'Account Name'),
			'has_sale_price'     => Yii::t('lazada_product', '是否有特价'),
			'shop_sku'           => Yii::t('lazada_product', 'Shop Sku'),
			'seller_name'        =>	Yii::t('common', 'Seller Name'),
			'primary_category'   => '产品类目',
			'category_level_one' => '产品一级目录',
			'profit_margin'      => '利润率',
			'create_time'        => '创建时间'
		);
	}
	
	/**
	 * 更新产品线上状态
	 */
	public function updateStatus($id, $status) {
		return $this->dbConnection->createCommand()->update(self::tableName(), array('status'=>$status),'id=:id', array(':id'=>$id));
	}
	/**
	 * @desc 批量更新产品状态
	 * @param unknown $idarr
	 * @param unknown $status
	 * @return Ambigous <number, boolean>
	 */
	public function  batchUpdateStatus($idarr, $status){
		return $this->dbConnection
					->createCommand()
					->update(self::tableName(), array('status'=>$status), array('in', 'id', $idarr));
	}
	/**
	 * 
	 * @desc 获取产品状态选项 
	 * 
	 */
	private function getLazadaStatusOptions(){
		return array(
			self::PRODUCT_STATUS_ACTIVE => Yii::t('lazada_product', 'Product Status Active'),
			self::PRODUCT_STATUS_INACTIVE => Yii::t('lazada_product', 'Product Status Inactive'),
			self::PRODUCT_STATUS_DELETED => Yii::t('lazada_product', 'Product Status Deleted'),		
			//self::PRODUCT_STATUS_UNKNOW => Yii::t('lazada_product', 'Product Status Unkown'),		
		);
	}
	/**
	 * @desc 设置搜索条件
	 * @return multitype:
	 */
	public function filterOptions() {
		
		$result	= array(
			array(
				'name' => 'sku',
				'type' => 'text',
				'search' => 'LIKE',
				'htmlOption' => array(
					'size' => '22',
				),
			),
			array(
				'name' => 'site_id',
				'type' => 'dropDownList',
				'data' => LazadaSite::getSiteList(),
				'search' => '=',
				//'htmlOptions' => array('onchange' => 'getAccountList(this)'),
				'htmlOptions' => array(),

			),
			array(
				'name' => 'account_id',
				'type' => 'dropDownList',
				'data' => LazadaAccount::model()->getAccountList(Yii::app()->request->getParam('site_id')),
				'search' => '=',
			),	
			array(
					'name'	=>	'status',
					'type'	=>	'dropDownList',
					'data'	=>	$this->getLazadaStatusOptions(),
					'search'	=>	'='
				
			),
                        array(
                                        'name'	=>	'quantity',
                                        'type'	=>	'dropDownList',
                                        'data'	=>	array('1'=>'无库存','2'=>'有库存'),
                                        'search'	=>	'=',
                                        'rel'	=>	true

                        ),
			array(
					'name'	=>	'seller_sku',
					'type'	=>	'text',
					'search'	=>	'=',
					'rel'		=> 'true',
					'htmlOption' => array(
							'size' => '22',
					),
			),
                        array(
					'name'	=>	'has_sale_price',
					'type'	=>	'dropDownList',
                                        'data'	=>	array('1'=>'有特价','2'=>'无特价'),
                                        'search'	=>	'=',
                                        'rel'	=>	true
			),
                        array(
                                        'name' => 'shop_sku',
                                        'type' => 'text',
                                        'search' => 'LIKE',
                                        'htmlOption' => array(
                                                'size' => '22',
                                        ),
			),
			array(
                'name'          => 'create_time',
                'type'          => 'text',
                'search'        => 'RANGE',
                'htmlOptions'   => array(
                        'class'    => 'date',
                        'dateFmt'  => 'yyyy-MM-dd HH:mm:ss',
                ),
            ),
            array(
				'name' 			=> 'category_level_one',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'value' 		=> Yii::app()->request->getParam('category_level_one'),
				'data' 			=> CHtml::listData(LazadaCategory::model()->getCategoriesByParentID(), "category_id", "name"),
				'htmlOptions' 	=> array(
					'id' => 'category_level_one',
				),
				'rel' 			=> true,
			),
		);
 		$productAttributeListArr = UebModel::model('Product')->getAttributeIdAndNameList();
		foreach ($productAttributeListArr as $key => $val) {
			if($val['type']=='check_box'){
				$result[] = array(
						'label'			=> $key=='Product features'?Yii::t('product', 'Product attribute'):$key,
						'name'          => 'attribute_name',
						'type'          => 'checkBoxList',
						'rel'			=> true,
						'data'          => $val['attribute'],
						'clear'         => true,
						'hide'          => isset($_REQUEST['filterToggle']) ? $_REQUEST['filterToggle'] : 1,
						'htmlOptions'   => array( 'container' => '', 'separator' => ''),
				);
			}
		}
		$this->addFilterOptions($result);
		
		return $result;
	}
	
	/**
	 * add relate table filter conditions
	 *
	 * @return array $filterOptions
	 */
	public function addFilterOptions(&$result) {
		$skuArr = array();
		if(! empty($_REQUEST['attribute_name'])) {
			$attributeValueId = $_REQUEST['attribute_name'];
			$attributeSkuArr = UebModel::model('Product')->getAttributeStatusData($attributeValueId);
			$skuArr = !empty($skuArr) ? array_intersect($attributeSkuArr,$skuArr) : $attributeSkuArr;
			if(!$skuArr){
				$_REQUEST['search']['sku'] = 'null';
				return false;
			}
		}
		//$sell_sku = $_POST['seller_sku'];
		if($skuArr)	$_REQUEST['search']['sku'] = $skuArr;
	}
	
	/**
	 * @desc 获取菜单对应ID
	 * @return integer
	 */
	public static function getIndexNavTabId() {
		return UebModel::model('Menu')->getIdByUrl('/lazada/lazadaproduct/list');
	}
        
        /**
         * @desc 根据sku获取价格
         * @param string $sku
         * @param integer $site_id
         * @return float $price
         */
        public function getPriceBySku($sku, $site_id){
            $currency       = LazadaSite::getCurrencyBySite($site_id);
            //获取卖价
            //根据刊登条件匹配卖价方案
            $dataParam = array(
                ':platform_code'         => Platform::CODE_LAZADA,
                ':profit_calculate_type' => SalePriceScheme::PROFIT_SYNC_TO_SALE_PRICE
            );
            $schemeWhere = 'platform_code = :platform_code AND profit_calculate_type = :profit_calculate_type';
            $salePriceScheme = SalePriceScheme::model()->getSalePriceSchemeByWhere($schemeWhere,$dataParam);
            if (!$salePriceScheme) {
                $tplParam = $this->tplParam;
            } else {
                $tplParam = array(
                        'standard_profit_rate'  => $salePriceScheme['standard_profit_rate'],
                        'lowest_profit_rate'    => $salePriceScheme['lowest_profit_rate'],
                        'floating_profit_rate'  => $salePriceScheme['floating_profit_rate'],
                );
            }
            //计算卖价，获取描述
            $priceCal = new CurrencyCalculate();
            //设置参数值
            $priceCal->setProfitRate($tplParam['standard_profit_rate']);    //设置利润率
            $priceCal->setCurrency($currency);//币种
            $priceCal->setPlatform(Platform::CODE_LAZADA);                  //设置销售平台
            $priceCal->setSku($sku);                                        //设置sku
            $priceCal->setSiteID($site_id);//设置站点
            $price      = $priceCal->getSalePrice();                        //获取卖价
            return $price;
        }
        
        /**
         * @desc 根据sku获取信息
         * @param array $sku
         * @return array
         */
        public function getInfoBySku($sku){
            $result =  $this->dbConnection->createCommand()
                ->select('*')
                ->from(self::tableName())
                ->where('sku in(' . MHelper::simplode($sku) . ')')
                ->queryAll();
            return $result;
        }
        
    /**
     * @desc 将ueb_lazada_product当前表数据备份到历史表中
     */
    public function bakListing($log_id){
        $bak_time = date('Y-m-d H:i:s', time());

        //备份ueb_lazada_product表
        $flag = LazadaProduct::model()->getDbConnection()->createCommand()->update(LazadaProduct::model()->tableName(), array('bak_time' => $bak_time, 'log_id'=>$log_id), "account_id = '{$this->_accountID}' and site_id = '{$this->_siteID}'");
        if (!$flag){
            $this->setExceptionMessage('update log_id Failure');
            return false;
        }
        $sql_bak_listing = "insert into market_lazada.ueb_lazada_product_history select * from market_lazada.ueb_lazada_product where account_id = '{$this->_accountID}' and site_id = '{$this->_siteID}'";
        $flag = LazadaProduct::model()->getDbConnection()->createCommand($sql_bak_listing)->query();
        if (!$flag){
            $this->setExceptionMessage('bak listing Failure');
            return false;
        }

        return true;
    }
    
    /**
     * @desc 新保存listing信息
     * @param object $products
     * @return boolean
     */
    public function saveListingInfoNew($products) {
            $insert_data = array();
            $sellersku_array = array();
            if (!empty($products)) {
                    foreach ($products as $product) {
                            $onlineSku = trim($product->SellerSku);
                            $sku = encryptSku::getRealSku($onlineSku);
                            $sku = $sku ? $sku : $onlineSku;
                            $listingData = array(
                                    'site_id' => $this->_siteID,
                                    'account_id' => $this->_accountID,
                                    'seller_sku' => $onlineSku,
                                    'sku' => $sku,
                                    'shop_sku' => trim($product->ShopSku),
                                    'parent_sku' => trim($product->ParentSku),
                                    'name' => trim($product->Name),
                                    'variation' => trim($product->Variation),
                                    'quantity' => (int)$product->Quantity,
                                    'available' => (int)$product->Available,
                                    'price'	=> floatval($product->Price),
                                    'sale_price' => empty($product->SalePrice) ? null : floatval($product->SalePrice),
                                    'sale_start_date' => empty($product->SaleStartDate) ? null : $product->SaleStartDate,
                                    'sale_end_date' => empty($product->SaleEndDate) ? null : $product->SaleEndDate,
                                    'status' => self::getProductStatusByStatusText(trim($product->Status)),
                                    'status_text' => trim($product->Status),
                                    'product_id' => trim($product->ProductId),
                                    'url' => $product->Url,
                                    'main_image' => $product->MainImage,
                                    'create_time' => date('Y-m-d H:i:s', time()),
                                    'modify_time' => date('Y-m-d H:i:s', time()),
                                    'confirm_status' => 1,
                                    'bak_time' => date('Y-m-d H:i:s', time()),
                                    'log_id' => $this->_logID,
                            );
                            
                            $sellersku_array[] = $onlineSku;
                            $insert_data[] = $listingData;
                    }
                    
                    
                    if($sellersku_array){
                        //2根据sellersku删除listing
                        $flag = LazadaProduct::model()->getDbConnection()->createCommand()->delete(LazadaProduct::model()->tableName(), "account_id = '{$this->_accountID}' and site_id='{$this->_siteID}' and seller_sku IN (".MHelper::simplode($sellersku_array).")");
                        if (!$flag){
                            $this->setExceptionMessage('delete listing Failure');
                            return false;
                        }
                    }
                    //2批量插入listing
                    $table_listing = LazadaProduct::model()->tableName();
                    $flag = $this->insertBatch($insert_data, $table_listing);
                    if (!$flag){
                            $this->setExceptionMessage('insert batch listing Failure');
                            return false;
                    }
                    return true;
            } else {
                $this->setExceptionMessage('no product');
                return false;
            }
    }
    
    /**
     * @desc 批量插入数据
     * @param unknown $data
     */
    public function insertBatch($data, $table = null){
        if(!$table){
            $table = self::tableName();
        }
        if(empty($data)) { return true; }
        $columns = array();
        $sql = "INSERT INTO {$table} ( ";
        foreach ($data[0] as $column=>$value){
            $columns[] = $column;
            $sql .= '`' . $column . '`,';
        }
        $sql = substr($sql,0,strlen($sql)-1);
        $sql .= " ) VALUES ";
        foreach ($data as $one){
            $sql .= "(";
            foreach ($one as $value){
                $value = (!get_magic_quotes_gpc())?addslashes($value):$value;
                $sql .= '"' . $value . '",';
                //$sql .= '"' . $value . '",';
            }
            $sql = substr($sql,0,strlen($sql)-1);
            $sql .= "),";
        }
        $sql = substr($sql,0,strlen($sql)-1);
        return self::model()->getDbConnection()->createCommand($sql)->query();
    }


    /**
     * 从产品管理批量复制刊登
	 * @param string $productId
	 * @param integer $accountID
	 * @return boolean
     */
    public function productByCopy($productId, $accountID, $siteId){ 
		try{

			//判断要发布的产品ID是否为空
			if(!$productId){
				$this->setExceptionMessage("产品ID为空");
				return false;
			}

			//判断要发布的账号ID是否为空
			if(!$accountID){
				$this->setExceptionMessage("账号ID为空");
				return false;
			}

			$lazadaProductExtendModel = new LazadaProductExtend();
			$lazadaAccountModel = new LazadaAccount();
			//从产品表查询出要添加的sku信息
			$where = "p.id = '{$productId}' AND p.`status` = 1";
			$info = $this->getDbConnection()->createCommand()
			    ->select('p.sku,p.name,p.site_id,p.quantity,p.available,p.price,p.sale_price,p.brand,p.primary_category,e.images,e.description,e.product_data,p.account_auto_id')
				->from($this->tableName() . ' p')
				->leftJoin($lazadaProductExtendModel->tableName() . ' e', 'e.product_id = p.id')
				->where($where)
				->queryRow();
			if(!$info){
				$this->setExceptionMessage("查询信息为空");
				return false;
			}

			if($info['site_id'] != $siteId){
				$this->setExceptionMessage("复制的站点不相同");
				return false;
			}

			$accountListInfo = $lazadaAccountModel->getIdSellerNamePairs();

			$lazadaProductAddModel = new LazadaProductAdd();
			$lazadaProductAddAttributeModel = new LazadaProductAddAttribute();

			//判断待刊登列表是否存在
			$existProductAdd = $lazadaProductAddModel->getOneByCondition('sku',"sku = '{$info['sku']}' AND account_id = '{$accountID}' AND site_id = '{$info['site_id']}'");
			if($existProductAdd){
				$this->setExceptionMessage("复制的产品已经存在");
				return false;
			}

			//除了c账号以外的都加密
			$encryptSku = new encryptSku();
			// $not_crystalawaking_list = LazadaAccount::model()->getDbConnection()->createCommand()
   //                              ->from(LazadaAccount::tableName())
   //                              ->select("id")
   //                              ->where("account_id !=1")
   //                              ->queryColumn();


			//判断价格是否为0
			if($info['price'] <= 0){
				$this->setExceptionMessage("产品价格小于等于0");
				return false;
			}

			//取出产品信息
			$extendInfo = json_decode($info['product_data']);
			if(!isset($extendInfo->description_ms) || !isset($extendInfo->short_description)){
				$this->setExceptionMessage("产品描述不存在");
				return false;
			}

			$extendInfoArr = (array)$extendInfo;

			//取出产品描述
			$descriptionInfo = json_decode($info['description']);
			$description = $extendInfoArr['description_ms'];
			if(isset($descriptionInfo->description)){
				$description = $descriptionInfo->description;
			}

			//取出图片地址
			$imagesInfo = json_decode($info['images']);

			//除了c账号以外的都加密
			// $sellerSku = $info['sku'];
   //      	if (in_array($accountID, $not_crystalawaking_list)){
        		$sellerSku = $encryptSku->getEncryptSku($info['sku']);
            // }

            $times = date('Y-m-d H:i:s');

            //根据站点获取币种
            $currency = LazadaSite::model()->getCurrencyBySite($info['site_id']);
            if(!$currency){
            	$this->setExceptionMessage("获取币种失败");
				return false;
            }

            //获取价格
            $priceCal = new CurrencyCalculate();
            //设置参数值
            $priceCal->setProfitRate('0.1');//设置利润率
            $priceCal->setCurrency($currency);//币种
            $priceCal->setPlatform(Platform::CODE_LAZADA);//设置销售平台
            $priceCal->setSku($info['sku']);//设置sku
            $priceCal->setSiteID($info['site_id']);//设置站点

            $salePrice = $priceCal->getSalePrice();//获取卖价
            $price = round($salePrice / 0.5, 2);

            if($salePrice <= 0){
            	$this->setExceptionMessage("产品价格小于等于0");
				return false;
            }

            //获取品牌
            $brandParam = $this->brandByAccountID;
            $brand = $info['brand'];
            if(strtolower($brand) == 'vakind'){
	            $brand = isset($brandParam[$accountID])?$brandParam[$accountID]:$brand;
            }

			$addData = array(
				'site_id' => $info['site_id'],
				'account_id' => $accountID,
				'sku' => $info['sku'],
				'currency' => $currency,
				'listing_type' => 2,
				'title' => $info['name'],
				'price' => $price,
				'sale_price' => $salePrice,
				'brand' => $brand,
				'category_id' => $info['primary_category'],
				'create_user_id' => Yii::app()->user->id,
				'create_time' => $times,
				'modify_user_id' => Yii::app()->user->id,
				'modify_time' => $times,
				'seller_sku' => $sellerSku,
				'description' => $description,
				'highlight' => $extendInfoArr['short_description'],
				'add_type' => LazadaProductAdd::ADD_TYPE_COPY,
			);

			if($salePrice > 0){
				$addData['sale_price_start'] = date('Y-m-d 00:00:00');
				$addData['sale_price_end'] = date('Y-m-d 00:00:00', strtotime('+10 year'));
			}

			$attributesLabelArr = array();
            $response = LazadaCategoryAttribute::model()->getCategoryAttributeOnlineNew($accountID, $info['primary_category']);
            if(isset($response->Body)){
                $body = $response->Body;
                $body = (array)$body;
                $attr = $body['Attribute'];
                foreach($attr as $detail){
                    $labelName = (string)$detail->label;
                    $labelName = str_replace(' ', '', $labelName);
                    $valueName = (string)$detail->name;
                    if($valueName == 'storage_storage'){
                        $valueName = 'storage_capacity_new';
                    }
                    $attributesLabelArr[$valueName] = $labelName;
                }
            }

            if(empty($attributesLabelArr)){
                $this->setExceptionMessage("获取线上类目属性失败");
				return false;
            }

			try{
				$dbtransaction = $lazadaProductAddModel->getDbConnection()->beginTransaction();
				//插入图片
				$imgResult = LazadaProductImageAdd::model()->addGetJavaProductImageBySku($info['sku'], $accountID, Platform::CODE_LAZADA);
				if(!$imgResult){
					$this->setExceptionMessage("获取图片失败");
					$dbtransaction->rollback();
					return false;
				}

				$result = $lazadaProductAddModel->saveRecord($addData);
				if($result){

					//判断属性数组里是否有color_family，如果没有需要默认一个
		            $attributesKeyArr = array_keys($extendInfoArr);
		            if(!in_array('color_family', $attributesKeyArr)){
		            	$lazadaProductAddAttributeModel->saveRecord($result, 'ColorFamily', 'Black');
		            }

	                foreach ($attributesLabelArr as $k => $v) {
	                	if(in_array($k, array('name','short_description','description','SellerSku','quantity','name_ms','price','special_price','special_from_date','special_to_date','description_ms','package_content','package_weight','package_length','package_width','package_height','product_weight','__images__'))){
	                		continue;
	                	}

	                	$attributeName = isset($extendInfoArr[$k])?$extendInfoArr[$k]:'';
	                	if(!$attributeName){
	                		continue;
	                	}

	                	//重新赋值model
	                	if(strtolower($v) == 'model'){
	                		$attributeName = $accountListInfo[$accountID].'-'.$sellerSku;
	                	}

	                	//重新赋值brand
	                	if(strtolower($v) == 'brand'){
	                		$attributeName = $brand;
	                	}

	                	$lazadaProductAddAttributeModel->saveRecord($result, $v, $attributeName);
	                }
				}
				
				$dbtransaction->commit();
			}catch (Exception $e){
				$dbtransaction->rollback();
			}

			return true;

		}catch (Exception $e){
			$this->setExceptionMessage($e->getMessage());
			return false;
		}
	}


	/**
     * order field options
     * @return $array
     */
    public function orderFieldOptions() {
        return array(
            'create_time',
            'sku',
        );
    }
}