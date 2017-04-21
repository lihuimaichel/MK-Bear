<?php
/**
 * 
 * 产品与账号分配功能
 * @author chenxy
 *
 */
class ProductToAccountRelation extends ProductsModel
{	

	public $MarketersManager_emp_dept;
	public $product_status;
	public $platformCode;
	public $title;
	public $seller_user_id;
	public $is_to_image;
	public $ready_publish_time;
	public $online_time;
	public $platform_code;
	public $account_id;
	public $site;
	public $dept;
	public $category_id;
	public $is_multi;
	public $ismulti;
	
	public $online_status;

	const IS_TO_IMAGE_YES = 1;//已经刊登图片
	const IS_TO_IMAGE_NOT = 0;//未刊登图片
	const ONLINE_STATUS_YES 	  = 1; //刊登状态，1是已刊登
	const ONLINE_STATUS_IMAGE_NOT = 0; //刊登状态，0是未刊登
	const ONLINE_STATUS_FAILURE = 2;//刊登失败
	
	const IS_MULTI_NO = 0;//单品模式
	const IS_MULTI_YES = 1;//多属性模式
	
	/**
	public function __construct($scenario='insert'){
		
		parent::__construct($scenario);
	}
	**/
	
	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'ueb_product';

	} 
    public function rules() {
        $rules = array(         
          
        	
        );      
        return $rules;
    }
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
			'id'                    => Yii::t('system', 'No.'),
            'sku'    		        => Yii::t('product', 'SKU'),
            'platform_code'       	=> Yii::t('product', 'Promotion Platform Code'),
        	'create_time'			=> Yii::t('system', 'Create Time'),
        	'update_time'			=> Yii::t('system', 'Modify Time'),
        	'create_user_id'		=> Yii::t('system', 'Create User'),
        	'update_user_id'		=> Yii::t('system', 'Modify User'),
        	'title'					=> Yii::t('system', 'Title'),
        	'product_title'			=> Yii::t('system', 'Title'),
        	'site'	                => Yii::t('product', 'Platform Site'),
        	'account_id'			=> '账号',
			'seller_user_id'		=> '销售员',
        	'MarketersManager_emp_dept' =>'部门',
        	'dept'                  =>'部门',
            'dept2'                 =>'部门',
        	'is_to_image'			=> '图片上传状态',
        	'ready_publish_time'	=> '预计刊登时间',
        	'online_time'           => '刊登时间',
        	'online_status'			=> '刊登状态',
        	'is_multi'				=> '是否多属性刊登',
        	'ismulti'				=> '是否多属性刊登',
        	'product_status'		=> Yii::t('product', 'Product Status'),
        	'category_id'			=> Yii::t('product', 'Company Category'),
        			
        );
    }
    /**
     * get search info
     */
    public function search() {
    	$sort = new CSort();
    	$sort->attributes = array(
    			'defaultOrder'  =>'id',
    	);
		// $pageSize = isset($_REQUEST['numPerPage']) ? $_REQUEST['numPerPage'] : Yii::app()->params['per_page_num'];
  //   	Yii::app()->session->add(get_class($this).'_criteria', $this->_setCDbCriteria());
  //   	Yii::app()->session->add(get_class($this).'_condition', $criteria->condition);
  //   	Yii::app()->session->add(get_class($this).'_order', $criteria->order);
  //   	Yii::app()->session->add(get_class($this).'_numPerPage', $pageSize);
  //   	$dataProvider =  new CActiveDataProvider(get_class($this), array(
  //   			'criteria' => $this->_setCDbCriteria(),
  //   			'sort' => $sort,
  //   			'pagination' => array(
  //   					'pageSize'      => $pageSize,
  //   					'currentPage'   => isset($_POST['pageNum'])? $_POST['pageNum']-1 : 0,
  //   			),
  //   	));
    	$dataProvider = parent::search(get_class($this), $sort,array(),$this->_setCDbCriteria());
		$data = $this->addition($dataProvider->data); 
		$dataProvider->setData($data);
		
		return $dataProvider;
    }

    private function _setCDbCriteria() {
        //必填
        $platformCode     = isset($_REQUEST['platform_code']) ? trim($_REQUEST['platform_code']) :'EB' ;
        $accountID        = isset($_REQUEST['account_id']) ? trim($_REQUEST['account_id']) :'' ;
        //非必填
        $site             = isset($_REQUEST['site']) ? trim($_REQUEST['site']) : null;
        $sellerUserID     = isset($_REQUEST['seller_user_id']) ? $_REQUEST['seller_user_id'] : '';
        $productStatusStr = isset($_REQUEST['product_status_str']) ? $_REQUEST['product_status_str'] : '';
        $categoryId       = isset($_REQUEST['category_id']) ? $_REQUEST['category_id'] : ''; 
        $sku              = isset($_REQUEST['sku']) ? $_REQUEST['sku'] : '';
        $productStatus    = isset($_REQUEST['product_status']) ? $_REQUEST['product_status'] : null;//产品状态
        $onlineStatus     = isset($_REQUEST['online_status']) ? $_REQUEST['online_status'] : null;
        $dept             = isset($_REQUEST['dept2']) ? $_REQUEST['dept2'] : (isset($_REQUEST['dept']) ? $_REQUEST['dept']:'');//部门
        $isMulti          = isset($_REQUEST['is_multi']) ? $_REQUEST['is_multi'] : null;

    	$criteria = new CDbCriteria();
    	$criteria->select = 'p.*,t.product_status';
    	$criteria->join = ' right join ueb_product_to_account_relation_'.strtoupper($platformCode).' p ON t.sku = p.sku ';

    	$criteria->condition = " 1=1 ";
        //公司分类
    	if( $categoryId ){
    		$criteria->join .= ' left join '.UebModel::model('ProductClassToOnlineClass')->tableName() .' c ON c.online_id = t.online_category_id ';
    		$criteria->condition .= " and  c.category_id = ".$categoryId;
    	}

        //产品状态
    	if( $productStatus || $productStatusStr){
    		if(is_array( $productStatus )) {
    			$criteria->condition .= " and  t.product_status in ( ".implode(',',$productStatus).")";
    		}else{
    			if($productStatus){
    				$criteria->condition .= " and  t.product_status in ( ".$productStatus.")";
    			} else if($productStatusStr) {
                    $criteria->condition .= " and  t.product_status in ( ".$productStatusStr.")";
                }
    		}
    	}

        //部门
    	if($dept){
    		$arr = UebModel::model('User')->getEmpByDept($dept);
    		$sellerArr = array_keys($arr);
    		if(!empty($_REQUEST['seller_user_id'])){
    			$seller = $_REQUEST['seller_user_id'];
    			$inSellerIds =  array_intersect($sellerArr, array($seller));
    			$criteria->addInCondition("p.seller_user_id", $inSellerIds);
    		}elseif($sellerArr){
    			$criteria->addInCondition("p.seller_user_id", $sellerArr);
    		}else{
    			$criteria->addCondition("1=0");
    		}
    	}
    	
    	if( $site ){
    		$criteria->condition .= " and  p.site = '".$site."'";
    	}

    	if($sku){
    		$criteria->condition .= " and  p.sku = '".$sku."'";
    	}

        //是否多属性刊登
        if (!is_null($isMulti) && $isMulti !== '') {
            $criteria->condition .= " and  p.is_multi = {$isMulti} ";
        }

        //刊登状态
        if (!is_null($onlineStatus) && $onlineStatus !== '' && !is_array( $onlineStatus ) ) {
            $criteria->condition .= " and  p.online_status={$onlineStatus} ";
        }

    	return $criteria;
    }
    
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
    	$tmpIsUse  = Yii::app()->request->getParam('is_use');
    	if( $tmpIsUse === 0 ){
    		$tmpIsUse = 0;
    	}
    	if(isset($_REQUEST['platform_code'])){
    		$account = UebModel::model('ProductToAccountRelation')->getPlatformAccountById(trim($_REQUEST['platform_code']));
    		$siteArr = UebModel::model('ProductToAccountRelation')->getOfferSiteByPlatfromCode(trim($_REQUEST['platform_code']));
    	}else{
    		$account = UebModel::model('ProductToAccountRelation')->getPlatformAccountById('EB');
    		$siteArr = UebModel::model('ProductToAccountRelation')->getOfferSiteByPlatfromCode('EB');
    	}
    	$platform = UebModel::model('Platform')->getUseStatusCode();
    	unset($platform['JDGJ']);
    	
        $dept = isset($_REQUEST['dept2']) ? $_REQUEST['dept2'] : (isset($_REQUEST['dept']) ? $_REQUEST['dept']:'');
    	if($dept){
    		$sellerArr = UebModel::model('User')->getEmpByDept($dept);
    	}else{
    		$sellerArr = array();
    	}

    	//超级管理，主管 组长可以查看全部自己部门下面所有销售人员
    	$userId = Yii::app()->user->id;
    	$isSuper = UebModel::model("UserSuperSetting")->checkSuperPrivilegeByUserId($userId);
    	$isAdmin = UebModel::model("AuthAssignment")->checkCurrentUserIsAdminister($userId, '');
    	$isGroup = false;
    	$depList = UebModel::model('Department')->getMarketsDepartmentInfo();
    	if(!$isSuper/*  && !$isAdmin && !$isGroup */){
    		//获取当前用户所属部门ID
    		$depId = UebModel::model("User")->getDepIdById($userId);
    		$depList = array($depId=>$depList[$depId]);
    		$sellerArr = UebModel::model('User')->getEmpByDept($depId);
    	}
    	if(!$isSuper && !$isAdmin){
    		$isGroup = UebModel::model("AuthAssignment")->checkCurrentUserIsGroup($userId, '');
    		if(!$isGroup){
    			if(isset($sellerArr[$userId]))
    				$sellerArr = array($userId=>$sellerArr[$userId]);
    			else
    				$sellerArr = array();
    		}
    	}
    	$isNotAll = $isSuper || $isAdmin||$isGroup ? false : true;
    	$isNotDepAll = $isSuper ? false : true;
    	$sellerId = $isSuper || $isAdmin||$isGroup ? (isset($_REQUEST['seller_user_id']) ? $_REQUEST['seller_user_id'] :'') : $userId;
    	
    	$result = array(
    	
    			array(
    					'name'          => 'sku',
    					'type'          => 'text',
    					'search'        => '=',
    					'htmlOptions'   => array(),
    					'alias'			=> 'p',
    			),
				array(
						'name'          => 'platform_code',
						'type'          => 'dropDownList',
						'search'        => '=',
						'value'         => isset($_REQUEST['platform_code']) ? $_REQUEST['platform_code']:'EB',
						'data'          => $platform ,
						'htmlOptions'   => array('onchange' => 'getAccount(this)'),
						//	'alias'			=> 't',
				),
				array(
						'name'          => 'account_id',
						'type'          => 'dropDownList',
						'search'        => '=',
						//'value'         => $_REQUEST['account_id'] ? $_REQUEST['account_id']:'',
						'data'          => $account ? $account : array(),
						'htmlOptions'   => array(),
				),
				array(
						'name'          => 'site',
						'type'          => 'dropDownList',
						'search'        => '=',
						//'value'         => $_REQUEST['site'] ? $_REQUEST['site']:'',
						'data'          => $siteArr,
						'htmlOptions'   => array(),
				),
    			
    			array(
    					'name'          => 'dept2',
    					'type'          => 'dropDownList',
    					'search'        => '=',
    					'rel'			=> true,
    					'data'          => $depList,
                        'value'         => $dept,
    					'htmlOptions'   => array('onchange' => 'getSellerByEmp(this)'),
    					//	'alias'			=> 'c',
    					'notAll'		=>	$isNotDepAll
    			),
    			array(
    					'name'          => 'seller_user_id',
    					'type'          => 'dropDownList',
    					'search'        => '=',
    					//'rel'			=> true,
    					'value'         => $sellerId,
    					'data'			=> $sellerArr,
    					'htmlOptions'   => array(),
    					'notAll'		=>	$isNotAll
    			),
    	);
    	if(isset($_REQUEST['ispublish'])){
    		$result[]= array(
    				'name'          => 'category_id',
    				'type'          => 'dropDownList',
    				'search'        => '=',
    				'rel'			=> true,
    				'data'          => UebModel::model('ProductClass')->getCat(),
    				'value'         => $_REQUEST['category_id'] ,
    				'htmlOptions'   => array(),
    				'alias'			=> 'c',
    		);
    		$result[] = array(
    				'name'          => 'product_status',
    				'type'          => 'checkBoxList',
    				'rel'			=> true,
    				'data'          => array(Product::STATUS_ON_SALE=>'在售中',Product::STATUS_WAIT_CLEARANCE=>'待清仓'),
    				//'data'          =>Product::getProductStatusConfig(),
    				'clear'         => true,
    				'hide'          => '',
    				'htmlOptions'   => array( 'container' => '', 'separator' => ''),
    				'alias'			=> 't',
    		);
    	}else{
    		$result[] = array(
    				'name'          => 'product_status',
    				'type'          => 'dropDownList',
    				'search'        => '=',
    				'rel'			=> true,
    				'data'          => UebModel::model('Product')->getProductStatusConfig(),
    				'value'         => isset($_REQUEST['product_status']) ? $_REQUEST['product_status'] : Product::STATUS_ON_SALE,
    				'htmlOptions'   => array(),
    				'alias'			=> 't',
    		);
    		
    		$result[] = array(
    				'name'          => 'is_multi',
    				'type'          => 'dropDownList',
    				'search'        => '=',
    				//'rel'			=> true,
    				'data'          => $this->getMultiOption(),
    				'value'			=>	isset($_REQUEST['is_multi']) ? $_REQUEST['is_multi'] : null,
    				'htmlOptions'   => array(),
    				'alias'			=> 'p',
    		);
    		$result[] = array(
    				'name'          => 'online_status',
    				'type'          => 'dropDownList',
    				'search'        => '=',
    				//'rel'			=> true,
    				'data'          => $this->getOnlineStatus(),
    				'value'			=>	isset($_REQUEST['online_status']) ? $_REQUEST['online_status'] : null,
    				'htmlOptions'   => array(),
    				'alias'			=> 'p',
    		);
    	}
    	//$this->addFilterOptions($result);
    	
    	return $result;
    }
   
    public function addFilterOptions(&$result){
    	if(isset($_REQUEST['dept']) && !empty($_REQUEST['dept'])){
    		$arr = UebModel::model('User')->getEmpByDept($_REQUEST['dept']);
    		$sellerArr = array_keys($arr);
    		if($_REQUEST['seller_user_id']){
    			$seller = $_REQUEST['seller_user_id'];
    			$_REQUEST['search']['seller_user_id'] =  array_intersect($sellerArr, array($seller));
    		}else{
    			$_REQUEST['search']['seller_user_id'] = $sellerArr;
    		}
    
    	}
    }
    
    /**
     * order field options
     * @return $array
     */
    public function orderFieldOptions() {
    	return array(
    			'sku','site','platform_code','account_id','id'
    	);
    }
    /**
     * 图片状态
     * @param string $type
     */
    public function getImageStatus($type=null){
    	$config = array(
    		self::IS_TO_IMAGE_YES =>'上传成功',
    		self::IS_TO_IMAGE_NOT =>'待上传',
    	);
    	if(isset($type)){
    		return $config[$type];
    	}else{
    		return $config;
    	}
    	
    }
    /**
     * 刊登状态状态
     * @param string $type
     */
    public function getOnlineStatus($type=null){
    	$config = array(
    		self::ONLINE_STATUS_YES 	  =>'已刊登',
    		self::ONLINE_STATUS_IMAGE_NOT =>'预刊登',
    			self::ONLINE_STATUS_FAILURE =>'刊登失败',
    	);
    	if(isset($type)){
    		return $config[$type];
    	}else{
    		return $config;
    	}
    	 
    }
    
   	public function getMultiOption($isMulti = null){
   		
   		$config = array(
   				self::IS_MULTI_NO 	  =>'单品模式',
   				self::IS_MULTI_YES	  =>'多属性模式',
   		);
   		if(isset($isMulti)){
   			return $config[$isMulti];
   		}else{
   			return $config;
   		}
   	}
   	
	public function addition($data){
		foreach($data as $key=>$value){
			$title = UebModel::model('Productdesc')->getTitleBySku($value->sku);
			$data[$key]->title = isset($title['Chinese']) ? $title['Chinese'] : '';
			$data[$key]->ismulti = $this->getMultiOption($value->is_multi);
		}
		return $data;
	}
    /**
     * 根据平台和查询条件得到记录
     * @param string $platformCode
     * @param unknown $where
     * @return boolean|mixed
     */
	public function getRecordByselect($platformCode='EB',$where) {
		if(empty($platformCode)){
			return false;
		}
		$result = $this->getDbConnection()->createCommand()
		->select( '*' )
		->from("ueb_product.ueb_product_to_account_relation_".strtoupper($platformCode))
		->where($where)
		->queryRow();
		//echo $result->text;
		//echo '<pre>';print_r($result);
		return $result;

	}

	/**
	 * 更新数据
	 * @param unknown $platformCode 平台
	 * @param unknown $where 条件
	 * @param unknown $data 需要更新的数据
	 */
    public function setUpdateBySelect($platformCode,$where,$data){
    	$set ='';
    	foreach($data as $key=>$value){
    		$set .= $key.'='."'".$value."',";
    	}
    	$set = trim($set,',');		
    	$sql = "update ueb_product.ueb_product_to_account_relation_".strtoupper($platformCode)." set $set where $where";
    	return $this->getDbConnection()->createCommand($sql)->execute();
    }
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/products/producttoaccountrelation/list');
    } 
    public function batchInsertAll($platformCode,$data){
    	if(empty($platformCode)){
    		return false;
    	}
    	return UebModel::model('ProductToAccountRelation')->batchInsert("ueb_product_to_account_relation_".strtoupper($platformCode),array_keys($data[0]),$data);
    	 
    }

    /**
     * 根据平台，销售员ID，站点，账号，产品状态得到待刊登SKU数量
     * @param unknown $platformCode
     * @param unknown $seller_user_id
     * @param unknown $classId
     * @param unknown $site
     * @param unknown $accountId
     * @param unknown $productStatus
     */
    public function getUserReadyPublishCount($platformCode,$seller_user_id,$category_id,$site,$accountId,$productStatus){
    	$condition = '';
    	if($site != ''){
    		$condition .= " and p.site = '{$site}' ";
    	}
    	if($productStatus){
    		$condition .= " and t.product_status in ($productStatus) ";
    	}
    	$command = $this->getDbConnection()->createCommand()
        	->select( 'count(p.sku) as sku_count' )
            ->from("ueb_product_to_account_relation_".strtoupper($platformCode)." p" )
        	->leftJoin( UebModel::model('Product')->tableName() . ' t', 't.sku = p.sku')
        	->leftJoin( UebModel::model('ProductClassToOnlineClass')->tableName() . ' c', 'c.online_id = t.online_category_id')
        	->where("c.category_id = $category_id  and p.online_status = 0 and p.seller_user_id = $seller_user_id  and  p.platform_code = '{$platformCode}' and p.account_id = '{$accountId}'  " .$condition);
        //echo '<pre>';print_r($command->getText());die;
        $result = $command->queryAll();
    	
    	$skuCount = 0;
    	if($result){
    		foreach($result as $val){
    			$skuCount += $val['sku_count'];
    		}
    	}
    	return $skuCount;
    }
    
    
    // ==================== oms sku刊登状态迁移 =====================
    
    /**
     * 根据平台和查询条件得到多条记录
     * @param string $platformCode
     * @param unknown $where
     * @return boolean|mixed
     */
    public function getRecordByselectAll($platformCode='EB',$where, $limit = null) {
    	if(empty($platformCode)){
    		return false;
    	}
    	$command = $this->getDbConnection()->createCommand()
    	->select( '*' )
    	->from("ueb_product.ueb_product_to_account_relation_".strtoupper($platformCode))
    	->where($where);
    	
    	if($limit>0){
    		$command->limit($limit);
    	}
    	$result = $command->queryAll();
    	return $result;
    
    }
    
    public function getPlatformAccountById($platform,$account_id = null, $sellerId = null){
    	$arr =array();
    	$isFilterAccount = false;
    	if($account_id == null && $sellerId){
    		$isFilterAccount = true;
    		$sellerId = Yii::app()->request->getParam('seller_id');
    		$productToSellerRelationModel = new ProductToSellerRelation();
    		$accountIds = $productToSellerRelationModel->getAccountIdListByPlatformAndSellerId($platform, $sellerId);
    	}
    	switch($platform){
    		case Platform::CODE_EBAY:
    			$ebay = UebModel::model('EbayAccount')->findAll(array('order'=>'short_name asc'));
    			foreach($ebay as $key =>$val){
    				if($isFilterAccount && !in_array($val['id'], $accountIds)){
    					continue;
    				}
    				$arr[$val['id']] = $val['short_name'];
    			}
    			if($account_id){
    				return $arr[$account_id];
    			}else{
    				return $arr;
    			}
    			break;
    		case Platform::CODE_NEWFROG:
    			return false;
    			break;
    		case Platform::CODE_YESFOR:
    			return false;
    			break;
    		case Platform::CODE_ALIEXPRESS:
    			$aliexpress = UebModel::model('AliexpressAccount')->findAll(array('order'=>'short_name asc'));
    			foreach($aliexpress as $key => $val){
    				if($isFilterAccount && !in_array($val['id'], $accountIds)){
    					continue;
    				}
    				$arr[$val['id']] = $val['short_name'];
    			}
    			if($account_id){
    				return $arr[$account_id];
    			}else{
    				return $arr;
    			}
    			break;
    		case Platform::CODE_DUNHUANG:
    			return false;
    			break;
    		case Platform::CODE_NEWFROG:
    			return false;
    			break;
    		case Platform::CODE_ALIBABA:
    			return false;
    			break;
    		case Platform::CODE_KF:
    			$wish = UebModel::model('WishAccount')->getAbleAccountList();
    			foreach($wish as $key => $val){
    				if($isFilterAccount && !in_array($val['id'], $accountIds)){
    					continue;
    				}
    				$arr[$val['id']] = $val['account_name'];
    			}
    			if($account_id){
    				return $arr[$account_id];
    			}else{
    				return $arr;
    			}
    			return false;
    			break;
    		case Platform::CODE_AMAZON:
    			$amoazon = UebModel::model('AmazonAccount')->findAll("status=1");
    			foreach($amoazon as $key => $val){
    				if($isFilterAccount && !in_array($val['id'], $accountIds)){
    					continue;
    				}
    				$arr[$val['id']] = $val['account_name'];
    			}
    			if($account_id){
    				return $arr[$account_id];
    			}else{
    				return $arr;
    			}
    			break;
    		case Platform::CODE_NEWEGG:
    			return false;
    			break;
    		case Platform::CODE_BELLABUY:
    			return false;
    			break;
    		case Platform::CODE_ECOOLBUY:
    			return false;
    			break;
    		case Platform::CODE_JD:
    			return false;
    			break;
    		case Platform::CODE_PM:
    			return false;
    			break;
    		case Platform::CODE_SHOPEE:
    			return false;
    			break;
    		case Platform::CODE_JOOM:
    			$lazada = UebModel::model('JoomAccount')->findAll(array('order'=>'account_name asc'));
    			foreach($lazada as $key => $val){
    				if($isFilterAccount && !in_array($val['id'], $accountIds)){
    					continue;
    				}
    				$arr[$val['id']] = $val['account_name'];
    			}
    			if($account_id){
    				return $arr[$account_id];
    			}else{
    				return $arr;
    			}
    			break;
    		case Platform::CODE_LAZADA:
    			$lazada = UebModel::model('LazadaAccount')->findAll(array('order'=>'short_name asc'));
    			foreach($lazada as $key => $val){
    				if($isFilterAccount && !in_array($val['old_account_id'], $accountIds)){
    					continue;
    				}
    				$arr[$val['old_account_id']] = $val['short_name'];
    			}
    			if($account_id){
    				return $arr[$account_id];
    			}else{
    				return $arr;
    			}
    			break;
    	}
    }
    
    
    /**
     * 获取去除ALL后的各个平台站点
     */
    public function getOfferSiteByPlatfromCode($platform){
    	$result = self::getSiteByPlatfromCode($platform);
    	unset($result['ALL']);
    	return $result;
    }
    
    /**
     * 平台个站点
     */
    public function getSiteByPlatfromCode($platform){
    	switch ($platform){
    		case Platform::CODE_EBAY:
    			return array(
    			'ALL'		=> '全部',
    			'US' 		=> 'US',
    			'UK' 		=> 'UK',
    			'Germany' 	=> 'Germany',
    			'France' 	=> 'France',
    			'Spain'		=> 'Spain',
    			'Canada' 	=> 'Canada',
    			'Australia' => 'Australia',
    			'eBayMotors' => 'eBayMotors',
    			'Italy'      => 'Italy',
    			);
    			break;
    		case Platform::CODE_AMAZON:
    			return array(
    			'ALL'	=> '全部',
    			'us' => 'us',
    			'uk' => 'uk',
    			'de' => 'de',
    			'fr' => 'fr',
    			'es' => 'es',
    			'ca' => 'ca',
    			'jp' => 'jp',
    			'it' => 'it',
    			'mx' => 'mx',
    			);
    			break;
    		case Platform::CODE_LAZADA:
    			return array(
    			'ALL'=> '全部',
    			'my' => 'my',
    			'id' => 'id',
    			'th' => 'th',
    			'ph' => 'ph',
    			'sg' => 'sg',
    			'vn' => 'vn'
    					);
    					break;
    		case Platform::CODE_ALIEXPRESS:
    			return array(
    			'ali' => 'ali',
    			);
    			break;
    		case Platform::CODE_WISH:
    			return array(
    			'kf' => 'kf'
    					);
    			break;
    		case Platform::CODE_JOOM:
    			return array(
    				'jm' => 'jm'
    			);
    			break;
    		case Platform::CODE_NEWFROG:
    			return array(
    			'nf' => 'nf'
    					);
    					break;
    					break;
    		case Platform::CODE_JD:
    			return array(
    			'jdgj' => 'jdgj'
    					);
    					break;
    	}
    }
    
    /**
     * @desc 站名转换站点
     * @param unknown $platformCode
     * @param unknown $site
     * @return NULL|Ambigous <NULL, number>
     */
    public function getSiteIDFromSite($platformCode, $site){
    	switch ($platformCode){
    		case Platform::CODE_EBAY:
    			return EbaySite::model()->getSiteIdByName($site);
    			break;
    		case Platform::CODE_AMAZON:
    			return null;
    			break;
    		case Platform::CODE_LAZADA:
    			$sites =  array(
			    			'ALL'=> null,
			    			'my' => 1,
			    			'sg' => 2,
			    			'id' => 3,
			    			'th' => 4,
			    			'ph' => 5,
			    			'vn' => 6
    					);
    			return isset($sites[$site]) ? $sites[$site] : null;
    			break;
    		case Platform::CODE_ALIEXPRESS:
    			return null;
    			break;
    		case Platform::CODE_KF:
    			return null;
    			break;
    		case Platform::CODE_NEWFROG:
    			return null;
    			break;
    		case Platform::CODE_JD:
    			return null;
    			break;
    	}
    }
    /**
     * @desc 获取站点站名映射
     * @param unknown $platformCode
     * @param unknown $siteID
     * @return Ambigous <NULL, string>
     */
    public function getSiteNameMappingSiteIDWithPlatform($platformCode, $siteID){
    	$siteName = null;
    	switch ($platformCode){
    		case Platform::CODE_EBAY:
    			$ebaySiteModel = new EbaySite();
    			$siteName = $ebaySiteModel->getSiteName($siteID);
    			if(empty($siteName)){
    				$siteName = null;
    			}
    			break;
    		case Platform::CODE_LAZADA:
    			$siteShortNameList = LazadaSite::$siteShortNameList;
    			$siteName = isset($siteShortNameList[$siteID])?$siteShortNameList[$siteID]:'';
    			break;
    		
    	}
    	return empty($siteName) ? $siteID : $siteName;
    }
    // ==================== oms sku刊登状态迁移 =====================
    
    public function getProductToAccountRelationTableByPlatform($platformCode){
    	$tableName = "";
    	switch ($platformCode){
    		case Platform::CODE_ALIEXPRESS:
    			$tableName = "ueb_product_to_account_relation_ALI";
    			break;
    		case Platform::CODE_AMAZON:
    			$tableName = "ueb_product_to_account_relation_AMAZON";
    			break;
    		case Platform::CODE_EBAY:
    			$tableName = "ueb_product_to_account_relation_EB";
    			break;
    		case Platform::CODE_JD:
    			$tableName = "ueb_product_to_account_relation_JD";
    			break;
    		case Platform::CODE_KF:
    			$tableName = "ueb_product_to_account_relation_KF";
    			break;
    		case Platform::CODE_LAZADA:
    			$tableName = "ueb_product_to_account_relation_LAZADA";
    			break;
    		case Platform::CODE_JOOM:
    			$tableName = "ueb_product_to_account_relation_JM";
    			break;
    	}
    	return $tableName;
    }
    
}