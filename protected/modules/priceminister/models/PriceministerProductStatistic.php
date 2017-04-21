<?php
class PriceministerProductStatistic extends UebModel{
	public $en_title   = null;
	public $cn_title   = null;
	public $account_id = null;
	/**
	 * @desc 设置表名
	 * @author Michael
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

	public function relations() {
		return array();
	}

	public function filterOptions() {
		$isDisplayVariation = Yii::app()->request->getParam("is_display_variation");
		$result = array(
			array(
				'name'		 => 'sku',
				'search'	 => 'IN',
				'type'		 => 'text',
				'rel'		 => 'selectedTodo',
				'htmlOptions'=> array(),
			),
			array(
				'name'		 => 'product_category_id',
				'type'		 => 'dropDownList',
				'data'		 => ProductClass::model()->getProductClassPair(),
				'search'	 => '=',
				'rel'		 => 'selectedTodo',
				'htmlOptions'=> array(),
			),
			array(
				'name' 			=> 'account_id',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'data' 			=> CHtml::listData(UebModel::model('PriceministerAccount')->findAll(), "id", "user_name"),
				'htmlOptions' 	=> array(),
				'rel' 			=> 'selectedTodo',
			),
			array(
				'name' 			=> 'product_status',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'data' 			=> Product::getProductStatusConfig(),
				'htmlOptions' 	=> array(),
			),
			array(
				'name' 			=> 'product_is_bak',
				'type' 			=> 'dropDownList',
				'value' 		=> isset($_REQUEST['product_is_bak']) ? $_REQUEST['product_is_bak'] : '',
				'data' 			=> Product::getStockUpStatusList(),
				'search' 		=> '=',
				'htmlOptions' 	=> array(),
			),
			array(
				'name' 			=> 'product_is_multi',
				'type' 			=> 'dropDownList',
				'value' 		=> isset($_REQUEST['product_is_multi']) ? $_REQUEST['product_is_multi'] : '',
				'data' 			=> array('2' => Yii::t('system', 'Yes'), '0' => Yii::t('system', 'No')),
				'search' 		=> '=',
				'htmlOptions' 	=> array(
				),
				'rel' 			=> 'selectedTodo',
			),
			array(
				'name' 			=> 'product_cost',
				'type' 			=> 'text',
				'search' 		=> 'RANGE',
				'htmlOptions'	=> array(
					'size'=> 4
				),
			),
			array(
				'name'		 	=> 'is_online',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'data' 			=> array('1' => Yii::t('system', 'Yes'), '2' => Yii::t('system', 'No')),
				'htmlOptions' 	=> array(),
				'rel' 			=> 'selectedTodo',
			),
			array(
				'name'		 	=> 'is_display_variation',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'data' 			=> array('不显示','显示'),
				'value'			=> $isDisplayVariation,
				'htmlOptions' 	=> array(),
				'rel' 			=> 'selectedTodo',
			),
		);
		return $result;
	}

	public function attributeLabels() {
		return array(
			'sku' => Yii::t('priceminister', 'Sku'),
			'product_category_id'=> Yii::t('priceminister','Product Category Id'),
			'account_id' => Yii::t('priceminister', 'Account Name'),
			'en_title'	 => Yii::t('priceminister', 'En Title'),
			'is_online'=>Yii::t('priceminister', 'Is Online'),
			'product_status'=>Yii::t('priceminister', 'Product Status'),
			'product_is_bak'=>Yii::t('priceminister', 'Product Is Bak'),
			'product_cost'	=> Yii::t('priceminister', 'Product Cost'),
			'product_is_multi' => Yii::t('priceminister', 'Product Is Multi'),
			'is_display_variation'  => Yii::t('priceminister', 'Is Display Variation'),
		);
	}

	protected function _setCDbCriteria() {
		$criteria = new CDbCriteria();
		$criteriaSku = new CDbCriteria();
		$skuArr = array();
		$isSKU = false;
		//sku
		if (isset($_REQUEST['sku']) && !empty($_REQUEST['sku'])) {
			$criteriaSku->addCondition("sku = '" . $_REQUEST['sku'] . "'");
			$skuArr[] = trim($_REQUEST['sku']);
		}
		//account_id
		if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id']))
			$criteriaSku->addCondition("account_id = " . (int)$_REQUEST['account_id']);


		$isOnline = isset($_REQUEST['is_online']) ? $_REQUEST['is_online'] : '';

		if($isOnline){
			$isSKU = true;
		}
		if($isSKU){
			/*$ebayProduct = new EbayProduct();
			$criteriaSku->addCondition("p.listing_type <> 1");
			$criteriaSku->addCondition("p.item_status = 1");
			$skuList = $ebayProduct->getDbConnection()->createCommand()
				->select("p.sku")
				->from("ueb_ebay_product as p")
				//->join("ueb_ebay_product_variation as pv", "p.id = pv.listing_id")
				->where($criteriaSku->condition)
				->group("p.sku")
				->queryColumn();*/
		}

		//is_online
		if (!empty($skuList)) {
			if ($isOnline == 1) {
				$inSkuArr = $skuList;
				$criteria->addInCondition("t.sku", $inSkuArr);
			} else if ($isOnline == 2) {
				if (!empty($skuArr))
					$criteria->addCondition("1=0");
				else
					$criteria->addNotInCondition("t.sku", $skuList);
			}
		} else {
			if ($isOnline == 1)
				$criteria->addCondition('1=0');
			else if ($isOnline == 2) {
				if (!empty($skuArr))
					$criteria->addInCondition("t.sku", $skuArr);
			}
		}
		if ($isOnline === '' && !empty($skuArr))
			$criteria->addInCondition("t.sku", $skuArr);

		//分类
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

		//is_multi，子sku
		$isMulti = Yii::app()->request->getParam("product_is_multi");
		$isDisplayVariation = Yii::app()->request->getParam("is_display_variation");
		$productMulti = array();
		if($isMulti === ''){
			$productMulti[] = Product::PRODUCT_MULTIPLE_NORMAL;
			if($isDisplayVariation){
				$productMulti[] = Product::PRODUCT_MULTIPLE_VARIATION;
			}else{
				$productMulti[] = Product::PRODUCT_MULTIPLE_MAIN;
			}
		}else{
			if($isMulti == Product::PRODUCT_MULTIPLE_MAIN && $isDisplayVariation){
				$productMulti[] = Product::PRODUCT_MULTIPLE_VARIATION;
			}else{
				$productMulti[] = $isMulti;
			}
		}


		//成本
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
		}


		$criteria->addInCondition("t.product_is_multi", $productMulti);

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

			$data[$key]->en_title = isset($title['english'])?$title['english']:'';
			$data[$key]->cn_title = isset($title['Chinese'])?$title['Chinese']:'';
		}
		return $data;
	}
}