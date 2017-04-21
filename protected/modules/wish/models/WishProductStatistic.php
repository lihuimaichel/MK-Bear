<?php
class WishProductStatistic extends UebModel {

	/** @var string 产品英文名称 **/
	public $en_title = null;
	public $cn_title = null;

	public $account_id = null;

	private $_errMsg = array();
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

	public function setErrMsg($msg){
		$this->_errMsg[] = $msg;
	}
	
	public function getErrMsg($glue = '<br/>'){
		return implode($glue, $this->_errMsg);
	}
	// ================ Start: 批量刊登 操作 =================== //
	/**
	 * @desc 批量添加产品
	 * @param array $skus
	 * @param integer $accountId
	 * @return boolean
	 */
	public function batchAddProduct($skus, $accountId){
		//循环选择中的sku
		//判断该sku是否为多属性组合产品（即wish变种产品）
		//判断该sku是否已经刊登过或者已经在上线了，不限账号;为子sku时则寻找对应的主sku是否已经刊登过
		//如果为单品sku则需要取出对应的产品名称、描述、属性（颜色，尺寸）、标签
		//根据价格模板进行价格运费计算	
		if(!$skus || !$accountId) return false;
		foreach ($skus as $sku){
			$saveType = 0;
			$productInfo = $this->getProduct($sku);
			if(!$productInfo){
				$this->setErrMsg("{$sku}:" . Yii::t('wish_listing', "Not found the sku"));
				continue;
			}
			$isMulti = $productInfo['product_is_multi'];
			$productId = $productInfo['id'];
			$mainSku = $subSku = null;
			if($isMulti == Product::PRODUCT_MULTIPLE_VARIATION){//子sku
				$mainSku = $this->getMainSkuOfSubSku($sku);
				$subSku = $sku;
			}else{
				$mainSku = $sku;
			}
			if(!$mainSku){ 
				$this->setErrMsg("{$sku}:" . Yii::t('wish_listing', "NO main sku"));
				continue;
			}
			//判断是否已经刊登过
			$mainSkuInfo = WishListing::model()->find('sku=:sku AND account_id=:account_id', array(':sku'=>$mainSku, ':account_id'=>$accountId));
			//该子sku对应的主sku还没有刊登过
			//if(!$mainSkuInfo && $subSku) continue;
			//已刊登过该主sku
			if($mainSkuInfo && !$subSku){
				$this->setErrMsg("{$sku}:" . Yii::t('wish_listing', "Had upload the sku"));
				continue;
			}
			//已刊登过该子sku
			if($subSku && WishVariants::model()->count('sku=:sku AND account_id=:account_id', array(':sku'=>$subSku, ':account_id'=>$accountId))){
				$this->setErrMsg("{$sku}:" . Yii::t('wish_listing', "Had upload the subsku"));
				continue;
			}
			if($subSku && $mainSkuInfo){//需要添加主刊登信息，但是状态置为成功
				$saveType = WishProductAdd::SAVE_TYPE_NO_MAIN_SKU;
			}
			//判断是否在待刊登列表，如果已经刊登成功了，则不再提交
			$wishProductIsAdd = WishProductAdd::model()->find('parent_sku=:parent_sku AND account_id=:account_id',
														array(':parent_sku'=>$mainSku, ':account_id'=>$accountId));
			if($wishProductIsAdd && !$subSku){
				$this->setErrMsg("{$sku}:" . Yii::t('wish_listing', "Had added the Sku"));
				continue;
			}
			if($subSku && $wishProductIsAdd){
				//主表中附带了子sku，必须进行对比判断
				/* if($wishProductIsAdd->sku == $subSku){
					$this->setErrMsg("{$sku}:" . Yii::t('wish_listing', "Had added the Subsku"));
					continue;
				} */
				$wishVariantsIsAdd = WishProductVariantsAdd::model()->find('sku=:sku AND add_id=:add_id AND upload_status=:upload_status', 
																	array(':sku'=>$subSku, ':add_id'=>$wishProductIsAdd->id, ':upload_status'=>WishProductAdd::WISH_UPLOAD_SUCCESS));
				if($wishVariantsIsAdd) {
					$this->setErrMsg("{$sku}:" . Yii::t('wish_listing', "Had added the Subsku"));
					continue;
				}
				//不对主刊登信息的状态做改变
				$saveType = WishProductAdd::SAVE_TYPE_ONLY_SUBSKU;
			}
			
			//获取刊登过的主sku相应信息
			$mainSkuInfo = WishListing::model()->find('sku=:sku AND account_id<>:account_id', array(':sku'=>$mainSku, ':account_id'=>$accountId));
			if(!$mainSkuInfo){
				//到待刊登表中寻找对应
				$mainSkuInfo = WishProductAdd::model()->find('parent_sku=:parent_sku', array(':parent_sku'=>$mainSku));
				if(!$mainSkuInfo){
					$this->setErrMsg("{$sku}:" . Yii::t('wish_listing', "Not found the sku upload or added"));
					continue;
				}
                                $mainImg = $mainSkuInfo['main_image'];
                                $extraImg = $mainSkuInfo['extra_images'];
                                $remote_main_img = $mainSkuInfo['remote_main_img'];
                                $remote_extra_img = $mainSkuInfo['remote_extra_img'];
			}else{
				//获取对应的desc
				$descInfo = WishListingExtend::model()->getDbConnection()->createCommand()
										->from(WishListingExtend::tableName())
										->where('listing_id=:id', array(':id'=>$mainSkuInfo->id))
										->queryRow();
				if($descInfo && $descInfo['description']){
					$mainSkuInfo->description = $descInfo['description'];
				}
				$remote_main_img = $mainImg = $mainSkuInfo['main_image'];
                                $remote_extra_img = $extraImg = $mainSkuInfo['extra_images'];
			}
			//print_r($mainSkuInfo);
			//获取对应主产品sku信息
			if($subSku){
				$productInfo = $this->getProduct($mainSku);
			}
			//去除描述中的html标签
			if(!empty($productInfo['description'])){
				foreach ($productInfo['description'] as $key=>$val){
					$productInfo['description'][$key] = str_replace(array("\t","\r", "\n", "  ", "	"), array("","","","", " "), strip_tags($val));
				}
			}
			//拼接添加信息
			//tags从已经刊登过或者在待刊登里面抽取
			//市场价格、销售价格、运费则从对应的模板里面抽取
                        if(!$mainImg){
                            $skuImg = array();
                            $imageType = array('zt', 'ft');
                            $config = ConfigFactory::getConfig('serverKeys');
                            foreach($imageType as $type){
                                    $images = Product::model()->getImgList($sku,$type);
                                    foreach($images as $k=>$img){
                                            $skuImg[] = $config['oms']['host'].$img;
                                    }
                            }
                            //无图片不上传
                            if(empty($skuImg)){
                                    $this->setErrMsg("{$sku}:" . Yii::t('wish_listing', "No main image can't upload"));
                                    continue;
                            }
                            $mainImg = array_shift($skuImg);
                            $extraImg = implode('|', $skuImg);
                        }
			$data = array(
							'parent_sku'	=>	$mainSku,
							'sku'			=>	$sku,
							'main_image'	=>	empty($mainImg)?'':$mainImg,
							'extra_images'	=>	empty($extraImg)?'':$extraImg,
                                                        'remote_main_img'	=>	empty($remote_main_img)?'':$remote_main_img,
                                                        'remote_extra_img'	=>	empty($remote_extra_img)?'':$remote_extra_img,
							'product_is_multi'	=>	$isMulti,
							'variants'		=>	array()
					);
			$data['subject'] = empty($mainSkuInfo->name)?'':$mainSkuInfo->name;
			$data['tags'] = empty($mainSkuInfo->tags)?'':$mainSkuInfo->tags;
			$data['brand'] = !empty($productInfo['brand_info']['brand_en_name'])?$productInfo['brand_info']['brand_en_name']:'';
			$data['detail'] = empty($mainSkuInfo->description) ? (!empty($productInfo['description']['english'])?$productInfo['description']['english']:'') : $mainSkuInfo->description;
			if(empty($data['tags'])){
				$this->setErrMsg("{$sku}:" . Yii::t('wish_listing', "No Tags"));
				continue;
			}
			if(empty($data['detail'])){
				$this->setErrMsg("{$sku}:" . Yii::t('wish_listing', "No Description"));
				continue;
			}
			//如果没有对应的属性值，则需要指定主产品信息
			$subSkuList = WishProductAdd::model()->getSubProductByMainProductId($productId, $isMulti);
			if($subSkuList['skuList']){
				foreach($subSkuList['skuList'] as $skuinfo){
					$salePrice = WishProductAdd::model()->getSalePrice($skuinfo['skuInfo']['sku'], $accountId);
					//...
					$variants = array(
										'sku'	=>	$skuinfo['skuInfo']['sku'],
										'inventory'	=>	WishProductAdd::PRODUCT_PUBLISH_INVENTORY,//库存数量
										'price'		=>	$salePrice['salePrice'],
										'shipping'	=>	$salePrice['shipPrice'],
										'shipping_time'	=>	'',
										'market_price'	=>	$salePrice['salePrice'],
										'color'	=>	'',
										'size'	=>	'',
									);
					if($skuinfo['attribute']){
						foreach ($skuinfo['attribute'] as $attr){
							switch ($attr['attribute_name']){
								case 'color':
									$variants['color'] = $attr['attribute_value_name'];
									break;
								case 'size':
									$variants['size'] = $attr['attribute_value_name'];
									break;
							}
							
						}
					}
					$data['variants'][] = $variants;
				}
			}else{
				$salePrice = WishProductAdd::model()->getSalePrice($sku, $accountId);
				
				//...
				$variants = array(
						'sku'	=>	$sku,
						'inventory'	=>	WishProductAdd::PRODUCT_PUBLISH_INVENTORY,//库存数量
						'price'		=>	$salePrice['salePrice'],
						'shipping'	=>	$salePrice['shipPrice'],
						'shipping_time'	=>	'',
						'market_price'	=>	$salePrice['salePrice'],
				);
				$data['variants'][] = $variants;
			}
			WishProductAdd::model()->saveWishAddData(array($accountId=>$data), $saveType);
		}
		
		return true;
	}
	
	/**
	 * @desc 判断当前sku产品是否为子产品
	 * @param unknown $sku
	 * @return Ambigous <number>
	 */
	public function getProduct($sku){
		/* $skuInfo = $this->getDbConnection()->createCommand()
											->from(self::tableName())
											->select('product_is_multi, id')
											->where('sku=:sku', array(':sku'=>$sku))
											->queryRow(); */
		$skuInfo = Product::model()->getProductInfoBySku($sku);
		return $skuInfo;
	}
	/**
	 * @desc 获取子SKU的主SKU
	 * @param unknown $subSku
	 */
	public function getMainSkuOfSubSku($subSku){
		$productSelectAttributeModel = new ProductSelectAttribute;
		$mainProductId = $productSelectAttributeModel->getMainSku(null, $subSku);
		if(empty($mainProductId)) return null;
                return $mainProductId;
		$mainSku = $this->getDbConnection()->createCommand()
								->from(self::tableName())
								->select('sku')
								->where('id=:id', array(':id'=>$mainProductId))
								->queryScalar();
		return $mainSku;
	}
	// ================ End: 批量刊登 操作 ===================== //
	
	/**
	 * (non-PHPdoc)
	 * @see CActiveRecord::relations()
	 */
//	public function relations() {
//		return array(
//
//		);
//	}

	/**
	 * @desc 获取状态列表
	 * @param string $status
	 */
	public static function getStatusList($status = null){
		$statusArr = array(
			self::UPLOAD_STATUS_DEFAULT     => Yii::t('wish', 'UPLOAD STATUS DEFAULT'),
			self::UPLOAD_STATUS_RUNNING     => Yii::t('wish', 'UPLOAD STATUS RUNNING'),
			self::UPLOAD_STATUS_IMGFAIL     => Yii::t('wish', 'UPLOAD STATUS IMGFAIL'),
			self::UPLOAD_STATUS_IMGRUNNING  => Yii::t('wish', 'UPLOAD STATUS IMGRUNNING'),
			self::UPLOAD_STATUS_SUCCESS     => Yii::t('wish', 'UPLOAD STATUS SUCCESS'),
			self::UPLOAD_STATUS_FAILURE     => Yii::t('wish', 'UPLOAD STATUS FAILURE'),
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
			'sku'					=> Yii::t('wish_product_statistic', 'Sku'),
			'en_title' 				=> Yii::t('wish_product_statistic', 'Product Title'),
			'product_cost' 			=> Yii::t('wish_product_statistic', 'Product Cost'),
			'product_category_id' 	=> Yii::t('wish_product_statistic', 'Product Category'),
			'account_id' 			=> Yii::t('wish_product_statistic', 'Account'),
			'product_status' 		=> Yii::t('wish_product_statistic', 'Product Status'),
			'online_number' 		=> Yii::t('wish_product_statistic', 'Online Number'),
			'product_is_bak' 		=> Yii::t('wish_product_statistic', 'If Stock Up'),
			'is_online' 			=> Yii::t('wish_product_statistic', 'Is Online'),
			'product_is_multi'		=> Yii::t('ebay', 'Product Is Multi'),
			'online_category_id'	=> Yii::t('ebay', 'Online Category ID'),
			'is_display_variation'  => Yii::t('ebay', 'Is Display Variation'),
		);
	}

	/**
	 * @return array search filter (name=>label)
	 */
	public function filterOptions() {
		$classId = Yii::app()->request->getParam("product_category_id");
		$onlineCategoryId = Yii::app()->request->getParam("online_category_id");
		$isMulti = Yii::app()->request->getParam("product_is_multi");
		$isDisplayVariation = Yii::app()->request->getParam("is_display_variation");
		$result = array(
			array(
				'name'		 	=> 'sku',
				'search' 		=> 'IN',
				'type' 			=> 'text',
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
				'name' 			=> 'account_id',
				'type' 			=> 'dropDownList',
				'search' 		=> '=',
				'data' 			=> UebModel::model('WishAccount')->getIdNamePairs(),
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
				'htmlOptions' 	=> array(
				),
			),
			array(
				'name' 			=> 'product_cost',
				'type' 			=> 'text',
				'search' 		=> 'RANGE',
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
	/**
	 * search SQL
	 * @return $array
	 */
	protected function _setCDbCriteria() {
		$criteria = new CDbCriteria();
		$criteriaSku = new CDbCriteria();

		$saleAccountID = Yii::app()->user->id;
		
		//联接sku销售关系表
		if(!AuthAssignment::model()->checkPlatformByUserIdAndPlatformCode($saleAccountID, Platform::CODE_WISH) && !UserSuperSetting::model()->checkSuperPrivilegeByUserId($saleAccountID)){
			$criteria->join = "join ueb_product_to_seller_relation ps on (ps.sku = t.sku and ps.seller_id={$saleAccountID}) ";
		}
		
		$skuArr = array();

		$where = '';
		if (isset($_REQUEST['sku']) && !empty($_REQUEST['sku'])) {
			$sku = trim($_REQUEST['sku']);
			$criteriaSku->addCondition("sku = '" . $_REQUEST['sku'] . "'");
			$where = ' where sku=' . $_REQUEST['sku'];
			$skuArr[] = trim($_REQUEST['sku']);
			$criteria->addCondition("t.sku='".$sku."'");
		}

		if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
			$criteriaSku->addCondition("account_id = " . (int)$_REQUEST['account_id']);
			if(!empty($where)) {
				$where .=  ' and account_id=' . (int)$_REQUEST['account_id'];
			} else {
				$where = ' where account_id=' . (int)$_REQUEST['account_id'];
			}
		}

		if (!isset($_REQUEST['is_online'])) {
			$_REQUEST['is_online'] = "";
		}

		$WishProduct = new WishProduct();
		$isOnline = isset($_REQUEST['is_online']) ? $_REQUEST['is_online'] : null;
		$skuList = array();
		if($isOnline){
			//确保传过来的sku没有遗漏，从主sku、子sku表中关联查询
			$sql = "select sku from " . WishListing::model()->tableName() . $where . " group by sku union
					select sku from ". WishVariants::model()->tableName() . $where . " group by sku";
			$skuList = $WishProduct->getDbConnection()->createCommand($sql)->queryColumn();
		}

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
		
		//var_dump($criteria);
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
                        if(!isset($title['english'])){
                            $title['english'] = '';
                        }
                        if(!isset($title['Chinese'])){
                            $title['Chinese'] = '';
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

	
	
	private function throwE($message,$code=null){
		throw new Exception($message,$code);
	}
	
    /**
	 * @desc 批量添加产品
	 * @param array $skus
	 * @param integer $accountId
	 * @return boolean
	 */
	public function batchAddProductFromListing($skus, $accountId){
      //从listing中获取数据用于批量刊登
      //1.检查当前账号是否已上传子sku，是则continue;
      //2.检查当前账号是否已待刊登成功子sku,是则continue
      //3.从别的账号查ueb_listing_variants数据（子sku）,没有则continue;
      //4.根据listing_id查ueb_wish_listing数据(父sku)，没有则continue
      //5.检查当前账号父sku是否已待刊登，是则$saveType改为2（修改次数为0）
      //6.检查当前账号父sku是否已上传，是则$saveType改为4(添加但不上传)
      //7.组装数据
		if(!$skus || !$accountId) return false;
		foreach ($skus as $sku){
			//检测是否有权限去刊登该sku
			//上线后打开注释---lihy 2016-05-10
			if(! Product::model()->checkCurrentUserAccessToSaleSKUNew($sku,$accountId,Platform::CODE_WISH)){
				$this->setErrMsg ( "{$sku}:" . Yii::t('system', 'Not Access to Add the SKU') );
				continue;
			}
			
			$saveType = 0;
			// 1.检查当前账号是否已上传子sku，是则continue;
			$variants_is_upload = WishVariants::model ()->count ( 'sku=:sku AND account_id=:account_id', array (
					':sku' => $sku,
					':account_id' => $accountId 
			) );
			if ($variants_is_upload) {
				$this->setErrMsg ( "{$sku}:" . Yii::t ( 'wish_listing', "Had upload the subsku" ) );
				continue;
			}
			
			// 2.检查当前账号是否已待刊登成功子sku,是则continue
			$variants_is_add = WishProductVariantsAdd::model ()->getDbConnection ()->createCommand ()->select ( 'v.sku' )->from ( WishProductVariantsAdd::tableName () . ' v' )->leftJoin ( WishProductAdd::tableName () . ' a', 'v.add_id=a.id' )->where ( 'v.sku=:sku', array (
					':sku' => $sku 
			) )->andWhere ( 'a.account_id=:account_id', array (
					':account_id' => $accountId 
			) )->andWhere ( 'v.upload_status=:upload_status', array (
					':upload_status' => WishProductAdd::WISH_UPLOAD_SUCCESS 
			) )->queryRow ();
			
			if ($variants_is_add) {
				$this->setErrMsg ( "{$sku}:" . Yii::t ( 'wish_listing', "Had added the Subsku" ) );
				continue;
			}
			
			// 3.从别的账号查ueb_listing_variants数据（子sku）,没有则continue;
			$variants_info = WishVariants::model ()->find ( 'sku=:sku AND account_id<>:account_id', array (
					':sku' => $sku,
					':account_id' => $accountId 
			) );
			if (! $variants_info) {
				$this->setErrMsg ( "{$sku}:" . Yii::t ( 'wish_listing', "Not found the sku upload or added" ) );
				continue;
			}
			
			// 4.根据listing_id查ueb_wish_listing数据(父sku)，没有则continue
			$listing_id = $variants_info->listing_id;
			$mainSkuInfo = WishListing::model ()->find ( 'id=:id', array (
					':id' => $listing_id 
			) );
			if (! $mainSkuInfo) {
				$this->setErrMsg ( "{$sku}:" . Yii::t ( 'wish_listing', "Not found the sku upload or added" ) );
				continue;
			}
			$mainSku = $mainSkuInfo->sku;
			
			// 5.检查当前账号父sku是否已待刊登，是则$saveType改为2（修改次数为0）
			$wishProductIsAdd = WishProductAdd::model ()->find ( 'parent_sku=:parent_sku AND account_id=:account_id', array (
					':parent_sku' => $mainSku,
					':account_id' => $accountId 
			) );
			if ($wishProductIsAdd) {
				$saveType = WishProductAdd::SAVE_TYPE_ONLY_SUBSKU;
			}
			
			// 6.检查当前账号父sku是否已上传，是则$saveType改为4(添加但不上传)
			$wishProductIsUpload = WishListing::model ()->find ( 'sku=:sku AND account_id=:account_id', array (
					':sku' => $mainSku,
					':account_id' => $accountId 
			) );
			if ($wishProductIsUpload) {
				$saveType = WishProductAdd::SAVE_TYPE_NO_MAIN_SKU;
			}
			
			// 7.组装数据
			$descInfo = WishListingExtend::model ()->getDbConnection ()->createCommand ()->from ( WishListingExtend::tableName () )->where ( 'listing_id=:id', array (
					':id' => $listing_id 
			) )->queryRow ();
			if ($descInfo && $descInfo ['description']) {
				$mainSkuInfo->description = $descInfo ['description'];
			}
			
			// 图片直接调用线上数据
			$remote_main_img = $mainImg = $mainSkuInfo ['main_image'];
			$remote_extra_img = $extraImg = $mainSkuInfo ['extra_images'];
			if (! $mainImg) {
				$skuImg = array ();
				$imageType = array (
						'zt',
						'ft' 
				);
				$config = ConfigFactory::getConfig ( 'serverKeys' );
				foreach ( $imageType as $type ) {
					$images = Product::model ()->getImgList ( $sku, $type );
					foreach ( $images as $k => $img ) {
						$skuImg [] = $config ['oms'] ['host'] . $img;
					}
				}
				// 无图片不上传
				if (empty ( $skuImg )) {
					$this->setErrMsg ( "{$sku}:" . Yii::t ( 'wish_listing', "No main image can't upload" ) );
					continue;
				}
				$mainImg = array_shift ( $skuImg );
				$extraImg = implode ( '|', $skuImg );
			}
			
			// add信息
			$data = array (
					'parent_sku' => $mainSku,
					'sku' => $sku,
					'main_image' => empty ( $mainImg ) ? '' : $mainImg,
					'extra_images' => empty ( $extraImg ) ? '' : $extraImg,
					'remote_main_img' => empty ( $remote_main_img ) ? '' : $remote_main_img,
					'remote_extra_img' => empty ( $remote_extra_img ) ? '' : $remote_extra_img,
					'upload_times' => 0,
					'product_is_multi' => '',
					'variants' => array () 
			);
			$data ['subject'] = empty ( $mainSkuInfo->name ) ? '' : $mainSkuInfo->name;
			$data ['tags'] = empty ( $mainSkuInfo->tags ) ? '' : $mainSkuInfo->tags;
			$data ['brand'] = empty ( $mainSkuInfo->brand ) ? '' : $mainSkuInfo->brand;
			$data ['detail'] = empty ( $mainSkuInfo->description ) ? '' : $mainSkuInfo->description;
			if (empty ( $data ['tags'] )) {
				$this->setErrMsg ( "{$sku}:" . Yii::t ( 'wish_listing', "No Tags" ) );
				continue;
			}
			if (empty ( $data ['detail'] )) {
				$this->setErrMsg ( "{$sku}:" . Yii::t ( 'wish_listing', "No Description" ) );
				continue;
			}
			
			// 子表信息
			$variants = array (
					'sku' => $sku,
					'inventory' => WishProductAdd::PRODUCT_PUBLISH_INVENTORY, // 库存数量
					'price' => $variants_info->price,
					'shipping' => $variants_info->shipping,
					'shipping_time' => $variants_info->shipping_time,
					'market_price' => $variants_info->msrp,
					'color' => $variants_info->color,
					'size' => $variants_info->size 
			);
			
			$data ['variants'] [] = $variants;
			WishProductAdd::model ()->saveWishAddData ( array (
					$accountId => $data 
			), $saveType , WishProductAdd::ADD_TYPE_BATCH);
		}
		return true;
	}
        
}