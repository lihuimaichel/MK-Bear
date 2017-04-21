<?php
/**
 * @author Liutf
 * 2016-05-03
 */
class ProductMarketersManager extends ProductsModel
{	
	public $emp_dept;
	public $emp_no;
	public $emp_id;
	public $class_id = 0;
	public $user_full_name;
	public $class_name;
	public $detail = array();
	
	const IS_DEL_DEFAULT = 0; //未删除
	const IS_DEL_DELETED = 1; //删除
	
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
		return 'ueb_seller_user_to_account_site';
	} 
	
	/**
	 * @return array() column name
	 */
	public function columnName() {
		return MHelper::getColumnsArrByTableName(self::tableName());
	}
	
    public function rules() {
        $rules = array(
            array('platform_code,account_id,seller_user_id', 'required'), 
        	array('platform_code,account_id,site,seller_user_id,is_del', 'safe'),
        ); 
        return $rules;
    }
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(//负责人姓名,部门,产品类别,创建时间,操作
        	'seller_user_id'       	=> Yii::t('users', '负责人姓名'),
        	'emp_dept'				=> Yii::t('system', '部门'),
        	'site'					=> Yii::t('system', '站点'),
        	'class_id'				=> Yii::t('products', 'Product category'),
        	
            'create_time'			=> Yii::t('system', 'Create Time'),
        	'create_user_id'		=> Yii::t('system', '添加人'),
        	'modify_user_id'		=> Yii::t('system', '修改人'),
        	'modify_time'           => Yii::t('system', 'Modify Time'),
        	'sku'					=> Yii::t('system', 'SKU'),
        	'platform_code'			=> Yii::t('system', '平台'),
        	'account_id'			=> Yii::t('system', '帐号'),
        	
        	'category_id'			=> Yii::t('system', '分类'),
        	'product_category'		=> Yii::t('products', 'Product category'),
        	'user_full_name'		=> Yii::t('users', '负责人姓名'),
        	'emp_no'				=> Yii::t('users', '负责人姓名'),
//         	'emp_id'				=> Yii::t('products', ''),
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
    	$dataProvider = parent::search(get_class($this), $sort,array(),$this->_setCDbCriteria());
    	$data = $this->addition($dataProvider->data);
    	$dataProvider->setData($data);
    	return $dataProvider;
    }
    
   	public function addition($data){

	   	foreach ($data as $key => $val) {
	   		$data[$key]->platform_code 	= $this->getAllPlatormCodeBySellerUserId($val->seller_user_id);
	   		$data[$key]->account_id 	= $this->getAccountBySellerUserId($val->seller_user_id);
	   		$data[$key]->site	 		= $this->getSiteBySellerUserId($val->seller_user_id);
	   		$classIdInfo				= UebModel::model("SellerUserToClass")->getInfoBySellerUserId($val->seller_user_id);
	   		$data[$key]->class_name 	= $classIdInfo['classIdNameStr'];
	   		$userInfo 					= User::model()->getUserNameById($val->seller_user_id);
	   		$data[$key]->emp_dept 		= $userInfo['department_id'];
	   		$data[$key]->seller_user_id = $userInfo['user_full_name'];//seller_user_id 被替换成名称
	   		
	   	}
	   	return $data;
   	}
   
   	public function getAllPlatormCodeBySellerUserId($sellerUserId){
   		$patformInfo = $this->getDbConnection()->createCommand()
					   		->select("platform_code")
					   		->from(self::tableName())
					   		->where("seller_user_id ={$sellerUserId} and is_del =0")
					   		->queryAll();
   		
   		if (empty($patformInfo)) return false;
   		$platformList = UebModel::model('Platform')->getUseStatusCode();
   		$platformStr = '';
   		$platformArr = array();
   		foreach ($patformInfo as $key => $val){
   			//$platformStr .= $val['platform_code'].',';
   			if (!in_array($val['platform_code'], $platformArr)){
   				$platformArr[] = $platformList[$val['platform_code']];
   			}
   		}
   		$platformArr = array_flip(array_flip($platformArr));
   		$platformStr = implode(',', $platformArr);
   		return trim($platformStr,',');
  	}
   
	public function getAccountBySellerUserId($sellerUserId) {
		$accountInfo = $this->getDbConnection()->createCommand()
							->select("platform_code,account_id")
							->from(self::tableName())
							->where("seller_user_id ={$sellerUserId} and is_del =0")
							->queryAll();
		
		if (empty($accountInfo)) return false; 
		$accountNameStr = ''; 
		$accountNameArr = array();
		foreach ($accountInfo as $key => $val){
			$accountNames = UebModel::model('ProductToAccountRelation')->getPlatformAccountById($val['platform_code'],$val['account_id']);
			if (!in_array($accountNames,$accountNameArr)){
				$accountNameArr[] = $accountNames;
			}
			//$accountNameStr .= $accountNames.',';
		}
		$accountNameArr = array_flip(array_flip($accountNameArr));
		$accountNameStr = implode(',', $accountNameArr);
		return trim($accountNameStr,',');
	}
	
	public function getSiteBySellerUserId($sellerUserId) {
		$siteInfo = $this->getDbConnection()->createCommand()
							->select("site")
							->from(self::tableName())
							->where("seller_user_id ={$sellerUserId} and is_del =0")
							->queryAll();
		
		if (empty($siteInfo)) return false; 
		$siteStr = '';
		$siteArr = array();
		foreach ($siteInfo as $key => $val){
			//$siteStr .= $val['site'].',';
			if (!in_array($val['site'],$siteArr)){
				$siteArr[] = $val['site'];
			}
		}
		$siteArr = array_flip(array_flip($siteArr));
		$siteStr = implode(',', $siteArr);
		return trim($siteStr,',');
	}
   
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
    	
    	$provider_name=trim(Yii::app()->request->getParam('class_id',''));
    	
    	$result = array(
	    			array(
	    				'name'          => 'class_id',
	    				'type'          => 'dropDownList',
	    				'search'        => '=',
	    				//'prefix'		=> true,
	    				'rel'			=> true,
	    				'data'          => UebModel::model('ProductClass')->getCat(),
	    				'htmlOptions'   => array(),
	    				//'alias'			=> 't',
	    			),
	    			array(
	    				'name'          => 'emp_dept',
	    				'type'          => 'dropDownList',
	    				'search'        => '=',
	    				//'prefix'		=> true,
	    				'rel'			=> true,
	    				'data'          => UebModel::model('Department')->getMarketsDepartmentInfo(),
	    				'htmlOptions'   => array(),
	    				//'alias'			=> 't',
	    			),
	    	        array(
	    			    'name'         	=> 'seller_user_id',
	    			    'type'         	=> 'text',
	    			    'search'       	=> '=',
	    			    'htmlOptions'  	=> array(),
	    	        	'alias'			=> 't',
	    			),
    	);
    	$this->addFilterOptions($result);
    	return $result;
    }
    
    
    /**
     * add relate table filter conditions
     *
     * @return array $filterOptions
     */
    public function addFilterOptions(&$result) {
    	
    	$userArr = array();
    	 
    	$userName = trim($_REQUEST['seller_user_id']);
    	if(!empty($userName)){
    		$userInfo = User::model()->getUserInfoByFullName($userName);
    		$userIdArr = array($userInfo['id']);
    		$userArr  = !empty($userArr) ? array_intersect($userIdArr,$userArr) : $userIdArr;
    		if(!$userArr){
    			$_REQUEST['search']['seller_user_id'] = 'null';
    			return false;
    		}
    	}
    	
    	$classId = trim($_REQUEST['class_id']);
    	if(!empty($classId)){
    		$sellerUserId = SellerUserToClass::model()->getAllUserByClassId($classId);
    		$userArr  = !empty($userArr) ? array_intersect($sellerUserId,$userArr) : $sellerUserId;
    		if(!$userArr){
    			$_REQUEST['search']['seller_user_id'] = 'null';
    			return false;
    		}
    	}
    	
    	$empId = trim($_REQUEST['emp_dept']);
    	if(!empty($empId)){
    		$emptUserInfo = User::model()->getEmpByDept($empId);
    		$empUserIds	= array_keys($emptUserInfo);
    		$userArr  = !empty($userArr) ? array_intersect($empUserIds,$userArr) : $empUserIds;
    		if(!$userArr){
    			$_REQUEST['search']['seller_user_id'] = 'null';
    			return false;
    		}
    	}
    	
    	if($userArr)
    		$_REQUEST['search']['seller_user_id'] = $userArr;
    	
    }
        
    /**
     * order field options
     * @return $array
     */
    public function orderFieldOptions() {
    	return array(
    			'create_time','modify_time'
    	);
    } 
    
    private function _setCDbCriteria() {
    	$criteria = new CDbCriteria();
    	$criteria->select 	= "t.id,t.seller_user_id,t.platform_code,t.account_id,t.site,t.create_time,t.create_user_id,t.update_time";
    	$criteria->join 	= "left join ueb_system.ueb_user u on t.seller_user_id = u.id ";
    	//$criteria->join 	.= "left join ueb_product.ueb_seller_user_to_class c on t.seller_user_id = c.seller_user_id ";
    	$criteria->addCondition("t.is_del !=1");//or c.is_del !=1
    	$criteria->group 	= " t.seller_user_id";
    	return $criteria;
    }
    
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/products/Productmarketersmanager/list');
    } 
    
    /* public function inserSellerClassInfo($columns) {
    	
    	$flag = $this->getDbConnection()->createCommand()
    			->insert("ueb_seller_user_to_class", $columns);
    	return $flag;
    } */
    
    public function getInfoBySellerUserId($sellerUserId) {
    	
    	$returnArr = $this->getDbConnection()->createCommand()
    						->select("*")
    						->from(self::tableName())
    						->where("seller_user_id ={$sellerUserId} and is_del =0")
    						->queryAll();
    	
    	return $returnArr;
    	
    }
    
    public function getSellerUserIdsByIdArr($idArr){
    	if (empty($idArr)) return false;
    	$idArr = explode(',', $idArr);
    	$return = $this->getDbConnection()->createCommand()
    					->select('*')
    					->from(self::tableName())
    					->where(array("in","id",$idArr))
    					->queryAll();
    	
    	if (empty($return)) return false;
    	$sellerUserIds = array();
    	foreach ($return as $key => $val) {
    		$sellerUserIds[] = $val['seller_user_id'];
    	}
    	return $sellerUserIds;
    	
    }
    
    public function getSellerUserInfo( $field = '*',$platformCode,$site = '',$accountId ){

    	if( !$platformCode || !$accountId ) return null;
    	$ret = $this->getDbConnection()->createCommand()
		    	->select($field)
		    	->from(self::tableName())
		    	->where('platform_code="'.$platformCode.'"')
		    	//->andWhere('site="'.$site.'"')
		    	->andWhere('account_id="'.$accountId.'"');
		    	//->andWhere('is_del='.self::IS_DEL_DEFAULT);
    	if(!empty($site)) $ret->andWhere('site="'.$site.'"');

    	return $ret->queryRow();
    }
    
    /**
     * 新增数据
     */
    public function saveData($data) {
    	$model = new self();
    	$model->attributes = $data;
    	$model->create_time = date('Y-m-d H:i:s');
    	$model->create_user_id = Yii::app()->user->id;
    	if ($model->save()) {
    		return $model->id;
    	}
    	return false;
    }
    /**
     * 得到所有绑定的人员ID和对应的名字
     * @param unknown $sellerUserId
     * @return Ambigous <multitype:, mixed>
     */
    public function getSellerUserIdAndName() {
    	 
    	$returnArr = $this->getDbConnection()->createCommand()
    	->select("distinct(seller_user_id)")
    	->from(self::tableName())
    	->where('is_del='.self::IS_DEL_DEFAULT)
    	->queryAll();
    	$data = array();
    	$userAll = MHelper::getUserPairs();
    	
    	foreach($returnArr as $list){
    		if(isset($userAll[$list['seller_user_id']])) {
				$data[$list['seller_user_id']] = $userAll[$list['seller_user_id']];
			}
    	}
    	return $data;
    	 
    }
    /**
     * 得到所有绑定的人员ID
     * @return multitype:Ambigous <>
     */
    public function getSellerUserId() {
    
    	$returnArr = $this->getDbConnection()->createCommand()
    	->select("distinct(seller_user_id)")
    	->from(self::tableName())
    	->where('is_del='.self::IS_DEL_DEFAULT)
    	->queryAll();
    	$data = array();
    	foreach($returnArr as $list){
    		$data[] = $list['seller_user_id'];
    	}
    	return $data;
    
    }
    
    /**
     * 根据用户ID得到绑定的平台和站点，账号
     * @param unknown $userId
     */
    public function getUserPlatformById($userId){
    	$returnArr = $this->getDbConnection()->createCommand()
    	->select("seller_user_id,platform_code,account_id,site")
    	->from(self::tableName())
    	->where('seller_user_id = '.$userId .' and is_del='.self::IS_DEL_DEFAULT)
    	->queryAll();
    	return $returnArr;
    }

    /**
     * @desc 获取销售归属账号
     * @param  int $userId  
     * @param  string $platformCode
     * @return array
     */
    public function getSellerAccounts($userId,$platformCode) {
        if ($platformCode == '') {
            return array();
        }
        return $this->getDbConnection()->createCommand()
                    ->selectDistinct("account_id")
                    ->from(self::tableName())
                    ->where('seller_user_id = '.$userId .' and is_del='.self::IS_DEL_DEFAULT)
                    ->andWhere("platform_code=:platform_code",array(":platform_code"=>$platformCode))
                    ->queryColumn();
    }


    public function findSellerAccountsByPlatform($platform, array $sellerId)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->select('seller_user_id as seller_id, account_id')
            ->from($this->tableName() . ' AS r')
            ->where('platform_code =:platform', array(':platform'=> $platform))
            ->andWhere(array('IN', 'seller_user_id', $sellerId));

        $result = array();
        $list = $queryBuilder->queryAll();
        foreach($list as $l) {
            $result[$l['account_id']] [] = $l['seller_id'];
        }
        return $result;
    }
}
