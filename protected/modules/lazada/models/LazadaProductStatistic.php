<?php
class LazadaProductStatistic extends UebModel {
	
	/**
	 * Security Level
	 * @var 安全级别
	 */
	const STATUS_SECURITY='A';
	const STATUS_POSSIBLE_INFRINGEMENT='B';
	const STATUS_INFRINGEMENT='C';
	const STATUS_VIOLATION='D';
	const STATUS_UNALLOCATED='E';
	
	/**
	 * @var 侵权种类
	 */
	const INFRINGEMENT_NORMAL_STATUS=1;
	const INFRINGEMENT_WEIGUI_STATUS=2;
	const INFRINGEMENT_QINQUAN_STATUS=3;
	
	/** @var string 产品英文名称 **/
	public $en_title = null;
	//中文标题
	public $cn_title = null;
	
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
	
	/**
	 * (non-PHPdoc)
	 * @see UebModel::search()
	 */
	public function search($model = null) {
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder' => 'create_time',
		);
		$dataProvider = parent::search($this, $sort, '', $this->_setDbCriteria());
		$datas = $this->addtion($dataProvider->data);
		$dataProvider->setData($datas);
		return $dataProvider;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see CActiveRecord::relations()
	 */
//	public function relations() {
//		return array(
//			'productdesc' => array(self::HAS_MANY, 'Productdesc', 'product_id'),
//		);
//	}
	
	/**
	 * @desc 处理列表数据
	 * @param unknown $datas
	 * @return unknown
	 */
	public function addtion($data) {
		foreach ($data as $key => $val) {

			$sku = $val['sku'];
			$title = Productdesc::model()->getTitleBySku($val['sku']);
                        if(!isset($title['english'])){
                            $title['english'] = array();
                        }
                        if(!isset($title['Chinese'])){
                            $title['Chinese'] = array();
                        }
			$data[$key]->en_title = $title['english'];
			$data[$key]->cn_title = $title['Chinese'];
                        
			if(empty($title['Chinese']) && empty($title['english'])) {
				//中英文标题都为空，如果是子sku情况，取父sku标题
				if(strpos($val['sku'],'.') !== false) {

					//子sku，取父sku标题
					$skuParent = (int)$val['sku'];

					$titleNew = Productdesc::model()->getTitleBySku($skuParent);
					$data[$key]->en_title = $titleNew['english'];
					$data[$key]->cn_title = $titleNew['Chinese'];

				}

			}

		}
		return $data;
	}
	
	/**
	 * @desc 设置列表条件
	 * @return CDbCriteria
	 */
	protected function _setDbCriteria() {
		$criteria = new CDbCriteria();
		$criteriaSku = new CDbCriteria();
		$skuArr = array();
		$notInSkuArr = array();
		$inSkuArr = array();
		$isSKU = false;
		if (isset($_REQUEST['sku']) && !empty($_REQUEST['sku'])) {
			$criteriaSku->addCondition("sku = '" . $_REQUEST['sku'] . "'");
			$skuArr[] = trim($_REQUEST['sku']);
			$isSKU = true;
		}
		if(isset($_REQUEST['seller_sku']) && !empty($_REQUEST['seller_sku'])){
			$sellSkuArr = explode(";",$_REQUEST['seller_sku']);
			foreach($sellSkuArr as $value)
			{
				$sellerSku[] = "'".$value."'";
			}
			$criteriaSku->condition='`seller_sku` in ('.implode(",",$sellerSku).")";
			$isSKU = true;
		}
		if (!empty($_REQUEST['en_title'])) {
			$criteria->join = "join " . Productdesc::model()->tableName() . " as t3 on (t.id = t3.product_id)";
			$title =$this->merge_spaces(trim($_REQUEST['en_title']));
			$criteria->addSearchCondition("t3.title", $title);
			//$titleSkuArr = Productdesc::model()->getSkuByTitle($title);
			//$skuArr = !empty($skuArr) ? array_intersect($titleSkuArr,$skuArr) : $titleSkuArr;
		}
		$siteID = LazadaSite::SITE_MY;
		if (isset($_REQUEST['site_id']) && !empty($_REQUEST['site_id'])) {
			$criteriaSku->addCondition("site_id = " . (int)$_REQUEST['site_id']);
			$isSKU = true;
		}else {
			$criteriaSku->addCondition("site_id = " . $siteID);
			$isSKU = true;
		}
		if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])){
			$criteriaSku->addCondition("account_id = " . (int)$_REQUEST['account_id']);
			$isSKU = true;
		}
			
		if (!isset($_REQUEST['is_online'])) {
			$_REQUEST['is_online'] = "";
		}
		$LazadaProduct = new LazadaProduct();
		$isOnline = isset($_REQUEST['is_online']) ? $_REQUEST['is_online'] : null;
		$skuList = array();
		if($isOnline && $isSKU){//数据量过大
			$criteriaSku->addCondition("site_id=1");//只查马来西亚站点
			$skuList = $LazadaProduct->getDbConnection()->createCommand()->select("sku")
			->from("ueb_lazada_product")
			->where($criteriaSku->condition)
			//->having($criteriaSku->having)
			->group("sku")
			->queryColumn();
		}
		
		$lazadaWare = new LazadaProductWarehouse();
		if(isset($_REQUEST['stock_status']) && !empty($_REQUEST['stock_status'])){
			/* if($_REQUEST['stock_status']==1){
				$productHouseList = $lazadaWare->getAvailableQtyBySku($skuList);
				$criteria->addInCondition("sku", $productHouseList);
			}elseif ($_REQUEST['stock_status']==2){
				$productHouseList = $lazadaWare->getAvailableQtyBySku($skuList);
				$criteria->addNotInCondition("sku", $productHouseList);
			} */
			$criteria->join .= " join ueb_warehouse." . $lazadaWare->tableName() . " as ws on (t.sku = ws.sku)";
			if($_REQUEST['stock_status']==1){
				$criteria->addCondition("ws.available_qty>2");
			}elseif ($_REQUEST['stock_status']==2){
				$criteria->addCondition("ws.available_qty<=2");
			}
			//var_dump($productHouseList);
		}
		//$criteria->addInCondition("t.product_is_multi", array(Product::PRODUCT_MULTIPLE_NORMAL,Product::PRODUCT_MULTIPLE_VARIATION));
		
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
			$criteria->join .= " join ueb_product_category_sku_old t1 on (t1.sku = t.sku) join ueb_product_category_old t2 on (t1.classid = t2.id)";
			$criteria->addCondition("t2.id = " . (int)$_REQUEST['product_category_id']);
		} */
		
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
		
		if ((isset($_REQUEST['security_level']) && !empty($_REQUEST['security_level'])) ||
		 (isset($_REQUEST['infringement']) && !empty($_REQUEST['infringement']))) {
			$criteria->join .=" join ueb_product_infringement t4 on (t.sku = t4.sku)";
			if (!empty($_REQUEST['security_level']))
				$criteria->addCondition("t4.security_level='".$_REQUEST['security_level']."'");
			if (!empty($_REQUEST['infringement']))
				$criteria->addCondition("t4.infringement=".$_REQUEST['infringement']);
		}
		//var_dump($criteria);
		return $criteria;
	}
	
	/**
	 * @desc 设置搜索条件
	 * @return multitype:
	 */
	public function filterOptions() {
		$classId = Yii::app()->request->getParam("product_category_id");
		$onlineCategoryId = Yii::app()->request->getParam("online_category_id");
		$isMulti = Yii::app()->request->getParam("product_is_multi");
		$isDisplayVariation = Yii::app()->request->getParam("is_display_variation");
		$result = array(
			array(
				'name' => 'sku',
				'search' => 'IN',
				'type' => 'text',
				'rel' => 'selectedTodo',
				'htmlOptions' => array(),
			),
			array(
					'name' => 'seller_sku',
					'search' => '=',
					'type' => 'text',
					'rel' => true,
					'htmlOptions' => array(),
			),
			array(
					'name' => 'en_title',
					'search' => 'LIKE',
					'type' => 'text',
					'rel' => true,
					'htmlOptions' => array(),
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
				'name' => 'site_id',
				'type' => 'dropDownList',
				'search' => '=',
				'data' => LazadaSite::getSiteList(),
				//'htmlOptions' => array('onchange' => 'getAccountList(this)'),
				'rel' => 'selectedTodo',
			),
			array(
				'name' => 'account_id',
				'type' => 'dropDownList',
				'search' => '=',
				'data' => LazadaAccount::model()->getAccountList(Yii::app()->request->getParam('site_id')),
				//'data' => CHtml::listData(UebModel::model('LazadaAccount')->findAll(), "id", "seller_name"),
				'htmlOptions' => array(),
				'rel' => 'selectedTodo',
			),				
			
			array(
				'name' => 'product_status',
				'type' => 'dropDownList',
				'search' => '=',
				'data' => Product::getProductStatusConfig(),
				'htmlOptions' => array(),		
			),
			array(
				'name' => 'product_is_bak',
				'type' => 'dropDownList',
				'value' => isset($_REQUEST['product_is_bak']) ? $_REQUEST['product_is_bak'] : '',
				'data' => Product::getStockUpStatusList(),
				'search' => '=',
				'htmlOptions' => array(
				),
			),				
			/* array(
				'name' => 'product_cost',
				'type' => 'text',
				'search' => 'RANGE',
				'htmlOptions' => array(
					'size' => 4,
				),
			), */
			array(
				 'name' => 'stock_status',
				 'type' => 'dropDownList',
				 'search' => '=',
				 'data'	 => array('1'=> Yii::t('system','Yes'),'2'=>Yii::t('system','No')),
				 'htmlOptions' =>array(),
				 'rel'	 => 'selectedTodo',	
			),
			
			
			array(
					'name'          => 'security_level',
					'type'          => 'dropDownList',
					'data'          => $this->getProductSecurityList(),
					'search'        => '=',
					'rel'			=> true,
					'htmlOptions'   => array(
								
					),
			),
			array(
					'name'          => 'infringement',
					'type'          => 'dropDownList',
					'data'          => $this->getProductInfringementList(),
					'search'        => '=',
					'rel'			=> 'true',
					'htmlOptions'   => array(
								
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
					'name' => 'is_online',
					'type' => 'dropDownList',
					'search' => '=',
					'data' => array('1' => Yii::t('system', 'Yes'), '2' => Yii::t('system', 'No')),
					'htmlOptions' => array(),
					'rel' => 'selectedTodo',
			),
				
			array(
					'name' => 'lazada_category_id',
					'type' => 'multiSelect',
					'search' => 'IN',
					'rel'	=> 'true',
					'dialog' => 'lazada/lazadacategory/CategoryTreeBatch',
					'htmlOptions' => array(
							'width' => '1050',
							'height' => '500',
					),
			),
		);
		return $result;
	}
	
	public function getProductInfringementList($num=null){
		$Infringement= array(
				self::INFRINGEMENT_NORMAL_STATUS     	 =>Yii::t('lazada_product_statistic', 'Nomal'),
				self::INFRINGEMENT_WEIGUI_STATUS    	 =>Yii::t('lazada_product_statistic', 'Is Infringe'),
				self::INFRINGEMENT_QINQUAN_STATUS     	 =>Yii::t('lazada_product_statistic', 'Is Violation'),
		);
		if($num!==null){
			return $Infringement[$num];
		}else{
			return $Infringement;
		}
	}
	
	public function getProductSecurityList(){
		return array(
				self::STATUS_SECURITY 				=> Yii::t('lazada_product_statistic', 'Security'),
				self::STATUS_POSSIBLE_INFRINGEMENT 	=> Yii::t('lazada_product_statistic', 'Possible infringement'),
				self::STATUS_INFRINGEMENT 			=> Yii::t('lazada_product_statistic', 'Tort'),
				self::STATUS_VIOLATION 				=> Yii::t('lazada_product_statistic', 'Violation'),
				self::STATUS_UNALLOCATED 			=> Yii::t('lazada_product_statistic', 'Unallocateds'),
		);
	}
	
	/**
	 * 标题搜索 去掉标题中多余空格只保留一个
	 * @param  $string
	 *
	 */
	public static function merge_spaces($string){
		return preg_replace ("/\s(?=\s)/","\\1", $string);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see CModel::attributeLabels()
	 */
	public function attributeLabels() {
		return array(
			'sku'	=> Yii::t('lazada_product_statistic', 'Sku'),
			'seller_sku'=> Yii::t('lazada_product_statistic', 'Seller Sku'),	
			'en_title' => Yii::t('lazada_product_statistic', 'Product Title'),
			'product_cost' => Yii::t('lazada_product_statistic', 'Product Cost'),
			'stock_status' => Yii::t('lazada_product_statistic', 'Stock Status'),
			'product_category_id' => Yii::t('lazada_product_statistic', 'Product Category'),
			'lazada_category_id' => Yii::t('lazada_product_statistic', 'lazada Category'),
			'account_id' => Yii::t('lazada_product_statistic', 'Account'),
			'product_status' => Yii::t('lazada_product_statistic', 'Product Status'),
			'site_id' => Yii::t('lazada_product_statistic', 'Site'),
			'online_number' => Yii::t('lazada_product_statistic', 'Online Number'),
			'product_is_bak' => Yii::t('lazada_product_statistic', 'If Stock Up'),
			'is_online' => Yii::t('lazada_product_statistic', 'Is Online'),
			'security_level'=>Yii::t('lazada_product_statistic','Security Level'),
			'infringement'  =>Yii::t('lazada_product_statistic','Infringement'),
			'infringement category'=>Yii::t('lazada_product_statistic','Infringement Category'),
			'product_is_multi'		=> Yii::t('ebay', 'Product Is Multi'),
			'online_category_id'	=> Yii::t('ebay', 'Online Category ID'),
			'is_display_variation'  => Yii::t('ebay', 'Is Display Variation'),
		);
	}
}