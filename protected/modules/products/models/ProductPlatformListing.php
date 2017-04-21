<?php 
/**
 * sku 在各平台上线的状态（ProductPlatform统计的数据变更为这个model）
 * @author 陈先钰
 *
 */
class ProductPlatformListing extends ProductsFromModel 
{	
	
	const ONLINE_STATUS=1; //已刊登
	const UMLINE_STATUS=2; //未刊登
	const FAILURE_STATUS = 3; //刊登失败
	public $product_status;
	public $category_id;
	public $product_title;
	public $product_id;
	public $seller_user_id;
    static $noAccountPlatform = array(Platform::CODE_NEWEGG,Platform::CODE_NEWFROG,Platform::CODE_PM,Platform::CODE_YESFOR,Platform::CODE_ECOOLBUY,Platform::CODE_JD);//无账号的平台
    static $mutiSitePlatform = array(Platform::CODE_EBAY,Platform::CODE_AMAZON,Platform::CODE_LAZADA,Platform::CODE_SHOPEE);//分站点的平台

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
		return 'ueb_product_platform_listing';
	}
    
    public function rules() {
       return  array();        
       
    }
    
    /**
     * @return array relational rules.
     */
    public function relations()
    {
    	return array();
    	
    }
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        $attr = array(
			'id'                    => Yii::t('system', 'No.'),
			'product_status'        => Yii::t('system', 'Status'),
        	'product_category_name' => Yii::t('product','Company category'),
        	'category_id' 			=> Yii::t('product','Company category'),
        	'platform_code'       	=> Yii::t('product', 'Promotion Platform Code'),
        	'sku'                   => Yii::t('product', 'Sku'),
        	'title'					=> Yii::t('system', 'Title'),
        	'product_title'			=> Yii::t('system', 'Title'),
        	'category_id'           => Yii::t('product', 'Company Category'),
        	'product_status'        => Yii::t('product', 'Product Status'),
        	'site'	                => Yii::t('product', 'Platform Site'),
        	'account_id'			=> '账号',
        	'seller_user_id'        => '负责人姓名',
        	'online_status'         => '刊登状态',  
            'is_multi'              => '多属性刊登(批量待刊登选项)',  	
        );

        return $attr;
    }
    /**
     * get search info
     */
    public function search() {	
    	$sort = new CSort();
    	$sort->attributes = array(
    			'defaultOrder'  => 'id',
    	);        	
    	$criteria = $this->_setCDbCriteria();
    	$pageSize = isset($_REQUEST['numPerPage']) ? $_REQUEST['numPerPage'] : Yii::app()->params['per_page_num'];
    	Yii::app()->session->add(get_class($this).'_criteria', $criteria);
    	Yii::app()->session->add(get_class($this).'_condition', $criteria->condition);
    	Yii::app()->session->add(get_class($this).'_order', $criteria->order);
    	Yii::app()->session->add(get_class($this).'_numPerPage', $pageSize);
    	$dataProvider =  new CActiveDataProvider(get_class($this), array(
    			'criteria' => $this->_setCDbCriteria(),
    			'sort' => $sort,
    			'pagination' => array(
    					'pageSize'      => $pageSize,
    					'currentPage'   => isset($_POST['pageNum'])? $_POST['pageNum']-1 : 0,
    			),
    	));
    	$data = $this->addition($dataProvider->data);
    	$dataProvider->setData($data);
    	return $dataProvider;
    }
     
    private function _setCDbCriteria() {
        //必填
        $platformCode     = isset($_REQUEST['platform_code']) ? trim($_REQUEST['platform_code']) :'' ;
        $accountID        = isset($_REQUEST['account_id']) ? trim($_REQUEST['account_id']) :'' ;
        //非必填
        $site             = isset($_REQUEST['site']) ? trim($_REQUEST['site']) : null;
        $sellerUserID     = isset($_REQUEST['seller_user_id']) ? $_REQUEST['seller_user_id'] : '';
        $productStatusStr = isset($_REQUEST['product_status_str']) ? $_REQUEST['product_status_str'] : '';
        $categoryId       = isset($_REQUEST['category_id']) ? $_REQUEST['category_id'] : ''; 
        $sku              = isset($_REQUEST['sku']) ? $_REQUEST['sku'] : '';
        $productStatus    = isset($_REQUEST['product_status']) ? $_REQUEST['product_status'] : null;//产品状态
        $ispublish        = isset($_REQUEST['ispublish']) ? $_REQUEST['ispublish'] : '';
        $onlineStatus     = isset($_REQUEST['online_status']) ? $_REQUEST['online_status'] : null;//上下架状态

        $noAccountPlatform = ProductPlatformListing::$noAccountPlatform;//无账号的平台
        $mutiSitePlatform = ProductPlatformListing::$mutiSitePlatform;//多站点的平台
		$criteria = new CDbCriteria();
        if ($platformCode == '' || (!in_array($platformCode,$noAccountPlatform) && $accountID == '') ) {
            $criteria->addCondition("1=0");
        } else {
            $criteria->distinct = true;
            $criteria->select = 'p.sku,t.id,d.category_id,p.product_status,p.id as product_id,t.platform_code,t.site,t.account_id,s.seller_user_id';

            //刊登状态
            $con = '';
            if ( $onlineStatus || $ispublish) {
                if ($ispublish == 2 || ( count($onlineStatus) ==1 && $onlineStatus[0] == 2) ) {
                    $con = ' and t.online_status !=1 ';
                }
            }

            $criteria->join = ' RIGHT JOIN ueb_product p ON t.sku = p.sku '.$con ;

            $criteria->join .= ' LEFT JOIN ueb_product_class_to_online_class d ON d.online_id = p.online_category_id ';
            
            $criteria->join .= ' LEFT JOIN '. ProductToAccountSellerPlatform::getRelationTableName($platformCode,$accountID) . ' s on s.sku = p.sku ';

            //平台
            $criteria->condition = ' t.platform_code ="'.$platformCode.'"';

            //账号
            if($accountID != ''){
                $criteria->condition .= ' and t.account_id ='.$accountID;
            }

            //站点
            if( $site !== '' ){//US站非0
                $criteria->condition .= ' and t.site ="'. $site .'"';
            }

            //公司分类
            $criteria->condition .= " and  p.product_is_multi != 2 ";
            if( $categoryId ){
                //$criteria->condition .= " and  d.category_id = ".$categoryId;
				$criteria->condition .= " and  d.category_id IN(".$categoryId.")";
            }

            //产品状态
            if( !empty($productStatus) || $productStatusStr != '' ){
                $statusStr = $productStatusStr != '' ? $productStatusStr : (!empty($productStatus) ? implode(',', $productStatus) : '');
                $criteria->condition .= ' and p.product_status in('.$statusStr.')';
            } else {
                $criteria->condition .= ' and p.product_status in(4,6)';
            }

            //刊登状态
            if ( $onlineStatus || $ispublish) {
                if ($ispublish == 1 || ( count($onlineStatus) ==1 && $onlineStatus[0] == 1) ) {
                    $criteria->condition .= ' and t.online_status=1 ';
                }
            }

            //sku
            if($sku){
                $criteria->condition .= " and p.sku ='{$sku}' ";
            }

            // //负责人
            if($sellerUserID){
                $criteria->condition .= " and  s.seller_user_id = {$sellerUserID} ";
            }
        }

        // print_r($criteria);
 		return $criteria;
    }

    /**
     * addition information
     *
     * @param type $dataProvider
     */
    public function addition($data) {
    	foreach($data as $key=>$val){
    		$productTitle	= UebModel::model('Productdesc')->getDescriptionInfoByProductIdAndLanguageCode($val->product_id);
    		$data[$key]->product_title = $productTitle['title'] ? $productTitle['title'] : '';
    	}
    	 
    	return $data;
    }
    /**
     * filter search options
     * @return type
     */
	public function filterOptions() {
// 	    if($_REQUEST['platform_code']){
//     		$arr = UebModel::model('Order')->getPlatformAccount(trim($_REQUEST['platform_code']));
//     	}else{
//     		$arr = array();
//     	}
    	if($_REQUEST['platform_code']){
    		$account = UebModel::model('ProductToAccountRelation')->getPlatformAccountById(trim($_REQUEST['platform_code']));
    		$siteArr = UebModel::model('ProductToAccountRelation')->getOfferSiteByPlatfromCode(trim($_REQUEST['platform_code']));
    	}else{
    		$account = UebModel::model('ProductToAccountRelation')->getPlatformAccountById('EB');
    		$siteArr = UebModel::model('ProductToAccountRelation')->getOfferSiteByPlatfromCode('EB');
    	}
    	
    	$sellerUserList = UebModel::model("ProductMarketersManager")->getSellerUserIdAndName();
    	
    	//超级管理，主管 组长可以查看全部自己部门下面所有销售人员
    	$userId = Yii::app()->user->id;
    	$depId = UebModel::model("User")->getDepIdById($userId);
    	$isSuper = UebModel::model("UserSuperSetting")->checkSuperPrivilegeByUserId($userId);
    	$isAdmin = UebModel::model("AuthAssignment")->checkCurrentUserIsAdminister($userId, '');
    	
    	$isGroup = false;
    	if(!$isSuper && !$isAdmin){
    		$isGroup = UebModel::model("AuthAssignment")->checkCurrentUserIsGroup($userId, '');
    		if(!$isGroup){
    			if(isset($sellerUserList[$userId]))
    				$sellerUserList = array($userId=>$sellerUserList[$userId]);
    			else
    				$sellerUserList = array();
    		}
    	}
		$result = array(
				array(
						'name'          => 'sku',
						'type'          => 'text',
						'search'        => '=',
						'alias'			=> 't',
				),
				array(
						'name'          => 'category_id',
						'type'          => 'dropDownList',
						'search'        => '=',
						'value'         => $_REQUEST['category_id'] ? $_REQUEST['category_id']:'',
						'data'          => UebModel::model('ProductClass')->getCat(),
						'htmlOptions'   => array(),
						'alias'			=> 'd',
				),
				array(
						'name'          => 'platform_code',
						'type'          => 'dropDownList',
						'search'        => '=',
						'value'         => $_REQUEST['platform_code'] ? $_REQUEST['platform_code']:'',
						'data'          =>  UebModel::model('Platform')->getUseStatusCode(),
						'htmlOptions'   => array('onchange' => 'getAccount(this)'),
						'alias'			=> 't',
				),
				array(
						'name'          => 'account_id',
						'type'          => 'dropDownList',
						'search'        => '=',
						//'value'         => $_REQUEST['account_id'] ? $_REQUEST['account_id']:'',
						'data'          => $account,
						'htmlOptions'   => array(),
						'alias'			=> 't',
				),
				array(
						'name'          => 'site',
						'type'          => 'dropDownList',
						'search'        => '=',
						//'value'         => $_REQUEST['site'] ? $_REQUEST['site']:'',
						'data'          => $siteArr,
						'htmlOptions'   => array(),
						'alias'			=> 't',
				),
				array(
						'name'          => 'seller_user_id',
						'type'          => 'dropDownList',
						'search'        => '=',
						'data'          => $sellerUserList,
						'value'         => $_REQUEST['seller_user_id'] ? $_REQUEST['seller_user_id']:'',
						'htmlOptions'   => array(),
						'alias'			=> 'm',
						'notAll'		=>	$isSuper || $isAdmin||$isGroup ? false : true
				),
				array(
						'name'          => 'product_status',
						'type'          => 'checkBoxList',
						'rel'			=> true,
						'data'          => array(Product::STATUS_ON_SALE=>'在售中',Product::STATUS_WAIT_CLEARANCE=>'待清仓'),
						//'data'          =>Product::getProductStatusConfig(),
						'clear'         => true,
						'hide'          => '',
						'htmlOptions'   => array( 'container' => '', 'separator' => ''),
						'alias'			=> 'p',
				),
 				array(
						'name'          => 'online_status',
						'type'          => 'checkBoxList',
						'rel'			=> true,
						'data'          => array(self::ONLINE_STATUS=>'已刊登',self::UMLINE_STATUS=>'未刊登'),
						
						'clear'         => true,
						'hide'          => '',
						'htmlOptions'   => array( 'container' => '', 'separator' => ''),
						'alias'			=> 't',
				), 
				array(
						'name'          => 'is_multi',
						'type'          => 'checkBoxList',
						'rel'			=> true,
						'data'          => array(self::ONLINE_STATUS=>'使用多属性组合刊登'),
						'clear'         => true,
						'hide'          => '',
						'htmlOptions'   => array('id'=>'productPlatfromListingisMulti'),
						'alias'			=> 't',
				),
		);
		
		
		return $result;
	}
    /**
     * 根据SKU得到刊登数据
     * @param unknown $arrSku
     * @return multitype:
     */
    public function getSkuPlatformBySku($arrSku,$flatformCodeArr=null){
    	$where = '';
    	if($flatformCodeArr){
    		$where = " and platform_code in ("."'" . str_replace(",", "','", implode(',', $flatformCodeArr)) . "'".")";
    	}
    	$result = $this->getDbConnection()->createCommand()
		->select( '*' )
		->from( self::tableName() )
		->where(array('in','sku',$arrSku))
		->andWhere("online_status = '".self::ONLINE_STATUS."' $where")
	//	->group('platform_code')
		->queryAll();
    	$list=array();
    	foreach($result as $val){
    		if(!in_array($val['platform_code'],$list[$val['sku']])){
    			$list[$val['sku']][]=$val['platform_code'];
    		}
    		    		
    	}
    	return $list;
    }


    /**
     * @param $platformCode
     * @param $arr_sku
     * @param $site
     * @param $account_id
     *
     * 获取 sku 的数量
     */
    public function getSkuNum($platformCode, $arr_sku, $site, $account_id)
    {
        $status = self::ONLINE_STATUS;
        $command = $this->getDbConnection()->createCommand()
            ->select('COUNT(DISTINCT(sku)) AS total')
            ->from(self::tableName())
            ->where(array('in', 'sku', $arr_sku))
            ->andWhere("online_status = '{$status}'")
            ->andWhere("platform_code = '{$platformCode}' AND site = '{$site}' AND account_id = '{$account_id}'");
        $row = $command->queryRow();
        //echo $command->getText();
        return $row['total'];
    }

    /**
     * 根据公司分类和平台得到SKU的刊登数量
     * @param unknown $category_id
     * @param unknown $platformCode
     * $productStatus 产品状态为字符串
     * @return unknown
     */
    public function getSkuPlatformByPlatformCodeAndClassId($category_id,$platformCode,$productStatus=null){
    	$where = '';
    	if($productStatus){
    		$where .= "  and d.product_status in ($productStatus) ";
    	}
    	$result = $this->getDbConnection()->createCommand()
    	//->select( 'count(p.id) as sku_count,c.category_id,p.platform_code' )
    	->select( 'count(distinct(p.sku)) as sku_count,c.category_id,p.platform_code' )
    	->from( self::tableName() .' AS p')
    	->leftJoin( UebModel::model('Product')->tableName() . ' d', 'd.sku=p.sku')
    	->leftJoin( UebModel::model('ProductClassToOnlineClass')->tableName() . ' c', 'c.online_id = d.online_category_id')
    	->where("c.category_id = $category_id and d.product_is_multi != 2  ".$where)
    	->andWhere("p.platform_code = '{$platformCode}' and p.online_status = '".self::ONLINE_STATUS."'")
    	->queryRow();
    	return $result['sku_count'];
    }

    /**
     * 根据刊登人员ID，公司分类和平台,账号，站点，刊登状态得到SKU的数量
     * （该方法作废）
     * $sc_name_id 刊登人员ID
     * @param unknown $category_id 分类
     * @param unknown $platformCode 平台
     * @param string $site 站点 
     * @param unknown $accountId 账号
     * @param string $productStatus 产品状态
     * @return mixed
     */
    public function getUserpublishCount($sc_name_id,$category_id,$platformCode,$site =null,$accountId,$productStatus=null){
    	$where = '';
    	if($site){
    		$where .= "  and p.site = '{$site}' ";
    	}
    	if($productStatus){
    		$where .= "  and d.product_status in ($productStatus) ";
    	}
    	$result = $this->getDbConnection()->createCommand()
    	->select( 'count(distinct(p.sku)) as sku_count,c.category_id,p.platform_code' )
    	->from( self::tableName() .' AS p')
    	->leftJoin( UebModel::model('Product')->tableName() . ' d', 'd.sku=p.sku')
    	->leftJoin( UebModel::model('ProductClassToOnlineClass')->tableName() . ' c', 'c.online_id = d.online_category_id')
    	->where("c.category_id = $category_id  and p.sc_name_id = $sc_name_id  and  p.platform_code = '{$platformCode}' and p.account_id = '{$accountId}' and  p.online_status = '".self::ONLINE_STATUS."' " .$where)
    	->queryRow();
    	return $result['sku_count'];
    }
    /**
     * 查询未分配公司分类的SKU刊登数量
     * @param unknown $category_id
     * @param unknown $platformCode
     * $productStatus 产品状态为字符串
     * @return unknown
     */
    public function getSkuPlatformByPlatformCodeAndNotClassId($platformCode,$productStatus=null){
    	$where = '';
    	if($productStatus){
    		$where .= "  and d.product_status in ($productStatus) ";
    	}
    	$result = $this->getDbConnection()->createCommand()
        	->select( 'count(distinct(p.sku)) as sku_count,p.platform_code,c.category_id' )
        	->from( self::tableName() .' AS p')
        	->leftJoin( UebModel::model('Product')->tableName() . ' d', 'd.sku=p.sku')
        	->leftJoin( UebModel::model('ProductClassToOnlineClass')->tableName() . ' c', 'c.online_id = d.online_category_id')
        	->where("c.category_id is null and d.product_is_multi != 2  ".$where)
        	->andWhere("p.platform_code = '{$platformCode}' and p.online_status = '".self::ONLINE_STATUS."'")
        	->queryRow();
    	return $result['sku_count'];
    }

    /**
     * 根据公司分类，销售员，平台得到SKU的刊登数量
     * @param unknown $category_id
     * @param unknown $platformCode
     * $productStatus 产品状态为字符串
     * $sellerId 销售员ID
     * @return unknown
     */
    public function getSkuPlatformByPlatformCodeAndClassIdAndSeller($category_id,$platformCode,$productStatus=null,$sellerId){
    	$where = '';
    	if($productStatus){
    		$where .= "  and d.product_status in ($productStatus) ";
    	}
    	$result = $this->getDbConnection()->createCommand()
    	//->select( 'count(p.id) as sku_count,c.category_id,p.platform_code' )
    	->select( 'count(distinct(p.sku)) as sku_count,c.category_id,p.platform_code' )
    	->from( self::tableName() .' AS p')
    	->leftJoin( UebModel::model('Product')->tableName() . ' d', 'd.sku=p.sku')
    	->leftJoin( UebModel::model('ProductToSellerRelation')->tableName() . ' r', 'r.sku=p.sku')
    	->leftJoin( UebModel::model('ProductClassToOnlineClass')->tableName() . ' c', 'c.online_id = d.online_category_id')
    	->where("c.category_id = $category_id and d.product_is_multi != 2  ".$where)
    	->andWhere("p.platform_code = '{$platformCode}' and p.online_status = '".self::ONLINE_STATUS."' and r.seller_id = '{$sellerId}'")
    	->queryRow();
    	return $result['sku_count'];
    }

    
    /**
     * 获取所有上线出货平台代码、sku、站点、账号ID
     * @param 
     */
    public function getPlatformCodeSkuSiteAccountId($type){
        $lastuptime = date("Y-m-d",strtotime("-1 day")).' 00:00:00';
        $where = '';
        if(!empty($type)){
            $where .= "  and lastuptime >='{$lastuptime}'";
        }
        $result = $this->getDbConnection()->createCommand()
        ->select( 'platform_code,sku,site,account_id' )
        ->from( self::tableName())
        ->where("online_status = '".self::ONLINE_STATUS. "'".$where)
        ->queryAll();
        
        return $result;
    }
    
    
}