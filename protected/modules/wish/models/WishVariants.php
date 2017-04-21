<?php
class WishVariants extends WishModel{
	/**
	 * @desc 产品可售
	 * @var unknown
	 */
	const WISH_PRODUCT_ENABLED = 1;
	/**
	 * @desc 产品不可售
	 * @var unknown
	 */
	const WISH_PRODUCT_DISABLED = 0;
	const WISH_PRODUCT_DISABLED_MAPPING = -1;
	
	
	public $detail;
	public $sale_property;
	public $status_text;
	public $sku;
	public $review_status_text;
	public $variants_id;
	public $oprator;
	public $parent_sku;
	public $name;
	public $main_image;
	public $account_name;
	
	public static $wishAccountPairs;
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
	public function tableName(){
		return 'ueb_listing_variants';
	}
	/**
	 * @desc 根据条件获取子sku列表
	 * @param string $conditions
	 * @param string $params
	 * @return mixed
	 */
	public function getWishProductVarantList($conditions = null, $params = null){
		
		$builderCommand = $this->getDbConnection()
								->createCommand();
		$builderCommand->where($conditions, $params);
		return	$builderCommand->from(self::tableName())
								->queryAll();
	}
	/**
	 * @desc 根据父级产品ID来获取该产品的所有变种产品
	 * @param unknown $productID
	 * @return mixed
	 */
	public function getWishProductVarantListByProductId($productID, $conditions = null, $params = null){
		$conditions1 = "product_id=:product_id";
		$params1 = array(':product_id'=>$productID);
		$builderCommand = $this->getDbConnection()
						->createCommand()
						->where("product_id=:product_id", array(':product_id'=>(string)$productID));
		if($conditions) {
			$conditions1 .= ' AND '.$conditions;
			$params1 = array_merge($params1, $params);
		}
		$builderCommand->where($conditions1, $params1);
		return	$builderCommand->from(self::tableName())
						->queryAll();
	}
	/**
	 * @desc 根据线上sku下架子产品
	 * @param unknown $skus
	 * @return boolean
	 */
	public function disabledWishVariantsByOnlineSku($skus, $accountID){
		if(!$skus || empty($accountID)) return false;
		if(!is_array($skus)){
			$skus = array($skus);
		}
		//首先获取需要下架的sku对应的listing_id
		$result = $this->getDbConnection()->createCommand()
						->from(self::tableName())
						->where(array('IN', 'online_sku', $skus))
						->andWhere("account_id=:account_id", array(':account_id'=>$accountID))
						->group('listing_id')
						->select('listing_id')
						->queryAll();
		if(!$result) return false;
		$transaction = $this->getDbConnection()->beginTransaction();
		try{
			$this->getDbConnection()->createCommand()
									->update(WishVariants::tableName(),
											array('enabled'=>WishVariants::WISH_PRODUCT_DISABLED),
											array('IN', 'online_sku', $skus));
			$listingIds = array();
			foreach ($result as $val){
				$listingIds[] = $val['listing_id'];
			}
			$result = $this->getDbConnection()->createCommand()
							->from(self::tableName())
							->where(array('IN', 'listing_id', $listingIds))
							->andWhere("enabled=".WishVariants::WISH_PRODUCT_ENABLED)
							->group('listing_id')
							->select('listing_id')
							->queryAll();
			$listingIds2 = array();
			if($result){
				foreach ($result as $val){
					$listingIds2[] = $val['listing_id'];
				}
			}
			$diffIds = array_diff($listingIds, $listingIds2);
			if($diffIds){
				$this->getDbConnection()->createCommand()
										->update(WishListing::tableName(),
												array('enabled'=>WishVariants::WISH_PRODUCT_DISABLED),
												array('IN', 'id', $diffIds));
			}
			$transaction->commit();
			return true;
		}catch (Exception $e){
			$transaction->rollBack();
			return false;
		}
	}
	// ========== Start: 针对于产品列表搜索展示  ============
	
	public function search(){
		$sort = new CSort();
		$sort->attributes = array('defaultOrder'=>'t.product_id');
		$cdbcriteria = new CDbCriteria();
		$cdbcriteria->join = 'LEFT JOIN '.self::model('Wishlisting')->tableName().' AS wl ON wl.product_id=t.product_id AND wl.id=t.listing_id ';
		$cdbcriteria->select = 't.*, wl.id as pid,wl.name,wl.parent_sku,wl.main_image';
		//$cdbcriteria->select = 't.*';
		$cdbcriteria->group = 't.product_id';
		if(isset($_REQUEST['account_id']) && $_REQUEST['account_id']){
			$account_id = (int)$_REQUEST['account_id'];
			$condition = "t.account_id=".$account_id;
			$cdbcriteria->addCondition($condition);
		}
		if(isset($_REQUEST['enabled']) && $_REQUEST['enabled']){
			$enabled = (int)$_REQUEST['enabled'];
			if($enabled == self::WISH_PRODUCT_DISABLED_MAPPING)
				$enabled = self::WISH_PRODUCT_DISABLED;
			$condition = "t.enabled=".$enabled;
			$cdbcriteria->addCondition($condition);
		}
		if(isset($_REQUEST['parent_sku']) && $_REQUEST['parent_sku']){
			$condition = "wl.parent_sku LIKE '%".addslashes($_REQUEST['parent_sku'])."%'";
			$cdbcriteria->addCondition($condition);
		}
		if(isset($_REQUEST['sku']) && $_REQUEST['sku']){
			$condition = "t.sku LIKE '%".addslashes($_REQUEST['sku'])."%'";
			$cdbcriteria->addCondition($condition);
		}
		if(isset($_REQUEST['online_sku']) && $_REQUEST['online_sku']){
			$condition = "t.online_sku LIKE '%".addslashes($_REQUEST['online_sku'])."%'";
			$cdbcriteria->addCondition($condition);
		}
		$dataProvider = parent::search($this, $sort, '', $cdbcriteria);
		$dataProvider->setData($this->_additions($dataProvider->data));
		return $dataProvider;
	}
	/**
	 * @desc 增加额外的数据
	 * @param unknown $datas
	 */
	private function _additions($datas){
		if(empty($datas)) return $datas;
		foreach ($datas as $key=>$data){
			//获取父级产品信息
			$productInfo = self::model('WishListing')->find('product_id=:product_id', array(':product_id'=>$data['product_id']));
			$datas[$key]->id = $productInfo->id;
			$datas[$key]->sku = $productInfo->sku;
			$datas[$key]->parent_sku = $productInfo->parent_sku;
			$datas[$key]->name = $productInfo->name;
			$datas[$key]->account_name = isset(self::$wishAccountPairs[$data['account_id']])?self::$wishAccountPairs[$data['account_id']]:'';
			unset($productInfo);
			//获取当前父级SKU所拥有的变种产品列表
			$variants = $this->filterWishProductVarantListByProductId($data['product_id']);
			if(empty($variants)){
				unset($datas[$key]);
				continue;
			}
			$datas[$key]->detail = array();
			foreach ($variants as $variant){
				$variant['staus_text'] = $this->getWishProductVariantStatusText($variant['enabled']);
				$variant['sale_property'] = $this->getWishProductVariantSalePropertyText($variant['color'], $variant['size']);
				$variant['variants_id'] = $variant['id'];
				$variant['oprator'] = $this->getWishProductVariantOprator($variant['enabled'], $variant['id']);
				$datas[$key]->detail[] = $variant;
			}
		}
		return $datas;
	}
	/**
	 * @desc 获取产品变种列表
	 * @param unknown $productId
	 */
	public function filterWishProductVarantListByProductId($productId){
		$condition = array();
		$params = array();
		if(isset($_REQUEST['enabled']) && $_REQUEST['enabled']){
			$condition[] = 'enabled=:enabled';
			$enabled = (int)$_REQUEST['enabled'];
			if($enabled == self::WISH_PRODUCT_DISABLED_MAPPING)
				$enabled = self::WISH_PRODUCT_DISABLED;
			$params[':enabled'] = $enabled;
		}
		if(isset($_REQUEST['account_id']) && $_REQUEST['account_id']){
			$condition[] = "account_id = '".(int)$_REQUEST['account_id']."'";
			
		}
		if(isset($_REQUEST['sku']) && $_REQUEST['sku']){
			$condition[] = "sku LIKE '".addslashes($_REQUEST['sku'])."%'";
		}
		if(isset($_REQUEST['online_sku']) && $_REQUEST['online_sku']){
			$condition[] = "online_sku LIKE '".addslashes($_REQUEST['online_sku'])."%'";
		}
		$conditions = null;
		if($condition)
			$conditions = implode(' AND ', $condition);
		return self::model('WishVariants')->getWishProductVarantListByProductId($productId, $conditions, $params);
	}
	/**
	 * @desc 获取操作文案
	 * @param unknown $status
	 * @param unknown $variantId
	 * @return string
	 */
	public function getWishProductVariantOprator($status, $variantId){
		$str = "<select style='width:75px;' onchange = 'offLine(this,".$variantId.")' >
				<option>".Yii::t('system', 'Please Select')."</option>";
		if($status == self::WISH_PRODUCT_ENABLED){
			$str .= '<option value="offline">'.Yii::t('wish_listing', 'Product Disabled').'</option>';
		}
		$str .="</select>";
		return $str;
	}
	/**
	 * @desc 获取产品变种状态文案
	 * @param unknown $enabled
	 * @return string
	 */
	public function getWishProductVariantStatusText($enabled){
		$statusText = '';
		$color = 'red';
		switch ($enabled){
			case self::WISH_PRODUCT_ENABLED:
				$color = 'green';
				$statusText = Yii::t('wish_listing', 'Product Enabled');
				break;
			case self::WISH_PRODUCT_DISABLED:
				$statusText = Yii::t('wish_listing', 'Product Disabled');
				break;
		}
		return '<font color='.$color.'>'.$statusText.'</font>';
	}
	/**
	 * @desc 获取产品变种销售属性文案
	 * @param unknown $color
	 * @param unknown $size
	 * @return string
	 */
	public function getWishProductVariantSalePropertyText($color, $size){
		$saleProperty = '';
		if($color)
			$saleProperty .= Yii::t('wish_listing', 'Color').':'.$color;
		if($size)
			$saleProperty .= "  |  ".Yii::t('wish_listing', 'Size').':'.$size;
		return $saleProperty;
	}
	/**
	 * @desc 设置搜索栏内容
	 * @return multitype:multitype:string multitype:string   multitype:string NULL
	 */
	public function filterOptions(){
		return array(
				array(
						'name'=>'sku',
						'type'=>'text',
						'search'=>'LIKE',
						'rel'	=>	true,
						'htmlOption' => array(
								'size' => '22',
						)
				),
				array(
						'name'=>'online_sku',
						'type'=>'text',
						'search'=>'LIKE',
						'htmlOption' => array(
								'size' => '22',
						)
				),
				array(
						'name'=>'parent_sku',
						'type'=>'text',
						'search'=>'LIKE',
						'rel'	=>	true,
						'htmlOption'=>array(
								'size'=>'22'
						)
				),
				array(
						'name'=>'account_id',
						'type'=>'dropDownList',
						'search'=>'=',
						'rel'	=>	true,
						'data'=>$this->getWishAccountList()
				),
				array(
						'name'=>'enabled',
						'type'=>'dropDownList',
						'search'=>'=',
						'rel'	=>	true,
						'data'=>$this->getWishProductStatusOptions()
				),
		);
	}

	/**
	 * @desc  获取公司账号
	 */
	public function getWishAccountList(){
		if(self::$wishAccountPairs == null)
			self::$wishAccountPairs = self::model('WishAccount')->getIdNamePairs();
		return self::$wishAccountPairs;
	}

	/**
	 * @desc 获取产品状态选线
	 * @return multitype:NULL Ambigous <string, string, unknown>
	 */
	public function getWishProductStatusOptions(){
		return array(
				self::WISH_PRODUCT_ENABLED=>Yii::t('wish_listing', 'Product Enabled'),
				self::WISH_PRODUCT_DISABLED_MAPPING=>Yii::t('wish_listing', 'Product Disabled')
		);
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
                    ->from($this->tableName().' as v')
                    ->join(WishListing::tableName().' as p', "v.listing_id=p.id")
                    ->where($where)
                    ->queryAll();
        return $res;
    }
	
	/**
	 * @desc 定义字段名称
	 * @return multitype:string NULL Ambigous <string, string, unknown>
	 */
	public function attributeLabels(){
		return array(
				'variants_id'=> '',
				'sku'=>Yii::t('wish_listing', 'Sku'),
				'enabled'=>Yii::t('wish_listing', 'Status Text'),
				'parent_sku'=>Yii::t('wish_listing', 'Parent Sku'),
				'name'=>Yii::t('wish_listing', 'Product Name'),
				'review_status_text'=>Yii::t('wish_listing', 'Product Review Status'),
				'online_sku'=>Yii::t('wish_listing', 'Online Sku'),
				'sale_property'=>Yii::t('wish_listing', 'Sale Property'),
				'inventory'=>Yii::t('wish_listing', 'Inventory'),
				'price'=>Yii::t('wish_listing', 'Price'),
				'shipping'=>Yii::t('wish_listing', 'Shipping'),
				'msrp'=>Yii::t('wish_listing', 'Market Recommand Price'),
				'oprator'=>Yii::t('system', 'Oprator'),
				'staus_text'=>Yii::t('wish_listing', 'Status Text'),
				'account_id'=>Yii::t('wish_listing', 'Account Name')
		);
	}
	// ========== End: 针对于产品列表搜索展示 ==============

	/**
	 * 根据条件获取单个数据
     * @param  string $condition 
     * @return array
	 */
	public function getInfoByCondition($condition) {
		if (empty($condition)) return false;
        return $this->getDbConnection()->createCommand()
					->select('*')
					->from($this->tableName())
					->where($condition)
					->limit(1)
                    ->queryRow();
	}

    public function updateVariantDataOnline($onlineSku, $accountId, $variantData)
    {
        $request = new UpdateProductVariantRequest();

        $request->setAccount($accountId)->setSku($onlineSku);

        $request->setVariantData($variantData);

        $request->setRequest()->sendRequest()->getResponse();

        if (!$request->getIfSuccess()) {
            throw new \Exception($request->getErrorMsg());
        }
    }


}