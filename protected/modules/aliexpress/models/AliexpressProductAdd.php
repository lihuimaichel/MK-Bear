<?php
/**
 * @desc aliexpress 刊登模型
 * @author zhangF
 *
 */
class AliexpressProductAdd extends AliexpressModel {
	
	public $addID = null;
	public $status_desc = NULL;
	
	/** @var string 账号名称 **/
	public $account_name = null;
	
	/** @var string 分类名称 **/
	public $category_name = null;
	
	/** @var boolean 是否显示upload **/
	public $visiupload;
	
	private $_errorMsg;
	public $discount;
	//海外仓id
	public $overseas_warehouse_id;
	
	const PRODUCT_PUBLISH_MODE_EASY = 1;			//精简刊登模式
	const PRODUCT_PUBLISH_MODE_ALL = 2;				//详细模式

	/**@var 上传状态*/
	const UPLOAD_STATUS_DEFAULT     = 0;//待上传
	const UPLOAD_STATUS_RUNNING     = 1;//上传中
	const UPLOAD_STATUS_IMGFAIL     = 2;//图片上传失败
	const UPLOAD_STATUS_IMGRUNNING  = 3;//等待上传图片
	const UPLOAD_STATUS_SUCCESS     = 4;//上传成功
	const UPLOAD_STATUS_FAILURE     = 5;//上传失败
	
	const PRODUCT_PUBLISH_TYPE_FIXEDPRICE = 1;			//一口价
	const PRODUCT_PUBLISH_TYPE_VARIATION = 2;			//多属性
	
	const PRODUCT_MAIN_IMAGE_MAX_NUMBER = 6;		//刊登主图最多多少张
	
	const PRODUCT_PUBLISH_CURRENCY = 'USD';		//刊登货币
	const MAX_NUM_PER_TASK = 60;		//每次上传产品个数
	const MAX_UPLOAD_TIMES = 30;		//最大上传次数
	
	const ADD_TYPE_DEFAULT = 0;//默认
	const ADD_TYPE_BATCH = 1;//批量
	const ADD_TYPE_PRE = 2;//预刊登
	const ADD_TYPE_COPY = 3;//复制刊登

	const UNION_COMMISSION = 0.02;  //联盟佣金
	
	public function tableName() {
		return 'ueb_aliexpress_product_add';
	}
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function rules() {
		return array(
			array('account_id, category_id, publish_type, publish_mode, subject, upload_user_id, 
					service_template_id, freight_template_id, gross_weight, package_length, package_width,
					package_height', 'required'),
		);
	}
	
	/**
	 * @desc 获取刊登方式
	 * @param string $addType
	 * @return multitype:string |string
	 */
	public function getProductAddTypeOptions($addType = null){
		$addTypeOptions = array(
				self::ADD_TYPE_DEFAULT	=>	'默认',
				self::ADD_TYPE_BATCH	=>	'批量',
				self::ADD_TYPE_PRE		=>	'预刊登',
				self::ADD_TYPE_COPY		=>	'复制刊登',
		);
		if(is_null($addType)) return $addTypeOptions;
		return isset($addTypeOptions[$addType]) ? $addTypeOptions[$addType] : '';
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
    	       'id'                => Yii::t('system', 'No.'),
    	       'sku'               => Yii::t('aliexpress', 'Sku'),
    	       'publish_type'      => Yii::t('aliexpress', 'Publish Type'),
			   'publish_mode'	   => Yii::t('aliexpress', 'Publish Mode'),
    	       'subject'      	   => Yii::t('aliexpress', 'Subject'),
    	       'category_id'       => Yii::t('aliexpress', 'Category Id'),
    	       'status'	           => Yii::t('aliexpress', 'Status'),
    	       'product_price'	   => Yii::t('aliexpress', 'Product Price'),
    	       'product_id'		   => Yii::t('aliexpress', 'Aliexpress Product Id'),
    	       'upload_message'	   => Yii::t('aliexpress', 'Upload Message'),	
			   'create_time'	   => Yii::t('system', 'Create Time'),
			   'upload_time'	   => Yii::t('system', 'Modify Time'),
			   'create_user_id'	   => Yii::t('system', 'Create User'),
			   'upload_time'	   => Yii::t('system', 'Upload Time'),
			   'upload_user_id'	   => Yii::t('system', 'Upload User'),
			   'account_name'	   => Yii::t('aliexpress', 'Account Name'),
			   'category_name'	   => Yii::t('aliexpress', 'Category Name'),
			   'account_id'		   => Yii::t('aliexpress', 'Account Name'),
			   'add_type'		   => '添加方式',
			   'discount'          => '产品折扣',
			   'overseas_warehouse_name'          => '海外仓',
    	);
	}
	
	/**
	 * @return array search filter (name=>label)
	 */
	public function filterOptions() {
		$result = array(
				array(
    					'name'      => 'sku',
    					'type'      => 'text',
    					'search'    => 'LIKE',
    					'alias'     => 't',
    				),
				array(
						'name'      => 'account_id',
						'type'      => 'dropDownList',
						'search'    => '=',
						'data' 		=> AliexpressProduct::model()->getAliexpressAccountPairsList(),
						'alias'     => 't',
				),				
    			array(
    					'name'      => 'publish_type',
    					'type'      => 'dropDownList',
    					'search'    => '=',
    					'data' 		=> self::getProductPublishTypeList(),
    					'alias'     => 't',
    			),
    			array(
    					'name'		 => 'subject',
    					'type'		 => 'text',
    					'search'	 => 'LIKE',
    					'alias'	     => 't',
    			),
    			array(
    					'name'          => 'create_time',
    					'type'          => 'text',
    					'search'        => 'RANGE',
    					'htmlOptions'   => array(
    							'class'    => 'date',
    							'dateFmt'  => 'yyyy-MM-dd HH:mm:ss',
    					),
    					'alias'			=> 't',
    			),
    			array(
    					'name'          => 'upload_time',
    					'type'          => 'text',
    					'search'        => 'RANGE',
    					'htmlOptions'   => array(
    							'class'    => 'date',
    							'dateFmt'  => 'yyyy-MM-dd HH:mm:ss',
    					),
    					'alias'			=> 't',
    			),
    			array(
    					'name'       => 'create_user_id',
    					'type'	     => 'dropDownList',
    					'search'     => '=',
    					'data'		 => User::model()->getEmpByDept(array(4, 25)),
    					'htmlOptions'=> array(),
    					'alias'	     => 't',
    			),
    			array(
    					'name'       => 'upload_user_id',
    					'type'	     => 'dropDownList',
    					'search'     => '=',
    					'data'		 =>  User::model()->getEmpByDept(array(4, 25)),
    					'htmlOptions'=> array(),
    					'alias'	     => 't',
    			),
    			array(
    					'name'       => 'status',
    					'type'	     => 'dropDownList',
    					'search'     => '=',
    					'data'       => self::getStatusList(),
						'htmlOptions'=> array(),
    					'alias'	     => 't',
    			        'value'      => isset($_REQUEST['status']) ? $_REQUEST['status'] : '',
    			        //'notAll'     => true,
    			),
        	    array(
    					'name'		 => 'upload_message',
    					'type'		 => 'text',
    					'search'	 => 'LIKE',
    					'alias'	     => 't',
    			),
				array(
						'name'       => 'add_type',
						'type'	     => 'dropDownList',
						'search'     => '=',
						'data'       => self::getProductAddTypeOptions(),
						'htmlOptions'=> array(),
						'alias'	     => 't',
				),
		);
	
		return $result;
	
	}
	
	/**
	 * @return $array
	 */
	public function getOverseasWarehouseName ($OverseasWarehouseID){
	    if ($OverseasWarehouseID == 0) {
	        echo '';
	    } else {
	        $OverseasWarehouseList = AliexpressWarehouseConfig::model()->getWarehouseList();
	        if ($OverseasWarehouseList && isset($OverseasWarehouseList[$OverseasWarehouseID])){
	            echo $OverseasWarehouseList[$OverseasWarehouseID]['name'];
	        } else {
	            echo '';
	        }	        
	    }
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
	
	protected function _setCDbCriteria(){
		$criteria = new CDbCriteria;
		$criteria->select = 't.*';

		$account_id = '';
		$accountIdArr = array();
		if(isset(Yii::app()->user->id)){
			$accountIdArr = AliexpressAccountSeller::model()->getListByCondition('account_id','seller_user_id = '.Yii::app()->user->id);
		}

		if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id']) ){
			$account_id = (int)$_REQUEST['account_id'];
		}

		if($accountIdArr && !in_array($account_id, $accountIdArr)){
			$account_id = implode(',', $accountIdArr);
		}

		if($account_id){
			$criteria->condition = "t.account_id IN(".$account_id.")";
		}



		if( (isset($_REQUEST['upload_time'][0]) && !empty($_REQUEST['upload_time'][0]))
				&& isset($_REQUEST['upload_time'][1]) && !empty($_REQUEST['upload_time'][1]) )
			$criteria->condition = " upload_time >= '" . addslashes($_REQUEST['upload_time'][0]) . "' and t.upload_time <= '" . addslashes($_REQUEST['upload_time'][1]) . "'";
		if( (isset($_REQUEST['create_time'][0]) && !empty($_REQUEST['create_time'][0]))
		&& isset($_REQUEST['create_time'][1]) && !empty($_REQUEST['create_time'][1]) )
			$criteria->condition = " create_time >= '" . addslashes($_REQUEST['create_time'][0]) . "' and t.create_time <= '" . addslashes($_REQUEST['create_time'][1]) . "'";
		return $criteria;
	}
	
	/**
     * @desc 附加查询条件
     * @param unknown $data
     */
	public function addition($data){
		$accountList = AliexpressAccount::model()->queryPairs(array('id', 'short_name'));
		foreach ($data as $key => $val){
			$sku = $val->sku;
			//$data[$key]->category_id = AliexpressCategory::model()->getTopCategory($val->category_id);
			$data[$key]->status_desc = self::getStatusList($val->status);
			$data[$key]->publish_type = $this->getProductPublishTypeList($val['publish_type']);
			$data[$key]->publish_mode = $this->getProductPublishModelList($val['publish_mode']);
			$data[$key]->add_type = $this->getProductAddTypeOptions($val['add_type']);
			//$data[$key]->sku = CHtml::link($sku, 'products/product/productview/sku/'.$sku,
					//array('style'=>'color:blue;','target'=>'dialog','width'=>'900','height'=>'600','mask'=>true, 'rel' => 'external', 'external' => 'true'));
			$data[$key]->sku = $sku;
			//$data[$key]->status_desc = self::getStatusList($val->status);
			$data[$key]->account_name = array_key_exists($val['account_id'], $accountList) ? $accountList[$val['account_id']] : '';
			$data[$key]->category_name = AliexpressCategory::model()->getBreadcrumbCnAndEn($val->category_id);
			$data[$key]->visiupload = false;
			if($val['status']==self::UPLOAD_STATUS_IMGFAIL || $val['status']==self::UPLOAD_STATUS_DEFAULT || $val['status']==self::UPLOAD_STATUS_FAILURE){
				$data[$key]->visiupload = true;
			}
			if( $val['status']==self::UPLOAD_STATUS_SUCCESS){
				$data[$key]->upload_message = CHtml::link($val['aliexpress_product_id'], 'http://www.aliexpress.com/item//' . $val['aliexpress_product_id'] . '.html', array('target' => '_blank'));
			}elseif( $val['status']==self::UPLOAD_STATUS_FAILURE ){
				$data[$key]->upload_message = $val['upload_message'];
			}else{
				$data[$key]->upload_message = $val['upload_message'];
			}

			$data[$key]->discount = ($val['discount'] == 0)?'':$val['discount'].'%';
		}
		return $data;
	}
	
	
	/**
	 * @desc 获取产品刊登模式列表
	 * @param string $key
	 * @return Ambigous <string, Ambigous <string, string, unknown>>|multitype:string Ambigous <string, string, unknown>
	 */
	public static function getProductPublishModelList($key = null) {
		$list = array(
			self::PRODUCT_PUBLISH_MODE_EASY => Yii::t('aliexpress_product', 'Publish Mode Easy'),
			//self::PRODUCT_PUBLISH_MODE_ALL => Yii::t('aliexpress_product', 'Publish Mode All'),
		);
		if (!is_null($key) && array_key_exists($key, $list)) {
			return $list[$key];
		}
		return $list;
	}
	
	/**
	 * @desc 添加待刊登任务
	 */
	public function productAdd($sku, $accountID){
		/**@ 1.获取需要的参数*/
		//产品信息
		$skuInfo = Product::model()->getProductInfoBySku($sku);
		//货币
		$currency = 'USD';
		/**@ 2.检测是否能添加*/
		//检测sku信息是否存在
		if( empty($skuInfo) ){
			return array(
					'status'    => 0,
					'message'   => Yii::t('common','SKU Does Not Exists.'),
			);
		} 
		
		/** 3.拼装刊登数据 **/
		$aliexpressListingData = array();
		$productAddData = array();	//刊登主数据
		$productAddAttributeData = array();	//刊登属性
		$productAddVariationData = array();	//刊登多属性SKU
		$productAddVariationAttributeData = array();	//刊登多属性SKU的属性
		$productAddImageData = array();	//刊登图片
		$hasCustomImage = false;	//是否添加多属性图片
		//检查是否有在线数据
		$aliexpressListings = AliexpressProduct::model()->getOnlineListingBySku($sku);
		if (!empty($aliexpressListings)) {
			//复制已经在线的该SKU数据刊登
			$aliexpressListingData = $aliexpressListings[0];
			$variationInfos = AliexpressProductVariation::model()->getByProductId($aliexpressListingData['id']);
			$aliexpressProductExtendInfo = AliexpressProductVariation::getInfoByProductID($aliexpressListingData['id']);
			//判断是否为多属性SKU
			$publishType = self::PRODUCT_PUBLISH_TYPE_FIXEDPRICE;
			if ($aliexpressListingData['is_variation'] == 1) {
				$productSku = '';
				$publishType = self::PRODUCT_PUBLISH_TYPE_VARIATION;
				$productPrice = null;
			} else {
				$productSku = $aliexpressListingData['sku'];
				$productPrice = $aliexpressListingData['product_price'];
				
			}
			//$groupID = 
			
			$productAddData = array(
				'account_id' => $accountID,
				'sku' => $aliexpressListingData['sku'],
				'category_id' => $aliexpressListingData['category_id'],
				'group_id' => '',
				'currency' => $aliexpressListingData['currency'],
				'publish_type' => $publishType,
				'publish_mode' => self::PRODUCT_PUBLISH_MODE_ALL,
				'create_user_id' => Yii::app()->user->id,
				'create_time' => date('Y-m-d H:i:s'),
				'product_price' => round(floatval($productPrice), 2),
				'service_template_id' => '',
				'freight_template_id' => '',
				'is_package' => '',
				'product_unit' => '',
				'lot_num' => '',
				'gross_weight' => '',
				'package_length' => '',
				'package_width' => '',
				'package_height' => '',
				'detail' => '',
				'subject' => $aliexpressListingData['subject']
			);
			
			//产品普通属性
			$attributes = json_decode($aliexpressProductExtendInfo['product_property']);
			foreach ($attributes as $key => $attribute) {
				$attributeID = null;
				$attributeName = null;
				$attributeValueID = null;
				$attributeValueName = null;
				$isCustom = 0;
				if (isset($attribute->attrNameId))
					$attributeID = $attribute->attrNameId;
				if (isset($attribute->attrName))
					$attributeName = $attribute->attrName;
				if (isset($attribute->attrValueId))
					$attributeValueID = $attribute->attrValueId;
				if (isset($attribute->attrValue))
					$attributeValueName = $attribute->attrValue;
				if (is_null($attributeID) && is_null($attributeValueID))
					$isCustom = 1;
				$productAddAttributeData[$key] = array(
					'attribute_id' => $attributeID,
					'value_id' => $attributeValueID,
					'attribute_name' => $attributeName,
					'value_name' => $attributeValueName,
					'is_custom' => $isCustom,
				);
			}
			
			//产品多属性SKU
			foreach ($variationInfos as $key => $variationInfo) {
				$productAddVariationData[$key] = array(
					'sku' => $variationInfo['sku'],
					'price' => $variationInfo['sku_price'],
				);
					
				$skuAttributes = json_decode($variationInfo['sku_property']);
				foreach ($skuAttributes as $skuAttribute) {
					$customAttributeValueName = null;
					if (isset($skuAttribute->propertyValueDefinitionName) && !empty($skuAttribute->propertyValueDefinitionName))
						$customAttributeValueName = $skuAttribute->propertyValueDefinitionName;
					if (isset($skuAttribute->skuImage) && !empty($skuAttribute->skuImage))
						$hasCustomImage = true;
					$productAddVariationAttributeData[$key][] = array(
						'attribute_id' => $skuAttribute->skuPropertyId,
						'value_id' => $skuAttribute->propertyValueId,
						'value_name' => $customAttributeValueName,
					);
				}
			}
		}
		
		//产品图片
		$mainImageList = array();
		$additionalImageList = array();
		$mainImageList = Product::model()->getImgList($sku, ProductImageAdd::IMAGE_ZT);
		$additionalImageList = Product::model()->getImgList($sku, ProductImageAdd::IMAGE_ZT);
		
		//获取产品描述模板
		$data = array(
				'sku' => $sku,
				'platform_code' => Platform::CODE_ALIEXPRESS,
				'account_id' => $accountInfo->id,
		);
		$descriptionTemplate = ConditionsRules::model()->getTemplateInfo($data, DescriptionTemplate::model());
		if (empty($descriptionTemplate)) {
			
		}
		//获取产品价格模板
		
		//获取产品参数模板
		$paramTemplate = ConditionsRules::model()->getTemplateInfo($data, AliexpressParamTemplate::model());
		if (empty($paramTemplate)) {
			
		}
		
		if( $addID > 0 ){
			//属性信息
			if( $attributes ){
				foreach($attributes as $attribute){
					LazadaProductAddAttribute::model()->saveRecord($addID, $attribute['attribute_name'], $attribute['value_name']);
				}
			}
		}
		return array(
				'status'    => 1,
				'addID'     => $addID,
		);
	}
	
	/**
	 * @desc 通过sku获取刊登记录(按上传时间排序)
	 * @param string $sku
	 */
	public function getRecordBySku($sku){
		return $this->dbConnection->createCommand()
		->select('*')
		->from(self::tableName())
		->where('sku = "'.$sku.'"')
		->order('upload_time DESC')
		->queryAll();
	}
	
	public function getListingPrepareUploadBySku($sku){
		return $this->dbConnection->createCommand()
		->select('t.*')
		->from(self::tableName() . " t")
		->where('sku = "'.$sku.'"')
		->andWhere('status != '.self::UPLOAD_STATUS_SUCCESS)
		->queryAll();
	}

	/**
	 * @desc 根据sku获取可刊登账号
	 * @param string $sku
	 */
	public function getAbleAccountsBySku($sku){
		$excludeAccounts = array();
		//获取sku在线listing
		$listOnline = AliexpressProduct::model()->getOnlineListingBySku($sku);
		foreach($listOnline as $item){
			$excludeAccounts[$item['account_id']] = $item['account_id'];
		}
		//获取准备刊登(在刊登列表里)的记录
		$listTask = $this->getListingPrepareUploadBySku($sku);
		foreach($listTask as $item){
			$excludeAccounts[$item['account_id']] = $item['account_id'];
		}
		
		$accountAll = AliexpressAccount::getAbleAccountList();
		$accounts = array();$accountInfo = array();
		foreach($accountAll as $account){
			//TODO 排除锁定状态设定为无法刊登的账号
			$accounts[$account['id']] = $account['id'];
		}
		$ableAccounts = array_diff($accounts,$excludeAccounts);
		foreach($accountAll as $account){
			if( in_array($account['id'], $ableAccounts) ){
				$accountInfo[$account['id']] = $account['short_name'];
			}
		}
		return $accountInfo;
	}
	/**
	 * @desc 获取可用账号列表，根据sku
	 * @param unknown $sku
	 * @return multitype:unknown
	 */
	public function getAbleAccountListBySku($sku){
		$excludeAccounts = array();
		//获取sku在线listing
		$listOnline = AliexpressProduct::model()->getOnlineListingBySku($sku);
		foreach($listOnline as $item){
			$excludeAccounts[$item['account_id']] = $item['account_id'];
		}
		//获取准备刊登(在刊登列表里)的记录
		$listTask = $this->getListingPrepareUploadBySku($sku);
		foreach($listTask as $item){
			$excludeAccounts[$item['account_id']] = $item['account_id'];
		}
	
		$accountAll = AliexpressAccount::getAbleAccountList();
		$accounts = array();$accountInfo = array();
		foreach($accountAll as $account){
			//TODO 排除锁定状态设定为无法刊登的账号
			$accounts[$account['id']] = $account['id'];
		}
		$ableAccounts = array_diff($accounts,$excludeAccounts);
		foreach($accountAll as $account){
			// $account['is_upload'] = true;
			// if( in_array($account['id'], $ableAccounts) ){
				$account['is_upload'] = false;
			// }
			$accountInfo[$account['id']] = $account;
		}
		return $accountInfo;
	}
	/**
	 * @desc 获取产品刊登类型
	 * @param string $key
	 * @return Ambigous <string, Ambigous <string, string, unknown>>|multitype:string Ambigous <string, string, unknown>
	 */
	public static function getProductPublishTypeList($key = null) {
		$list = array(
			self::PRODUCT_PUBLISH_TYPE_FIXEDPRICE => Yii::t('aliexpress_product', 'Publish Type Fixed Price'),
			self::PRODUCT_PUBLISH_TYPE_VARIATION => Yii::t('aliexpress_product', 'Publish Type Variation'),
		);
		if (!is_null($key) && array_key_exists($key, $list))
			return $list[$key];
		return $list;
	}
	
	/**
	 * @desc 查找sku刊登列表信息
	 * @param string $sku
	 * @param string $status
	 * @return Ambigous <multitype:, mixed>
	 */
	public function getPublishListBySku($sku, $status = null) {
		$command = $this->dbConnection->createCommand()
			->select('*')
			->from(self::tableName())
			->where('sku = :sku', array(':sku' => $sku));
		if (!is_null($status))
			$command->andWhere("status = :status", array(':status' => $status));
		return $command->queryAll();		
	}

	/**
	 * @desc 保存刊登数据
	 * @param array $param
	 */
	public function saveRecord($param){
		$flag = $this->dbConnection->createCommand()->insert(self::tableName(), $param);
		if( $flag ){
			return $this->dbConnection->getLastInsertID();
		}else{
			return false;
		}
	}

	/**
	 * @desc 获取需要上传的待刊登记录
	 */
	public function getNeedUploadRecord($accountID, $status = null){
		$statusID = self::UPLOAD_STATUS_DEFAULT;
		if($status){
			$statusID = $status;
		}

		return $this->dbConnection->createCommand()
		->select('id')
		->from(self::tableName())
		->where('status = '. $statusID)
		->andWhere('account_id = '.$accountID)
		->andWhere("(aliexpress_product_id = 0 OR ISNULL(aliexpress_product_id))")
		->limit(self::MAX_NUM_PER_TASK)
		->queryColumn();
	}
	
	/**
	 * @desc 按账号分组上传产品
	 * @param array $addIDs
	 */
	public function uploadProduct($addIDs){
		$addGroup = array();
		$addInfos = $this->dbConnection->createCommand()->select('*')->from(self::tableName())->where('id IN ('.MHelper::simplode($addIDs).')')->queryAll();
		foreach($addInfos as $addInfo){
			$addGroup[$addInfo['account_id']][$addInfo['id']] = $addInfo['id'];
		}
		foreach($addGroup as $accountID=>$ids){
				$this->uploadProductByAccount($ids, $accountID, 1);
		}
	}
	
	/**
	 * @desc 根据保存记录上传广告
	 * @param array   $addIDs
	 * @param integer $imgUploadType  上传图片的方式 0、为旧方式上传  1、为新方式上传
	 */
	public function uploadProductByAccount($addIDs, $accountID, $imgUploadType = 0){
	    //$uploadUserID = intval(Yii::app()->user->id);
	    foreach ($addIDs as $addID) {
	        $this->uploadProductByAccountEach($addID, $accountID, $imgUploadType);
	    }
	}
	
	/**
	 * @desc 根据保存记录上传广告
	 * @param array   $addIDs
	 * @param integer $imgUploadType  上传图片的方式 0、为旧方式上传  1、为新方式上传
	 */
	public function uploadProductByAccountEach($addID, $accountID, $imgUploadType = 0){
		$uploadUserID = intval(Yii::app()->user->id);


			try{
				$skus = array();
				/** 1.获取基础信息 **/
				//获取产品刊登数据
				$addInfo = AliexpressProductAdd::model()->findByPk($addID);
				if (empty($addInfo)) continue;
				$this->addID = $addID;
				$this->setRunning();
				$accountID = $addInfo->account_id;
				$sku = $addInfo->sku;
				if (in_array($addInfo->status, array(self::UPLOAD_STATUS_RUNNING, self::UPLOAD_STATUS_SUCCESS, self::UPLOAD_STATUS_IMGRUNNING))) {
					$this->setFailure('任务已经运行或者完成');
					return false;
				}
				//获取产品信息
				$skuInfo = Product::model()->getProductInfoBySku($addInfo['sku']);
				if (empty($skuInfo)) {
					$this->setFailure("找不到对应的sku:{$sku}");
					return false;
				}
				
				//获取最优价格模板
				$data = array(
						'sku' => $sku,
						'platform_code' => Platform::CODE_ALIEXPRESS,
						'account_id' => $accountID,
				);
				$ruleModel = new ConditionsRulesMatch();
				$ruleModel->setRuleClass(TemplateRulesBase::MATCH_PRICE_TEMPLATE);
				$salePriceSchemeID = $ruleModel->runMatch($data);
				if (empty($salePriceSchemeID) || !($salePriceScheme = SalePriceScheme::model()->getSalePriceSchemeByID($salePriceSchemeID))) {
					$this->setFailure('找不到卖价销售模型');
					return false;
				}
				//echo 2;
				//获取最优描述模板
	  			$data = array(
						'sku' => $sku,
						'platform_code' => Platform::CODE_ALIEXPRESS,
						'account_id' => $accountID,
				);
				$ruleModel = new ConditionsRulesMatch();
				$ruleModel->setRuleClass(TemplateRulesBase::MATCH_DESCRI_TEMPLATE);
				$descriptionTemplateID = $ruleModel->runMatch($data);
				if (empty($descriptionTemplateID) || !($descriptTemplate = DescriptionTemplate::model()->getDescriptionTemplateByID($descriptionTemplateID))) {
					$this->setFailure('找不到描述模板');
					return false;
				}
				
				//获取最优参数模板
	 			$data = array(
						'sku' => $sku,
						'platform_code' => Platform::CODE_ALIEXPRESS,
						'account_id' => $accountID,
				);
				$ruleModel = new ConditionsRulesMatch();
				$ruleModel->setRuleClass(TemplateRulesBase::MATCH_PARAM_TEMPLATE);
				$paramsTemplateID = $ruleModel->runMatch($data);
				if (empty($paramsTemplateID) || !($paramsTemplate = AliexpressParamTemplate::model()->getParamTemplateByID($paramsTemplateID))) {
					$this->setFailure('找不到参数模板');
					return false;				
				}
				
				/** 2.检查是否能刊登(侵权，利润等) */
				//判断是否已有在线广告
				// $existListing = AliexpressProduct::model()->getOnlineListingBySku($addInfo['sku'], $addInfo['account_id']);
				// if( !empty($existListing) ){
				// 	$this->setFailure(Yii::t('aliexpress_product', 'Exist Product'));continue;
				// }
	 			//判断产品是否侵权
	    		$checkInfringe = ProductInfringe::model()->getProductIfInfringe($addInfo['sku']);
				if( $checkInfringe ){
					$this->setFailure('sku侵权了');
					return false;
				}
				
				//判断利润情况
	   			/*$priceCal = new CurrencyCalculate();
				$priceCal->setCurrency($addInfo['currency']);//币种
				$priceCal->setPlatform(Platform::CODE_ALIEXPRESS);//设置销售平台
				$priceCal->setSku($addInfo['sku']);//设置sku
				$priceCal->setSalePrice($addInfo['product_price']);
				$profitRate = $priceCal->getProfitRate();
				if( $profitRate < $salePriceScheme['lowest_profit_rate'] ){
					$this->setFailure( Yii::t('common', 'Profit Rate').':'.$profitRate.','.Yii::t('common', 'Lowest Profit Rate').':'.$salePriceScheme['lowest_profit_rate'] );
					continue;
				} */

				$aliexpressProductImageAdd = new AliexpressProductImageAdd();
				//判断是否添加了图片
				$imgInfo = $aliexpressProductImageAdd->getAliexpressImageBySku($addInfo['sku'], $accountID);
				if(!$imgInfo){
					$result = $aliexpressProductImageAdd->aliexpressAutoImagesAdd($addInfo['sku'], $accountID);
					if(!$result){
						//$this->setFailure('添加sku图片失败');
						$this->dbConnection->createCommand()->update(self::tableName(), array(
						    'status'        => self::UPLOAD_STATUS_IMGFAIL,
						    'upload_message'=> '添加sku图片失败,请编辑添加图片',
						), 'id = '.$addInfo['id']);
						return false;
					}
				}

				if($imgUploadType == 1){
		    		$res1 = $aliexpressProductImageAdd->newUploadImageOnline($addInfo['sku'], $accountID);
		    		if(!$res1){
		    			$this->dbConnection->createCommand()->update(self::tableName(), array(
							'upload_message'    => '上传sku图片失败(新),这个情况有两个原因，一个是图片银行满了,要删除未引用图片。一个是java那边没有上传图片，请联系相应的java技术人员。'.$aliexpressProductImageAdd->getErrorMessage(),
							'status'            => self::UPLOAD_STATUS_IMGFAIL,
						), 'id = '.$addInfo['id']);
						return false;
		    		}

				} else {
					//上传产品图片
					$result = $aliexpressProductImageAdd->uploadImageOnline($addInfo['sku'], $accountID);
					if (!$result) {
						$this->dbConnection->createCommand()->update(self::tableName(), array(
								'upload_message'    => '上传sku图片失败(旧),这个情况有两个原因，一个是图片银行满了,要删除未引用图片。一个是java那边没有上传图片，请联系相应的java技术人员。' . $aliexpressProductImageAdd->getErrorMessage(),
								'status'            => self::UPLOAD_STATUS_IMGFAIL,
						), 'id = '.$addInfo['id']);					
						return false;
					}
				}
								
				$postAeProductRequest = new PostAeProductRequest();
				
				/** 3.设置刊登数据 **/			
				//设置基础数据
				$postAeProductRequest->setCategoryID($addInfo->category_id);	//设置产品分类
				if (!empty($addInfo['group_id']))
					$postAeProductRequest->setGroupId($addInfo['group_id']);	//设置产品分组
				$postAeProductRequest->setCurrencyCode($addInfo->currency);		//设置货币
				$title = $addInfo['subject'];
				$postAeProductRequest->setSubject($title);	//设置标题
				$postAeProductRequest->setPackageLength($addInfo['package_length']);	//设置包装长
				$postAeProductRequest->setPackageWidth($addInfo['package_width']);	//设置包装宽
				$postAeProductRequest->setPackageHeight($addInfo['package_height']);	//设置包装高
				$postAeProductRequest->setGrossWeight($addInfo['gross_weight']);	//设置产品重量
				$postAeProductRequest->setPromiseTemplateId($addInfo['service_template_id']);	//设置服务器模板ID
				$postAeProductRequest->setFreightTemplateId($addInfo['freight_template_id']);	//设置运费模板ID
				$postAeProductRequest->setProductUnit($addInfo['product_unit']);	//设置产品单位
				//是否打包销售
				if ($addInfo['is_package']) {
					$postAeProductRequest->setPackageType('true');
					$postAeProductRequest->setLotNum($addInfo['lot_num']);
				}
				
				//获取产品主图
	 			$mainImageList = AliexpressProductImageAdd::model()->getRemoteImages($sku, $accountID, Platform::CODE_ALIEXPRESS, ProductImageAdd::IMAGE_ZT);
				//获取产品附图
				$additionalImageList = AliexpressProductImageAdd::model()->getRemoteImages($sku, $accountID, Platform::CODE_ALIEXPRESS, ProductImageAdd::IMAGE_FT);

				
				if (empty($additionalImageList))
					$additionalImageList = array();
				//子sku图片
				/* 
				if($addInfo['publish_type'] ==self::PRODUCT_PUBLISH_TYPE_VARIATION && $variationProducts){
					foreach ($variationProducts as $variationSKU){
						$temlArr = ProductImageAdd::model()->getRemoteImages($variationSKU['sku'], $addInfo['account_id'], Platform::CODE_ALIEXPRESS, ProductImageAdd::IMAGE_ZT);
						$additionalImageList = array_merge($additionalImageList, $temlArr);
					}
				} */
				//如果主图少于速卖通限制张数则用附图补充
				$mianImageNum = sizeof($mainImageList);
				$needNum = self::PRODUCT_MAIN_IMAGE_MAX_NUMBER - $mianImageNum;	//还需要的图片张数
				if ($needNum > 0) {
					$imageList = array_diff($additionalImageList, $mainImageList);	//去掉附图里面和主图同名的图片
					$mainImageList = array_merge($mainImageList, array_slice($imageList, 0, $needNum));
				}
				if (empty($mainImageList)) {
				    $this->dbConnection->createCommand()->update(self::tableName(), array(
				        'status'        => self::UPLOAD_STATUS_IMGFAIL,
				        'upload_message'=> '产品主图数量不够。',
				    ), 'id = ' . $addInfo['id']);
					//$this->setFailure('产品图片没有上传');
					return false;
				}
				$imageUrls = implode(';', $mainImageList);
				$postAeProductRequest->setImageURLs($imageUrls);
				
				//设置产品描述
				$description = $skuInfo['description']['english'];
				$include = $skuInfo['included']['english'];
				//$content = $descriptTemplate['template_content'];
				$content = $addInfo['detail'];		
				
				//设置产品属性
				$attributes = AliexpressProductAddAttribute::model()->getProductAddAttributes($addID);
				
				$postAeProductRequest->setAeopAeProductPropertys($attributes);

				//取出佣金
				$commissionRate = AliexpressCategoryCommissionRate::getCommissionRate($addInfo->category_id);

				//设置多属性产品
				$variationProducts = AliexpressProductAddVariation::model()->getVariationProductAdd($addID);
				$variationProductAttributesArray = array();
				$variationProductAttributesLoop = array();
	 			if (empty($variationProducts)) {
	 				//判断是否小于最低利润情况
		   			$checkLowest = Product::model()->checkProfitRate($addInfo->currency, Platform::CODE_ALIEXPRESS, $sku, $addInfo['product_price'], $commissionRate);
					if(!$checkLowest){
						$this->setFailure('小于最低利润了');
						return false;
					}

					//一口价刊登
					$postAeProductRequest->setProductPrice($addInfo['product_price']);	//设置产品一口价
					$skus[] = array(
						'aeopSKUProperty' => array(),
						'skuPrice' => $addInfo['product_price'],
						'skuCode' => $sku,
						'skuStock' => true,
						'ipmSkuStock' => $paramsTemplate['stock_num'],
						'currencyCode' => self::PRODUCT_PUBLISH_CURRENCY,
					);
					$postAeProductRequest->setAeopAeProductSKUs($skus);
				} else {
					//多属性刊登
					$variationProducts = AliexpressProductAddVariation::model()->getVariationProductAdd($addID);
					if (empty($variationProducts)) {
						$this->setFailure('子sku为空');
						return false;					
					}

					//验证每个子sku是否大于最低利润率
					$moreThanLowestSKU = '';
					foreach ($variationProducts as $varProInfo) {
						//判断是否小于最低利润情况
			   			$checkLowest = Product::model()->checkProfitRate($addInfo->currency, Platform::CODE_ALIEXPRESS, $varProInfo['sku'], $varProInfo['price'], $commissionRate);
						if(!$checkLowest){
							$moreThanLowestSKU .= $varProInfo['sku'].',';
						}
					}

					if($moreThanLowestSKU){
						$this->setFailure(rtrim($moreThanLowestSKU,',').' 小于最低利润了');
						return false;
					}
					
					$skuAttributes = array();
					$attributeSort = array();
					foreach ($variationProducts as $key => $variationProduct) {
					    $childSkuAttributes = array();
						$skus[$key] = array(
							'skuPrice' => $variationProduct['price'],
							'skuCode' => $variationProduct['sku'],
							'skuStock' => true,
							'ipmSkuStock' => $paramsTemplate['stock_num'],
							'currencyCode' => self::PRODUCT_PUBLISH_CURRENCY,						
						);
						$childSkuAttributes = array(
							'skuPrice' => $variationProduct['price'],
							'skuCode' => $variationProduct['sku'],
							'skuStock' => true,
							'ipmSkuStock' => $paramsTemplate['stock_num'],
							'currencyCode' => self::PRODUCT_PUBLISH_CURRENCY,						
						);
						$variationProductAttributes = AliexpressProductAddVariationAttribute::model()->getVariationProductAttributes($variationProduct['id']);
						$variationProductAttributesArray[$variationProduct['id']] = $variationProductAttributes;
						if (empty($variationProductAttributes)) {
							$this->setFailure('子sku属性为空');
							return false;						
						}
						$childSkuAttributesProperty = array();
						foreach ($variationProductAttributes as $k => $variationProductAttribute) {
							if (!array_key_exists($variationProductAttribute['attribute_id'], $skuAttributes)) {
								$skuAttribute = AliexpressCategoryAttributes::model()->getCategoryAttribute($addInfo->category_id, $variationProductAttribute['attribute_id']);
								$variationProductAttributesLoop[$variationProduct['id']][$k]['skuAttribute'] = $skuAttribute;
								if (empty($skuAttribute)) {
								    $this->setFailure('获取分类属性失败，请尝试更新分类属性');
								    return false;
								}
								$skuAttributes[$variationProductAttribute['attribute_id']] = $skuAttribute;
								$attributeSort[$skuAttribute['spec']] = $variationProductAttribute['attribute_id'];
							}
							$skuAttribute = $skuAttributes[$variationProductAttribute['attribute_id']];
							$sort = $skuAttribute['spec'];
							$variationProductAttributesLoop[$variationProduct['id']][$k]['sort'] = $sort;
							/** @TODO 临时判断是否需要上传多属性图片 **/
							$attributeImage = '';
							$attributeInfo = AliexpressCategoryAttributes::model()->getCategoryAttribute($addInfo->category_id, $variationProductAttribute['attribute_id']);
							if ($skuAttribute['customized_pic'] == 1) {
								//上传多属性图片
	 							$aliexpressProductImageAdd = new AliexpressProductImageAdd();
	 							//$productImageAddModel      = new ProductImageAdd();
	 							if($imgUploadType != 1){
									$result = $aliexpressProductImageAdd->uploadImageOnline($variationProduct['sku'], $accountID);
								}else{
									$isExist = $aliexpressProductImageAdd->getAliexpressImageBySku($variationProduct['sku'], $accountID);
									if(!$isExist){
										$imagesFt = Product::model()->getImgList($variationProduct['sku'], 'ft');
								        if (!empty($imagesFt)){
								        	$imgUrl = array_shift($imagesFt);
								            $imageName = basename($imgUrl);
								        }
								        
								        $imageAddData = array(
								                'image_name'    => $imageName,
								                'sku'           => $variationProduct['sku'],
								                'type'          => ProductImageAdd::IMAGE_ZT,
								                'local_path'    => $imgUrl,
								                'platform_code' => Platform::CODE_ALIEXPRESS,
								                'account_id'    => $accountID,
								                'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
								                'create_user_id'=> Yii::app()->user->id,
								                'create_time'   => date('Y-m-d H:i:s'),
								        );
								        $imageVarModel = new AliexpressProductImageAdd();
								        $imageVarModel->setAttributes($imageAddData,false);
								        $imageVarModel->setIsNewRecord(true);
								        $imageVarModel->save();
									}

									$result = $aliexpressProductImageAdd->newUploadImageOnline($variationProduct['sku'], $accountID);
								}
								if(!$result){
								    $this->dbConnection->createCommand()->update(self::tableName(), array(
								        'status'        => self::UPLOAD_STATUS_IMGFAIL,
								        'upload_message'=> '子sku图片上传失败,这个情况有两个原因，一个是图片银行满了,要删除未引用图片。一个是java那边没有上传图片，请联系相应的java技术人员。',
								    ), 'id = ' . $addInfo['id']);
									//$this->setFailure('子sku图片上传失败');
									return false;
								}
	
								$attributeImages = $aliexpressProductImageAdd->getRemoteImages($variationProduct['sku'], $accountID, Platform::CODE_ALIEXPRESS, ProductImageAdd::IMAGE_ZT);
								if(!empty($attributeImages)){
									$attributeImage = $attributeImages[0];
									$additionalImageList[] = $attributeImage;
								}
							}

							if ($attributeImage) {
								$skus[$key]['aeopSKUProperty'][$sort] = array(
										'skuPropertyId' => $variationProductAttribute['attribute_id'],
										'propertyValueId' => $variationProductAttribute['value_id'],
										'skuImage'	=> $attributeImage
								);
								$childSkuAttributesProperty[$sort] = array(
										'skuPropertyId' => $variationProductAttribute['attribute_id'],
										'propertyValueId' => $variationProductAttribute['value_id'],
										'skuImage'	=> $attributeImage
								);
							} else {			
								$skus[$key]['aeopSKUProperty'][$sort] = array(
										'skuPropertyId' => $variationProductAttribute['attribute_id'],
										'propertyValueId' => $variationProductAttribute['value_id'],
								);
								$childSkuAttributesProperty[$sort] = array(
										'skuPropertyId' => $variationProductAttribute['attribute_id'],
										'propertyValueId' => $variationProductAttribute['value_id'],
								);
							}
							//是否自定义属性名
							if (!empty($variationProductAttribute['value_name'])){
							    $skus[$key]['aeopSKUProperty'][$sort]['propertyValueDefinitionName'] = $variationProductAttribute['value_name'];
							    $childSkuAttributesProperty[$sort]['propertyValueDefinitionName'] = $variationProductAttribute['value_name'];
							}
								
							$variationProductAttributesLoop[$variationProduct['id']][$k]['aeopSKUProperty'][$sort] = $skus[$key]['aeopSKUProperty'][$sort];
						}
						
						/*此排序会重复数组
						$i = 0;						
						ksort($skus[$key]['aeopSKUProperty']);
						foreach($skus[$key]['aeopSKUProperty'] as $k => $row) {
							unset($skus[$key]['aeopSKUProperty'][$k]); 
							$skus[$key]['aeopSKUProperty'][$i] = $row;
							$i++;
						}
						*/
						$childSkuAttributes['aeopSKUProperty'] = $childSkuAttributesProperty;
						
						$variationProductAttributesLoop[$variationProduct['id']]['aeopSKUProperty'] = $skus[$key]['aeopSKUProperty'];
						$variationProductAttributesLoop[$variationProduct['id']]['key'] = $key;
						$variationProductAttributesLoop[$variationProduct['id']]['childSkuAttributes'] = $childSkuAttributes;
						
						//新的排序方式
						if(!isset($skus[$key]['aeopSKUProperty']) || !$skus[$key]['aeopSKUProperty']){
						    $this->setFailure('子sku属性不存在，请编辑');
						    return false;
						}
						
						$tempArray = array();
						$tempNum = count($skus[$key]['aeopSKUProperty']);
						$j = 0;
						for ($i=0; $i<666; $i++){
						    if (isset($skus[$key]['aeopSKUProperty'][$i])){
						        $tempArray[] = $skus[$key]['aeopSKUProperty'][$i];
						        $j++;
						        if ($j == $tempNum) break;
						    }
						}
						$skus[$key]['aeopSKUProperty'] = $tempArray;
						if ($tempNum != count($childSkuAttributesProperty)){
						    $skus[$key] = $childSkuAttributes;
						}
					}
					
					$postAeProductRequest->setAeopAeProductSKUs($skus);
				}

				//去掉附图里重复的图片地址
				$singleImageList = array_unique($additionalImageList);

				$detail = DescriptionTemplate::model()->getDescription($content, $description, $title, $include, $singleImageList);
				//将产品信息模块占位符替换成对应内容
				$detail = $this->getDescription($accountID, $detail);
				$postAeProductRequest->setDetail($detail);

				//设置默认参数
				$postAeProductRequest->setDeliveryTime($paramsTemplate['delivery_time']);		//设置备货期
				$postAeProductRequest->setWsValidNum($paramsTemplate['ws_valid_num']);	//设置产品有效天数
				if ($paramsTemplate['package_type'] == 1) {
					$postAeProductRequest->setPackageType($paramsTemplate['package_type']);	//设置产品是否打包销售
				}
				$postAeProductRequest->setReduceStrategy($paramsTemplate['reduce_strategy']);	//库存扣减策略
				/** 4.刊登交互 **/
				$response = $postAeProductRequest->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
				if ($postAeProductRequest->getIfSuccess()) {
					//更新刊登状态为成功
					$this->getDbConnection()->createCommand()->update(self::tableName(), array(
						'status' => self::UPLOAD_STATUS_SUCCESS,
						'upload_message' => 'SUCCESS',
						'aliexpress_product_id' => $response->productId,
						'upload_user_id'	=>	intval($uploadUserID)
					), "id = $addID");

					//更新待刊登列表的状态
					AliexpressWaitListing::model()->updateWaitingListingStatus($addInfo, AliexpressWaitListing::STATUS_SCUCESS);
                    AliexpressHistoryListing::model()->updateWaitingListingStatus($addInfo, AliexpressWaitListing::STATUS_SCUCESS);

					//更新海外仓映射表
                    if (isset($response->productId) && isset($addInfo['overseas_warehouse_id']) && $addInfo['overseas_warehouse_id'] > 0) {
                        $sql = "insert into " . AliexpressOverseasWarehouse::model()->tableName() . "(sku,product_id,overseas_warehouse_id,seller_id,account_id) values ";
                        $sql .="('" .trim(addslashes($addInfo['sku'])). "','" .trim(addslashes($response->productId)). "','" .$addInfo['overseas_warehouse_id']. "','" .$addInfo['create_user_id']. "','" .$addInfo['account_id']. "'),";
                        $sql = substr($sql,0,strlen($sql)-1);
                        $ret = AliexpressOverseasWarehouse::model()->getDbConnection()->createCommand($sql)->execute();
                        if ($ret){
                            //删除重复记录(并且是保留最新插入的重复记录)
                            $delsql = "delete from " .AliexpressOverseasWarehouse::model()->tableName(). " where id not in ( select * from ( select max(id) from " .AliexpressOverseasWarehouse::model()->tableName(). " group by product_id ) as u )";
                            AliexpressOverseasWarehouse::model()->getDbConnection()->createCommand($delsql)->execute();
                        }
                    }
					
					//将上传的产品拉到系统
					$url = '/aliexpress/aliexpressproduct/getproduct/account_id/' . $accountID . '/product_id/' . $response->productId . '/product_status_type/onSelling';
					sleep(3);	//避免平台数据未更新，等待3秒
					MHelper::runThreadSOCKET($url);
				} else {
					//记录上传错误消息
					$this->getDbConnection()->createCommand()->update(self::tableName(), array(
							'status' => self::UPLOAD_STATUS_FAILURE,
							'upload_message' => $response->error_code.' - '.$response->error_message.' - '.$postAeProductRequest->getErrorDetail($response->error_code),
							'upload_user_id'	=>	intval($uploadUserID)
					), "id = $addID");		

					//记录上传错误消息之api上传数据
					$apiErrorInsert = array();
					$apiErrorInsert['account_id']      = $addInfo['account_id'];
					$apiErrorInsert['sku']             = $addInfo['sku'];
					$apiErrorInsert['rid']             = $addInfo['create_user_id'];
					$apiErrorInsert['status']          = self::UPLOAD_STATUS_FAILURE;  
					$apiErrorInsert['message']         = '子sku：' . json_encode($variationProducts) . ' 子sku属性：' . json_encode($variationProductAttributesArray);
					$apiErrorInsert['message']        .= '子sku属性循环详细参数：' . json_encode($variationProductAttributesLoop);
					$apiErrorInsert['message']        .= 'api上传参数：' . json_encode($postAeProductRequest->getRequest()) . ' api错误返回：' . json_encode($response);
					$apiErrorInsert['create_time']     = date('Y-m-d H:i:s');
					$this->getDbConnection()->createCommand()->insert('ueb_aliexpress_product_add_pre_log', $apiErrorInsert);
				}
			}catch (Exception $e){
				echo $e->getMessage();
				$this->setFailure($e->getMessage());
			}

	}
	
	/**
	 * @desc 设置任务运行
	 */
	public function setRunning(){	
		$flag = $this->dbConnection->createCommand("update " . self::tableName() . " set `status` = " . self::UPLOAD_STATUS_RUNNING . ", `upload_count` = upload_count + 1, `upload_time` = '" . date('Y-m-d H:i:s') . "' where `id` = " . (int)$this->addID)->execute();
	}	
	
	/**
	 * @desc 设置任务失败
	 * @param string $message
	 */
	public function setFailure($message){
		$status = self::UPLOAD_STATUS_FAILURE;
		if($message == 'Failed to get SKU pictures'){
			$status = self::UPLOAD_STATUS_IMGFAIL;
		}

		$this->dbConnection->createCommand()->update(self::tableName(), array(
				'status'        => $status,
				'upload_user_id'=> intval(Yii::app()->user->id),
				'upload_time'   => date('Y-m-d H:i:s'),
				'upload_message'=> $message,
		), 'id = '.$this->addID);
	}
	
	/**
	 * @desc 根据规则获取产品包装长，宽，高
	 * @param unknown $skuInfo
	 */
	public function getPackageSize($skuInfo) {
		/* 如果产品有包装尺寸并且尺寸大于等于1cm则优先包装尺寸，否则使用产品尺寸，如果产品尺寸小于1cm，则根据如下规则设置
		 * weight（0-50g）      尺寸计5 x 3 x 2cm
		* weight（50-100g）  尺寸计10 x 8 x 7cm
		* weight（>100g）    尺寸计20 x 10 x 1cm
		*/
		$packageSize = array();
		$packageSizePreSetting = array();
		$weight = $skuInfo['gross_product_weight'] > 0 ? $skuInfo['gross_product_weight'] : $skuInfo['product_weight'];
		if ($weight >= 0 && $weight < 50)
			$packageSizePreSetting = array(5, 3, 2);
		else if ($weight >= 50 && $weight < 100)
			$packageSizePreSetting = array(10, 8, 7);
		else
			$packageSizePreSetting = array(20, 10, 1);
		//包装长
		$packageSize[0] = $skuInfo['pack_product_length'] / 10 >= 1 ? round($skuInfo['pack_product_length'] / 10) :
		($skuInfo['product_length'] / 10 >= 1 ? round($skuInfo['product_length'] / 10) : round($packageSizePreSetting[0]));
		//包装宽
		$packageSize[1] = $skuInfo['pack_product_width'] / 10 >= 1 ? round($skuInfo['pack_product_width'] / 10) :
		($skuInfo['product_width'] / 10 >= 1 ? round($skuInfo['product_width'] / 10) : round($packageSizePreSetting[1]));
		//包装高
		$packageSize[2] = $skuInfo['pack_product_height'] / 10 >= 1 ? round($skuInfo['pack_product_height'] / 10) :
		($skuInfo['product_height'] / 10 >= 1 ? round($skuInfo['product_height'] / 10) : round($packageSizePreSetting[2]));
		return $packageSize;		
	}
	
	/**
	 * @desc 根据状态获取刊登信息
	 * @param int $accountID
	 * @param tinyint $status
	 */
	public function getUploadRecordByStatus($accountID, $status, $skuLine = ''){
		$where = '';
		if($skuLine!==''){
			$where = 'sku LIKE "'.$skuLine.'%"';
		}
		return $this->dbConnection->createCommand()
		->select('*')
		->from(self::tableName())
		->where('account_id = "'.$accountID.'"')
		->andWhere('status = '.$status)
		->andWhere($where)
		->queryAll();
	}

	/**
	 * @desc 设置正在上传图片
	 */
	public function setImageRunning(){
		$this->dbConnection->createCommand()->update(self::tableName(), array(
				'status'        => self::UPLOAD_STATUS_IMGRUNNING,
		), 'id = '.$this->addID);
	}
	
	/**
	 * @desc 获取菜单对应ID
	 * @return integer
	 */
	public static function getIndexNavTabId() {
		return UebModel::model('Menu')->getIdByUrl('/aliexpress/aliexpressproductadd/list');
	}
	
	/**
	 * @desc 根据ID查询记录
	 * @param unknown $id
	 * @return mixed
	 */
	public function getInfoById($id) {
		return $this->getDbConnection()->createCommand()
			->select("*")
			->from(self::tableName())
			->where("id = :id", array(':id' => $id))
			->queryRow();
	}
	
	public function getDescription($accountID, $detail) {
		$pattern = "/<img.*class=\"product_info_module_placeholder_img\".*>/Ui";
		$placeholderArr = array();
		$replaceArr = array();
		$moduleIds = array();
		$placeholders = array();
		if (preg_match_all($pattern, $detail, $placeholderArr)) {
			$placeholders = $placeholderArr[0];
			foreach ($placeholders as $key => $placeholder) {
				$pattern = "/\d+/";
				$arr = array();
				if (preg_match($pattern, $placeholder, $arr)) {
					if (!empty($arr[0]))
						$moduleIds[$key] = $arr[0];
					else
						unset($placeholders[$key]);
				}
			}
		}
		/*
		<div style="font-size: 28px; font-family: tahoma, geneva, sans-serif; font-weight: bold; padding: 8px 100px;">
			<kse:widget data-widget-type="customText|relatedProduct" id="22151594" title="Hot Tool" type="custom"></kse:widget>
			<kse:widget data-widget-type="[:ModuleText]" id="[:ModuleID]" title="[:ModuleName]" type="[:ModuleType]"></kse:widget>
		</div>
		 * */
		$moduleDesc = '<div style="font-size: 28px; font-family: tahoma, geneva, sans-serif; font-weight: bold; padding: 2px 2px;">
				<kse:widget data-widget-type="[:ModuleText]" id="[:ModuleID]" title="[:ModuleName]" type="[:ModuleType]"></kse:widget>
				</div>';
		foreach ($moduleIds as $key => $moduleId) {
			//$moduleContents = Aliexpressproductinfomodule::model()->getModuleContents($accountID, $moduleId);
			$moduleNameAndType = Aliexpressproductinfomodule::model()->getModuleNameAndType($accountID, $moduleId);
			if (empty($moduleNameAndType)) {
				$replaceArr[$key] = '';
			} else {
				
				$moduleText = "relatedProduct";
				if($moduleNameAndType['type'] == 'custom'){
					$moduleText = "customText";
				}
				$moduleContents = str_replace(array(
					"[:ModuleText]", "[:ModuleID]", "[:ModuleName]", "[:ModuleType]"
				), array(
					$moduleText, $moduleId, $moduleNameAndType['name'], $moduleNameAndType['type']
				), $moduleDesc);
				$replaceArr[$key] = $moduleContents;
			}
		}
		$detail = str_replace($placeholders, $replaceArr, $detail);
		return $detail;
	}
	
	public function updateByCondition($data, $condition, $params = array()){
		if(empty($data) || empty($condition)) return false;
		return $this->getDbConnection()->createCommand()->update($this->tableName(), $data, $condition, $params);
	}


	/**
	 * 通过sku数组和上传状态查询数据
	 * @param array $sku
	 * @param int   $accountId  账号
	 * @param int   $status     上传状态
	 * @return array
	 */
	public function getPublishListBySkuAndStatusAndAccountId($sku, $accountId, $status = null) {
		$command = $this->dbConnection->createCommand()
			->select('*')
			->from(self::tableName())
			->where(array("IN",'sku',$sku))
			->andWhere('account_id = :accountId', array(':accountId'=>$accountId));
		if (!is_null($status))
			$command->andWhere("status = :status", array(':status' => $status));
		return $command->queryAll();		
	}


	/**
	 * 用sql语句插入数据
	 */
	public function insertBySql($insertFields,$insertData){
		$insertSql = "INSERT INTO ".self::tableName()." (".$insertFields.") VALUES".$insertData;
		return $this->dbConnection->createCommand($insertSql)->execute();
	}


	/**
	 * @desc 查找sku产品列表信息
	 * @return array
	 */
	public function getProductListSku() {
		$command = $this->dbConnection->createCommand()
			->select('sku')
			->from(self::tableName())
			->where('id > :id', array(':id' => '0'))
			->group('sku');
		return $command->queryColumn();		
	}


	/**
	 * [getOneByCondition description]
	 * @param  string $fields [description]
	 * @param  string $where  [description]
	 * @param  mixed $order  [description]
	 * @return [type]         [description]
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
	 * @param string $fields
	 * @param string $where
	 * @param string $order
	 * @return mixed
	 */
	public function getAllByCondition($fields='*', $where='1',$order='')
	{
		$sql = "SELECT {$fields} FROM ".$this->tableName()." WHERE {$where} ";
		$cmd = $this->dbConnection->createCommand($sql);
		return $cmd->queryAll();
	}


	
	/**
	 * @desc 批量刊登
	 * @param unknown $sku
	 * @param unknown $accountID
	 * @param string $addType
	 * @param string $onlineAccountId
	 * @param string $moduleId
	 * @param string $groupId
	 * @param string $freightTemplateId
	 * @return boolean
	 */
	public function productAddByBatch($sku, $accountID, $addType = null, $onlineAccountId = '', $moduleId = '', $groupId = '', $freightTemplateId = ''){
		if(is_null($addType)){
			$addType = self::ADD_TYPE_DEFAULT;
		}
		try{
			//判断sku是否为空
			if(!$sku){
				$this->throwE('要发布的SKU不能为空');
			}
			//判断要发布的账号ID是否为空
			if(!$accountID){
				$this->throwE('要发布的账号ID不能为空');
			}
			//从产品表查询出要添加的sku信息
			$where = '';
			if($onlineAccountId){
				$where = "sku='{$sku}' AND account_id = ".$onlineAccountId;
			}else{
				$onlineAccountId = $accountID;
				$selectWhere = "sku='{$sku}' ";
				$order = 'id DESC';
				$group = 'sku';
				$onlineId = AliexpressProduct::model()->getListByCondition('MAX(id) AS id',$selectWhere,$order,$group);
				if($onlineId){
					$onlineArr = array();
					foreach ($onlineId as $onlineValue) {
						$where = "id = {$onlineValue['id']}";
						break;
					}
				}
			}
			
			$addInfo = AliexpressProduct::model()->find($where);
			if(!$addInfo){
				$this->throwE("没有匹配到刊登SKU");
			}
			
			$aliexpressAccountName = AliexpressAccount::model()->getIdNamePairs();
			$aliexpressProductAddModel = new AliexpressProductAdd();
			$aliexpressProductAddAttribute = new AliexpressProductAddAttribute();
			$aliexpressProductAddVariation = new AliexpressProductAddVariation();
			
			
			$status = 0;
			$times = date('Y-m-d H:i:s');
			$loginUserId = intval(Yii::app()->user->id);
		
			//产品分组ID
			if(!$groupId){
				// 取出默认账号的第一个
				$groupInfo = AliexpressGroupList::model()->getGroupListOneByAccountId($accountID);
				$groupId = isset($groupInfo['groupId'])?$groupInfo['groupId']:0;
			}
		
			//取出服务模板
			$serviceTemplateId = AliexpressPromiseTemplate::model()->getTemplateIdByAccountId($accountID);
			if(!is_numeric($serviceTemplateId)){
				$serviceTemplateId = 0;
			}
		
			$freightTemplateInfo = AliexpressFreightTemplate::model()->getTemplateIdInfoByAccountId($accountID);
			$freightTemplateArr = array();
			if($freightTemplateInfo){
				foreach ($freightTemplateInfo as $ftkey => $ftvalue) {
					$freightTemplateArr[] = $ftvalue['template_id'];
				}
			}
		
			//如果没有选择运费模板，取默认模板
			if(!in_array($freightTemplateId, array_unique($freightTemplateArr))){
				$freightTemplateId = AliexpressFreightTemplate::model()->getTemplateIdByAccountId($accountID);
			}
		
			$skuAttributeImages = array();
			
		
			$publishFields = '';
			$sku = $addInfo['sku'];
			$addId = $addInfo['id'];
			$cateGoryId = $addInfo['category_id'];
			$publishType = ($addInfo['is_variation'] == 1)?2:1;
			// $floatPrice = round(floatval($addInfo['product_price']), 2);
	
			//检测是否有权限去刊登该sku
			if(!Product::model()->checkCurrentUserAccessToSaleSKUNew($sku, $accountID, Platform::CODE_ALIEXPRESS)){
				$this->throwE("无权限刊登SKU");
			}
	
			//查找产品信息
			$skuInfo = Product::model()->getProductInfoBySku($sku);
			if(!$skuInfo){
				$this->throwE("SKU不存在");
			}
	
			//判断要发布的sku和账号是否已经存在
			$isProductModel = AliexpressProduct::model()->getProductListBySkuAndAccountId(array($sku),$accountID);
			if($isProductModel){
				$this->throwE("SKU已经刊登过");
			}
	
			//判断要发布到账号的sku是否已经存在待刊登列表里
			$queryWhere = "sku = '".$sku."' AND account_id = ".$accountID;
			$isProductAddModelData = $aliexpressProductAddModel->getOneByCondition('*',$queryWhere);
			if($isProductAddModelData){
				$this->throwE("SKU已经存在预刊登记录里");
			}
	
			//取出产品价格
			$priceInfo = AliexpressProductStatistic::model()->getProductPriceBySkuAndAccoutId($sku,$accountID);
			if(!isset($priceInfo[$accountID]['salePrice']) || $priceInfo[$accountID]['salePrice'] < 0 ){
				$this->throwE('sku为：'.$sku.'的产品价格没有找到');
			}
			$floatPrice = round(floatval($priceInfo[$accountID]['salePrice']), 2);
			if($floatPrice <= 0){
				$this->throwE('sku为：'.$sku.'的产品价格低于0');
			}
	
			//获取产品图片
			$imageType = array('zt', 'ft');
			$config = ConfigFactory::getConfig('serverKeys');
			$skuImages = array();
			foreach($imageType as $type){
				$images = Product::model()->getImgList($sku,$type);
				foreach($images as $k=>$img){
					$skuImages[$type][$k] = $img;
				}
			}
	
			$insertData = '';
			//获取最优描述模板
			$descriptTemplate = AliexpressProductStatistic::model()->getDescriptTemplateInfo($sku, $accountID);
			if(!$descriptTemplate){
				$this->throwE('描述模板为空');
			}
			
			//设置产品描述
			$description = $skuInfo['description']['english'];
			$include = $skuInfo['included']['english'];
			$content = $descriptTemplate['template_content'];
			$publishTitle = $skuInfo['title']['english'];
			
			//取出已经刊登过的账号，sku模板前后缀
			$onlineAccountIdDescriptTemplate = AliexpressProductStatistic::model()->getDescriptTemplateInfo($sku, $accountID);
			if($onlineAccountIdDescriptTemplate){
				//去掉账号的前缀
				$publishTitle = str_replace($onlineAccountIdDescriptTemplate['title_prefix'], '', $addInfo['subject']);
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
			$insertFields = 'account_id,sku,category_id,group_id,currency,publish_type,publish_mode,subject,create_user_id,create_time,
					modify_user_id,modify_time,product_price,status,service_template_id,freight_template_id,is_package,
					product_unit,lot_num,gross_weight,package_length,package_width,package_height,detail,add_type';
			$insertData =
					"(".
						$accountID.",'".
						$addInfo['sku']."',".
						$addInfo['category_id'].",".
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
						$addInfo['package_type'].",".
						$addInfo['product_unit'].",'".
						$addInfo['lot_num']."','".
						$grossWeight."','".
						$addInfo['package_length']."','".
						$addInfo['package_width']."','".
						$addInfo['package_height']."','".
						str_replace("'", "\'", $descriptions)."',".$addType.
					")";
			try{
				$dbtransaction = Yii::app()->db->beginTransaction();
				$addResult = $aliexpressProductAddModel->insertBySql($insertFields,$insertData);
				if(!$addResult){
					$this->throwE("保存主表数据失败！");
				}
				$newAddId = $aliexpressProductAddModel->getDbConnection()->getLastInsertID();
				//保存产品图片
				$productImageAdd = new ProductImageAdd();
				//删除以前的图片
				ProductImageAdd::model()->deleteSkuImages($sku, $accountID, Platform::CODE_ALIEXPRESS);
				foreach ($skuImages as $type => $images) {
					foreach ($images as $image) {
						$typeId = 0;
						$imagename = basename($image, '.jpg');
		
						if($type == 'zt'){
							$typeId = ProductImageAdd::IMAGE_ZT;
						}elseif ($type == 'ft') {
							$typeId = ProductImageAdd::IMAGE_FT;
						}

						$imageAddData = array(
								'image_name'    => $imagename,
								'sku'           => $sku,
								'type'          => $typeId,
								'local_path'    => $image,
								'platform_code' => Platform::CODE_ALIEXPRESS,
								'account_id'    => $accountID,
								'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
								'create_user_id'=> Yii::app()->user->id,
								'create_time'   => date('Y-m-d H:i:s'),
						);
						$imageModel = new ProductImageAdd();
						$imageModel->setAttributes($imageAddData,false);
						$imageModel->setIsNewRecord(true);
						$flag = $imageModel->save();
						if (!$flag){
							$this->throwE("保存图片失败！");
						}
					}
				}
				unset($imageAddData);
				if($newAddId){
					//添加产品普通属性
					$insertAttributeData = '';
					$aliexpressProductExtendInfo = AliexpressProductExtend::model()->getInfoByProductID($addInfo['id']);
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
								$this->throwE("属性添加失败!");
							}
						}
					}
		
					//添加多属性
					$attributeInfos = array();
					if($addInfo['is_variation'] == 1){
						//查找产品属性表
						$productVariationInfo = AliexpressProductVariation::model()->getByProductId($addInfo['id']);
						$insertVariationFields = 'add_id,sku,price';
						$insertVariationData = '';
						foreach ($productVariationInfo as $pvkey => $variation) {
		
							$variationSku = $variation['sku'];
		
							//设置利润在15%的价格
							//取出子sku价格
							$variationPriceInfo = AliexpressProductStatistic::model()->getProductPriceBySkuAndAccoutId($variationSku,$accountID);
							if(!isset($variationPriceInfo[$accountID]['salePrice']) || $variationPriceInfo[$accountID]['salePrice'] < 0 ){
								$this->throwE('sku为：'.$sku.'的产品价格没有找到');
							}
		
							//判断利润率是否小于0
							if(!isset($variationPriceInfo[$accountID]['profitRate']) || $variationPriceInfo[$accountID]['profitRate'] < '0.15'){
								$this->throwE('子sku为：'.$variationSku.'的产品利润率小于0.15');
							}
		
							$variationPrice = round(floatval($variationPriceInfo[$accountID]['salePrice']), 2);
							if($variationPrice <= 0){
								$this->throwE('子sku为：'.$variationSku.'的产品价格小于等于0');
							}
		
							//添加刊登多属性表
							$insertVariationData = "('".$newAddId."','".$variationSku."','".$variationPrice."')";
							$addVariationResult = $aliexpressProductAddVariation->insertBySql($insertVariationFields, $insertVariationData);
							if(!$addVariationResult){
								$this->throwE("添加子sku失败");
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
										$this->throwE("添加子sku属性失败！");
									}
								}
							}
		
		
							//是否需要自定义多属性图片
							if ($attributeInfo['customized_pic'] == 1) {
								//取sku的一个主图
								$images = Product::model()->getImgList($variationSku, 'ft');
								if (!empty($images)) {
									$skuAttributeImages[$variationSku] = basename(array_shift($images), '.jpg');
								}
								else {
									$imagesFt = Product::model()->getImgList($variationSku, 'ft');
									if (!empty($imagesFt))
										$skuAttributeImages[$variationSku] = basename(array_shift($imagesFt), '.jpg');
								}
							}
		
							//将SKU多属性图片添加到图片上传表
							if ($attributeInfo['customized_pic'] == 1 && isset($skuAttributeImages[$variationSku]) && !empty($skuAttributeImages[$variationSku])) {
								if($variationSku == $sku){
									continue;//当子SKU 和主SKU一致时
								}
								//查询图片是否已经添加
								ProductImageAdd::model()->deleteSkuImages($variationSku, $accountID, Platform::CODE_ALIEXPRESS);
								$imageAddData = array(
										'image_name'    => $skuAttributeImages[$variationSku],
										'sku'           => $variationSku,
										'type'          => ProductImageAdd::IMAGE_ZT,
										'local_path'    => ProductImageAdd::getImageLocalPathBySkuAndName($variationSku, 1, $skuAttributeImages[$variationSku]),
										'platform_code' => Platform::CODE_ALIEXPRESS,
										'account_id'    => $accountID,
										'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
										'create_user_id'=> Yii::app()->user->id,
										'create_time'   => date('Y-m-d H:i:s'),
								);
								$imageModel = new ProductImageAdd();
								$imageModel->setAttributes($imageAddData,false);
								$imageModel->setIsNewRecord(true);
								$imageModel->save();
							}
		
						}
					}
				}
				$dbtransaction->commit();
			}catch (Exception $e){
				$dbtransaction->rollback();
				$this->throwE($e->getMessage());
			}
			return true;
		}catch (Exception $e){
			$this->setErrorMsg($e->getMessage());
			return false;
		}
	}
	
	private function throwE($message, $code = null){
		throw new Exception($message, $code);
	}
	/**
	 * @desc 设置错误消息
	 * @param unknown $msg
	 */
	public function setErrorMsg($msg){
		$this->_errorMsg = $msg;
	}
	/**
	 * @desc 获取错误信息
	 * @return unknown
	 */
	public function getErrorMsg(){
		return $this->_errorMsg;
	}


	/**
	 * @desc 根据条件获取多条数据
	 * @param unknown $fields
	 * @param unknown $conditions
	 * @param string $param
	 * @return mixed
	 */
	public function getProductAddInfoAll($fields, $conditions, $param = null){
		return $this->getDbConnection()->createCommand()
								->select($fields)
								->from(self::tableName())
								->where($conditions, $param)
								->queryAll();
	}


	/**
	 * @desc 复制刊登
	 * @param unknown $sku
	 * @param unknown $accountID
	 * @param string $addType
	 * @param string $onlineAccountId
	 * @param string $moduleId
	 * @param string $groupId
	 * @param string $freightTemplateId
	 * @return boolean
	 */
	public function productAddByCopy($sku, $accountID, $addType = null, $onlineAccountId = '', $moduleId = '', $groupId = '', $freightTemplateId = ''){ 
		if(is_null($addType)){
			$addType = self::ADD_TYPE_DEFAULT;
		}
		try{
			//判断sku是否为空
			if(!$sku){
				throw new Exception("要发布的SKU不能为空");
			}
			//判断要发布的账号ID是否为空
			if(!$accountID){
				throw new Exception("要发布的账号ID不能为空");
			}
			//从产品表查询出要添加的sku信息
			$where = '';
			if($onlineAccountId){
				$where = "sku='{$sku}' AND account_id = ".$onlineAccountId;
			}else{
				$onlineAccountId = $accountID;
				$selectWhere = "sku='{$sku}' ";
				$order = 'id DESC';
				$group = 'sku';
				$onlineId = AliexpressProduct::model()->getListByCondition('MAX(id) AS id',$selectWhere,$order,$group);
				if($onlineId){
					$onlineArr = array();
					foreach ($onlineId as $onlineValue) {
						$where = "id = {$onlineValue['id']}";
						break;
					}
				}
			}
			
			$addInfo = AliexpressProduct::model()->find($where);
			if(!$addInfo){
				throw new Exception("没有匹配到刊登SKU");
			}
			
			$aliexpressAccountName = AliexpressAccount::model()->getIdNamePairs();
			$aliexpressProductAddModel = new AliexpressProductAdd();
			$aliexpressProductAddAttribute = new AliexpressProductAddAttribute();
			$aliexpressProductAddVariation = new AliexpressProductAddVariation();
			
			
			$status       = 0;
			$times        = date('Y-m-d H:i:s');
			$loginUserId  = intval(Yii::app()->user->id);
			$platformCode = Platform::CODE_ALIEXPRESS;
		
			//产品分组ID
			if(!$groupId){
				// 取出默认账号的第一个
				$groupInfo = AliexpressGroupList::model()->getGroupListOneByAccountId($accountID);
				$groupId = isset($groupInfo['groupId'])?$groupInfo['groupId']:0;
			}
		
			//取出服务模板
			$serviceTemplateId = AliexpressPromiseTemplate::model()->getTemplateIdByAccountId($accountID);
			if(!is_numeric($serviceTemplateId)){
				$serviceTemplateId = 0;
			}
				
			$skuAttributeImages = array();
			$publishFields = '';
			$sku = $addInfo['sku'];
			$addId = $addInfo['id'];
			$cateGoryId = $addInfo['category_id'];
			$publishType = ($addInfo['is_variation'] == 1)?2:1;
			// $floatPrice = round(floatval($addInfo['product_price']), 2);
	
			//检测是否有权限去刊登该sku
			if(!Product::model()->checkCurrentUserAccessToSaleSKU($sku, Platform::CODE_ALIEXPRESS)){
				throw new Exception("无权限刊登SKU");
			}
	
			//查找产品信息
			$skuInfo = Product::model()->getProductInfoBySku($sku);
			if(!$skuInfo){
				throw new Exception("SKU不存在");
			}

			//排除已停售产品
			if($skuInfo['product_status'] == 7){
				throw new Exception("SKU是已停售状态");
			}

			//验证主sku
			if($publishType == Product::PRODUCT_MULTIPLE_MAIN){
				$productSelectedAttribute = new ProductSelectAttribute();
				$skuAttributeList = $productSelectedAttribute->getChildSKUListByProductID($skuInfo['id']);
				if(!$skuAttributeList){
					throw new Exception("异常主sku");
				}

				if($publishType == 1){
					throw new Exception("主sku不能当做单品刊登");
				}
			}
	
			//判断要发布的sku和账号是否已经存在
			$isProductModel = AliexpressProduct::model()->getProductListBySkuAndAccountId(array($sku),$accountID);
			if($isProductModel){
				throw new Exception("SKU已经刊登过");
			}
	
			//判断要发布到账号的sku是否已经存在待刊登列表里
			$queryWhere = "sku = '".$sku."' AND account_id = ".$accountID;
			$isProductAddModelData = $aliexpressProductAddModel->getOneByCondition('*',$queryWhere);
			if($isProductAddModelData){
				throw new Exception("SKU已经存在预刊登记录里");
			}

			//取出佣金
			$commissionRate = AliexpressCategoryCommissionRate::getCommissionRate($addInfo['category_id']);
	
			//取出产品价格
			$priceInfo = AliexpressProductStatistic::model()->getProductPriceBySkuAndAccoutId($sku,$accountID,$commissionRate);
			if(!isset($priceInfo[$accountID]['salePrice']) || $priceInfo[$accountID]['salePrice'] < 0 ){
				throw new Exception('sku为：'.$sku.'的产品价格没有找到');
			}
			$floatPrice = round($priceInfo[$accountID]['salePrice'], 2);
			if($floatPrice <= 0){
				throw new Exception('sku为：'.$sku.'的产品价格低于0');
			}

			//单品取出对应的运费模板
			if($addInfo['is_variation'] != 1){
				$freightTemplateId = AliexpressFreightTemplate::model()->getAppointTemplateId($sku,$accountID,$floatPrice);
			}else{
				$freightTemplateId = AliexpressFreightTemplate::model()->getTemplateIdByAccountId($accountID);
			}
		
			$insertData = '';
			//获取最优描述模板
			$descriptTemplate = AliexpressProductStatistic::model()->getDescriptTemplateInfo($sku, $accountID);
			if(!$descriptTemplate){
				throw new Exception('描述模板为空');
			}

			//设置产品描述
			$description = isset($skuInfo['description']['english'])?$skuInfo['description']['english']:'';
			$include = isset($skuInfo['included']['english'])?$skuInfo['included']['english']:'';
			$content = $descriptTemplate['template_content'];
			$publishTitle = isset($skuInfo['title']['english'])?$skuInfo['title']['english']:'';
			
			//取出已经刊登过的账号，sku模板前后缀
			$onlineAccountIdDescriptTemplate = AliexpressProductStatistic::model()->getDescriptTemplateInfo($sku, $onlineAccountId);
			if($onlineAccountIdDescriptTemplate){
				//去掉账号的前缀
				$publishTitle = str_replace($onlineAccountIdDescriptTemplate['title_prefix'], '', $addInfo['subject']);
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

			$paramData = array(
				'account_id' 			=> $accountID,
				'sku' 					=> $addInfo['sku'],
				'category_id' 			=> $addInfo['category_id'],
				'group_id' 				=> $groupId,
				'currency' 				=> 'USD',
				'publish_type' 			=> $publishType,
				'publish_mode' 			=> 1,
				'subject' 				=> trim($subject),
				'create_user_id' 		=> $loginUserId,
				'create_time' 			=> $times,
				'modify_user_id' 		=> $loginUserId,
				'modify_time' 			=> $times,
				'status' 				=> $status,
				'service_template_id' 	=> $serviceTemplateId,
				'freight_template_id' 	=> $freightTemplateId,
				'is_package' 			=> $addInfo['package_type'],
				'product_unit'			=> $addInfo['product_unit'],
				'lot_num' 				=> 0,
				'gross_weight' 			=> $grossWeight,
				'package_length' 		=> $addInfo['package_length'],
				'package_width' 		=> $addInfo['package_width'],
				'package_height' 		=> $addInfo['package_height'],
				'detail' 				=> $descriptions,
				'add_type' 				=> $addType
			);

			//多属性待刊登表不用写入价格
			if($addInfo['is_variation'] != 1){
				$paramData['product_price'] = $floatPrice;
			}

			try{
				$dbtransaction = AliexpressProductAdd::model()->getDbConnection()->beginTransaction();
				$addResult = $this->saveRecord($paramData);
				if(!$addResult){
					throw new Exception('保存主表数据失败！');
				}
				$newAddId = $aliexpressProductAddModel->getDbConnection()->getLastInsertID();

				//保存产品图片
				$aliProductImageAddModel = new AliexpressProductImageAdd();
				$result = $aliProductImageAddModel->aliexpressAutoImagesAdd($addInfo['sku'], $accountID);
				if(!$result){
					throw new Exception('保存图片数据失败！');
				}

				//推送图片
				$productImageAdd = new ProductImageAdd();
				if($newAddId){
					//添加产品普通属性
					$insertAttributeData = '';
					$aliexpressProductExtendInfo = AliexpressProductExtend::model()->getInfoByProductID($addInfo['id']);
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
			                $attributeWhere = 'add_id = '.$newAddId.' AND attribute_id = '.$attributeID;
			                $attributeIsExits = $aliexpressProductAddAttribute->getOneByCondition('*',$attributeWhere);
			                if($attributeIsExits){
			                    if($attributeIsExits['value_name'] && empty($attributeIsExits['value_id']) && isset($attributeValueID)) {
		                            $updateData = array('value_id'=>$attributeValueID);
		                            $addAttributeResult=$aliexpressProductAddAttribute->updateBySql($updateData, $attributeWhere);
		                        }elseif(empty($attributeIsExits['value_name']) && $attributeIsExits['value_id'] && isset($attributeValueName)){
		                            $updateData = array('value_name'=>$attributeValueName);
		                            $addAttributeResult=$aliexpressProductAddAttribute->updateBySql($updateData, $attributeWhere);
		                        }else{
		                            continue;
		                        }
			                }else{
			                	$attributeName = str_replace("'", "\'", $attributeName);
			                	$attributeValueName = str_replace("'", "\'", $attributeValueName);
			                    $insertAttributeData .= $newAddId.",".$attributeID.",".$attributeValueID.",'".$attributeName."','".$attributeValueName."',".$isCustom.")";
			                    $addAttributeResult=$aliexpressProductAddAttribute->insertBySql($insertAttributeFields, $insertAttributeData);
			                }
		
							if(!$addAttributeResult){
								throw new Exception('属性添加失败');
							}
						}
					}
		
					//添加多属性
					$attributeInfos = array();
					$variationPriceArr = array();
					if($addInfo['is_variation'] == 1){
						//查找产品属性表
						$productVariationInfo = AliexpressProductVariation::model()->getByProductId($addInfo['id']);
						$insertVariationFields = 'add_id,sku,price';
						$insertVariationData = '';
						foreach ($productVariationInfo as $pvkey => $variation) {
		
							$variationSku = $variation['sku'];
		
							//设置利润在15%的价格
							//取出子sku价格
							$variationPriceInfo = AliexpressProductStatistic::model()->getProductPriceBySkuAndAccoutId($variationSku,$accountID,$commissionRate);
							if(!isset($variationPriceInfo[$accountID]['salePrice']) || $variationPriceInfo[$accountID]['salePrice'] < 0 ){
								throw new Exception('sku为：'.$sku.'的产品价格没有找到');
							}
		
							$variationPrice = round(floatval($variationPriceInfo[$accountID]['salePrice']), 2);
							if($variationPrice <= 0){
								throw new Exception('子sku为：'.$variationSku.'的产品价格小于等于0');
							}

							$variationPriceArr[$variationSku] = $variationPrice;
		
							//添加刊登多属性表
							$insertVariationData = "('".$newAddId."','".$variationSku."','".$variationPrice."')";
							$addVariationResult = $aliexpressProductAddVariation->insertBySql($insertVariationFields, $insertVariationData);
							if(!$addVariationResult){
								throw new Exception('添加子sku失败');
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
										throw new Exception('添加子sku属性失败！');
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
								$aliProductImageAddModel->deleteAliexpressSkuImages($variationSku, $accountID, Platform::CODE_ALIEXPRESS);
								$variationSkuImgName = basename($skuAttributeImages[$variationSku]);
								$imageAddData = array(
										'image_name'    => $variationSkuImgName,
										'sku'           => $variationSku,
										'type'          => ProductImageAdd::IMAGE_ZT,
										'local_path'    => $skuAttributeImages[$variationSku],
										'platform_code' => Platform::CODE_ALIEXPRESS,
										'account_id'    => $accountID,
										'upload_status' => ProductImageAdd::UPLOAD_STATUS_DEFAULT,
										'create_user_id'=> Yii::app()->user->id,
										'create_time'   => date('Y-m-d H:i:s'),
								);
								$imageModel = new AliexpressProductImageAdd();
								$imageModel->setAttributes($imageAddData,false);
								$imageModel->setIsNewRecord(true);
								$imageModel->save();

								//推送图片
								$productImageAdd->addSkuImageUpload($accountID,$variationSku,0,$platformCode);
							}
		
						}

						//更新运费模板ID
						if($variationPriceArr){
							$skuKey = array_search(max($variationPriceArr), $variationPriceArr);
							$freightTemplateId = AliexpressFreightTemplate::model()->getAppointTemplateId($skuKey,$accountID,$variationPriceArr[$skuKey]);
							$isResult = $this->dbConnection->createCommand()->update(self::tableName(), array(
										'freight_template_id' => $freightTemplateId), 'id = '.$newAddId);
							if(!$isResult){
								throw new Exception('子sku更新模板失败');
							}
					        
						}
					}
				}
				$dbtransaction->commit();
			}catch (Exception $e){
				$dbtransaction->rollback();
				return $this->ReturnMessage(false,$e->getMessage());
			}
			return $this->ReturnMessage(true,'刊登成功');
		}catch (Exception $e){
			$this->setErrorMsg($e->getMessage());
			return $this->ReturnMessage(false,$e->getMessage());
		}
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


	/**
	 * 返回运送方案
	 * @param floot   $salePrice  售价
	 * @param string  $sku        sku
	 * @return array
	 */
	public function returnShipCode($salePrice,$sku){
		$shipCode = null;
		//获取产品信息
		$skuInfo = Product::model()->getProductInfoBySku($sku);
        if(!$skuInfo || !$salePrice){
        	return $shipCode;
        }

		//取出产品属性
		$wheres = 'attribute_id = :attribute_id';
		$params = array(':attribute_id'=>3);
    	$attributeIdsInfo = ProductSelectAttribute::model()->getSelectedAttributeSKUListByProductId($skuInfo['id'],$wheres,$params);

    	/**
    	 * 通过产品价格判断shipCode(包裹)
    	 * 条件1：若sku售价小于5美元，sku不带任何特殊属性选择---速卖通东莞邮局小包
    	 * 条件2：若sku售价小于5美元，sku带特殊属性选择---------递四方新加坡小包
    	 * 条件3：若sku售价大于5美元，sku不带任何特殊属性选择---东莞邮局挂号
    	 * 条件4：若sku售价大于5美元，sku带特殊属性选择---------递四方新加坡挂号
    	 */
		if($salePrice <= 5 && !$attributeIdsInfo){
    		$shipCode = Logistics::CODE_CM_ALI_DGYZ;
    	}elseif ($salePrice <= 5 && $attributeIdsInfo) {
    		$shipCode = Logistics::CODE_CM_PLUS_SGXB;
    	}elseif ($salePrice > 5 && !$attributeIdsInfo) {
    		$shipCode = Logistics::CODE_GHXB_DGYZ;
    	}else{
    		$shipCode = Logistics::CODE_GHXB_SG;
    	}

    	return $shipCode;
	}

}