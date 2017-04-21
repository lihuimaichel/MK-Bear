<?php
class Jdproductstatistic extends UebModel{
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
				'data'		 => ProductCategoryOld::model()->ProductCategory(),
				'search'	 => '=',
				'rel'		 => 'selectedTodo',
				'htmlOptions'=> array(),		
			),	
			array(
					'name' 			=> 'account_id',
					'type' 			=> 'dropDownList',
					'search' 		=> '=',
					'data' 			=> CHtml::listData(UebModel::model('JdAccount')->findAll(), "id", "short_name"),
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
					'data' 			=> Product::getProductMultiList(),
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
		);
		return $result;
	}
	
	public function attributeLabels() {
		return array(
				'sku' => Yii::t('jd', 'Sku'),
				'product_category_id'=> Yii::t('jd','Product Category Id'),
				'account_id' => Yii::t('jd', 'Account Name'),
				'en_title'	 => Yii::t('jd', 'En Title'),
				'is_online'=>Yii::t('jd', 'Is Online'),
				'product_status'=>Yii::t('jd', 'Product Status'),
				'product_is_bak'=>Yii::t('jd', 'Product Is Bak'),
				'product_cost'	=> Yii::t('jd', 'Product Cost'),
				'product_is_multi' => Yii::t('jd', 'Product Is Multi'),
		);
	}
	
	protected function _setCDbCriteria() {
		$criteria = new CDbCriteria();
		$criteriaSku = new CDbCriteria();
		$skuArr = array();
		$notInSkuArr = array();
		$inSkuArr = array();
		if (isset($_REQUEST['sku']) && !empty($_REQUEST['sku'])) {
			$criteriaSku->addCondition("sku = '" . $_REQUEST['sku'] . "'");
			$skuArr[] = trim($_REQUEST['sku']);
		}
		if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id']))
			$criteriaSku->addCondition("account_id = " . (int)$_REQUEST['account_id']);
	
		$jdProduct = new JdProduct();
		$skuList = $jdProduct->getDbConnection()->createCommand()
												->select("sku")
												->from("ueb_jd_variants")
												//->join("ueb_jd_product_add_variation as pv", "p.sku = pv.sku")
												->where($criteriaSku->condition)
												//->group("pv.sku")
												->queryColumn();
		//var_dump($skuList);
		//$criteria->addInCondition("t.product_is_multi", array(Product::PRODUCT_MULTIPLE_NORMAL, Product::PRODUCT_MULTIPLE_VARIATION));
		if (isset($_REQUEST['product_is_multi']) && $_REQUEST['product_is_multi'] !== '') {
			$criteria->addInCondition("t.product_is_multi", array((int)$_REQUEST['product_is_multi']));
		}
		$isOnline = isset($_REQUEST['is_online']) ? $_REQUEST['is_online'] : '';
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
		//过滤分类
		if (isset($_REQUEST['product_category_id']) && !empty($_REQUEST['product_category_id'])) {
			$criteria->join = "join ueb_product_category_sku_old t1 on (t1.sku = t.sku) join ueb_product_category_old t2 on (t1.classid = t2.id)";
			$criteria->addCondition("t2.id = " . (int)$_REQUEST['product_category_id']);
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
}