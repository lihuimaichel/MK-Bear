<?php
/**
 * @desc Amazon 刊登模型
 * @author Liz
 *
 */
class AmazonProductAdd extends AmazonModel {
	
	public $addID = null;
	public $status_desc = NULL;

	/** @var int 账号ID*/
	protected $_accountID = null;	

	/** @var string 账号名称 */
	protected $_merchantID = null;

	/** @var int 上传类型 */
	protected $_upload_type = null;

	/** @var string 接口类型 */
	protected $_feed_type = null;	

	/** @var int 日志ID */
	protected $_logID = null;		
	
	/** @var string 账号名称 **/
	public $account_name = null;
	
	/** @var string 分类名称 **/
	public $category_name = null;
	
	/** @var boolean 是否显示upload **/
	public $visiupload;

    /**@var 消息提示*/
    private $_errorMessage = null;	

    public $variation_id;
	public $product_price;
	public $standard_price;
	public $sale_price;
	public $product_id;
	public $asin;
	public $seller_sku;
	public $amazon_identifier_type;
	public $amazon_standard_product_id;
	public $product_quantity;
	public $inventory;

	public $upload_status_product;
	public $upload_status_price;
	public $upload_status_inventory;
	public $upload_status_image;
	public $upload_status_product_no;
	public $upload_status_price_no;
	public $upload_status_inventory_no;
	public $upload_status_image_no;
	public $detail;
	public $sub_status_desc;
	public $sub_upload_time;
	public $sub_upload_status;
	public $sub_seller_sku;
	public $sub_product_id;

	
	const PRODUCT_PUBLISH_TYPE_SINGLE    = 1;	//单品(单品刊登)
	const PRODUCT_PUBLISH_TYPE_VARIATION = 2;	//多属性(多属性刊登)
	const PRODUCT_PUBLISH_MODE_EASY      = 1;	//精简刊登模式
	const PRODUCT_PUBLISH_MODE_ALL       = 2;	//详细模式
	
	/**@var 上传类型*/
	const UPLOAD_TYPE_PRODUCT            = 1;	//基本产品
	const UPLOAD_TYPE_PRICE              = 2;	//价格
	const UPLOAD_TYPE_INVENTORY          = 3;	//库存
	const UPLOAD_TYPE_IMAGE              = 4;	//图片	
	const UPLOAD_TYPE_VARIATION          = 5;	//多属性关系
	const UPLOAD_TYPE_SHIPPING           = 6;	//运费	
	const UPLOAD_TYPE_FULFILLMENT        = 7;	//送货方式:	在库存上传接口处理
	
	/**@var 上传状态*/
	const UPLOAD_STATUS_DEFAULT          = 0;	//待上传
	const UPLOAD_STATUS_RUNNING          = 1;	//上传中
	const UPLOAD_STATUS_IMGFAIL          = 2;	//图片上传失败
	const UPLOAD_STATUS_IMGRUNNING       = 3;	//等待上传图片
	const UPLOAD_STATUS_SUCCESS          = 4;	//上传成功
	const UPLOAD_STATUS_FAILURE          = 5;	//上传失败
	
	const PRODUCT_MAIN_IMAGE_MAX_NUMBER  = 1;			//刊登主图最多多少张	
	const PRODUCT_PUBLISH_CURRENCY       = 'USD';		//刊登货币
	const MAX_NUM_PER_TASK               = 20;			//每次上传产品个数
	const MAX_UPLOAD_TIMES               = 5;		    //最大上传次数
	const PRODUCT_INVENTORY_DEFAULT      = 200;		//库存默认数值，测试用1，正式用200个
	const PRODUCT_PUBLISH_LIMIT			 = 100;	//每次批量刊登最大限制在100条

	public function tableName() {
		return 'ueb_amazon_product_add';
	}
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function rules() {
		return array(
			array('account_id, category_id, publish_type, publish_mode, title, upload_user_id, 
					service_template_id, freight_template_id, gross_weight, package_length, package_width,
					package_height', 'required'),
		);
	}

	/**
	 * @desc 获取上传类型列表
	 * @param string $status
	 */
	public static function getPublishList(){
		return array(
					self::UPLOAD_TYPE_PRODUCT,
					self::UPLOAD_TYPE_PRICE,
					self::UPLOAD_TYPE_INVENTORY,
					self::UPLOAD_TYPE_IMAGE
			);
	}	

	/**
	 * @desc 获取上传接口类型
	 * @param int $upload_type
	 */
	public static function getFeedTypeList($upload_type = 0){
		$feedTypeList = array(
				self::UPLOAD_TYPE_PRODUCT   => SubmitFeedRequest::FEEDTYPE_POST_PRODUCT_DATA,
				self::UPLOAD_TYPE_PRICE     => SubmitFeedRequest::FEEDTYPE_POST_PRODUCT_PRICING_DATA,
				self::UPLOAD_TYPE_INVENTORY => SubmitFeedRequest::FEEDTYPE_POST_INVENTORY_AVAILABILITY_DATA,
				self::UPLOAD_TYPE_IMAGE     => SubmitFeedRequest::FEEDTYPE_POST_PRODUCT_IMAGE_DATA,
				self::UPLOAD_TYPE_VARIATION => SubmitFeedRequest::FEEDTYPE_POST_PRODUCT_RELATIONSHIP_DATA,
				self::UPLOAD_TYPE_SHIPPING  => SubmitFeedRequest::FEEDTYPE_POST_PRODUCT_OVERRIDES_DATA
		);
		if($upload_type == 0){
			return $feedTypeList;
		}else{
			return $feedTypeList[$upload_type];
		}
	}	
	
	/**
	 * @desc 获取状态列表
	 * @param string $status
	 */
	public static function getStatusList($status = null){
		$statusArr = array(
				self::UPLOAD_STATUS_DEFAULT     => Yii::t('amazon_product', 'UPLOAD STATUS DEFAULT'),
				self::UPLOAD_STATUS_RUNNING     => Yii::t('amazon_product', 'UPLOAD STATUS RUNNING'),
				self::UPLOAD_STATUS_SUCCESS     => Yii::t('amazon_product', 'UPLOAD STATUS SUCCESS'),
				self::UPLOAD_STATUS_FAILURE     => Yii::t('amazon_product', 'UPLOAD STATUS FAILURE'),
		);
		if($status===null){
			return $statusArr;
		}else{
			return $statusArr[$status];
		}
	}

	/**
	 * @desc 获取上传类型描述
	 * @param int $uploadType 
	 */
	public static function getUploadTypeDesc($uploadType = 0){
		$uploadType = (int)$uploadType;
		$statusArr = array(
				self::UPLOAD_TYPE_PRODUCT     => Yii::t('amazon_product', 'UPLOAD Type Product'),     
				self::UPLOAD_TYPE_PRICE       => Yii::t('amazon_product', 'UPLOAD Type Price'),       
				self::UPLOAD_TYPE_INVENTORY   => Yii::t('amazon_product', 'UPLOAD Type Inventory'),   
				self::UPLOAD_TYPE_IMAGE       => Yii::t('amazon_product', 'UPLOAD Type Image'),       
				self::UPLOAD_TYPE_VARIATION   => Yii::t('amazon_product', 'UPLOAD Type Variation'),   
				self::UPLOAD_TYPE_SHIPPING    => Yii::t('amazon_product', 'UPLOAD Type Shipping'), 
				self::UPLOAD_TYPE_FULFILLMENT => Yii::t('amazon_product', 'UPLOAD Type Fulfillment')
		);
		if($uploadType == 0){
			return '未知';
		}else{
			return $statusArr[$uploadType];
		}
	}

	/**
	 * @desc 属性翻译
	 */
	public function attributeLabels() {
		return array(
    	       'id'                => Yii::t('system', 'No.'),
    	       'sku'               => Yii::t('aliexpress', 'Sku'),
    	       'asin'              => Yii::t('amazon_product', 'ASIN'),
    	       'seller_sku'        => Yii::t('amazon_product', 'Seller Sku'),
    	       'publish_type'      => Yii::t('aliexpress', 'Publish Type'),
			   'publish_mode'	   => Yii::t('aliexpress', 'Publish Mode'),
    	       'title'      	   => Yii::t('aliexpress', 'Subject'),
    	       'category_id'       => Yii::t('aliexpress', 'Category Id'),
    	       'status'	           => Yii::t('aliexpress', 'Status'),
    	       'product_price'	   => Yii::t('aliexpress', 'Product Price'),
    	       'product_quantity'  => Yii::t('amazon_product', 'Quantity'),
    	       'product_id'		   => Yii::t('amazon_product', 'Product ID Item'),
    	       'upload_message'	   => Yii::t('aliexpress', 'Upload Message'),	
			   'create_time'	   => Yii::t('system', 'Create Time'),
			   'create_user_id'	   => Yii::t('system', 'Create User'),
			   'upload_time'	   => Yii::t('amazon_product', 'Publish Time'),
			   'upload_user_id'	   => Yii::t('amazon_product', 'Upload User'),
			   'account_name'	   => Yii::t('aliexpress', 'Account Name'),
			   'category_name'	   => Yii::t('aliexpress', 'Category Name'),
			   'account_id'		   => Yii::t('aliexpress', 'Account Name'),
			   'upload_status'	   => Yii::t('amazon_product', 'UPLOAD STATUS'),
			   'upload_start_time' => Yii::t('amazon_product', 'upload_start_time'),
			   'sub_seller_sku'    => Yii::t('amazon_product', 'Sub Seller Sku'),
			   'sub_product_id'   => Yii::t('amazon_product', 'Product Id'),
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
    					'name'      => 'seller_sku',
    					'type'      => 'text',
    					'search'    => 'LIKE',
    					'alias'     => 'v',
    				),				
				array(
						'name'      => 'account_id',
						'type'      => 'dropDownList',
						'search'    => '=',
						'data' 		=> AmazonAccount::getIdNamePairs(),
						'alias'     => 't',
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
    					'name'          => 'upload_start_time',
    					'type'          => 'text',
    					'search'        => 'RANGE',
    					'htmlOptions'   => array(
    							'class'    => 'date',
    							'dateFmt'  => 'yyyy-MM-dd HH:mm:ss',
    					),
    					'alias'			=> 't',
    			),
    			array(
    					'name'		 => 'title',
    					'type'		 => 'text',
    					'search'	 => 'LIKE',
    					'alias'	     => 't',
    			),    	
    			array(
    					'name'		 => 'asin',
    					'type'		 => 'text',
    					'search'	 => 'LIKE',
    					'alias'	     => 'v',
    			),    					
    			array(
    					'name'       => 'create_user_id',
    					'type'	     => 'dropDownList',
    					'search'     => '=',
    					'data'		 => User::model()->getUserNameByDeptID(array(5, 24)),
    					'htmlOptions'=> array(),
    					'alias'	     => 't',
    			),
    			array(
    					'name'      => 'publish_type',
    					'type'      => 'dropDownList',
    					'search'    => '=',
    					'data' 		=> self::getProductPublishTypeList(),
    					'alias'     => 't',
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
		);
	
		return $result;
	
	}
	
	/**
	 * @return $array
	 */
	public function search(){
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'  => 'update_time',
		);
		$criteria = null;
		$criteria = $this->_setCDbCriteria();
		// MHelper::printvar($criteria);
		$dataProvider = parent::search(get_class($this), $sort,array(),$criteria);
		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}
	
	protected function _setCDbCriteria(){
		$criteria = new CDbCriteria;
		// $criteria->select = 't.*,v.id as variation_id,v.amazon_identifier_type,v.amazon_standard_product_id,v.sale_price,v.standard_price,v.inventory,v.asin,v.seller_sku';
		// $criteria->join = 'LEFT JOIN '.AmazonProductAddVariation::model()->tableName().' AS v ON v.add_id = t.id';
		$criteria->select = 't.*';

		if( (isset($_REQUEST['upload_time'][0]) && !empty($_REQUEST['upload_time'][0])) && isset($_REQUEST['upload_time'][1]) && !empty($_REQUEST['upload_time'][1]) ){
			$criteria->condition = "t.upload_start_time >= '" . addslashes($_REQUEST['upload_time'][0]) . "' AND t.upload_finish_time <= '" . addslashes($_REQUEST['upload_time'][1]) . "'";
		}
		if( (isset($_REQUEST['create_time'][0]) && !empty($_REQUEST['create_time'][0])) && isset($_REQUEST['create_time'][1]) && !empty($_REQUEST['create_time'][1]) ){
			$criteria->condition = "create_time >= '" . addslashes($_REQUEST['create_time'][0]) . "' AND t.create_time <= '" . addslashes($_REQUEST['create_time'][1]) . "'";
		}

		//如果查询刊登失败时，查询只要有子SKU有刊登失败的，即显示
		// if(isset($_REQUEST['status']) && $_REQUEST['status'] == self::UPLOAD_STATUS_FAILURE){
		// 	$criteria->condition = "t.status = 1";
		// }

		// //用于更新刊登产品后跳转到待刊登列表
		// if(isset($_REQUEST['addid']) && (int)$_REQUEST['addid'] > 0){
		// 	$criteria->condition = "t.id = " .(int)$_REQUEST['addid'];
		// }
		return $criteria;
	}
	
	/**
     * @desc 附加查询条件
     * @param unknown $data
     */
	public function addition($data){
		$accountList = AmazonAccount::model()->queryPairs(array('id', 'account_name'));
		foreach ($data as $key => $val){
			$sku = $val->sku.'<br />'.$val->seller_sku.'<br />'.$val['id'].'<br />'.$val['product_type_text'].'<br />'.$val['asin'];
			$data[$key]->sku = $sku;
			$data[$key]->publish_type = $this->getProductPublishTypeList($val['publish_type']);
			$data[$key]->publish_mode = $this->getProductPublishModelList($val['publish_mode']);

			$data[$key]->account_name = array_key_exists($val['account_id'], $accountList) ? $accountList[$val['account_id']] : '';
			$data[$key]->category_name = AmazonCategory::model()->getBreadcrumbCategory($val->category_path);
			$data[$key]->visiupload = true;

			//子SKU信息（单品、多属性）
			$variants = AmazonProductAddVariation::model()->getVariationProductAdd($val['id']);
			$productAddStatusModel = new AmazonProductAddStatus();
			$data[$key]->detail = array();
    		foreach ($variants as $variant){
				//获取产品平台编码
				if ($variant['amazon_standard_product_id']){
					$amazonSiteModel = new AmazonSite();
					$amazonProductType = $amazonSiteModel->getSiteProductTypeByID($variant['amazon_identifier_type']);					
					$variant['product_id'] = $amazonProductType .':<br />' .$variant['amazon_standard_product_id'];
				}
				if ($variant['asin']){
					$data[$key]->title = CHtml::link($val['title'], 'https://www.amazon.com/dp/' .$variant['asin'], array('title' => '点击访问亚马逊商城刊登的listing','target' => '_blank'));
				}    		
				
				$variant['sub_seller_sku']   = $variant['seller_sku'];
				$variant['sub_product_id']   = $variant['amazon_standard_product_id'];
				$variant['product_price']    = ($variant['sale_price'] == 0) ? $variant['standard_price'] : $variant['sale_price'];
				$variant['product_quantity'] = $variant['inventory'];

    			$status_desc = '';
    			$uploadTypeList = $this->getPublishList();				
    			$statusReadonlyList = $productAddStatusModel->getAllUploadIsReadonlyListByVariationID($variant['id']);	//获取子SKU四个接口上传状态只读标识（上传中和上传成功状态）
				$statusSeccessList = $productAddStatusModel->getAllUploadIsFinishListByVariationID($variant['id']);	//获取子SKU四个接口上传状态已成功标识
				foreach($uploadTypeList as $uploadType){
					$showDesc = '';
					$showImage = '';
					$typeName = $this->getUploadTypeDesc($uploadType);
					if ($typeName) $typeName = mb_substr($typeName,2,4,'utf-8');
					// $status_desc .= "<button style='margin:2px 5px;' type='button'><a title='" .$typeName. "' href='#'>" .$typeName. "刊登结果</a></button>";
					if ($statusReadonlyList){
						if ($statusSeccessList && in_array($uploadType,$statusSeccessList)){
							$subHavePublishFlag = 1;
							$havePublishFlag = 1;
							$showDesc = '已刊登成功';
							$showImage = 'btnHook';
						}else{
							if ($statusReadonlyList && in_array($uploadType,$statusReadonlyList)){
								$showDesc = '刊登中';
								$showImage = 'btnInfo';
							}else{						
								$showDesc = '未刊登成功';
								$showImage = 'btnFork';
							}
						}
					}else{
						$showDesc = '未刊登成功';
						$showImage = 'btnFork';
					}
					$status_desc .= "<a title='".$typeName.$showDesc."' class='".$showImage."' rel='amazonproductadd-grid' href='javascript:void(0)'></a>";
				}

				//刷新失败重新上传
				$status_desc .= "<a title='刷新失败接口，重新上传' variation_id = '" .$variant['id']. "' class='btnRefresh reuploadstatus' style='margin-top:5px;' href='javascript:void(0)' ></a>";

				$variant['sub_upload_status'] = $status_desc;

				//显示刊登错误信息
				$errMessage = $this->getUploadErrMessage($variant['id'],$variant['status']);
				// MHelper::printvar($errMessage);
				$msg = '';
				$temp = array();
				if ($errMessage){
					// $errMessage = json_decode($errMessage,true);
					if (is_array($errMessage)){					
						foreach($errMessage as $i => $temp){	
							if (!empty($temp)){
								$item = json_decode($temp,true);
								if (is_array($item)){
									foreach($item as $j => $info){
										//xml语法错误
										if(is_array($info)){
											$info = (string)$info[0]['message'];
											$info = strip_tags($info);
										}
										if($j > 0) $msg .= '<br />';
										$msg .= "<span style='color:#666'>【" .$this->getUploadTypeDesc($i). "】错误 " .($j + 1). ":</span>";																
										$msg .= "<span style='color:gray;font-weight:normal;' title='".strip_tags($info)."'>" .strip_tags($info). "</span>";	//htmlspecialchars
									}
								}else{
									$msg .= "<span style='color:#666'>【" .$this->getUploadTypeDesc($i). "】错误 1:</span>";																
									$msg .= "<span style='color:gray;font-weight:normal;' title='".strip_tags($item)."'>" .strip_tags($item). "</span>";
								}
								$msg .= '<br />';
							}						
						}
					}else{
						$msg = "Error: <span style='color:gray;font-weight:normal;'>" .strip_tags($errMessage). "</span>";
					}
				}			
				$variant['status_desc'] = self::getStatusList($variant['status']).'<br />'.$msg;
				$variant['sub_status_desc'] = $variant['status_desc'];	

				$upload_time = '';
				$uploadStartTime = ($variant['upload_start_time'] != '0000-00-00 00:00:00') ? $variant['upload_start_time'] : '';
				$uploadFinishTime = ($variant['upload_finish_time'] != '0000-00-00 00:00:00') ? $variant['upload_finish_time'] : '';
				if(!empty($uploadStartTime)) $upload_time = $uploadStartTime.'<br />'.$uploadFinishTime;			
				$variant['sub_upload_time'] = $upload_time;	

    			$data[$key]->detail[] = $variant;
    		}			

		}
		// MHelper::printvar($data);
		return $data;
	}

	/**
	 * @desc 更新亚马逊刊登的产品数据
	 * @param unknown $condition
	 * @param unknown $updata
	 * @return boolean|Ambigous <number, boolean>
	 */
	public function updateProductAdd($conditions, $updata){
		if(empty($conditions) || empty($updata)) return false;
		return $this->getDbConnection()->createCommand()
						->update(self::tableName(), $updata, $conditions);
	}

	/**
	 * @desc 根据ID获取亚马逊刊登数据
	 * @param $AddID 自增ID
	 * @return array
	 */
	public function getProductAddInfoByID($AddID = 0){
		if((int)$AddID == 0) return false;
		return $this->getDbConnection()->createCommand()
						->select('*')
						->from(self::tableName())
						->where('id = '.$AddID)
						->queryRow();
	}				

	/**
	 * @desc 获取sku历史分类数据
	 * @param string $sku
	 * @return array()
	 */
	public function getSkuHistoryCategory($sku = '') {
		$tempCategory = array();
		$historyCategory = array();
		if (empty($sku)) return false;
		//查找在线listing的当前分类，OMS数据当前分类为空
		//查找待刊登列表里面SKU的分类
		$publishList = $this->getDbConnection()->createCommand()
			->select("*")
			->from(self::tableName())
			->where("sku = :sku", array(':sku' => $sku))
			->order('id DESC')
			->queryAll();

		//去除相同的分类（category_id和category_path两数据都相同的分类）
		if ($publishList) {
			foreach ($publishList as $list) {
				$addCategory = 0;
				if ($tempCategory){
					//如果分类ID不包含在临时分类数组中
					if (!in_array($list['category_id'], array_keys($tempCategory))){
						$addCategory = 1;
					}else{
						//如果category_id相同，但路径category_path不同，也属于不同分类
						if (!in_array($list['category_path'], $tempCategory)){
							$addCategory = 1;
						}
					}
				}else{
					$addCategory = 1;
				}
				if($addCategory == 1 && !empty($list['category_id']) && !empty($list['category_path'])) $tempCategory[$list['category_id']] = $list['category_path'];
			}
		}

		//格式化面包导航分类，category_id换成分类自增ID
		if ($tempCategory) {
			foreach ($tempCategory as $key => $val) {
				$ret = AmazonCategory::model()->getCategoryInfoByCidPath($key,$val);
				if($ret) $ID = $ret['id'];	//自增ID
				if(!empty($ID)) $historyCategory[$ID] = AmazonCategory::model()->getBreadcrumbCategory($val);		
			}
		}
		return $historyCategory;
	}	
	

	/**
	 * @DESC 上传图片到远程服务器
	 * @param unknown $imageUrl
	 * @return string|boolean
	 */
	public function uploadImageToServer($imageUrl, $accountID){
		$configs = ConfigFactory::getConfig('serverKeys');
		$config = $configs['image'];
		$domain = $config['domain'];
		$localpath = parse_url($imageUrl, PHP_URL_PATH);

		//判断OMS本地文件是否存在
		$param = array('path'=>$localpath);
		$api = Yii::app()->erpApi;
		$result = $api->setServer('oms')->setFunction('Products:Productimage:checkImageExist')->setRequest($param)->sendRequest()->getResponse();
		if( $api->getIfSuccess() ){
			if( !$result ){
				$this->setErrorMsg($localpath.' Not Exists.');
				return false;
			}
		}else{
			$this->setErrorMsg($api->getErrorMsg());
			return false;
		}
	
		$productImageAddModel = new AmazonProductImageAdd();
		//上传图片到指定文件夹,返回路径
		$absolutePath = $productImageAddModel->saveTempImage($localpath);
		list($remoteName, $remotePath) = $productImageAddModel->getImageRemoteInfo($localpath, $accountID, Platform::CODE_AMAZON);
		$uploadResult = $productImageAddModel->uploadImageServer($absolutePath, $remoteName, $remotePath);
		unlink($absolutePath);
		if( $uploadResult != 1 ){
			$this->setErrorMsg(Yii::t('common', 'Upload Connect Error'));
		}else{
			return $remote_path = $domain.$remotePath.$remoteName;
		}
		return false;
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
	 * @desc 获取产品刊登模式列表
	 * @param string $key
	 * @return Ambigous <string, Ambigous <string, string, unknown>>|multitype:string Ambigous <string, string, unknown>
	 */
	public static function getProductPublishModelList($key = null) {
		$list = array(
			self::PRODUCT_PUBLISH_MODE_EASY => Yii::t('amazon_product', 'Publish Mode Easy'),
			//self::PRODUCT_PUBLISH_MODE_ALL => Yii::t('aliexpress_product', 'Publish Mode All'),
		);
		if (!is_null($key) && array_key_exists($key, $list)) {
			return $list[$key];
		}
		return $list;
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
	
	/**
	 * @desc 通过sku判断是否可刊登（只要不是基本产品刊登失败，其它情况都不能再刊登）
	 * @param string $sku
	 */	
	public function getListingPrepareUploadBySku($sku){
		return $this->dbConnection->createCommand()
		->select('t.account_id')
		->from(self::tableName() . " as t")
		->leftjoin(AmazonProductAddStatus::model()->tableName() ." as s", "t.id = s.add_id")
		->where('t.sku = "'.$sku.'"')
		->andWhere('s.upload_type = '.self::UPLOAD_TYPE_PRODUCT)
		->andWhere('s.upload_status != '.self::UPLOAD_STATUS_FAILURE)
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
	 * @desc 获取可用账号列表，根据sku,站点
	 * @param unknown $sku
	 * @param string $site
	 * @return multitype:unknown
	 */
	public function getAbleAccountListBySku($sku,$site = null){
		$excludeAccounts = array();
		//获取sku在线listing
		$listOnline = AmazonList::model()->getListingBySku($sku);
		if ($listOnline){
			foreach($listOnline as $item){
				$excludeAccounts[$item['account_id']] = $item['account_id'];
			}
		}
		//获取准备刊登(在刊登列表里)的记录
		$listTask = $this->getListingPrepareUploadBySku($sku);
		if ($listTask){
			foreach($listTask as $item){
				$excludeAccounts[$item['account_id']] = $item['account_id'];
			}		
		}	
		$accountAll  = AmazonAccount::getAbleAccountList($site);
		$accounts    = array();
		$accountInfo = array();
		foreach($accountAll as $account){
			//TODO 排除锁定状态设定为无法刊登的账号
			$accounts[$account['id']] = $account['id'];
		}	
		$ableAccounts = array_diff($accounts,$excludeAccounts);		
		if ($ableAccounts){
			foreach($accountAll as $account){
				$account['is_upload'] = true;
				if( in_array($account['id'], $ableAccounts) ){
					$account['is_upload'] = false;
				}
				$accountInfo[$account['id']] = $account;
			}
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
			self::PRODUCT_PUBLISH_TYPE_SINGLE => Yii::t('amazon_product', 'Publish Type Single'),
			self::PRODUCT_PUBLISH_TYPE_VARIATION => Yii::t('amazon_product', 'Publish Type Variation'),
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
		// if (!is_null($status))
		// 	$command->andWhere("status = :status", array(':status' => $status));
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
				$this->uploadProductByAccount($ids, $accountID);
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
		$this->dbConnection->createCommand()->update(self::tableName(), array(
				'status'        => self::UPLOAD_STATUS_FAILURE,
				'upload_user_id'=> Yii::app()->user->id,
				'upload_time'   => date('Y-m-d H:i:s'),
				'upload_message'=> $message,
		), 'id = '.$this->addID);
	}
	
	/**
	 * @desc 获取菜单对应ID
	 * @return integer
	 */
	public static function getIndexNavTabId() {
		return UebModel::model('Menu')->getIdByUrl('/amazon/amazonproductadd/list');
	}
	
	/**
	 * @desc 根据ID查询刊登主表记录
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


	/**
	 * @desc Amazon刊登
	 * @param int $accountID
	 * @param string $addIDs 多属性表自增IDS
	 * @param int $uploadType 上传类型
	 * @return string
	 */
	public function amazonProductPublish($accountID = 0, $addIDs = '', $uploadType = 0){
		if ($accountID == 0 || empty($addIDs)){
			echo Yii::t('amazon_product', 'Param Error', array('param' => '$accountID/$addIDs'));
			return false;
		}		
		$uploadTypeList = array();
		$topErrFlag     = 0;	//失败标识
		$errMessageList = '';	//累加异常信息
		$submitFeedId = '';
		$uploadType     = (!empty($uploadType)) ? (int)$uploadType : 0;

		$this->setAccountID($accountID);

		//写入日志表		
		$amazonLogModel = new AmazonLog();		
		$logID = $amazonLogModel->prepareLog($accountID, AmazonFeedReport::EVENT_NAME);
		$this->setLogID($logID);

		//检查此账号事件是否可以提交请求
		$checkRunning = $amazonLogModel->checkRunning($accountID, AmazonFeedReport::EVENT_NAME, 1);	//暂时设定最大时间为1秒
		if(!$checkRunning){
			$amazonLogModel->setFailure($logID, Yii::t('systems', 'There Exists An Active Event'));
			echo Yii::t('systems', 'There Exists An Active Event');
			return false;
		}else{
			//写入日志事件表
			$eventLog = $amazonLogModel->saveEventLog(AmazonFeedReport::EVENT_NAME, array(
					'log_id'        => $logID,
					'account_id'    => $accountID,
					'start_time'    => date('Y-m-d H:i:s'),
					'end_time'      => date('Y-m-d H:i:s')
			));	
		}
		
		//列出刊登类型
		if ($uploadType > 0){
			$uploadTypeList = array($uploadType);
		}else{
			$uploadTypeList = $this->getPublishList();

			//正式批量测试时，不刊登库存，这样保证产品不会公开销售
			// array_splice($uploadTypeList, 2,1);				
		}

		//按上传类型分组循环执行
		foreach($uploadTypeList as $uploadType){
			$this->setUploadType($uploadType);
			$scheduled = AmazonFeedReport::SCHEDULED_SUBMIT;

			//先写入刊登报告表，为保存过程中出现的异常信息
			$FeedReport = new AmazonFeedReport();
			$params = array(
					'log_id'               => $logID,
					'feed_id'              => '',
					'account_id'           => $accountID,
					'upload_type'          => $uploadType,
					'scheduled'            => $scheduled,
					'submit_feed_date'     => date('Y-m-d H:i:s'),
			);
			$feedReportID = $FeedReport->addFeedReport($params);	

			try{				
				$FeedType = $this->_feed_type;	//接口类型
				if (empty($FeedType)){
					throw new Exception(Yii::t('amazon_product', 'Upload Type can\'t Empty'));	
				}

				// $submitFeedRequest = new SubmitFeedRequest();
				$submitFeedRequest = new CommonSubmitFeedRequest();
				$submitFeedRequest->setFeedType($FeedType)->setAccount($accountID);
				$merchantID = $submitFeedRequest->getMerchantID();
				if(!empty($merchantID)) $this->setMerchantID($merchantID);

				//基本产品过滤在listing已存在的记录（同账号同主SKU）
				//过滤不进入刊登列表的记录，已成功的排除，先决条件不满足的排除，限制最大刊登数（除基本产品其它刊登必须在基本产品刊登成功后才能刊登）
				$filterAddIDs = $this->setFilterProductAddIDs($addIDs,$uploadType);	//子SKU列表(variationIds)
				if (empty($filterAddIDs)){
					//更新刊登报告表
					$updata = array(
							'status'  => 2,	//失败
							'message' => Yii::t('amazon_product','Not Can Publish Product Records'),	//没有刊登记录
					);
					$FeedReport->updateFeedReportByID($feedReportID, $updata);					
					continue;
				}

				//组装XML数据
				switch ($uploadType){
					case self::UPLOAD_TYPE_PRODUCT:
						$submitFeedRequest->setFeedContent($this->getXmlDataPublish($filterAddIDs));
						break;
					case self::UPLOAD_TYPE_PRICE:
						$submitFeedRequest->setFeedContent($this->getXmlDataPrice($filterAddIDs));
						break;
					case self::UPLOAD_TYPE_INVENTORY:
						$submitFeedRequest->setFeedContent($this->getXmlDataInventory($filterAddIDs));
						break;		
					case self::UPLOAD_TYPE_IMAGE:
						$submitFeedRequest->setFeedContent($this->getXmlDataImage($filterAddIDs));
						break;											
				}

				//开始接口上传
				$submitFeedId = $submitFeedRequest->setRequest()->sendRequest()->getResponse();
				$feedProcessingStatus = $submitFeedRequest->getFeedProcessingStatus();
				if ($submitFeedRequest->getIfSuccess() && $feedProcessingStatus && $feedProcessingStatus != SubmitFeedRequest::FEED_STATUS_CANCELLED){			
					//此次刊登亚马逊平台已成功处理，但未取上传结果详情
					if($feedProcessingStatus == SubmitFeedRequest::FEED_STATUS_DONE) $scheduled = AmazonFeedReport::SCHEDULED_DONE;	
					$total = (!empty($filterAddIDs)) ? count(explode(',',$filterAddIDs)) : 0;

					//转为多属性IDs列表
					// $variationIDs = AmazonProductAddVariation::model()->getVariationIDsByAddIds($filterAddIDs);

					//更新刊登报告表
					$updata = array(
							'feed_id'              => $submitFeedId,
							'scheduled'            => $scheduled,
							'submit_feed_date'     => date('Y-m-d H:i:s'),
							'upload_variation_ids' => $filterAddIDs, //保存多属性IDs
							'feed_total'           => $total,
							'status'               => 1,	//成功
					);
					$FeedReport->updateFeedReportByID($feedReportID, $updata);	

					//更新子SKU上传状态
					$feedAddIDsArr = explode(',',$filterAddIDs);
					foreach ($feedAddIDsArr as $variationAddID){
						$errMessage         = array();
						$errFlag            = 0;
						$uploadStatus       = 0;
						$upload_type_status = 0;
						$addInfo            = array();
						
						//获取一子SKU和主SKU信息
						$addInfo = $this->getMainAndVariationInfoByVariationId($variationAddID);
						if (!$addInfo){
							$errFlag = 1;
							$errMessage[] = Yii::t('amazon_product', 'Record Not Exists');
						}else{
							$mainSkuID     = $addInfo['id'];							//主表自增ID
							$variationInfo = $addInfo['variation'];						//子SKU信息
							$uploadStatus  = $addInfo['variation']['status'];			//子SKU上传状态
							$publish_type  = (int)$addInfo['publish_type'];				//刊登类型（1-单品刊登； 2-多属性刊登）				
						}
						
						if (!$variationInfo){
							$errFlag = 1;
							$errMessage[] = Yii::t('amazon_product', 'Publish Sub Info not Exists');				
						}

						if($errFlag == 1) continue;	//不抛出异常，目前没有保存以上异常信息

						//处理上传状态表
						$productAddStatusModel = new AmazonProductAddStatus();
						$condition = "add_id='{$mainSkuID}' and variation_id='{$variationAddID}' and upload_type='{$uploadType}'";
						$uploadStatusInfo = $productAddStatusModel->getUploadStatusByCondition($condition);
						//只更新，无新增
						if ($uploadStatusInfo){
							$statusData = array(
									'feed_id'     => $submitFeedId,
									'upload_nums' => $uploadStatusInfo['upload_nums'] + 1,	//确认刊登成功了，才累加1次刊登
							);
							//只在待上传状态才改为上传中，排除在前面拼装XML时已失败的产品
							if($uploadStatusInfo['upload_status'] == self::UPLOAD_STATUS_DEFAULT) $statusData['upload_status'] = self::UPLOAD_STATUS_RUNNING;
							$productAddStatusModel->updateUploadStatusByID($uploadStatusInfo['id'], $statusData);
						}

						//更新子SKU状态为上传中
						if ($uploadStatus != self::UPLOAD_STATUS_SUCCESS && $uploadStatus != self::UPLOAD_STATUS_FAILURE && $uploadStatus != self::UPLOAD_STATUS_RUNNING){
							//首次进行刊登，记录刊登开始时间
							$updatProductAddVarationData = array(
								'status' => self::UPLOAD_STATUS_RUNNING,
								'upload_start_time' => date('Y-m-d H:i:s')
							);									
							AmazonProductAddVariation::model()->updateProductAddVarationByID($variationAddID, $updatProductAddVarationData);
						}

						//刊登主表更新为上传中
						$updatProductAddData = array(
							'status' => self::UPLOAD_STATUS_RUNNING,
						);						
						$this->updateProductAddByID($mainSkuID, $updatProductAddData);
					}
					// return $submitFeedId;
				}else{
					if ($submitFeedRequest->getErrorMsg()){
						throw new Exception($submitFeedRequest->getErrorMsg());
					}
					if ($feedProcessingStatus == SubmitFeedRequest::FEED_STATUS_CANCELLED){
						throw new Exception(Yii::t('amazon_product','Amazon API Processing Result: _CANCELLED_'));
					}										
				}
			} catch (Exception $e) {
				//把错误写入刊登报告表
				$updata = array(
						'status'  => 2,	//失败
						'message' => $e->getMessage()
				);
				$FeedReport->updateFeedReportByID($feedReportID, $updata);	

				$topErrFlag = 1;
				$errMessageList .= 'FeedType: '.$FeedType."<br />";
				$errMessageList .= $e->getMessage();
				$errMessageList .= '====================================='."<br /><br />";				

				//强行输出错误信息
				// echo "错误2："."\n".$e->getMessage();
				// exit;
			}
		}

		//如果是单个产品刊登
		// if (count($addIDs) == 1){
		// 	return $submitFeedId;
		// }

		//如果存在失败
		if($topErrFlag > 0){
			$amazonLogModel->setFailure($logID, $errMessageList);
			$amazonLogModel->saveEventStatus(AmazonFeedReport::EVENT_NAME, $eventLog, AmazonLog::STATUS_FAILURE);
			$msg = "上传失败!!!:"."\n".$errMessageList;	
		}else{
			$amazonLogModel->setSuccess($logID);
			$amazonLogModel->saveEventStatus(AmazonFeedReport::EVENT_NAME, $eventLog, AmazonLog::STATUS_SUCCESS);
			$msg = "上传成功";
		}
		return $msg;
	}

	
	/**
	 * @desc 刊登的xml代码：基本产品
	 */
	public function getXmlDataPublish($addIDs){
		$feedMain      = '';
		$allErrMessage = array();
		$merchantID    = $this->_merchantID;
		$accountID     = $this->_accountID;
		$logID         = $this->_logID;
		$uploadType    = $this->_upload_type;
		$FeedType      = $this->_feed_type;
		$path          = 'amazon/amazonbatchpublish/'.date("Ymd").'/'.$accountID.'/'.$FeedType;
		if(empty($merchantID) || empty($addIDs)) throw new Exception(Yii::t('amazon_product', 'Param Error', array('param' => '$merchantID/$varaitionAddIDs')));
		$productAddStatusModel = new AmazonProductAddStatus();

		//XML组装的头部和尾部
		$feedHeader = '<?xml version="1.0" encoding="UTF-8"?>
					<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">
					    <Header>
					        <DocumentVersion>1.01</DocumentVersion>
					        <MerchantIdentifier>' .$merchantID. '</MerchantIdentifier>
					    </Header>
						<MessageType>Product</MessageType>
						<PurgeAndReplace>false</PurgeAndReplace>';							
		$feedFoot = '</AmazonEnvelope>';		
		
		$addIDsArr = explode(',',$addIDs);	// $addIDs = array("5","1");
		foreach($addIDsArr as $key => $addID){
			$errFlag                 = 0;
			$errMessage              = array();	
			$variationInfo           = array();
			$publish_type            = 0;		
			$uploadStatusID          = 0;	
			$feedItem                = '';	//XML单个上传内容
			$xmlErrorArr             = '';	//XML错误		
			$sellerSKU               = '';	//在线SKU
			$accountName             = '';	//账号名称
			$itemType                = '';	//分类识别码
			$bulletPointXML          = '';	//产品简述列表
			$productDataXML          = '';	//产品分类类型
			$searchTermsXML          = '';	//搜索关键字		
			$amazonProductType       = '';	//产品识别类型,UPC等
			$amazonStandardProductID = '';	//产品识别码	

			// $addInfo = $this->getMainAndVariationInfoById($addID);	//获取主表和多属性表
			$addInfo = $this->getMainAndVariationInfoByVariationId($addID);	//通过子SKU自增ID获取主SKU和一子SKU信息
			if (!$addInfo){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Record Not Exists');
			}else{
				$mainSkuID       = $addInfo['id'];										//主SKU自增ID
				$variationInfo   = $addInfo['variation'];								//子SKU信息
				$publish_type    = (int)$addInfo['publish_type'];						//刊登类型（1-单品刊登； 2-多属性刊登）
				$title           = htmlentities((string)$addInfo['title']);				//标题（必填项）
				$description     = (string)$addInfo['description'];						//描述（必填项）
				$bulletPointAll  = (string)$addInfo['bullet_point'];					//简述列表（必填项）
				$searchTermsAll  = (string)$addInfo['search_terms'];					//搜索关键字
				$categoryID      = (string)$addInfo['category_id'];						//数值超长，不能转为int，否则会溢出
				$categoryPath    = (string)$addInfo['category_path'];
				$productTypeText = htmlentities((string)$addInfo['product_type_text']);	//上传分类类型（用.分隔父子分类结构）
				$brand           = htmlentities((string)$addInfo['brand']);				//品牌（必填项）
				$manufacturer    = htmlentities((string)$addInfo['manufacturer']);		//制造商（必填项）
				$part_number     = htmlentities((string)$addInfo['part_number']);		//制造商编码								
			}
			
			//子SKU信息
			if ($variationInfo){
				$sellerSKU               = $variationInfo['seller_sku'];
				$amazon_identifier_type  = $variationInfo['amazon_identifier_type'];
				$amazonStandardProductID = $variationInfo['amazon_standard_product_id'];
			}else{	
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Publish Sub Info not Exists');				
			}	

			if (empty($sellerSKU)){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Not found Product Seller SKU');
			}							

			//描述字符最大不能超过2000字符
			if (!empty($description)){
				// MHelper::printvar('<![PCDATA[' .$description. ']]>'); //chrome打印显示会有异常，只是浏览器转换了，实际上传数据没有影响
				if(strlen($description) > 2000) $description = mb_strcut($description,0,2000,'utf-8');
			}else{
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Product Description Not Empty');
			}

			//简述列表
			if ($bulletPointAll){
				$bulletArr = array();
				$bulletArr = explode('@@@@@',$bulletPointAll);
				if ($bulletArr){
					foreach($bulletArr as $val){
						if (!empty($val)){
							$bulletPointXML .='<BulletPoint><![CDATA[' .$val. ']]></BulletPoint>';
						}
					}
				}
			}
			if (empty($bulletPointXML)){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Product BulletPoint Not Empty');
			}

			//搜索关键字		
			if ($searchTermsAll){
				$searchTermsArr = array();
				$searchTermsArr = explode('@@@@@',$searchTermsAll);
				if ($searchTermsArr){
					foreach($searchTermsArr as $v){
						if (!empty($v)){
							$searchTermsXML .='<SearchTerms><![CDATA[' .$v. ']]></SearchTerms>';
						}
					}
				}
			}				

			//商品分类
			$itemType = AmazonCategory::model()->getCategoryItemType($categoryID,$categoryPath);
			if (empty($itemType)){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Product Item Type Not Empty');				
			}

			//产品标识码类型,产品标识码
			if ($amazon_identifier_type){
				$amazonSiteModel = new AmazonSite();
				$amazonProductType = $amazonSiteModel->getSiteProductTypeByID($amazon_identifier_type);	
			}
			if (empty($amazonProductType)){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Product Identifier Type Not Empty');				
			}
			if (empty($amazonStandardProductID)){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Product Standard Product ID Not Empty');				
			}									

			//账号名称
			$accountInfo = AmazonAccount::model()->getAccountInfoById($accountID);
			if ($accountInfo){
				$accountName = $accountInfo['account_name'];
			}

			//品牌、制造商、制造商编码为必填项，设置默认值填充
			if(empty($brand)) $brand = $accountName;
			if(empty($manufacturer)) $manufacturer = $accountName;
			if(empty($part_number)) $part_number = $sellerSKU;			

			//产品分类结构 ProductData
			$topTypeText         = '';	//顶级分类
			$nextTypeText        = '';  //二级分类
			$productData         = array();
			$poduct_arr          = array();
			$tmp                 = array();
			$tmpProductType      = array();
			$specialTopTypeArr   = array('ClothingAccessories','Tools','Toys','ToysBaby','Beauty','Health');	//特殊顶级分类，特殊处理：'ClothingAccessories','Tools'
			$specialAllTypeArr   = array('Jewelry.Watch');	//特殊全分类（顶级和二级分类）
			$specialValueTypeArr = array('Sports','SportsMemorabilia','Miscellaneous','GiftCard','Shoes');

			if ($productTypeText){	
				$poduct_arr = explode('.',$productTypeText);
				if ($poduct_arr){
					$topTypeText = $poduct_arr[0];
					if(isset($poduct_arr[1])) $nextTypeText = $poduct_arr[1];

					//如果包含于特殊的全分类或是顶级分类，特殊单独处理
					if (in_array($productTypeText,$specialAllTypeArr) || in_array($topTypeText,$specialTopTypeArr)){
						$productDataXML = $this->specialProductTypeXML($productTypeText);
					}else{
						if($nextTypeText){
							//参考XSD文件，为非链接产品分类，填充值拼接XML
							if (in_array($topTypeText,$specialValueTypeArr)){
								$tmp = $nextTypeText;			//用值填充:<ProductType>SportingGoods</ProductType>
							}else{
								$tmp[$nextTypeText] = array();	//数组填充：<ProductType><MusicPopular></MusicPopular></ProductType>
							}
							$tmpProductType['ProductType'] = $tmp;
						}
						if($tmpProductType) $productData[$topTypeText] = $tmpProductType;	
						if ($productData){
							$xmlgenerator = new XmlGenerator;
							$productDataXML = $xmlgenerator->buildXMLFilterMulti($productData, '', '')->getxml();	//数组直接转换XML
						}
					}
				}
			}
	
			if (empty($productDataXML)){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Product Category Structure Not Empty');				
			}

			$feedItem = '<Message>
							<MessageID>' .($key + 1). '</MessageID>
							<OperationType>Update</OperationType>
							<Product>
								<SKU>' .$sellerSKU. '</SKU>
								<StandardProductID>
									<Type>' .$amazonProductType. '</Type>
									<Value>' .$amazonStandardProductID. '</Value>
								</StandardProductID>
								<ItemPackageQuantity>1</ItemPackageQuantity>			
								<DescriptionData>
									<Title><![CDATA[' .$title. ']]></Title>
									<Brand>' .$brand. '</Brand>
									<Description><![CDATA[' .$description. ']]></Description>									
									' .$bulletPointXML. '			
									<Manufacturer>' .$manufacturer. '</Manufacturer>
									<MfrPartNumber>' .$part_number. '</MfrPartNumber>
									' .$searchTermsXML. '							
									<ItemType>' .$itemType. '</ItemType>
								<TargetAudience>unisex</TargetAudience>	
								</DescriptionData>
								<ProductData>' .$productDataXML. '</ProductData>															
							</Product>					
						</Message>';	

			//检查是否有XML语法错误						
			$singleResult = $feedHeader.$feedItem.$feedFoot;						
			$xmlErrorArr = $this->getCheckXml($singleResult);
			if ($xmlErrorArr){
				$errFlag = 1;
				$errMessage[] = $xmlErrorArr;			
				//把有错误的以XML形式保存文件
				MHelper::writefilelog('amazon/amazonbatchpublish/ErrorXML/Product-VariationID-'.$addID.'-'.date("YmdHis").'.xml', $singleResult);				
			}else{
				$feedMain .= $feedItem;
			}

			//处理上传状态表（更新为待上传状态，并把错误写入）
			$condition = "add_id='{$mainSkuID}' and variation_id='{$addID}' and upload_type='{$uploadType}'";
			$uploadStatusInfo = $productAddStatusModel->getUploadStatusByCondition($condition);
			$upload_status = ($errFlag == 1) ? self::UPLOAD_STATUS_FAILURE : self::UPLOAD_STATUS_DEFAULT;	//即使是更新，也先把状态设为待上传状态			
			//更新
			if ($uploadStatusInfo){
				$statusData = array(
						'feed_id'        => '',
						'upload_status'  => $upload_status,
						'upload_time'    => date('Y-m-d H:i:s'),
						'upload_message' => $errMessage ? json_encode($errMessage) : '',
				);
				$productAddStatusModel->updateUploadStatusByID($uploadStatusInfo['id'], $statusData);
				$uploadStatusID = $uploadStatusInfo['id'];
			}else{
				//新增
				$statusData = array(
						'add_id'         => $mainSkuID,
						'variation_id'   => $addID,
						'upload_type'    => $uploadType,
						'publish_type'   => $publish_type,	//刊登类型	
						'upload_status'  => $upload_status,
						'upload_time'    => date('Y-m-d H:i:s'),							
						'upload_message' => $errMessage ? json_encode($errMessage) : '',				
				);
				$uploadStatusID = $productAddStatusModel->addUploadStatus($statusData);
			}

			//更新子SKU刊登状态为失败
			if ($upload_status == self::UPLOAD_STATUS_FAILURE){
				$updatProductAddData = array(
					'status' => self::UPLOAD_STATUS_FAILURE,
					'upload_finish_time' => date('Y-m-d H:i:s')
				);									
				AmazonProductAddVariation::model()->updateProductAddVarationByID($addID, $updatProductAddData);
			}

			//如果存在错误，则把错误写入错误日志，不能抛出异常中断其它正常产品的上传操作
			if($errFlag == 1){
				$allErrMessage[$addID] = $errMessage;
			}						
		}

		//把所有错误写入错误日志文档
		// if ($allErrMessage){
		// 	MHelper::writefilelog($path.'/Error-log'.$logID.'-'.date("YmdHis").'.txt', json_encode($allErrMessage));
		// }		
		if(empty($feedMain)) throw new Exception(Yii::t('amazon_product', 'Upload Main XML Empty').',原因：'.json_encode($allErrMessage));

		$result = $feedHeader.$feedMain.$feedFoot;

        MHelper::writefilelog($path.'/log'.$logID.'-'.$FeedType.'-'.date("YmdHis").'.xml', $result);	//保存到XML文档
        // MHelper::printvar($result);
		return $result;
	}
	
	/**
	 * @desc 刊登的xml代码：价格
	 */
	public function getXmlDataPrice($addIDs){
		$feedMain      = '';
		$feedItem      = '';	//单个上传内容
		$allErrMessage = array();
		$merchantID    = $this->_merchantID;
		$accountID     = $this->_accountID;
		$logID         = $this->_logID;
		$uploadType    = $this->_upload_type;
		$FeedType      = $this->_feed_type;
		$path          = 'amazon/amazonbatchpublish/'.date("Ymd").'/'.$accountID.'/'.$FeedType;
		if(empty($merchantID) || empty($addIDs)) throw new Exception(Yii::t('amazon_product', 'Param Error', array('param' => '$merchantID/$addIDs')));
		$productAddStatusModel = new AmazonProductAddStatus();

		$feedHeader = '<?xml version="1.0" encoding="UTF-8"?>
					<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
					    <Header>
					        <DocumentVersion>1.01</DocumentVersion>
					        <MerchantIdentifier>'.$merchantID.'</MerchantIdentifier>
					    </Header>
					    <MessageType>Price</MessageType>';
		$feedFoot = '</AmazonEnvelope>';		

		$addIDsArr = explode(',',$addIDs);
		foreach($addIDsArr as $key => $addID){	
			$variationID    = 0;
			$currency       = '';
			$sellerSKU      = '';
			$xmlErrorArr    = '';	//XML错误
			$feedItem       = '';
			$standard_price = 0;
			$salePrice      = 0;
			$saleStartTime  = '';
			$saleEndTime    = '';
			$errFlag        = 0;
			$errMessage     = array();
			$variation      = array();
			$publish_type   = 0;

			$addInfo = $this->getMainAndVariationInfoByVariationId($addID);	//通过子SKU自增ID获取主SKU和一子SKU信息
			if (!$addInfo){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Record Not Exists');
			}else{
				$mainSkuID    = $addInfo['id'];
				$variation    = $addInfo['variation'];
				$currency     = (string)$addInfo['currency'];	//货币
				$publish_type = (int)$addInfo['publish_type'];	//刊登类型
			}

			if ($variation){
				$variationID    = isset($variation['id']) ? $variation['id'] : 0;
				$sellerSKU      = isset($variation['seller_sku']) ? $variation['seller_sku'] : '';
				$standard_price = isset($variation['standard_price']) ? $variation['standard_price'] : 0;
				$salePrice      = isset($variation['sale_price']) ? $variation['sale_price'] : 0;
				$saleStartTime  = isset($variation['sale_start_time']) ? $variation['sale_start_time'] : '';
				$saleEndTime    = isset($variation['sale_end_time']) ? $variation['sale_end_time'] : '';		
			}else{
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Publish Sub Info not Exists');	
			}

			if (empty($currency)){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Product Currency Can\'t Empty');					
			}

			if (empty($sellerSKU)){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Not found Product Seller SKU');					
			}

			if ($standard_price == 0){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Product Price Can\'t Zero');					
			}

			//促销价不能大于标准价
			if ($salePrice > $standard_price){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Product Sales Can\'t more harm than Standard Price');	
			}		

			//如果有促销价，并且有促销日期都有允许上传	
			$salesXML = '';	
			if ($salePrice > 0){
				if(empty($saleStartTime)) $saleStartTime = date('Y-m-d H:i:s');;
				if(empty($saleEndTime)) $saleStartTime = date('Y-m-d H:i:s',strtotime("+20 year"));;
				//转换GTC标准时间
				$saleStartTimeGTC = date('Y-m-d\TH:i:s\Z',strtotime($saleStartTime) - 8*3600);
				$saleEndTimeGTC = date('Y-m-d\TH:i:s\Z',strtotime($saleEndTime) - 8*3600);
				$salesXML = '<Sale> 
								<StartDate>'.$saleStartTimeGTC.'</StartDate>
								<EndDate>'.$saleEndTimeGTC.'</EndDate> 
								<SalePrice currency="'.$currency.'">'.$salePrice.'</SalePrice> 
							</Sale>';
			}

			$feedItem .= '<Message>
								<MessageID>' .($key + 1). '</MessageID>
								<Price>
									<SKU>'.$sellerSKU.'</SKU>
									<StandardPrice currency="'.$currency.'">'.$standard_price.'</StandardPrice>	
									'.$salesXML.'					
								</Price>
							</Message>';	

			//检查是否有XML语法错误
			$singleResult = $feedHeader.$feedItem.$feedFoot;						
			$xmlErrorArr = $this->getCheckXml($singleResult);
			if ($xmlErrorArr){
				$errFlag = 1;
				$errMessage[] = $xmlErrorArr;			
				//把有错误的以XML形式保存文件
				MHelper::writefilelog('amazon/amazonbatchpublish/ErrorXML/Price-VariationID-'.$addID.'-'.date("YmdHis").'.xml', $singleResult);				
			}else{
				$feedMain .= $feedItem;
			}


			//处理上传状态表（更新为待上传状态，并把错误写入）
			$condition = "add_id='{$mainSkuID}' and variation_id='{$addID}' and upload_type='{$uploadType}'";
			$uploadStatusInfo = $productAddStatusModel->getUploadStatusByCondition($condition);
			$upload_status = ($errFlag == 1) ? self::UPLOAD_STATUS_FAILURE : self::UPLOAD_STATUS_DEFAULT;	//即使是更新，也先把状态设为待上传状态			
			//更新
			if ($uploadStatusInfo){
				$statusData = array(
						'feed_id'        => '',
						'upload_status'  => $upload_status,
						'upload_time'    => date('Y-m-d H:i:s'),
						'upload_message' => $errMessage ? json_encode($errMessage) : '',
				);
				$productAddStatusModel->updateUploadStatusByID($uploadStatusInfo['id'], $statusData);
			}else{
				//新增
				$statusData = array(
						'add_id'         => $mainSkuID,
						'variation_id'   => $addID,
						'upload_type'    => $uploadType,
						'publish_type'   => $publish_type,	//刊登类型	
						'upload_status'  => $upload_status,
						'upload_time'    => date('Y-m-d H:i:s'),							
						'upload_message' => $errMessage ? json_encode($errMessage) : '',				
				);
				$productAddStatusModel->addUploadStatus($statusData);
			}

			//更新子SKU刊登状态为失败
			if ($upload_status == self::UPLOAD_STATUS_FAILURE){
				$updatProductAddData = array(
					'status' => self::UPLOAD_STATUS_FAILURE,
					'upload_finish_time' => date('Y-m-d H:i:s')
				);									
				AmazonProductAddVariation::model()->updateProductAddVarationByID($addID, $updatProductAddData);
			}					

			if($errFlag == 1){
				$allErrMessage[$addID] = $errMessage;
			}
		}

		//把所有错误写入错误日志文档
		if ($allErrMessage){
			//MHelper::writefilelog($path.'/Error-log'.$logID.'-'.date("YmdHis").'.txt', json_encode($allErrMessage));
		}
		
		if(empty($feedMain)) throw new Exception(Yii::t('amazon_product', 'Upload Main XML Empty').',原因：'.json_encode($allErrMessage));

		$result = $feedHeader.$feedMain.$feedFoot;
		//MHelper::writefilelog($path.'/log'.$logID.'-'.$FeedType.'-'.date("YmdHis").'.xml', $result);

		// MHelper::printvar($result);
		return $result;		
	}		

	/**
	 * @desc 刊登的xml代码：库存
	 */
	public function getXmlDataInventory($addIDs){
		$feedMain      = '';
		$allErrMessage = array();
		$merchantID    = $this->_merchantID;
		$accountID     = $this->_accountID;
		$logID         = $this->_logID;
		$uploadType    = $this->_upload_type;
		$FeedType      = $this->_feed_type;
		$path          = 'amazon/amazonbatchpublish/'.date("Ymd").'/'.$accountID.'/'.$FeedType;
		if(empty($merchantID) || empty($addIDs)) throw new Exception(Yii::t('amazon_product', 'Param Error', array('param' => '$merchantID/$addIDs')));
		$productAddStatusModel = new AmazonProductAddStatus();	

		$feedHeader = '<?xml version="1.0" encoding="UTF-8"?>
					<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
					    <Header>
					        <DocumentVersion>1.01</DocumentVersion>
					        <MerchantIdentifier>'.$merchantID.'</MerchantIdentifier>
					    </Header>
					    <MessageType>Inventory</MessageType>';				
		$feedFoot = '</AmazonEnvelope>';			

		$addIDsArr = explode(',',$addIDs);
		foreach($addIDsArr as $key => $addID){	
			$xmlErrorArr  = '';	
			$feedItem     = '';
			$sellerSKU    = '';
			$errFlag      = 0;
			$errMessage   = array();	
			$variation    = array();
			$publish_type = 0;	
			$inventory    = 0;			

			$addInfo = $this->getMainAndVariationInfoByVariationId($addID);
			if (!$addInfo){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Record Not Exists');
			}else{
				$mainSkuID    = $addInfo['id'];
				$variation    = $addInfo['variation'];
				$publish_type = (int)$addInfo['publish_type'];
			}	

			if ($variation){				
				$sellerSKU = isset($variation['seller_sku']) ? $variation['seller_sku'] : '';
				$inventory = isset($variation['inventory']) ? $variation['inventory'] : 0;	
			}else{
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Publish Sub Info not Exists');	
			}

			if (empty($sellerSKU)){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Not found Product Seller SKU');					
			}

			if ($inventory == 0){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Product Inventory Can\'t Zero');				
			}	

			$feedItem .= '<Message>
							<MessageID>' .($key + 1). '</MessageID>
							<OperationType>Update</OperationType>
							<Inventory>
								<SKU>'.$sellerSKU.'</SKU>
								<Quantity>'.$inventory.'</Quantity>
							</Inventory>
						</Message>';

			//检查是否有XML语法错误
			$singleResult = $feedHeader.$feedItem.$feedFoot;						
			$xmlErrorArr = $this->getCheckXml($singleResult);
			if ($xmlErrorArr){
				$errFlag = 1;
				$errMessage[] = $xmlErrorArr;			
				//把有错误的以XML形式保存文件
				MHelper::writefilelog('amazon/amazonbatchpublish/ErrorXML/Inventory-VariationID-'.$addID.'-'.date("YmdHis").'.xml', $singleResult);				
			}else{
				$feedMain .= $feedItem;
			}				

			//处理上传状态表（更新为待上传状态，并把错误写入）
			$condition = "add_id='{$mainSkuID}' and variation_id='{$addID}' and upload_type='{$uploadType}'";
			$uploadStatusInfo = $productAddStatusModel->getUploadStatusByCondition($condition);
			$upload_status = ($errFlag == 1) ? self::UPLOAD_STATUS_FAILURE : self::UPLOAD_STATUS_DEFAULT;			
			//更新
			if ($uploadStatusInfo){
				$statusData = array(
						'feed_id'        => '',
						'upload_status'  => $upload_status,
						'upload_time'    => date('Y-m-d H:i:s'),
						'upload_message' => $errMessage ? json_encode($errMessage) : '',
				);
				$productAddStatusModel->updateUploadStatusByID($uploadStatusInfo['id'], $statusData);
			}else{
				//新增
				$statusData = array(
						'add_id'         => $mainSkuID,
						'variation_id'   => $addID,
						'upload_type'    => $uploadType,
						'publish_type'   => $publish_type,	//刊登类型	
						'upload_status'  => $upload_status,
						'upload_time'    => date('Y-m-d H:i:s'),							
						'upload_message' => $errMessage ? json_encode($errMessage) : '',					
				);
				$productAddStatusModel->addUploadStatus($statusData);
			}

			//更新子SKU刊登状态为失败
			if ($upload_status == self::UPLOAD_STATUS_FAILURE){
				$updatProductAddData = array(
					'status' => self::UPLOAD_STATUS_FAILURE,
					'upload_finish_time' => date('Y-m-d H:i:s')
				);									
				AmazonProductAddVariation::model()->updateProductAddVarationByID($addID, $updatProductAddData);
			}

			if($errFlag == 1){
				$allErrMessage[$addID] = $errMessage;
			}
		}			

		//把所有错误写入错误日志文档
		if ($allErrMessage){
			MHelper::writefilelog($path.'/Error-log'.$logID.'-'.date("YmdHis").'.txt', json_encode($allErrMessage));
		}
		
		if(empty($feedMain)) throw new Exception(Yii::t('amazon_product', 'Upload Main XML Empty').',原因：'.json_encode($allErrMessage));		
		$result = $feedHeader.$feedMain.$feedFoot;

		//MHelper::writefilelog($path.'/log'.$logID.'-'.$FeedType.'-'.date("YmdHis").'.xml', $result);
		// MHelper::printvar($result);
		return $result;		
	}
	
	/**
	 * @desc 刊登的xml代码：图片
	 */
	public function getXmlDataImage($addIDs){
		$feedMain      = '';
		$number        = 0;
		$allErrMessage = array();
		$merchantID    = $this->_merchantID;
		$accountID     = $this->_accountID;
		$logID         = $this->_logID;
		$uploadType    = $this->_upload_type;
		$FeedType      = $this->_feed_type;
		$path          = 'amazon/amazonbatchpublish/'.date("Ymd").'/'.$accountID.'/'.$FeedType;
		if(empty($merchantID) || empty($addIDs)) throw new Exception(Yii::t('amazon_product', 'Param Error', array('param' => '$merchantID/$variationAddIDs')));

		$feedHeader = '<?xml version="1.0" encoding="UTF-8"?>
					<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
					    <Header>
					        <DocumentVersion>1.01</DocumentVersion>
					        <MerchantIdentifier>'.$merchantID.'</MerchantIdentifier>
					    </Header>
					    <MessageType>ProductImage</MessageType>';				
		$feedFoot = '</AmazonEnvelope>';

		$productImageAddModel = new AmazonProductImageAdd();
		$productAddStatusModel = new AmazonProductAddStatus();
		$addIDsArr = explode(',',$addIDs);
		foreach($addIDsArr as $key => $addID){	
			$sku               = '';
			$sellerSKU         = '';
			$xmlErrorArr       = '';
			$feedItem          = '';
			$errFlag           = 0;
			$errMessage        = array();
			$variation         = array();
			$publish_type      = 0;	
			$site_id           = 0;
			$mainImageArr      = array();
			$attachedImageArr  = array();	
			$mainImageUrl      = '';
			$AttachedImagesXML = '';
			$imgResult         = array();	

			$addInfo = $this->getMainAndVariationInfoByVariationId($addID);
			if (!$addInfo){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Record Not Exists');
			}else{
				$mainSkuID    = $addInfo['id'];
				$variation    = $addInfo['variation'];
				$publish_type = (int)$addInfo['publish_type'];	//刊登类型
				$publishSite  = $addInfo['country_code'];
				if(!empty($publishSite)) $site_id = AmazonSite::getSiteIdByName($publishSite);	//站点国家代码换成ID值
			}

			if ($variation){				
				$sku       = isset($variation['sku']) ? $variation['sku'] : '';
				$sellerSKU = isset($variation['seller_sku']) ? $variation['seller_sku'] : '';
			}else{
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Publish Sub Info not Exists');	
			}

			if (empty($sku)){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Not found Product SKU');					
			}

			if (empty($sellerSKU)){
				$errFlag = 1;
				$errMessage[] = Yii::t('amazon_product', 'Not found Product Seller SKU');					
			}

			//调用Java图片接口获取远程图片，并把获取到的远程图片地址更新到common的图片服务器
    		$imgResult = $productImageAddModel->sendImageUploadRequest($sku, $accountID, $site_id, Platform::CODE_AMAZON);	
			if ($imgResult){
				//获取图片服务器的图片地址
				$imagesList = AmazonProductImageAdd::model()->getImageBySku($sku, $accountID, Platform::CODE_AMAZON);
				if ($imagesList){
						$mainImageArr     = $imagesList[1];	//主图
						$attachedImageArr = $imagesList[2];	//附图

						//主图必须有且只有一张，附图不能超过八张
						if ($mainImageArr && isset($mainImageArr[0]['remote_path']) && !empty($mainImageArr[0]['remote_path'])){
							$mainImageUrl = $mainImageArr[0]['remote_path'];
						}else{
							$errFlag = 1;
							$errMessage[] = Yii::t('amazon_product', 'Main Image Remote Address can\'t Empty');				
						}

						//如果没有附图，则用主图代替
						if ($attachedImageArr && isset($attachedImageArr[0]['remote_path']) && !empty($attachedImageArr[0]['remote_path'])){
						}else{
							if($mainImageArr) $attachedImageArr = $mainImageArr;
						}						
				}else{
					$errFlag = 1;
					$errMessage[] = Yii::t('amazon_product', 'Product Image Remote Address Can\'t Empty');				
				}	
								
			}else{
				$errFlag = 1;
				if ($productImageAddModel->getErrorMessageImg()){
					$errMessage[] = $productImageAddModel->getErrorMessageImg();				
				}
			}

			$number++;

			//主图拼接（最大只支持1张）
			if (!empty($mainImageUrl)){
				$feedItem .= '<Message>
								<MessageID>' .$number. '</MessageID>
								<OperationType>Update</OperationType>
								<ProductImage>
									<SKU>' .$sellerSKU. '</SKU>		
									<ImageType>Main</ImageType>
									<ImageLocation><![CDATA[' .$mainImageUrl. ']]></ImageLocation>
								</ProductImage>
							</Message>';
			}

			//附图拼接（附图最大支持8张）
			if ($feedItem && $attachedImageArr){
				$j = 0;
				foreach($attachedImageArr as $image){
					if (!empty($image['remote_path'])){
						$number++;
						$j++;
						$AttachedImagesXML .='<Message>
													<MessageID>'.$number.'</MessageID>
													<OperationType>Update</OperationType>
													<ProductImage>
														<SKU>'.$sellerSKU.'</SKU>		
														<ImageType>PT'.$j.'</ImageType>
														<ImageLocation><![CDATA[' .$image['remote_path']. ']]></ImageLocation>
													</ProductImage>							
												</Message>';												
					}
					if($j == 8) break;				
				}				
			}
			if(!empty($AttachedImagesXML)) $feedItem .= $AttachedImagesXML;				

			//检查是否有XML语法错误
			$singleResult = $feedHeader.$feedItem.$feedFoot;
			$xmlErrorArr = $this->getCheckXml($singleResult);
			if ($xmlErrorArr){
				$errFlag = 1;
				$errMessage[] = $xmlErrorArr;			
				//把有错误的以XML形式保存文件
				// MHelper::writefilelog('amazon/amazonbatchpublish/ErrorXML/Image-VariationID-'.$addID.'-'.date("YmdHis").'.xml', $singleResult);				
			}else{
				$feedMain .= $feedItem;
			}

			//处理上传状态表（更新为待上传状态，并把错误写入）
			$condition = "add_id='{$mainSkuID}' and variation_id='{$addID}' and upload_type='{$uploadType}'";
			$uploadStatusInfo = $productAddStatusModel->getUploadStatusByCondition($condition);
			$upload_status = ($errFlag == 1) ? self::UPLOAD_STATUS_FAILURE : self::UPLOAD_STATUS_DEFAULT;		
			//更新
			if ($uploadStatusInfo){
				$statusData = array(
						'feed_id'        => '',
						'upload_status'  => $upload_status,
						'upload_time'    => date('Y-m-d H:i:s'),
						'upload_message' => $errMessage ? json_encode($errMessage) : '',
				);
				$productAddStatusModel->updateUploadStatusByID($uploadStatusInfo['id'], $statusData);
			}else{
				//新增
				$statusData = array(
						'add_id'         => $mainSkuID,
						'variation_id'   => $addID,
						'upload_type'    => $uploadType,
						'publish_type'   => $publish_type,	//刊登类型	
						'upload_status'  => $upload_status,
						'upload_time'    => date('Y-m-d H:i:s'),							
						'upload_message' => $errMessage ? json_encode($errMessage) : '',					
				);
				$productAddStatusModel->addUploadStatus($statusData);
			}

			//更新子SKU刊登状态为失败
			if ($upload_status == self::UPLOAD_STATUS_FAILURE){
				$updatProductAddData = array(
					'status' => self::UPLOAD_STATUS_FAILURE,
					'upload_finish_time' => date('Y-m-d H:i:s')
				);									
				AmazonProductAddVariation::model()->updateProductAddVarationByID($addID, $updatProductAddData);
			}						

			if($errFlag == 1){
				$allErrMessage[$addID] = $errMessage;
			}
		}

		//把所有错误写入错误日志文档
		// if ($allErrMessage){
		// 	MHelper::writefilelog($path.'/Error-log'.$logID.'-'.date("YmdHis").'.txt', json_encode($allErrMessage));
		// }
		
		if(empty($feedMain)) throw new Exception(Yii::t('amazon_product', 'Upload Main XML Empty').',原因：'.$addIDs.'的IDs列表错误：'.json_encode($allErrMessage));	

		$result = $feedHeader.$feedMain.$feedFoot;
		MHelper::writefilelog($path.'/log'.$logID.'-'.$FeedType.'-'.date("YmdHis").'.xml', $result);
		// MHelper::printvar($result);
		return $result;	
	}


	/**
	 * @desc Amazon产品刊登第五步：关系		Liz|2016/4/9
	 * @param string $accountID
	 * @param array $itemData
	 * @throws Exception                                            
	 * @return boolean
	 */
	public function amazonProductRelationships($accountID, $itemData){

		$encryptSku = new encryptSku();
        $itemData['sell_sku'] = $encryptSku->getAmazonEncryptSku($itemData['sku']);	

		try{
			// $submitFeedRequest = new SubmitFeedRequest();
			$submitFeedRequest = new CommonSubmitFeedRequest();
			$submitFeedRequest->setFeedType(SubmitFeedRequest::FEEDTYPE_POST_PRODUCT_RELATIONSHIP_DATA)
								->setAccount($accountID);
			$merchantID = $submitFeedRequest->getMerchantID();
			$submitFeedRequest->setFeedContent($this->getXmlDataRelationships($merchantID, $itemData));
			$submitFeedId = $submitFeedRequest->setRequest()
										->sendRequest()
										->getResponse();
			$feedProcessingStatus = $submitFeedRequest->getFeedProcessingStatus();
			if($submitFeedRequest->getIfSuccess() &&
				$feedProcessingStatus && $feedProcessingStatus != SubmitFeedRequest::FEED_STATUS_CANCELLED){
				$scheduled = 1;
				if ($feedProcessingStatus == SubmitFeedRequest::FEED_STATUS_DONE){
					$scheduled = 2;
				}
				
				//写入请求日报表
				$startDate = $endDate = date("Y-m-dTH:i:sZ");
				$params = array(
						'account_id'               => $accountID,
						'report_request_id'        => $submitFeedId,
						'report_type'              => SubmitFeedRequest::FEEDTYPE_POST_PRODUCT_RELATIONSHIP_DATA,
						'start_date'               => $startDate,
						'end_date'                 => $endDate,
						'submitted_date'           => $endDate,
						'scheduled'                => $scheduled,
						'report_processing_status' => $feedProcessingStatus,
						'report_skus'              => $itemData['sku'],
				);
				$requestReport = new AmazonRequestReport();
				$requestReport->addRequestReport($params);
				return $submitFeedId;
			}
			throw new Exception($submitFeedRequest->getErrorMsg());
		}catch(Exception $e){
			$this->setErrorMsg($e->getMessage());
			return false;
		}
	}
	
	/**
	 * @desc 刊登的xml代码：关系
	 */
	public function getXmlDataRelationships($merchantID, $itemData){

		//获取产品图片URL：current($itemData['product_img']['zt'])
		$feedHeader = '<?xml version="1.0" encoding="UTF-8"?>
				<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
				    <Header>
				        <DocumentVersion>1.01</DocumentVersion>
				        <MerchantIdentifier>'.$merchantID.'</MerchantIdentifier>
				    </Header>
				    <MessageType>Relationship</MessageType>';
	
		$feedMain = '';

		//Accessory(配件方式)
/*
		$feedMain .= '<Message>
					<MessageID>1</MessageID>
					<OperationType>Update</OperationType>
					<Relationship>
						<ParentSKU>9dl9gg9jb8gm6</ParentSKU>
						<Relation>
							<SKU>9234567899</SKU>
							<Type>Accessory</Type>
						</Relation>						
					</Relationship>
				</Message>';
*/

		//Variation(多属性方式)
		$feedMain .= '<Message>
					<MessageID>1</MessageID>
					<OperationType>Update</OperationType>
					<Relationship>
						<ParentSKU>9234567893</ParentSKU>	
						<Relation>
							<SKU>9234567891</SKU>
							<Type>Variation</Type>
						</Relation>		
						<Relation>
							<SKU>9234567894</SKU>
							<Type>Variation</Type>
						</Relation>																
					</Relationship>
				</Message>';				
				
		$feedFoot = '</AmazonEnvelope>';
		return $feedHeader.$feedMain.$feedFoot;		
	}

	/**
	 * @desc Amazon产品刊登第六步：运费		Liz|2016/4/9
	 * @param string $accountID
	 * @param array $itemData
	 * @throws Exception
	 * @return boolean
	 */
	public function amazonProductShippingOverride($accountID, $itemData){

		$encryptSku = new encryptSku();
        $itemData['sell_sku'] = $encryptSku->getAmazonEncryptSku($itemData['sku']);	

		try{
			$submitFeedRequest = new CommonSubmitFeedRequest();
			$submitFeedRequest->setFeedType(SubmitFeedRequest::FEEDTYPE_POST_PRODUCT_OVERRIDES_DATA)
								->setAccount($accountID);
			$merchantID = $submitFeedRequest->getMerchantID();
			$submitFeedRequest->setFeedContent($this->getXmlDataShippingOverride($merchantID, $itemData));
			$submitFeedId = $submitFeedRequest->setRequest()
										->sendRequest()
										->getResponse();
			$feedProcessingStatus = $submitFeedRequest->getFeedProcessingStatus();
			if($submitFeedRequest->getIfSuccess() &&
				$feedProcessingStatus && $feedProcessingStatus != SubmitFeedRequest::FEED_STATUS_CANCELLED){
				$scheduled = 1;
				if ($feedProcessingStatus == SubmitFeedRequest::FEED_STATUS_DONE){
					$scheduled = 2;
				}
				
				//写入请求日报表
				$startDate = $endDate = date("Y-m-dTH:i:sZ");
				$params = array(
						'account_id'               => $accountID,
						'report_request_id'        => $submitFeedId,
						'report_type'              => SubmitFeedRequest::FEEDTYPE_POST_PRODUCT_OVERRIDES_DATA,
						'start_date'               => $startDate,
						'end_date'                 => $endDate,
						'submitted_date'           => $endDate,
						'scheduled'                => $scheduled,
						'report_processing_status' => $feedProcessingStatus,
						'report_skus'              => $itemData['sku'],
				);
				$requestReport = new AmazonRequestReport();
				$requestReport->addRequestReport($params);
				return $submitFeedId;
			}
			throw new Exception($submitFeedRequest->getErrorMsg());
		}catch(Exception $e){
			$this->setErrorMsg($e->getMessage());
			return false;
		}
	}
	
	/**
	 * @desc 刊登的xml代码：运费
	 */
	public function getXmlDataShippingOverride($merchantID, $itemData){

		//获取产品图片URL：current($itemData['product_img']['zt'])
		$feedHeader = '<?xml version="1.0" encoding="UTF-8"?>
				<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
				    <Header>
				        <DocumentVersion>1.01</DocumentVersion>
				        <MerchantIdentifier>'.$merchantID.'</MerchantIdentifier>
				    </Header>
				    <MessageType>Override</MessageType>';
	
		$feedMain = '';
		$feedMain .= '<Message>
					<MessageID>1</MessageID>
					<OperationType>Update</OperationType>
					<Override>
						<SKU>9234567899</SKU>
						<ShippingOverride>
							<ShipOption>Exp Alaska Hawaii PO Box</ShipOption>
							<Type>Additive</Type>
							<ShipAmount currency="USD">2000.00</ShipAmount>
						</ShippingOverride>
						<ShippingOverride>
							<ShipOption>Exp Alaska Hawaii PO Box</ShipOption>
							<IsShippingRestricted>false</IsShippingRestricted>
						</ShippingOverride>											
					</Override>
				</Message>';
				
		$feedFoot = '</AmazonEnvelope>';
		return $feedHeader.$feedMain.$feedFoot;		
	}

	/**
	 * @desc 通过账号获取待刊登（排除成功和失败状态的记录）自增ID串
	 * @param $accountID 账号ID
	 * @return string
	 */
	public function getProductAddIDsByAccountID($accountID = 0){
		if ($accountID > 0){
			$ret = $this->getDBConnection()->createCommand()
					->select('id')
					->from($this->tableName())
					->where('status !=' .self::UPLOAD_STATUS_SUCCESS)
					->andWhere('status !=' .self::UPLOAD_STATUS_FAILURE)
					->andWhere('account_id =' .$accountID)
					// ->limit(self::PRODUCT_PUBLISH_LIMIT)
					->order('id asc')
					->queryAll();	
		}else{
			$ret = $this->getDBConnection()->createCommand()
					->select('id')
					->from($this->tableName())
					->where('status !=' .self::UPLOAD_STATUS_SUCCESS)
					->andWhere('status !=' .self::UPLOAD_STATUS_FAILURE)
					->order('id asc')
					->queryAll();
		}
		if($ret){
			$item = array();
			foreach($ret as $val){
				$item[] = $val['id'];
			}
			return implode(",",$item);
		}else{
			return '';
		}
	}	

	/**
	 * @desc 通过账号获取待刊登主表数据（上传总状态不成功的记录）
	 * @param $accountID 账号ID
	 * @return array()
	 */
	public function getProductAddMainInfoByAccountID($accountID = 0){
		if ($accountID > 0){
			return $this->getDBConnection()->createCommand()
					->select('*')
					->from($this->tableName())
					->where('status !=' .self::UPLOAD_STATUS_SUCCESS)
					->andWhere('account_id =' .$accountID)
					->queryAll();
		}else{
			return $this->getDBConnection()->createCommand()
					->select('*')
					->from($this->tableName())
					->where('status !=' .self::UPLOAD_STATUS_SUCCESS)
					->queryAll();
		}
	}


	/**
	 * 通过账号获取待刊登的详情记录（列举包括多属性数据，多属性为多条主表数据相同的记录）（上传总状态不成功的所有记录）
	 * @param $accountID 账号ID
	 * @return array()
	 */
	public function getProductAddListByAccountID($accountID = 0){
		$list = array();
		$mainList = $this->getProductAddMainInfoByAccountID($accountID);	//主表记录
		if ($mainList){
			foreach($mainList as $val){
				$variationModel = new AmazonProductAddVariation();
				$variationList = $variationModel->getVariationProductAdd($val['id']);
				if ($variationList){
					if ($val['publish_type'] == self::PRODUCT_PUBLISH_TYPE_VARIATION){
						//多属性刊登（子SKU对应相同的主表记录）
						if ($variationList){
							foreach($variationList as $item){
								$val['variation'] = $item;
								$list[$val['id']] = $val;
							}
						}					
					}else{
						//单品刊登
						$variationitem = $variationList[0];
						$val['variation'] = $variationitem;
						$list[$val['id']] = $val;
					}
				}
			}
		}
		return $list;
	}

	/**
	 * @desc 基本产品过滤在listing已存在的记录（同账号同SKU），过滤不进入刊登列表的记录（限制最大刊登数）
	 * @param string $varationIds 待刊登的多属性IDs
	 * @param int $uploadType 上传类型
	 */
	public function setFilterProductAddIDs($varationIds = '', $uploadType = 0){
		if(empty($varationIds) || $uploadType == 0) return '';
		$newAddIDs = '';		
		if(empty($varationIds)) return '';

		$addIDList = explode(',',$varationIds);	//多属性IDs
		if ($addIDList){
			foreach ($addIDList as $key => $varID){
				$delFlag = 0;
				// $info = $this->getMainAndVariationInfoById($addID);
				$info = $this->getMainAndVariationInfoByVariationId($varID);	//一子SKU和主SKU
				if ($info){
					$addID       = $info['id'];	//主表自增ID
					$SKU         = $info['sku'];	//主SKU
					$accountID   = $info['account_id'];	
					$publishType = $info['publish_type'];													
					$variationID = isset($info['variation']['id']) ? (int)$info['variation']['id'] : 0;	

					if ($variationID == 0){
						$delFlag = 1;
					}else{
						$uploadStatus = 0;
						$productStatusInfo = AmazonProductAddStatus::model()->getUploadStatusInfoByType($addID, $variationID, $uploadType);
						if($productStatusInfo) $uploadStatus = $productStatusInfo['upload_status'];	//当前上传状态

						//非基本产品类型：
						//1.刊登状态为上传中、上传成功
						//2.先决条件不满足的（除基本产品其它刊登必须在基本产品刊登成功后才能刊登）									
						if ($uploadType != self::UPLOAD_TYPE_PRODUCT){
							if ($uploadStatus == self::UPLOAD_STATUS_RUNNING || $uploadStatus == self::UPLOAD_STATUS_SUCCESS){
								$delFlag = 1;
							}else{
								//查询基本产品类型记录，即使此上传状态记录不存在(empty($productStatusInfo))
								$uploadProductSeccess = AmazonProductAddStatus::model()->getUploadStatusInfoByType($addID, $variationID, self::UPLOAD_TYPE_PRODUCT);
								if($uploadProductSeccess){
									if($uploadProductSeccess['upload_status'] != self::UPLOAD_STATUS_SUCCESS) $delFlag = 1;
								}else{
									$delFlag = 1;	//基本产品类型不能为空
								}
							}
						}else{
							//基本产品类型：
							//1.刊登状态为上传中、上传成功
							//2.上传失败（基本产品失败的，需要重新处理产品数据(更新后会自动变为待上传状态)，如已失败的，再刊登也不会成功，和其它类型有区别）
							//3.listing存在相同账号相同SKU记录
							if ($uploadStatus == self::UPLOAD_STATUS_RUNNING || $uploadStatus == self::UPLOAD_STATUS_SUCCESS || $uploadStatus == self::UPLOAD_STATUS_FAILURE){
								$delFlag = 1;
							}else{
								//待上传，即新增基本产品刊登
								//查询listing是否已存在（同账号同主SKU）
								$condition = "account_id = {$accountID} AND sku = '{$SKU}'";	
								$listingInfo = AmazonList::model()->getListingInfoByCondition($condition);						
								//listing存在，则此刊登总状态写入失败，并写入上传状态表的基本产品类型上，错误入库
								if ($listingInfo){
									$delFlag = 1;
									$listingID = $listingInfo['id'];
									$desc = '刊登失败：产品表已存在同账号同系统SKU的记录：' .$SKU. '，不允许重复刊登。';

									$amazonProductAddStatusModel = new AmazonProductAddStatus();
									$statusInfo = $amazonProductAddStatusModel->getUploadStatusInfoByType($addID,$variationID,AmazonProductAdd::UPLOAD_TYPE_PRODUCT);							
									if ($statusInfo){
										$updateData = array(
											'feed_id'        => '',
											'upload_status'  => AmazonProductAdd::UPLOAD_STATUS_FAILURE,
											'upload_time'    => date('Y-m-d H:i:s'),
											'upload_message' => (!empty($desc)) ? json_encode($desc) : '',
										);
										$amazonProductAddStatusModel->updateUploadStatusByID($statusInfo['id'],$updateData);
									}else{
										//新增
										$addData = array(
												'add_id'         => $addID,
												'variation_id'   => $variationID,
												'upload_type'    => AmazonProductAdd::UPLOAD_TYPE_PRODUCT,
												'publish_type'   => $publishType,
												'upload_status'  => AmazonProductAdd::UPLOAD_STATUS_FAILURE,
												'upload_time'    => date('Y-m-d H:i:s'),							
												'upload_message' => (!empty($desc)) ? json_encode($desc) : '',
										);								
										$amazonProductAddStatusModel->addUploadStatus($addData);
									}
									//子SKU刊登上传状态更新为刊登失败
									// AmazonProductAdd::model()->updateProductAddByID($addID, array('status' => AmazonProductAdd::UPLOAD_STATUS_FAILURE, 'upload_finish_time' => date('Y-m-d H:i:s')));									
									AmazonProductAddVariation::model()->updateProductAddByID($addID, array('status' => AmazonProductAdd::UPLOAD_STATUS_FAILURE, 'upload_finish_time' => date('Y-m-d H:i:s')));
								}								
							}
						}
					}
				}else{
					$delFlag = 1;
				}				
				if($delFlag == 1) unset($addIDList[$key]);	//清除此ID出ID数组
			}
		}else{
			return '';
		}
		if ($addIDList){
			$limitList = array();
			//限制最大刊登数
			if (count($addIDList) > self::PRODUCT_PUBLISH_LIMIT){
				$limitList = array_slice($addIDList,0,self::PRODUCT_PUBLISH_LIMIT);
			}else{
				$limitList = $addIDList;
			}
			$newAddIDs = implode(",",$limitList);
		}				
		return $newAddIDs;
	}	

	/**
	 * @desc 根据ID组合刊登主表及多属性记录
	 * @param unknown $id 主SKU自增ID
	 * @return mixed
	 */
	public function getMainAndVariationInfoById($id) {
		$list = array();
		$mainInfo = $this->getDbConnection()->createCommand()
			->select('*')
			->from(self::tableName())
			->where("id = :id", array(':id' => $id))
			->queryRow();

		//多属性记录
		if ($mainInfo){
			$variationList = AmazonProductAddVariation::model()->getVariationProductAdd($id);
			if ($variationList){
				$variationInfo = $variationList[0];
				if ($variationInfo){
					//组合主表和多属性记录
					$list = $mainInfo;
					if ($mainInfo['publish_type'] == self::PRODUCT_PUBLISH_TYPE_VARIATION){
						$list['variation'] = $variationList;
					}else{
						$list['variation'] = $variationInfo;	//单品就一维数据
					}
				}
			}			
		}
		return $list;
	}	

	/**
	 * @desc 根据多属性ID组合刊登主表及多属性记录（一子SKU对应一主SKU）
	 * @param unknown $variationID
	 * @return mixed
	 */
	public function getMainAndVariationInfoByVariationId($variationID) {
		$list = array();
		//子SKU记录
		$variationInfo = AmazonProductAddVariation::model()->getVariationInfoByID($variationID);
		if ($variationInfo){
			//主SKU记录
			$mainInfo = $this->getDbConnection()->createCommand()
				->select('*')
				->from(self::tableName())
				->where("id = :id", array(':id' => $variationInfo['add_id']))
				->queryRow();
			if ($mainInfo){
				$list = $mainInfo;
				$list['variation'] = $variationInfo;
			}
		}		
		return $list;
	}	

	/**
	 * @desc 新增刊登
	 * @param array $data
	 * @return int
	 */
	public function addProductAdd($data){
		if(empty($data)) return false;
		$id = 0;
		$ret = $this->getDbConnection()->createCommand()->insert(self::tableName(), $data);
		if($ret) $id = $this->getDbConnection()->getLastInsertID();
		return $id;
	}
	
	/**
	 * @desc 根据自增ID更新
	 * @param int $id
	 * @param array $updata
	 * @return boolean
	 */
	public function updateProductAddByID($id, $updata){
		if(empty($id) || empty($updata)) return false;
		$conditions = "id = {$id}";
		return $this->getDbConnection()->createCommand()->update(self::tableName(), $updata, $conditions);
	}

	/**
	 * @desc 刊登之前，要先过滤在listing已经存在，即已存在刊登的记录（同账号同SKU）
	 * @param string $addIDs
	 */
	// public function setFilterUploadedByListing($addIDs){
	// 	if(empty($addIDs)) return '';		
	// 	$newAddIDs = '';
		
	// 	$addIDList = explode(',',$addIDs);
	// 	if ($addIDList){
	// 		foreach ($addIDList as $key => $addID){
	// 			$sku  = '';
	// 			$desc = '';
	// 			$flag = 0;				
	// 			$addInfo = $this->getMainAndVariationInfoById($addID);
	// 			if ($addInfo){
	// 				//单品
	// 				if ($addInfo['publish_type'] == self::PRODUCT_PUBLISH_TYPE_SINGLE){
	// 					$accountID   = $addInfo['account_id'];
	// 					$variationID = $addInfo['variation']['id'];
	// 					$sku         = $addInfo['variation']['sku'];	//系统SKU
	// 					$publishType = $addInfo['publish_type'];
	// 					//和listing核对
	// 					$condition = "account_id = {$accountID} AND sku = '{$sku}'";
	// 					$listingInfo = AmazonList::model()->getListingInfoByCondition($condition);						
	// 					//listing存在，则此刊登总状态写入失败，并写入上传状态表的基本产品类型上，错误入库
	// 					if ($listingInfo){
	// 						$flag = 1;
	// 						$listingID = $listingInfo['id'];
	// 						$desc = '刊登失败：产品表已存在同账号同系统SKU的记录：' .$listingID. '，不允许重复刊登。';

	// 						$amazonProductAddStatusModel = new AmazonProductAddStatus();
	// 						$statusInfo = $amazonProductAddStatusModel->getUploadStatusInfoByType($addID,$variationID,AmazonProductAdd::UPLOAD_TYPE_PRODUCT);							
	// 						if ($statusInfo){
	// 							$updateData = array(
	// 								'feed_id'        => '',
	// 								'upload_status'  => AmazonProductAdd::UPLOAD_STATUS_FAILURE,
	// 								'upload_time'    => date('Y-m-d H:i:s'),
	// 								'upload_message' => (!empty($desc)) ? json_encode($desc) : '',
	// 							);
	// 							$amazonProductAddStatusModel->updateUploadStatusByID($statusInfo['id'],$updateData);
	// 						}else{
	// 							//新增
	// 							$addData = array(
	// 									'add_id'         => $addID,
	// 									'variation_id'   => $variationID,
	// 									'upload_type'    => AmazonProductAdd::UPLOAD_TYPE_PRODUCT,
	// 									'publish_type'   => $publishType,
	// 									'upload_status'  => AmazonProductAdd::UPLOAD_STATUS_FAILURE,
	// 									'upload_time'    => date('Y-m-d H:i:s'),							
	// 									'upload_message' => (!empty($desc)) ? json_encode($desc) : '',
	// 							);								
	// 							$amazonProductAddStatusModel->addUploadStatus($addData);
	// 						}
	// 						//刊登上传总状态更新为刊登失败
	// 						AmazonProductAdd::model()->updateProductAddByID($addID, array('status' => AmazonProductAdd::UPLOAD_STATUS_FAILURE, 'upload_finish_time' => date('Y-m-d H:i:s')));
	// 					}
	// 				}else{
	// 					//暂不支持多变体刊登
	// 					$flag = 1;
	// 				}
	// 			}else{
	// 				$flag = 1;
	// 			}
	// 			//排除不可用的ID
	// 			if($flag == 1) unset($addIDList[$key]);
	// 		}
	// 		if($addIDList) $newAddIDs = implode(',',$addIDList);
	// 	}	
	// 	return $newAddIDs;		
	// }

	/**
	 * @desc 获取产品刊登上传出错信息
	 * @param int $variationID 多属性ID
	 * @param int $uploadStatus 上传状态
	 */
	public function getUploadErrMessage($variationID = 0,$uploadStatus = 0){
		if ($variationID == 0) return '';
		$errArr = array();
		//当上传状态失败，才获取错误信息（包括基本产品、库存、价格、图片四接口错误信息）
		if ($uploadStatus == self::UPLOAD_STATUS_FAILURE){
			//查询出错的列表
			$condition = "variation_id = " .$variationID. " AND upload_status = " .self::UPLOAD_STATUS_FAILURE;
			$ret = AmazonProductAddStatus::model()->getUploadStatusListByCondition($condition);
			if ($ret){
				foreach($ret as $val){
					$errArr[$val['upload_type']] = $val['upload_message'];					
				}	
			}
		}
		return $errArr;
	} 

	/**
	 * @desc 特殊产品分类特殊XML组装处理
	 * @param string $productTypeText
	 */
	public function specialProductTypeXML($productTypeText){
		if (empty($productTypeText)) return false;
		$dataXML        = '';
		$topTypeText    = '';	//第一个分类，顶级分类
		$nextTypeText   = '';   //二级分类
		$tmpProductType = array();
		$productData    = array();

		$poduct_arr  = explode('.',$productTypeText);
		if($poduct_arr) $topTypeText = $poduct_arr[0];
		if (isset($poduct_arr[1])) $nextTypeText = $poduct_arr[1];

		//服装
		if ($topTypeText == 'ClothingAccessories'){
              $dataXML = '<ClothingAccessories>
							<VariationData>
								<Parentage>child</Parentage>
								<VariationTheme>Color</VariationTheme>
							</VariationData>
							<ClassificationData>
								<ClothingType>Shirt</ClothingType>
								<Department>Unisex</Department>
							</ClassificationData>				           
			           </ClothingAccessories>';
		}

		//工具
		if ($topTypeText == 'Tools'){
			// $dataXML = '<Tools>
			// 			<GritRating>2</GritRating>		           
			//    </Tools>';
			$dataXML = '<Tools></Tools>';			           
		}

		//玩具
		if ($topTypeText =='Toys'){
			$dataXML = '<' .$topTypeText. '>
							<ProductType>
								<' .$nextTypeText. '>
									<TargetGender>unisex</TargetGender>									
								</' .$nextTypeText. '>
							</ProductType>
							<AgeRecommendation>
								<MinimumManufacturerAgeRecommended unitOfMeasure="years">6</MinimumManufacturerAgeRecommended>
								<MaximumManufacturerAgeRecommended unitOfMeasure="years">99</MaximumManufacturerAgeRecommended>
							</AgeRecommendation>
						</' .$topTypeText. '>';
		}		

		//婴童玩具
		if ($topTypeText =='ToysBaby'){
			$dataXML = '<' .$topTypeText. '>
							<ProductType>' .$nextTypeText. '</ProductType>
							<AgeRecommendation>
								<MinimumManufacturerAgeRecommended unitOfMeasure="years">6</MinimumManufacturerAgeRecommended>
								<MaximumManufacturerAgeRecommended unitOfMeasure="years">99</MaximumManufacturerAgeRecommended>
							</AgeRecommendation>
							<TargetGender>unisex</TargetGender>
						</' .$topTypeText. '>';
		}

		//手表配件
		if ($productTypeText == 'Jewelry.Watch'){
			$dataXML = '<Jewelry>
							<ProductType>
								<Watch>
									<DepartmentName>Unisex</DepartmentName>
									<SubjectCharacter>glass</SubjectCharacter>
								</Watch>
							</ProductType>
						</Jewelry>';
		}

		//美容
		if ($topTypeText == 'Beauty'){
			$dataXML = '<Beauty>
							<ProductType>
								<BeautyMisc>
										<UnitCount unitOfMeasure="ounce">50</UnitCount>
								</BeautyMisc>
							</ProductType>	           
			   			</Beauty>';			           
		}

		//健康
		if ($topTypeText == 'Health'){
			$dataXML = '<Health>
							<ProductType>
								<' .$nextTypeText. '>
										<UnitCount unitOfMeasure="ounce">50</UnitCount>
								</' .$nextTypeText. '>
							</ProductType>	           
			   			</Health>';			           
		}		

		return $dataXML;
	}

	/**
	 * 设置账号ID
	 * @param int $accountID
	 */
	public function setAccountID($accountID) {
		$this->_accountID = $accountID;
		return $this;
	}	

	/**
	 * 设置账号名称
	 * @param string $merchantID
	 */
	public function setMerchantID($merchantID) {
		$this->_merchantID = $merchantID;
		return $this;
	}	

	/**
	 * 设置日志ID
	 * @param string $logID
	 */
	public function setLogID($logID) {
		$this->_logID = $logID;
		return $this;
	}	

	/**
	 * 设置上传类型，接口类型
	 * @param int $uploadType
	 */
	public function setUploadType($uploadType) {
		$this->_upload_type = $uploadType;
		$this->_feed_type = self::getFeedTypeList($uploadType);
		return $this;
	}		

    // ============ S:设置错误消息提示 =================
    public function getErrorMessage(){
    	return $this->_errorMessage;
    }
    
    public function setErrorMessage($errorMsg){
    	$this->_errorMessage = $errorMsg;
    }
    // ============ E:设置错误消息提示 =================	 

    /**
	 * @desc 根据条件获取单条数据
	 * @param unknown $fields
	 * @param unknown $conditions
	 * @param string $param
	 * @return mixed
	 */
	public function getProductAddInfoRow($fields, $conditions, $param = null){
		return $this->getDbConnection()->createCommand()
								->select($fields)
								->from(self::tableName())
								->where($conditions, $param)
								->queryRow();
	}


	/**
	 * @desc 验证XML是否有语法错误
	 * @return string
	 */
	public function getCheckXml($result){
		if (empty($result)) return false;
		$xmlErrorResult = array();
		libxml_use_internal_errors(true);	//开启错误信息
        $xmlRet = simplexml_load_string($result,'SimpleXmlElement', LIBXML_NOERROR | LIBXML_ERR_NONE);
        if ($xmlRet == false){    	
        	$xmlErrorArr = libxml_get_errors();	//获取错误信息
        	if ($xmlErrorArr){
        		if(isset($xmlErrorArr[0]->message)){
        			//级别>=3的错误，才是致命错误
        			if(isset($xmlErrorArr[0]->level) && $xmlErrorArr[0]->level >= 3) $xmlErrorResult = $xmlErrorArr;
        		}
        	}
        	libxml_clear_errors();	//清除XML错误信息
        }	

        return $xmlErrorResult;
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
	 * @desc 获取SKU选中图片（从Common图片库）并转换成一主图多个附图（最多8张）的字符串
	 * 输出字符串，例：/upload/image/main/1/0/2/5/102580.01-1.jpg,/upload/image/assistant/1/0/2/5/102580.01-2.jpg,/upload/image/assistant/1/0/2/5/102580.01-1.jpg
	 * @param string $sku 
	 * @param int $accountID 账号ID
	 * @param string $type 类型：add-新增(默认)；update-修改
	 * @param int $setString 1默认输出字符串，否则数组
	 * @return 
	 */
	public function getSkuSelectedImage($sku, $accountID, $type = 'add', $setString = 1){
		$imageStr       = '';		//输出字符串
		$image_array    = array();	//输出数组			   			   
		$imageType      = array('zt', 'ft');
		$selectedImages = array('zt'=>array(), 'ft'=>array());

   		$imgList = AmazonProductImageAdd::model()->getImageBySku($sku, $accountID, Platform::CODE_AMAZON);	//获取SKU图片
   		if($imgList){
   			foreach ($imgList as $type => $imgs){
   				foreach ($imgs as $img){
   					// $imgkey = substr($img['image_name'], 0, strrpos($img['image_name'], "."));
   					$imgkey = $img['image_name'];
   					if($type == AmazonProductImageAdd::IMAGE_ZT){
   						$selectedImages['zt'][$imgkey] = $imgkey;
   					}else{
   						$selectedImages['ft'][$imgkey] = $imgkey;
   					}
   				}
   			}
   		}

   		//新增下，如果图库没有已选的对应SKU图片，则默认顺序选中产品图片   		
   		if ($type == 'add' && count($selectedImages['zt']) == 0){
			//获取SKU产品图片
            $subImages = AmazonProductImageAdd::getImageUrlFromRestfulBySku($sku);//Product::model()->getImgList($sku,$type);

            foreach($subImages as $type => $images){

				if ($images){
					foreach($images as $k=>$subImg){
						//如果图片名称没有带-，一般是不规范的小图，java服务器也不会上传这类不规范小图，需要屏蔽不显示
						if (strpos($k,'-') > 0){
							$selectedImages[$type][$k] = $k;
						}
					}
				}
			}		   			
   		}
//
//   		//一主图+多个附图
//   		if ($selectedImages){
//   			//亚马逊的主图只支持一张，默认第一张
//   			if(isset($selectedImages['zt'][0])) {
//   				$image_array['zt'][] = $selectedImages['zt'][0];
//	   			$imageStr .=  $selectedImages['zt'][0];
//	   		}
//	   		//亚马逊的附图最大支持8张
//   			if (isset($selectedImages['ft']) && count($selectedImages['ft']) > 0){
//   				$selectedFt = $selectedImages['ft'];
//   				if (count($selectedFt) > 8) $selectedFt = array_slice($selectedFt,0,8);
//   				$image_array['ft'] = $selectedFt;
//   				if ($imageStr){
//   					$imageStr .= ','.implode(',',$selectedFt);
//   				}else{
//   					$imageStr .= implode(',',$selectedFt);
//   				}
//   			}
//   		}

        $imageData = array();
        if ($selectedImages) {

            foreach ($selectedImages as $type => $imageInfo) {
                foreach ($imageInfo as $key => $image) {
                    $imageData[] = $key;

                }
            }
        }
        if ($imageData) {
            return json_encode($imageData);
        }

	}


}