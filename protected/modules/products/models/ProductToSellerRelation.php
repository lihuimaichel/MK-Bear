<?php
/**
 * 
 * 产品与销售员分配功能
 * @author chenxy
 *
 */
class ProductToSellerRelation extends ProductsModel
{	

	public $MarketersManager_emp_dept;
	public $product_status;
	
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
		return 'ueb_product_to_seller_relation';
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
            'sku'    		        =>	'SKU',
            'seller_id'				=> Yii::t('system', '销售人'),
        	'create_time'			=> Yii::t('system', 'Create Time'),
        	'update_time'			=> Yii::t('system', 'Modify Time'),
        	'create_user_id'		=> Yii::t('system', 'Create User'),
        	'update_user_id'		=> Yii::t('system', 'Modify User'),
        	'department'            => Yii::t('system', '部门'),
        	'category_id'			=> Yii::t('product', 'Company Category'),
        	'product_status'        => Yii::t('product', 'Product Status'),
        	'MarketersManager_emp_dept' =>'部门',
        	'online_one_id'	        => Yii::t('product', 'One Class Classification of Platform'),
        			
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
    	
		$with = array();
		$dataProvider = parent::search(get_class($this), $sort, $with, $this->_setCDbCriteria());
		$data = $this->addition($dataProvider->data); 
		$dataProvider->setData($data);
		
		return $dataProvider;
    }
    private function _setCDbCriteria() {
    	$criteria = new CDbCriteria();
    	$criteria->select = 't.*,p.product_status';
    	$criteria->join = ' LEFT JOIN ueb_product p ON t.product_id = p.id';
    	if(isset($_REQUEST['MarketersManager_emp_dept'])){
    		$arr = UebModel::model('User')->getEmpByDept($_REQUEST['MarketersManager_emp_dept']);
    		$sellerArr = array_keys($arr);
    		if($_REQUEST['seller_id']){
    			$seller = $_REQUEST['seller_id'];
    			$inSellerIds =  array_intersect($sellerArr, array($seller));
    			$criteria->addInCondition("seller_id", $inSellerIds);
    		}elseif($sellerArr){
    			$criteria->addInCondition("seller_id", $sellerArr);
    		}else{
    			$criteria->addCondition("1=0");
    		}
    	
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
    	if(!empty($_REQUEST['category_id'])){
    		$arr = UebModel::model('ProductCategoryOnline')->getcategoryOneByClassId(trim($_REQUEST['category_id']));
    	}else{
    		$arr = array();
    	}
    	if(!empty($_REQUEST['MarketersManager_emp_dept'])){
    		$sellerArr = UebModel::model('User')->getEmpByDept($_REQUEST['MarketersManager_emp_dept']);
    	}else{
    		$sellerArr = array();
    	}
    	//$_REQUEST['seller_id'] ? $_REQUEST['seller_id'] :'';
    	$sellerId = Yii::app()->request->getParam('seller_id', '');
    	$result = array(
    	
    			array(
    					'name'          => 'sku',
    					'type'          => 'text',
    					'search'        => '=',
    					'htmlOptions'   => array(),
    					'alias'			=> 't',
    			),
    			array(
    					'name'          => 'MarketersManager_emp_dept',
    					'type'          => 'dropDownList',
    					'search'        => '=',
    					'rel'			=> true,
    					'data'          => UebModel::model('Department')->getMarketsDepartmentInfo(),
    					'htmlOptions'   => array('onchange' => 'getSellerByEmp(this)'),
    				//	'alias'			=> 'c',
    			),
    			array(
    					'name'          => 'seller_id',
    					'type'          => 'dropDownList',
    					'search'        => '=',
    					//'rel'			=> true,
    					'value'         => $sellerId,
    					'data'			=> $sellerArr,
    					'htmlOptions'   => array(),
    					'alias'			=> 't',
    			),
    			array(
    					'name'          => 'category_id',
    					'type'          => 'dropDownList',
    					'search'        => '=',
    					'data'          => UebModel::model('ProductClass')->getCat(),
    					'htmlOptions'   => array('onchange' => 'getClassOne(this)'),
    					'alias'			=> 't',
    			),
    			array(
    					'name'          => 'online_one_id',
    					'type'          => 'dropDownList',
    					'search'        => '=',
    					'data'			=> $arr,
    					'htmlOptions'   => array(),
    					'alias'			=> 't',
    			),
    			array(
    					'name'          => 'product_status',
    					'type'          => 'dropDownList',
    					'search'        => '=',
    					'data'          => Product::getProductStatusConfig(),
						'value'         => isset($_REQUEST['product_status']) ? $_REQUEST['product_status'] : Product::STATUS_ON_SALE,
    					'htmlOptions'   => array(),
    					'alias'			=> 'p',
    			),

    	);
    	//$this->addFilterOptions($result);
    	
    	return $result;
    }
    public function addFilterOptions(&$result){
    	$sellerId = array();

    	if(isset($_REQUEST['MarketersManager_emp_dept'])){
    		$arr = UebModel::model('User')->getEmpByDept($_REQUEST['MarketersManager_emp_dept']);
    		$sellerArr = array_keys($arr);
    		if($_REQUEST['seller_id']){
    			$seller = $_REQUEST['seller_id'];
    			$_REQUEST['search']['seller_id'] =  array_intersect($sellerArr, array($seller));
    		}else{
    			$_REQUEST['search']['seller_id'] = $sellerArr;
    		}
    		
    	}

    //	echo '<pre>';print_r($arr);die;
    }
    
    /**
     * order field options
     * @return $array
     */
    public function orderFieldOptions() {
    	return array(
    			'sku','seller_id','product_status','category_id','online_one_id'
    	);
    }
    
	public function addition($data){
		//部门
		$department = UebModel::model('Department')->getMarketsDepartmentInfo();
		//公司分类
		$classArr = UebModel::model('ProductClass')->getCat();
		//产品一级品类
		$cateName1List = UebModel::model('ProductClassToOnlineClass')->getCateName1();
		$productStatusList = Product::getProductStatusConfig();
		foreach($data as $key=>$value){
			$user = UebModel::model('User')->findByPk($value->seller_id);
			if($user) $departmentName = $department[$user->department_id];
			else $departmentName = "";
			$data[$key]->MarketersManager_emp_dept = $departmentName;
			$data[$key]->online_one_id = isset($cateName1List[$value->online_one_id]) ? $cateName1List[$value->online_one_id] : '';
			$data[$key]->category_id = isset($classArr[$value->category_id]) ? $classArr[$value->category_id] : '';
			$data[$key]->product_status = $productStatusList[$value->product_status];
		}
		return $data;
	}
    

    
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/products/producttosellerrelation/list');
    } 
    public function batchInsertAll($data){
    	return UebModel::model('ProductToSellerRelation')->batchInsert(ProductToSellerRelation::tableName(),array_keys($data[0]),$data);
    	 
    }
    /**
     * get list pairs
     *
     * @param array $idArr
     *
     * @return array $data
     */
    public function getListPairsByIdArr($idArr=array()) {
    	$selectObj = $this->getDbConnection()->createCommand()
    	->select('*')
    	->from(self::tableName());
    	if($idArr){
    		$selectObj->where(array('in', 'id', $idArr));
    	}
    		
    	$list = $selectObj->queryAll();
    	return $list;
    }
    
    
    /**
     * 查询出所有没有公司分类的SKU
     * @param unknown 
     * @return Ambigous <multitype:, mixed>
     */
    public function getSkuByWhere($where) {
    	$selectObj = $this->getDbConnection()->createCommand()
    	->select('DISTINCT(sku)')
    	->from(self::tableName());
    	$selectObj->where($where);
    
    	$list = $selectObj->queryAll();
    	return $list;
    }

    
    public function getAccountIdListByPlatformAndSellerId($platformCode, $sellerId){
    	$selectObj = $this->getDbConnection()->createCommand()
					    	->select('account_id')
					    	->from('ueb_seller_user_to_account_site')
					    	->where("platform_code=:platform_code and seller_user_id=:seller_user_id", array(':platform_code'=>$platformCode, ':seller_user_id'=>$sellerId));
					    					
    	
    	$list = $selectObj->queryColumn();
    	return $list;
    }
    
    public function getSKUSellerRelation($sku, $sellerId){
    	$skuInfo = $this->getDbConnection()->createCommand()
    		->select('sku, seller_id')
    		->from(self::tableName())
    		->where("sku=:sku and seller_id=:seller_id", array(':sku'=>$sku, ':seller_id'=>$sellerId))
    		->queryRow();
    	return $skuInfo;
    }

	//获取销售
	public function getSellerListByCondition($sku,$seller_id){

		$data = $this->getDbConnection()->createCommand()
			->select('seller_id')
			->from('ueb_product_to_seller_relation')
			->where("sku=:sku and seller_id=:seller_id", array(':sku'=>$sku,':seller_id'=>$seller_id))
			->queryRow();
		return $data;
	}

	public function findSellerListBySku($sku)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->select('seller_id')
            ->from($this->tableName())
            ->where('sku=:sku', array(':sku'=> $sku));

        return $queryBuilder->queryAll();
    }
}