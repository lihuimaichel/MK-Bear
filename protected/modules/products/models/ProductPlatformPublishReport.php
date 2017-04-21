<?php
/**
 * sku 在各平台刊登上线
 * @author chenxy
 *
 */
class ProductPlatformPublishReport extends ProductsFromModel
{	
	
	const ONLINE_STATUS=1;
	const UMLINE_STATUS=2;
	public $product_status;
	public $category_id;
	public $product_title;
	public $product_id;
	public $sku_count;
	public $count;
	public $product_category_name;
	public $EB;
	public $NF;
	public $ALI;
	public $KF;
	public $AMAZON;
	public $YF;
	public $NE;
	public $LAZADA;
	public $ECB;
	public $JDGJ;
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
		return 'ueb_product_seller_platform_publish_temporary';
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
        	'product_category_name' => Yii::t('products','company category'),
        	'category_id' 			=> Yii::t('products','company category'),
        	'platform_code'       	=> Yii::t('purchases', 'promotion_platform_code'),
        	'sku'                   => Yii::t('products', 'Sku'),
        	'title'					=> Yii::t('system', 'Title'),
        	'product_title'			=> Yii::t('system', 'Title'),
        	'category_id'           => Yii::t('products', 'company category'),
        	'online_status'         =>  Yii::t('products', '刊登状态'),
        	'product_status'        => Yii::t('products', 'Product Status'),
        	'account_id'			=> Yii::t('order', 'Account Id'),
        	'site'	                => Yii::t('purchases', 'platform_site'),
        	'sc_name_id'            => Yii::t('users', '负责人姓名'),
        );
        $platformList = UebModel::model('Platform')->getPlatformList();
        foreach($platformList as $code=>$name){
        	$attr[$code]="$name";
        }
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
    	$dataProvider = parent::search(get_class($this), $sort, array(), $this->_setCDbCriteria());
    	$data = $this->addition($dataProvider->data);
    	$dataProvider->setData($data);
    	return $dataProvider;
    }
     
    private function _setCDbCriteria() {
		$criteria = new CDbCriteria();
		$criteria->select = '*';
		//$criteria->join   = 'LEFT JOIN ueb_product p ON p.sku = t.sku ';
		//$criteria->join   .= ' LEFT JOIN ueb_product_class_to_online_class d ON d.online_id = p.online_category_id ';
	//	$criteria->condition = '1>1';
		if(!$_REQUEST['sc_name_id']){
			$criteria->condition .= '1>1';
		}
		//$criteria->group = 'd.category_id';
    
    	return $criteria;
    }
    /**
     * addition information
     *
     * @param type $dataProvider
     */
    public function addition($data) {
    	$productClass =	UebModel::model('ProductClass')->getClassToSkuConut();
    	foreach($productClass as $value){
    	}
    	echo '<pre>';print_r($productClass);die;
     	$platformList = UebModel::model('Platform')->getPlatformList();
    	$result = array();
		$count = 0 ;
		$i= 0;
		$platformTotalCountArr = array();//各个平台数量的各个分类总和
    	foreach ($data as $key => $val) {
    		if($val->category_id){
    			$category_id = $val->category_id;
    			$count += $val->sku_count;//各个分类下的SKU合计
    			foreach($platformList as $platformCode =>$list){
    				$platformSkuCount   = UebModel::model('ProductPlatformListing')->getSkuPlatformByPlatformCodeAndClassId($category_id,$platformCode);// sku 在各平台上线的 数量
    			//	$data[$key]->$platformCode = $list;
    				//$data[$key]->$platformCode = $platformSkuCount;
    				$platformTotalCountArr[$platformCode] += $platformSkuCount;
    				$data[$key]->$platformCode = CHtml::link($platformSkuCount,"javascript:;",
    					array('title'=>$platformSkuCount,"style"=>"color:blue",
    							"onclick"=>"showPlatformSkuCount('$category_id','$platformCode');"));
    			}
    			$data[$key]->sku_count = CHtml::link($val->sku_count,"javascript:;",
    					array('title'=>$val->sku_count,"style"=>"color:blue",
    							"onclick"=>"showProductPublish('$category_id');"));
    			$i = $key+1;
    		}else{
    			unset($data[$key]);
    		}
    		
    	}
    	//echo '<pre>';print_r($platformTotalCountArr);die;
    	foreach($platformList as $keys=>$vals){
    		//$data[$i]->$keys = $platformTotalCountArr[$keys];
    		$data[$i]->$keys = CHtml::link($platformTotalCountArr[$keys],"javascript:;",
    		array('title'=>$platformTotalCountArr[$keys],"style"=>"color:blue",
    		"onclick"=>"showPlatformSkuCount('','$keys');"));
    	}
    	$data[$i]->category_id = 'totalProduct';
    	//$data[$i]->sku_count = $count;
    	$data[$i]->sku_count = CHtml::link($count,"javascript:;",
    	array('title'=>$count,"style"=>"color:blue",
    	"onclick"=>"showProductPublish('');"));
    	 
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
		$result = array(
				array(
						'name'          => 'sc_name_id',
						'type'          => 'dropDownList',
						'search'        => '=',
						'data'          => UebModel::model("ProductMarketersManager")->getSellerUserIdAndName(),
						'htmlOptions'   => array(),
						'alias'			=> 't',
				),
				array(
						'name'          => 'platform_code',
						'type'          => 'dropDownList',
						'search'        => '=',
						'value'         => $_REQUEST['platformCode'] ? $_REQUEST['platformCode']:'',
						'data'          =>  UebModel::model('Platform')->getPlatformList(),
						'htmlOptions'   => array('onchange' => 'getAccount(this)'),
						'alias'			=> 't',
				),
				array(
						'name'          => 'site',
						'type'          => 'dropDownList',
						'search'        => '=',
						//'data'          => $arr,
						'htmlOptions'   => array(),
						'alias'			=> 't',
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
						'alias'			=> 't',
				),
		);
		
		
		return $result;
	}
    
    

    /**
     * 根据刊登人员ID，公司分类和平台,账号，站点，刊登状态得到SKU的数量
     * $sc_name_id 刊登人员ID
     * @param unknown $category_id 分类
     * @param unknown $platformCode 平台
     * @param string $site 站点 
     * @param unknown $accountId 账号
     * @param string $productStatus 产品状态
     * $type 报表统计类型，0是根据统计刊登人员ID，公司分类和平台,账号，站点，刊登状态得到SKU的数量
     * @return mixed
     */
    public function getUserpublishCount($sc_name_id,$category_id,$platformCode,$site =null,$accountId,$productStatus=null,$type = 0){
    	$where = '';
    	if($site){
    		$where .= "  and site = '{$site}' ";
    	}
    	if($productStatus){
    		$where .= "  and product_status in ($productStatus) ";
    	}
    	$result = $this->getDbConnection()->createCommand()
    	->select( 'sku_count' )
    	->from( self::tableName())
    	->where("category_id = $category_id  and seller_user_id = $sc_name_id  and  platform_code = '{$platformCode}' and account_id = '{$accountId}'  " .$where." and type ='{$type}'")
    	//->andWhere(" p.online_status = '".$onlineStatus."'")
    	->queryAll();
    	$skuCount =0;
        if($result){
     		foreach($result as $val){
     			$skuCount += $val['sku_count'];
     		}
     	}
    	return $skuCount;
    }
    /**
     * 根据分类得到公司分类的数量
     * @param unknown $category_id
     * $productStatus 产品状态
     * $sellerId 销售员ID
     * $type 统计报表类型 人员平台刊登统计的各个分类sku数量
     * @return mixed
     */
    public function getCategoryByClass($category_id,$sellerId,$productStatus=null,$type = 2){
    	$where = '';
    	if($productStatus){
    		$where .= "  and product_status in ($productStatus) ";
    	}
    	$result = $this->getDbConnection()->createCommand()
    	->select( 'sku_count,seller_user_id' )
    	->from( self::tableName())
    	->where("category_id = $category_id  and seller_user_id ='{$sellerId}' and platform_code =''  " .$where." and type ='{$type}'")
    	//->andWhere(" p.online_status = '".$onlineStatus."'")
    	->queryAll();
     	$skuCount =0;
        if($result){
     		foreach($result as $val){
     			$skuCount += $val['sku_count'];
     		}
     	}
    	return $skuCount;
    }
    /**
     * 根据公司分类和平台得到SKU的刊登数量
     * @param unknown $category_id
     * @param unknown $platformCode
     * $productStatus 产品状态为字符串
     * $type 统计报表类型
     * @return unknown
     */
    public function getSkuPlatformByPlatformCodeAndClassId($category_id,$platformCode,$productStatus=null,$type=1){
    	$where = '';
    	if($productStatus){
    		$where .= "  and product_status in ($productStatus) ";
    	}
    	$result = $this->getDbConnection()->createCommand()
    	->select( 'sku_count,seller_user_id' )
    	->from( self::tableName())
    	->where("category_id = '{$category_id}' and platform_code = '{$platformCode}'  and (seller_user_id ='' or seller_user_id is null)  " .$where." and type ='{$type}'")
    	//->andWhere(" p.online_status = '".$onlineStatus."'")
    	->queryAll();
     	$skuCount =0;
     	if($result){
     		foreach($result as $val){
     			$skuCount += $val['sku_count'];
     		}
     	}
    	return $skuCount;
    }
    
    /**
     * 查询未进行公司分类SKU的刊登数量
     * @param unknown $category_id
     * @param unknown $platformCode
     * $productStatus 产品状态为字符串
     * $type 统计报表类型
     * @return unknown
     */
    public function getSkuPlatformByPlatformCodeAndnotClassIds($platformCode,$productStatus=null,$type=1){
    	$where = '';
    	if($productStatus){
    		$where .= "  and product_status in ($productStatus) ";
    	}
    	$result = $this->getDbConnection()->createCommand()
    	->select( 'sku_count,seller_user_id' )
    	->from( self::tableName())
    	->where("(category_id is null or category_id =0) and platform_code = '{$platformCode}'    " .$where." and type ='{$type}'")
    	//->andWhere(" p.online_status = '".$onlineStatus."'")
    	->queryAll();
    	$skuCount =0;
    	if($result){
    		foreach($result as $val){
    			$skuCount += $val['sku_count'];
    		}
    	}
    	return $skuCount;
    }
}