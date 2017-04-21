<?php
class AliexpressProductStatistic extends UebModel {
	
	/** @var string 产品英文名称 **/
	public $en_title = null;
	//产品中文标题
	public $cn_title = null;

	public $account_id = null;

	//sku已发布的账号
	public $skuInAccount = '';

	//sku已发布的账号
	public $ProductCategoryString = '';
	
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
				'product_cost' 			=> Yii::t('aliexpress_product_statistic', 'Product Cost'),
				'product_category_id' 	=> Yii::t('aliexpress_product_statistic', 'Product Category'),
				'account_id' 			=> '已发布的账号',
				'not_in_account_id' 	=> '不包含已发布的账号',
				'publish_account_id' 	=> '要发布的账号',
				'product_status' 		=> Yii::t('aliexpress_product_statistic', 'Product Status'),
				'online_number' 		=> Yii::t('aliexpress_product_statistic', 'Online Number'),
				'product_is_bak' 		=> Yii::t('aliexpress_product_statistic', 'If Stock Up'),
				'is_online' 			=> Yii::t('aliexpress_product_statistic', 'Is Online'),
				'skuinaccount'			=> '已发布的账号',
				'product_is_multi'		=> '是否是多属性',
				'is_child_sku'          => '是否显示子SKU',
				'title'					=> '产品名称',
				'create_time' 			=> '产品创建时间',
				'product_stock'			=> '可用库存数量',
				'publish_group_id' 		=> '要发布的产品分组',
				'productcategorystring' => '所选已发布的账号产品类目',
				'product_category'		=> '产品类目一级',
				'product_category_two'	=> '产品类目二级',
				'product_category_three'	=> '产品类目三级',
				'product_category_four'	=> '产品类目四级',
				'module_id' 			=> '产品信息模块',
				'freight_template_id'   => '运费模板',
		);
	}
	
	/**
	 * @return array search filter (name=>label)
	 */
	public function filterOptions() {
		$categoryTwoArr = array();
		$categoryThreeArr = array();
		$categoryfourArr = array();
		$moduleArr = array();
		$freightTemplateArr = array();
		$isChildSku = Yii::app()->request->getParam("is_child_sku");
		$isMulti = Yii::app()->request->getParam("product_is_multi");
		$productCategory = Yii::app()->request->getParam("product_category");
		$categoryTwo = Yii::app()->request->getParam("product_category_two");
		$categoryThree = Yii::app()->request->getParam("product_category_three");
		$addAccountId = Yii::app()->request->getParam('publish_account_id');
		//获取二级类目
		if(intval($productCategory) > 0){
			$categoryTwoArr = CHtml::listData(AliexpressCategory::model()->getCategoryDataByparentCategoryId($productCategory), "id", "name");
		}

		//获取三级类目
		if(intval($categoryTwo) > 0){
			$categoryThreeArr = CHtml::listData(AliexpressCategory::model()->getCategoryDataByparentCategoryId($categoryTwo), "id", "name");
		}

		//获取四级类目
		if(intval($categoryThree) > 0){
			$categoryfourArr = CHtml::listData(AliexpressCategory::model()->getCategoryDataByparentCategoryId($categoryThree), "id", "name");
		}

		//获取产品模块
		if(intval($addAccountId) > 0){
			$moduleArr = CHtml::listData(Aliexpressproductinfomodule::model()->getModuleFieldsByAccountId($addAccountId), "module_id", "name");
			$freightTemplateArr = CHtml::listData(AliexpressFreightTemplate::model()->getTemplateIdInfoByAccountId($addAccountId), "template_id", "template_name");
		}

		$result = array(
			array(
				'name'		 	=> 'title',
				'search' 		=> '=',
				'type' 			=> 'text',
				'rel' 			=> 'selectedTodo',
				'htmlOptions'	=> array(),
			),
			array(
				'name'		 	=> 'sku',
				'search' 		=> 'IN',
				'type' 			=> 'text',
				'rel' 			=> 'selectedTodo',
				'htmlOptions'	=> array(),
			),
			// array(
			// 	'name' 			=> 'product_category_id',
			// 	'type'			=> 'dropDownList',
			// 	'data'		    => ProductCategoryOld::model()->ProductCategory(),
			// 	'search'		=> '=',
			// 	'rel'			=> 'selectedTodo',
			// 	'htmlOptions' 	=> array(),
			// ),
			array(
				'name'		 	=> 'is_online',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'data' 			=> array('1' => Yii::t('system', 'Yes'), '2' => Yii::t('system', 'No')),
				'htmlOptions' 	=> array(),
				'rel' 			=> 'selectedTodo',
								
			),
			array(
				'name' 			=> 'account_id',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'data' 			=> CHtml::listData(UebModel::model('AliexpressAccount')->findAll(), "id", "short_name"),
				'htmlOptions' 	=> array(
					'id'=>'online_account_id',
				),
				'rel' 			=> 'selectedTodo',
			),
			array(
				'name' 			=> 'not_in_account_id',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'data' 			=> CHtml::listData(UebModel::model('AliexpressAccount')->findAll(), "id", "short_name"),
				'htmlOptions' 	=> array(),
				'rel' 			=> 'selectedTodo',
			),
			array(
				'name' 			=> 'publish_account_id',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'data' 			=> CHtml::listData(UebModel::model('AliexpressAccount')->findAll(), "id", "short_name"),
				'htmlOptions' 	=> array(
					'id'=>'publish_account_id',
				),
				'rel' 			=> true,
			),
			array(
				'name' 			=> 'publish_group_id',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'value' 		=> Yii::app()->request->getParam('publish_group_id'),
				'data' 			=> array(),
				'htmlOptions' 	=> array(
					'id' => 'publish_group_id',
				),
				'rel' 			=> true,
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
				'value'			=> $isMulti,
				'data' 			=> array('2' => '是', '0' => '否'),
				'search' 		=> '=',
				'htmlOptions' 	=> array(),
			),	
			array(
				'name' 			=> 'is_child_sku',
				'type' 			=> 'dropDownList',
				'value'			=> $isChildSku,
				'data' 			=> array('1' => '显示', '0' => '不显示'),
				'search' 		=> '=',
				'htmlOptions' 	=> array(),
				'rel' 			=> true
			),	
			array(
				'name' 			=> 'product_stock',
				'type' 			=> 'text',
				'search' 		=> 'RANGE',
				'htmlOptions'	=> array(
					'size' => 4,
				),
				'rel' 			=> true,
			),				
            array(
				'name' 			=> 'product_cost',
				'type' 			=> 'text',
				'search' 		=> 'RANGE',
				'htmlOptions'	=> array(
					'size' => 4,
				),
				'rel'			=> true,
			),
			array(
                'name'          => 'create_time',
                'type'          => 'text',
                'search'        => 'RANGE',
                'alias'			=>	't',
				'htmlOptions'	=> array(
					'size' => 4,
					'class'=>'date',
					'style'=>'width:80px;'
				),
            ),
			array(
				'name' 			=> 'product_category',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'value' 		=> Yii::app()->request->getParam('product_category'),
				'data' 			=> CHtml::listData(AliexpressCategory::model()->getCategoryDataByparentCategoryId(0), "id", "name"),
				'htmlOptions' 	=> array(
					'id' => 'product_category',
				),
				'rel' 			=> true,
			),

			array(
				'name' 			=> 'product_category_two',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'value' 		=> Yii::app()->request->getParam('product_category_two'),
				'data' 			=> $categoryTwoArr,
				'htmlOptions' 	=> array(
					'id' => 'product_category_two',
				),
				'rel' 			=> true,
			),

			array(
				'name' 			=> 'product_category_three',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'value' 		=> Yii::app()->request->getParam('product_category_three'),
				'data' 			=> $categoryThreeArr,
				'htmlOptions' 	=> array(
					'id' => 'product_category_three',
				),
				'rel' 			=> true,
			),

			array(
				'name' 			=> 'product_category_four',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'value' 		=> Yii::app()->request->getParam('product_category_four'),
				'data' 			=> $categoryfourArr,
				'htmlOptions' 	=> array(
					'id' => 'product_category_four',
				),
				'rel' 			=> true,
			),

			array(
				'name' 			=> 'module_id',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'value' 		=> Yii::app()->request->getParam('module_id'),
				'data' 			=> $moduleArr,
				'htmlOptions' 	=> array(
					'id' => 'module_id',
				),
				'rel' 			=> true,
			),

			array(
				'name' 			=> 'freight_template_id',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'value' 		=> Yii::app()->request->getParam('freight_template_id'),
				'data' 			=> $freightTemplateArr,
				'htmlOptions' 	=> array(
					'id' => 'freight_template_id',
				),
				'rel' 			=> true,
			),
					
		);
	
		return $result;
	
	}
	/**
	 * search SQL
	 * @return $array
	 */
	protected function _setCDbCriteria() {
		$criteria = new CDbCriteria();
		$criteriaSku = new CDbCriteria();
		$aliexpressProduct = new AliexpressProduct();
		$skuArr = array();
		$notInSkuArr = array();
		$inSkuArr = array();
		$notInSkuListArr = array();
		$isSKU = false;
		$skuList = '';

		//产品标题搜索
		if(isset($_REQUEST['title']) && !empty($_REQUEST['title']) && empty($_REQUEST['sku'])){
			$skus = Productdesc::model()->getSkuByAllTitle($_REQUEST['title']);
			if($skus){
				$criteria->addInCondition("t.sku", $skus);
			}else{
				$criteria->addCondition("0=1");
			}
			
		}

		//sku搜索
		if (isset($_REQUEST['sku']) && !empty($_REQUEST['sku'])) {
			$sku = trim($_REQUEST['sku']);
			$criteriaSku->addCondition("p.sku = '" . $sku . "'");
			$criteria->addCondition("t.sku='".$sku."'");
			$skuArr = array($sku);
		}

		//是否在线搜索
		$isOnline = isset($_REQUEST['is_online']) ? $_REQUEST['is_online'] : null;
		if($isOnline){
			$isSKU = true;
		}

		//已发布的账号
		$accountId = isset($_REQUEST['account_id']) ? $_REQUEST['account_id'] : null;
		if ($accountId){
			$criteriaSku->addCondition("p.account_id = " . $_REQUEST['account_id']);
			$isSKU = true;
		}

		//不包含已发布的账号
		$notInAccountId = isset($_REQUEST['not_in_account_id']) ? $_REQUEST['not_in_account_id'] : null;
		if($notInAccountId){
			$notInSkuList = $aliexpressProduct->getDbConnection()->createCommand()
				 ->select("sku")
				 ->from("ueb_aliexpress_product")
				 ->where("account_id = ". intval($notInAccountId))
				 ->group("sku")
				 ->queryColumn();

			if($notInSkuList){
				$notInSkuListArr = $notInSkuList;
			}
		}

		if($isSKU){

			//类目过滤
			$categoryFour = Yii::app()->request->getParam("product_category_four");
			$categoryThree = Yii::app()->request->getParam("product_category_three");
			$categoryTwo = Yii::app()->request->getParam("product_category_two");
			$categoryOne = Yii::app()->request->getParam("product_category");
			if($categoryFour){
				$criteriaSku->addCondition('p.category_id = '.$categoryFour);
			}elseif($categoryThree){
				//判断有没有下级分类
				$categoryThreeData = AliexpressCategory::model()->getCategoriesByParentID($categoryThree);
				if($categoryThreeData){
					foreach ($categoryThreeData as $tk => $tv) {
						$threeData[] = $tv['category_id'];
					}
					$threeString = implode(',', $threeData);
					$criteriaSku->addCondition('p.category_id IN ('.$threeString.')'); 
				}else{
					$criteriaSku->addCondition('p.category_id = '.$categoryThree);
				}
			}elseif($categoryTwo){
				//判断有没有下级分类
				$categoryTwoData = AliexpressCategory::model()->getCategoriesByParentID($categoryTwo);
				if($categoryTwoData){
					foreach ($categoryTwoData as $wk => $wv) {
						//判断有没有4级分类
						$fourCategoryData = AliexpressCategory::model()->getCategoriesByParentID($wv['category_id']);
						if($fourCategoryData){
							foreach ($fourCategoryData as $tfkey => $tfvalue) {
								$twoData[] = $tfvalue['category_id'];
							}
						}else{
							$twoData[] = $wv['category_id'];
						}
					}
					$twoString = implode(',', $twoData);
					$criteriaSku->addCondition('p.category_id IN ('.$twoString.')'); 
				}else{
					$criteriaSku->addCondition('p.category_id = '.$categoryTwo);
				}
			}elseif($categoryOne){
				//判断有没有下级分类
				$categoryOneData = AliexpressCategory::model()->getCategoriesByParentID($categoryOne);
				if($categoryOneData){
					foreach ($categoryOneData as $ok => $ov) {
						//判断有没有3级分类
						$twoCategoryData = AliexpressCategory::model()->getCategoriesByParentID($ov['category_id']);
						if($twoCategoryData){
							foreach ($twoCategoryData as $kt => $vt) {
								//判断有没有4级分类
								$threeCategoryData = AliexpressCategory::model()->getCategoriesByParentID($vt['category_id']);
								if($threeCategoryData){
									foreach ($threeCategoryData as $kh => $vh) {
										$oneData[] = $vh['category_id'];
									}
								}else{
									$oneData[] = $vt['category_id'];
								}
							}
						}else{
							$oneData[] = $ov['category_id'];
						}
					}
					$oneString = implode(',', $oneData);
					$criteriaSku->addCondition('p.category_id IN ('.$oneString.')'); 
				}else{
					$criteriaSku->addCondition('p.category_id = '.$categoryOne);
				}
			}

			$skuList = $aliexpressProduct->getDbConnection()->createCommand()
				 ->select("p.sku")
				 ->from("ueb_aliexpress_product as p")
				 ->where($criteriaSku->condition)
				 ->group("p.sku")
				 ->queryColumn();
		}

		if ($isOnline == 1 || $accountId || $notInAccountId){//在线
			if($skuList){
				//去除不包含的sku
				if($notInAccountId && count($notInSkuListArr) > 0){
					$criteria->addInCondition("t.sku", array_diff($skuList,$notInSkuListArr));
				}else{
					$criteria->addInCondition("t.sku", $skuList);
				}
			}else{
				$criteria->addCondition("1=0"); 
			}
		}else if ($isOnline == 2) {//不在线
			if($skuList){
				$criteria->addNotInCondition("t.sku", $skuList);
			}
		}

		//是否是多属性
		$isMulti = Yii::app()->request->getParam("product_is_multi");
		//是否显示子SKU
		$isChildSku = Yii::app()->request->getParam("is_child_sku");
		$productMulti = array();
		if($isMulti === ''){
			$productMulti[] = Product::PRODUCT_MULTIPLE_NORMAL;
			if($isChildSku){
				$productMulti[] = Product::PRODUCT_MULTIPLE_VARIATION;
			}else{
				$productMulti[] = Product::PRODUCT_MULTIPLE_MAIN;
			}
		}else{
			if($isMulti == Product::PRODUCT_MULTIPLE_MAIN && $isChildSku){
				$productMulti[] = Product::PRODUCT_MULTIPLE_VARIATION;
			}else{
				$productMulti[] = $isMulti;
			}
		}

		if(count($productMulti)>0){
			$criteria->addInCondition("t.product_is_multi", $productMulti);
		}

		//库存数量过滤
		if( (!empty($_REQUEST['product_stock'][0]) || !empty($_REQUEST['product_stock'][1])) ){
			$criteria->join = "join ueb_warehouse.ueb_warehouse_sku_map t2 on (t2.sku = t.sku and t2.warehouse_id=41) ";
			$minStock = (int)$_REQUEST['product_stock'][0];
			$maxStock = (int)$_REQUEST['product_stock'][1];
			if(!empty($_REQUEST['product_stock'][0])){
				$criteria->addCondition("t2.available_qty >= {$minStock} ");
			}
			
			if(!empty($_REQUEST['product_stock'][1])){
				$criteria->addCondition("t2.available_qty <= {$maxStock} ");
			}
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


			$data[$key]->en_title = isset($title['english'])?$title['english']:'';
			$data[$key]->cn_title = isset($title['Chinese'])?$title['Chinese']:'';

			if(empty($title['Chinese']) && empty($title['english'])) {
				//中英文标题都为空，如果是子sku情况，取父sku标题
				if(strpos($val['sku'],'.') !== false) {

					//子sku，取父sku标题
					$skuParent = (int)$val['sku'];

					$titleNew = Productdesc::model()->getTitleBySku($skuParent);
					$data[$key]->en_title = isset($titleNew['english'])?$titleNew['english']:'';
					$data[$key]->cn_title = isset($titleNew['Chinese'])?$titleNew['Chinese']:'';

				}

			}

			$accountArray = array();
			$data[$key]->skuInAccount = '';
			$data[$key]->ProductCategoryString = '';
			$result = AliexpressProduct::model()->getProductListBySku($sku);
			if($result){
				foreach ($result as $zkey => $zvalue) {
					$accountArray[] = $zvalue['account_id'];
				}
			}

			if($accountArray){
				$accountNameArray = AliexpressAccount::model()->getAccountInfoByIds(array_unique($accountArray));
				foreach ($accountNameArray as $nkey => $nvalue) {
					$data[$key]->skuInAccount .= $nvalue['short_name'].' ';
				}
			}

			$pushAccountId = Yii::app()->request->getParam("account_id");
			if($pushAccountId){
				$productOne = AliexpressProduct::model()->getOneByCondition('category_id','account_id = '.$pushAccountId.' AND sku = "'.$val['sku'].'"');
				$data[$key]->ProductCategoryString = AliexpressCategory::model()->getBreadcrumbCnAndEn($productOne['category_id']);
			}

		}

		return $data;
	}


	/**
	 * 通过sku和账号Id获取描述模板信息
	 * @param  $sku
	 * @param  $accountId
	 */
	public function getDescriptTemplateInfo($sku, $accountId){
		$data = array(
				'sku' => $sku,
				'platform_code' => Platform::CODE_ALIEXPRESS,
				'account_id' => $accountId,
		);
		$ruleModel = new ConditionsRulesMatch();
		$ruleModel->setRuleClass(TemplateRulesBase::MATCH_DESCRI_TEMPLATE);
		$descriptionTemplateID = $ruleModel->runMatch($data);
		$descriptTemplate = DescriptionTemplate::model()->getDescriptionTemplateByID($descriptionTemplateID);
		if(!$descriptTemplate){
			$descriptTemplate = '';
		}

		return $descriptTemplate;
	}


	/**
	 * 批量刊登sku产品到其他账号
	 * @param array 	$addSkuArray     	sku
	 * @param integer 	$addAccountId    	要发布的账号ID
	 * @param integer 	$onlineAccountId 	已发布的账号ID
	 * @param integer 	$moduleId     		产品信息模块ID
	 * @param integer 	$groupId      		产品分组ID
	 * @param integer 	$freightTemplateId  运费模板ID
	 * @return array
	 */
	public function PublishSkuToProductAdd($addSkuArray,$addAccountId,$onlineAccountId='',$moduleId='',$groupId='',$freightTemplateId=''){
		//判断sku是否为空
		if(!$addSkuArray){
			return $this->ReturnMessage(false,'要发布的SKU不能为空');
		}

		//从产品表查询出要添加的sku信息
		$skuString = "";
		foreach ($addSkuArray as $skuKey => $skuVal) {
			$skuString .= "'".$skuVal."',";
		}
		$skuInString = rtrim($skuString,'\'');

		//判断要发布的账号ID是否为空
		if(!$addAccountId){
			return $this->ReturnMessage(false,'要发布的账号ID不能为空');
		}

		$where = '';
		if($onlineAccountId){
			$where = "sku IN(".rtrim($skuInString,',').") AND product_status_type = 'onSelling' AND account_id = ".$onlineAccountId;
		}else{
			$onlineAccountId = $addAccountId;
			$selectWhere = "sku IN(".rtrim($skuInString,',').")  AND product_status_type = 'onSelling'";
	        $order = 'id DESC';
			$group = 'sku';
			$onlineId = AliexpressProduct::model()->getListByCondition('MAX(id) AS id',$selectWhere,$order,$group);
			if($onlineId){
				$onlineArr = array();
				foreach ($onlineId as $onlineValue) {
					$onlineArr[] = $onlineValue['id'];
				}

				$where = "id IN(".implode(',', $onlineArr).")";
			}
		}

		$onlineData = AliexpressProduct::model()->getListByCondition('*',$where);
		if(!$onlineData){
			return $this->ReturnMessage(false,'无数据');
		}

		$aliexpressAccountName = AliexpressAccount::model()->getIdNamePairs();
		$aliexpressProductAddModel = new AliexpressProductAdd();
        $aliexpressProductAddAttribute = new AliexpressProductAddAttribute();
        $aliexpressProductAddVariation = new AliexpressProductAddVariation();

		$dbtransaction = Yii::app()->db->beginTransaction();

		try{

			$status = 0;
			$times = date('Y-m-d H:i:s');
			$loginUserId = Yii::app()->user->id;

			//产品分组ID
			if(!$groupId){
				// 取出默认账号的第一个
				$groupInfo = AliexpressGroupList::model()->getGroupListOneByAccountId($addAccountId);
				$groupId = isset($groupInfo['groupId'])?$groupInfo['groupId']:0;
			}

			//取出服务模板
			$serviceTemplateId = AliexpressPromiseTemplate::model()->getTemplateIdByAccountId($addAccountId);
			if(!is_numeric($serviceTemplateId)){
				// return $this->ReturnMessage(false,$aliexpressAccountName[$addAccountId].'账号服务模板不存在');
				$serviceTemplateId = 0;
			}

			$freightTemplateInfo = AliexpressFreightTemplate::model()->getTemplateIdInfoByAccountId($addAccountId);
			$freightTemplateArr = array();
			if($freightTemplateInfo){
				foreach ($freightTemplateInfo as $ftkey => $ftvalue) {
					$freightTemplateArr[] = $ftvalue['template_id'];
				}
			}

			//如果没有选择运费模板，取默认模板
			if(!in_array($freightTemplateId, array_unique($freightTemplateArr))){
				$freightTemplateId = AliexpressFreightTemplate::model()->getTemplateIdByAccountId($addAccountId);
			}

			$skuAttributeImages = array();
			$insertFields = 'account_id,sku,category_id,group_id,currency,publish_type,publish_mode,subject,create_user_id,create_time,modify_user_id,modify_time,product_price,status,service_template_id,freight_template_id,is_package,product_unit,gross_weight,package_length,package_width,package_height,detail';

			foreach ($onlineData as $key => $value) {
				$publishFields = '';
				$sku = $value['sku'];
				$addId = $value['id'];
				$cateGoryId = $value['category_id'];
				$publishType = ($value['is_variation'] == 1)?2:1;
				// $floatPrice = round(floatval($value['product_price']), 2);

				//检测是否有权限去刊登该sku
				if(!Product::model()->checkCurrentUserAccessToSaleSKU($sku,Platform::CODE_ALIEXPRESS)){
					continue;
					// return $this->ReturnMessage(false,'无刊登sku的权限');
				}

				//查找产品信息
				$skuInfo = Product::model()->getProductInfoBySku($sku);
				if(!$skuInfo){
					continue;
				}

				//排除已停售产品
				if($skuInfo['product_status'] == 7){
					continue;
				}

				//验证主sku
				Product::model()->checkPublishSKU($publishType, $skuInfo);

				//判断要发布的sku和账号是否已经存在
				$isProductModel = AliexpressProduct::model()->getProductListBySkuAndAccountId(array($sku),$addAccountId);
				if($isProductModel){
					continue;
				}

				//判断要发布到账号的sku是否已经存在待刊登列表里
				$queryWhere = "sku = '".$sku."' AND account_id = ".$addAccountId;
				$isProductAddModelData = $aliexpressProductAddModel->getOneByCondition('*',$queryWhere);
				if($isProductAddModelData){
					continue;
				}

				//取出佣金
				$commissionRate = AliexpressCategoryCommissionRate::getCommissionRate($cateGoryId);

				//取出产品价格
				$priceInfo = $this->getProductPriceBySkuAndAccoutId($sku,$addAccountId,$commissionRate);
				if(!isset($priceInfo[$addAccountId]['salePrice']) || $priceInfo[$addAccountId]['salePrice'] < 0 ){
					continue;
					// return $this->ReturnMessage(false,'sku为：'.$sku.'的产品价格没有找到');
				}
				$floatPrice = round(floatval($priceInfo[$addAccountId]['salePrice']), 2);
				if($floatPrice <= 0){
					continue;
				}			

				//保存产品图片
				$aliProductImageAddModel = new AliexpressProductImageAdd();
				$result = $aliProductImageAddModel->aliexpressAutoImagesAdd($sku, $addAccountId);
				if(!$result){
					continue;
				}

				$insertData = '';
				//获取最优描述模板
				$descriptTemplate = $this->getDescriptTemplateInfo($sku, $addAccountId);
				if(!$descriptTemplate){
					continue;
					// return $this->ReturnMessage(false,'描述模板为空');
				}

				//设置产品描述
				$description = $skuInfo['description']['english'];
				$include = $skuInfo['included']['english'];
				$content = $descriptTemplate['template_content'];
				$publishTitle = $skuInfo['title']['english'];

				//取出已经刊登过的账号，sku模板前后缀
				$onlineAccountIdDescriptTemplate = $this->getDescriptTemplateInfo($sku, $onlineAccountId);
				if($onlineAccountIdDescriptTemplate){
					//去掉账号的前缀
					$publishTitle = str_replace($onlineAccountIdDescriptTemplate['title_prefix'], '', $value['subject']);
					//去掉账号的后缀
					$publishTitle = str_replace($onlineAccountIdDescriptTemplate['title_suffix'], '', $publishTitle);
				}
				
				//设置标题
				$descriptSubject = $descriptTemplate['title_prefix'] . ' ' . trim($publishTitle) . ' ' . $descriptTemplate['title_suffix'];
				$subject = str_replace("'", "\'", $descriptSubject);
				//读取模板内容
				$additionalImageList = array();
				$descriptions = DescriptionTemplate::model()->getDescription($content, $description, $descriptSubject, $include, $additionalImageList);
				//模板替换推荐产品
				if($moduleId){
					$relateproductdetail = '<img id="'.$moduleId.'" class="product_info_module_placeholder_img" src="/images/widget2.png" />';
					$descriptions = str_replace('[relateproductdetail/]',$relateproductdetail,$descriptions);
				}
				//设置产品毛重
				$grossWeight = $skuInfo['product_weight'] > 0 ? round($skuInfo['product_weight'] / 1000, 3) : 0.000;

				$insertData = 
				"(".
					$addAccountId.",'".
					$value['sku']."',".
					$value['category_id'].",".
					$groupId.",'USD',".
					$publishType.",'1','".
					trim($subject)."',".
					$loginUserId.",'".
					$times."',".
					$loginUserId.",'".
					$times."',".
					$floatPrice.",".
					$status.",'".
					$serviceTemplateId."',".
					$freightTemplateId.",".
					$value['package_type'].",".
					$value['product_unit'].",'".
					$grossWeight."','".
					$value['package_length']."','".
					$value['package_width']."','".
					$value['package_height']."','".
					str_replace("'", "\'", $descriptions)."'".
				")";

				$addResult = $aliexpressProductAddModel->insertBySql($insertFields,$insertData);
				if(!$addResult){
                    $dbtransaction->rollback();
                    Yii::app()->end();
                }

				$newAddId = $aliexpressProductAddModel->getDbConnection()->getLastInsertID();
				if($newAddId){

					//添加产品普通属性
	                $insertAttributeData = '';
	                $aliexpressProductExtendInfo = AliexpressProductExtend::model()->getInfoByProductID($value['id']);
	                $attributes = json_decode($aliexpressProductExtendInfo['product_property']);
	                if($attributes){
	                	$insertAttributeFields = 'add_id,attribute_id,value_id,attribute_name,value_name,is_custom';
	                    foreach ($attributes as $ekey => $attribute) {
	                    	$insertAttributeData = '';
	                        $insertAttributeData .= "(";
	                        $attributeID = 'null';
	                        $attributeName = null;
	                        $attributeValueID = 'null';
	                        $attributeValueName = null;
	                        $isCustom = 0;
	                        if (isset($attribute->attrNameId) && !is_null($attribute->attrNameId))
	                            $attributeID = $attribute->attrNameId;
	                        if (isset($attribute->attrName) && !is_null($attribute->attrName))
	                            $attributeName = $attribute->attrName;
	                        if (isset($attribute->attrValueId) && !is_null($attribute->attrValueId))
	                            $attributeValueID = $attribute->attrValueId;
	                        if (isset($attribute->attrValue) && !is_null($attribute->attrValue))
	                            $attributeValueName = $attribute->attrValue;
	                        if (is_null($attributeID) && is_null($attributeValueID))
	                            $isCustom = 1;

	                        //判断是否存在
	                        $attributeArray = array();
	                        $attributeIsExits = $aliexpressProductAddAttribute->getProductAddAttributes($newAddId);
	                        foreach ($attributeIsExits as $aekey => $aeValue) {
								if(!isset($aeValue['attrNameId'])){
									continue;
								}
								$attributeArray[] = $aeValue['attrNameId'];
							}
	                        if(in_array($attributeID, $attributeArray)){
		                        if(isset($attributeValueID)){
		                        	$updateData = array('value_id'=>$attributeValueID);
		                        	$updateWhere = 'add_id = '.$newAddId.' AND attribute_id = '.$attributeID;
		                        	$addAttributeResult=$aliexpressProductAddAttribute->updateBySql($updateData, $updateWhere);
		                        }elseif (isset($attributeValueName)) {
		                        	$updateData = array('value_name'=>$attributeValueName);
		                        	$updateWhere = 'add_id = '.$newAddId.' AND attribute_id = '.$attributeID;
		                        	$addAttributeResult=$aliexpressProductAddAttribute->updateBySql($updateData, $updateWhere);
		                        }
			                }else{
			                	$insertAttributeData .= $newAddId.",".$attributeID.",".$attributeValueID.",'".$attributeName."','".$attributeValueName."',".$isCustom.")";
		                        $addAttributeResult=$aliexpressProductAddAttribute->insertBySql($insertAttributeFields, $insertAttributeData);
			                }

			                if(!$addAttributeResult){
		                        $dbtransaction->rollback();
		                        Yii::app()->end();
		                    }
	                    }
	                }

	                //添加多属性
	                $attributeInfos = array();
	                if($value['is_variation'] == 1){
	                    //查找产品属性表
	                    $productVariationInfo = AliexpressProductVariation::model()->getByProductId($value['id']);
	                    $insertVariationFields = 'add_id,sku,price';
	                    $insertVariationData = '';
	                    foreach ($productVariationInfo as $pvkey => $variation) {

	                        $variationSku = $variation['sku'];

	                        //设置利润在15%的价格
							//取出子sku价格
							$variationPriceInfo = $this->getProductPriceBySkuAndAccoutId($variationSku,$addAccountId,$commissionRate);
							if(!isset($variationPriceInfo[$addAccountId]['salePrice']) || $variationPriceInfo[$addAccountId]['salePrice'] < 0 ){
								continue;
								// return $this->ReturnMessage(false,'sku为：'.$sku.'的产品价格没有找到');
							}

							//判断利润率是否小于0
							// if(!isset($variationPriceInfo[$addAccountId]['profitRate']) || $variationPriceInfo[$addAccountId]['profitRate'] < '0.15'){
							// 	continue;
							// }

							$variationPrice = round(floatval($variationPriceInfo[$addAccountId]['salePrice']), 2);
							if($variationPrice <= 0){
								continue;
							}

	                        //添加刊登多属性表
	                        $insertVariationData = "('".$newAddId."','".$variationSku."','".$variationPrice."')";
	                        $addVariationResult = $aliexpressProductAddVariation->insertBySql($insertVariationFields, $insertVariationData);
	                        if(!$addVariationResult){
	                            $dbtransaction->rollback();
	                            Yii::app()->end();
	                        }
	                        $newVariationId = $aliexpressProductAddVariation->getDbConnection()->getLastInsertID();

	                        $addVariationAttributes = json_decode($variation['sku_property']);
	                        if($addVariationAttributes){
	                        	$addVariationAttributeArr = '';
	                            foreach ($addVariationAttributes as $k => $val) {

	                                if (!array_key_exists($val->skuPropertyId, $attributeInfos)) {
	                                    $attributeInfo = AliexpressCategoryAttributes::model()->getCategoryAttribute($cateGoryId, $val->skuPropertyId);
	                                    if (empty($attributeInfo)) continue;
	                                    $attributeInfos[$val->skuPropertyId] = $attributeInfo;
	                                }
	                                $attributeInfo = $attributeInfos[$val->skuPropertyId];

	                                //添加刊登多属性产品属性表
	                                $addVariationAttributeArr = array(
	                                    'add_id'            => $newAddId,
	                                    'variation_id'      => $newVariationId,
	                                    'attribute_id'      => $val->skuPropertyId,
	                                    'value_id'          => $val->propertyValueId,
	                                    'value_name'        => isset($val->propertyValueDefinitionName)?$val->propertyValueDefinitionName:''
	                                );
	                                $addVariationAttributeResult = AliexpressProductAddVariationAttribute::model()->saveAliProductAddVariationAttribute($addVariationAttributeArr);
	                                unset($addVariationAttributeArr);
	                                if(!$addVariationAttributeResult){
	                                    $dbtransaction->rollback();
	                                    Yii::app()->end();
	                                }
	                            }
	                        }


	                        //是否需要自定义多属性图片
	                        if ($attributeInfo['customized_pic'] == 1) {
	                            //取sku的一个主图
	                            // $images = Product::model()->getImgList($variationSku, 'ft');
	                            $images = ProductImageAdd::getImagesPathFromRestfulBySkuAndType($variationSku, 'ft', Platform::CODE_ALIEXPRESS);
								if (!empty($images)) {
									$getImageConfig = new Productimage();
							        $config = $getImageConfig->get_img_config();
							        $ass_path = $config['img_local_path'].$config['img_local_assistant_path'];
							        $first = substr($variationSku,0,1);
							        $second = substr($variationSku,1,1);
							        $third = substr($variationSku,2,1);
							        $four = substr($variationSku,3,1);
							        $filePath = '/'.$first.'/'.$second.'/'.$third.'/'.$four.'/'.array_shift($images);
							        $ztFilePath = $config['img_local_path'].$config['img_local_main_path'].$filePath;
									$skuAttributeImages[$variationSku] = $ztFilePath;
								}
	                        }

	                        //将SKU多属性图片添加到图片上传表
	                        if ($attributeInfo['customized_pic'] == 1 && isset($skuAttributeImages[$variationSku]) && !empty($skuAttributeImages[$variationSku])) {
	                            if($variationSku == $sku){
	                                continue;//当子SKU 和主SKU一致时
	                            }
	                            //查询图片是否已经添加
	                            AliexpressProductImageAdd::model()->deleteAliexpressSkuImages($variationSku, $addAccountId, Platform::CODE_ALIEXPRESS);
	                            $variationSkuImgName = basename($skuAttributeImages[$variationSku]);
	                            $imageAddData = array(
	                                    'image_name'    => $variationSkuImgName,
	                                    'sku'           => $variationSku,
	                                    'type'          => ProductImageAdd::IMAGE_ZT,
	                                    'local_path'    => $skuAttributeImages[$variationSku],
	                                    'platform_code' => Platform::CODE_ALIEXPRESS,
	                                    'account_id'    => $addAccountId,
	                                    'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
	                                    'create_user_id'=> Yii::app()->user->id,
	                                    'create_time'   => date('Y-m-d H:i:s'),
	                            );
	                            $imageModel = new AliexpressProductImageAdd();
	                            $imageModel->setAttributes($imageAddData,false);
	                            $imageModel->setIsNewRecord(true);
	                            $imageModel->save();                                
	                        }

	                    }                    
	                }

				}
			}

			$dbtransaction->commit();
			return $this->ReturnMessage(true,'发布成功');

		}catch (Exception $e){
			$dbtransaction->rollback();
			return $this->ReturnMessage(false,$e->getMessage());
		}
	}


	
	/**
	 * 通过sku和账号获取产品价格
	 * @param  $sku
	 * @param  $accountId
	 * @param  $commissionRate 佣金比例
	 * @return array
	 */
	public function getProductPriceBySkuAndAccoutId($sku, $accountId, $commissionRate = 0.05){
		$currency  = AliexpressProductAdd::PRODUCT_PUBLISH_CURRENCY;
		//根据刊登条件匹配卖价方案 TODO
		$salePrice = $profit = $profitRate = $calcDesc = array();
		$data = array();

		//获取最优价格模板
		$params = array(
				'sku' => $sku,
				'platform_code' => Platform::CODE_ALIEXPRESS,
				'account_id' => $accountId,
		);
		$ruleModel = new ConditionsRulesMatch();
		$ruleModel->setRuleClass(TemplateRulesBase::MATCH_PRICE_TEMPLATE);
		$salePriceSchemeID = $ruleModel->runMatch($params);
		if (empty($salePriceSchemeID) || !($salePriceScheme = SalePriceScheme::model()->getSalePriceSchemeByID($salePriceSchemeID))) {
			$tplParam = array(
					'standard_profit_rate'  => 0.15,
					'lowest_profit_rate'    => 0.1,
					'floating_profit_rate'  => 0.2,
			);
		} else {
			$tplParam = array(
					'standard_profit_rate'  => $salePriceScheme['standard_profit_rate'],
					'lowest_profit_rate'    => $salePriceScheme['lowest_profit_rate'],
					'floating_profit_rate'  => $salePriceScheme['floating_profit_rate'],
			);				
		}

		$productCost = 0;
		$standardProfitRate = $tplParam['standard_profit_rate'];  //标准利润率
		$data = array();

		//获取产品信息
		$skuInfo = Product::model()->getProductInfoBySku($sku);
        if(!$skuInfo){
        	echo json_encode($data);
			Yii::app()->end();
        }

        if($skuInfo['avg_price'] <= 0){
        	$productCost = $skuInfo['product_cost'];   //加权成本
    	}else{ 
    		$productCost = $skuInfo['avg_price'];      //产品成本
    	}

    	//产品成本转换成美金
    	$productCost = $productCost / CurrencyRate::model()->getRateToCny($currency);
    	$productCost = round($productCost,2);
    	$shipCode = AliexpressProductAdd::model()->returnShipCode($productCost,$sku);   	

		//计算卖价，获取描述
		$priceCal = new CurrencyCalculate();

		//设置运费code
		if($shipCode){
			$priceCal->setShipCode($shipCode);
		}

		$priceCal->setProfitRate($standardProfitRate);//设置利润率
		$priceCal->setCurrency($currency);//币种
		$priceCal->setPlatform(Platform::CODE_ALIEXPRESS);//设置销售平台
		$priceCal->setSku($sku);//设置sku
		$priceCal->setCommissionRate($commissionRate);//设置佣金比例
		$priceCal->setUnionCommission(AliexpressProductAdd::UNION_COMMISSION); //联盟佣金
		$salePrice = $priceCal->getSalePrice();//获取卖价
		if($salePrice > 5){
			$priceCal2 = new CurrencyCalculate();
			$shipCode = AliexpressProductAdd::model()->returnShipCode($salePrice,$sku);
			//设置运费code
			if($shipCode){
				$priceCal2->setShipCode($shipCode);
			}

			$priceCal2->setProfitRate($standardProfitRate);//设置利润率
			$priceCal2->setCurrency($currency);//币种
			$priceCal2->setPlatform(Platform::CODE_ALIEXPRESS);//设置销售平台
			$priceCal2->setSku($sku);//设置sku
			$priceCal2->setCommissionRate($commissionRate);//设置佣金比例
			$priceCal2->setUnionCommission(AliexpressProductAdd::UNION_COMMISSION); //联盟佣金
			$data[$accountId]['salePrice']     = $priceCal2->getSalePrice();//获取卖价
			$data[$accountId]['profit']        = $priceCal2->getProfit(true);//获取利润
			$data[$accountId]['profitRate']    = $priceCal2->getProfitRate(true);//获取利润率
			$data[$accountId]['desc']          = $priceCal2->getCalculateDescription();//获取计算详情
		}else{
			$data[$accountId]['salePrice']     = $priceCal->getSalePrice();//获取卖价
			$data[$accountId]['profit']        = $priceCal->getProfit(true);//获取利润
			$data[$accountId]['profitRate']    = $priceCal->getProfitRate(true);//获取利润率
			$data[$accountId]['desc']          = $priceCal->getCalculateDescription();//获取计算详情
		}

		return $data;
		
	}	


	/**
	 * 返回的消息数组
	 * @param bool   $booleans  布尔值
	 * @param string $message   提示的消息
	 * @return array
	 */
	public function ReturnMessage($booleans,$message){
		return array($booleans,$message);
		exit;
	}
	
}