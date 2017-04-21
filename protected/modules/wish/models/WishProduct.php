<?php
/**
 * @desc wish listing model
 * @author liht
 * @since 20151117
 *
 */
class WishProduct extends WishModel {
	/** @var integer  账号ID **/
	protected $_accountID = null;
	
	/** @var integer **/
	protected $_siteID = null;
	
	/** @var integer 日志ID **/
	protected $_logID = null;

	/** @var string 异常信息*/
	protected $_exception = null;
	
	public $detail = array();
	
	public $listing_id = null;
	
	public $offline	= null;
	public $seller_name;
	
	const EVENT_NAME = 'getproduct';
	
	const PRODUCT_STATUS_UNKNOW = 0;						//未知状态
	const PRODUCT_STATUS_ACTIVE = 1;						//在线产品
	const PRODUCT_STATUS_INACTIVE = 2;						//不活跃的产品
	const PRODUCT_STATUS_DELETED = 3;						//已经删除的产品
	
	const PRODUCT_STATUS_TEXT_ACTIVE = 'active';
	const PRODUCT_STATUS_TEXT_INACTIVE = 'inactive';
	const PRODUCT_STATUS_TEXT_DELETED = 'deleted';
	
	public $opration = '';
	
	public static function model($className = __CLASS__) {
	    return parent::model($className);
	}
	
	/**
	 * @desc 设置表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_wish_listing';
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
			$limit = 1000;			//每次拉取条数
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
				$flag = $this->saveListingInfo($response->Body->Products->Product);
				if (!$flag) {
					//$transaction->rollback();
					$this->setExceptionMessage(Yii::t('lazada_product', 'Save Product Info Failure'));
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
	 * @desc 设置账号ID
	 * @param integer $accountID
	 */
	public function setAccountID($accountID) {
		$this->_accountID = $accountID;
	}
	
	/** @var 设置站点ID **/
	public function setSiteID($siteID) {
		$this->_siteID = $siteID;
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
				$listingID = self::IfListingExists($this->_siteID, $this->_accountID, $sku);
				if ($listingID) {
					$listingData['modify_time'] = date('Y-d-m H:i:s');
					if (!$this->getDbConnection()->createCommand()->update(self::tableName(), $listingData, "id = $listingID"))
						return false;
				} else {
					$listingData['create_time'] = date('Y-d-m H:i:s');
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
			->where("site_id = :site_id and account_id = :account_id and seller_sku = :sku", array(':site_id' => $siteID, ':account_id' => $accountId, ':sku' => $sku))
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
			'defaultOrder' => 'sku',
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
		$criteria->select = "t.*";
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
					$currencySymbol = LazadaSite::getSiteCurrencyList($listing['site_id']) . ' ';
					$listingLink = $listing['url'] ? CHtml::link($listing['seller_sku'], $listing['url'], array('target' => '_blank', 'style' => 'color:blue')) : $listing['seller_sku'];
					$accountName = isset($accountList[$listing['account_id']]) ? $accountList[$listing['account_id']] : '';
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
					$productSellerRelationInfo = WishProductSellerRelation::model()->getProductSellerRelationInfoByItemIdandSKU($listing['variation_product_id'], $val['sku'], $val['online_sku']);
					$sellerName = $productSellerRelationInfo && isset($sellerUserList[$productSellerRelationInfo['seller_id']]) ? $sellerUserList[$productSellerRelationInfo['seller_id']] : '-';
					$datas[$key]->detail[$k] = array(
						'listing_id' => $listing['id'],
						'seller_sku' => $listingLink,
						'name' => $name,
						'site_id' => LazadaSite::getSiteList($listing['site_id']),
						'account_name' => $accountName,
						'quantity' => $listing['quantity'],
						'price' => $price,
						'sale_price' => $specialPrice,
						'status' => $status,
						'sale_start_date' => $saleStartDate,
						'sale_end_date' => $saleEndDate,
						'offline'	=> $this->getOprationList($status = $listing['status'], $listing['id']),
						'seller_name'	=>	$sellerName
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
		$str = "<select style='width:75px;' onchange = 'offLine(this,".$id.")' ><option>".Yii::t('system', 'Please Select')."</option>";
		if($status != 2){//未下线
			$str .= "<option value='offline'>".Yii::t('system', 'Off Line')."</option>";
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
				'sku' => Yii::t('lazada_product', 'Sku'),
				'name' => Yii::t('lazada_product', 'Name'),
				'seller_sku' => Yii::t('lazada_product', 'Seller Sku'),
				'quantity' => Yii::t('lazada_product', 'Stock Quantity'),
				'price' => Yii::t('lazada_product', 'price'),
				'sale_price' => Yii::t('lazada_product', 'Special Price'),
				'sale_start_date' => Yii::t('lazada_product', 'Sale Start Date'),
				'sale_end_date' => Yii::t('lazada_product', 'Sale End Date'),
				'status' => Yii::t('lazada_product', 'Status'),
				'site_id' => Yii::t('lazada_product', 'Site'),
				'account_id' => Yii::t('lazada_product', 'Account'),
				'listing_id' => Yii::t('lazada_product', 'Listing Tab Header Text'),
				'offline'	=> Yii::t('lazada_product', 'OffLine Opration'),
				'opration'	=> Yii::t('system', 'Oprator'),
				'seller_name'	=>	Yii::t('common', 'Seller Name'),
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
			//self::PRODUCT_STATUS_DELETED => Yii::t('lazada_product', 'Product Status Deleted'),		
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
				'htmlOptions' => array('onchange' => 'getAccountList(this)'),

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
	
		if($skuArr)	$_REQUEST['search']['sku'] = $skuArr;
	}
	
	/**
	 * @desc 获取菜单对应ID
	 * @return integer
	 */
	public static function getIndexNavTabId() {
		return UebModel::model('Menu')->getIdByUrl('/lazada/lazadaproduct/list');
	}	
}