<?php
/**
 * @desc Product Model
 * @author Gordon
 */
class ProductSlave extends ProductsModel {
    /** @var 产品实际库存*/
    public $product_real_qty = null;
    
    /** @var 产品可用库存*/
    public $product_available_qty = null;
    
    /** @var 产品在途库存*/
    public $product_onroad_qty = null;
    
    /** @var 产品标题*/
    public $product_titles = array();
    
    public $has_privileges = null;
    
    /** @var 产品类别ID **/
    public $product_category_id = null;
    
    public $title 			= null;
    public $combine 		= null;
    public $bind 			= null;
    public $security_level 	= null;
    public $infringement 	= null;
    public $provider 		= null;
    public $ft 				= null;
    
    /**@var 多熟悉参数*/
    const PRODUCT_MULTIPLE_NORMAL       = 0;//单品
    const PRODUCT_MULTIPLE_VARIATION    = 1;//子sku
    const PRODUCT_MULTIPLE_MAIN         = 2;//主sku
   
    const STATUS_NEWLY_DEVELOPED        = 1;
    const STATUS_EDITING                = 2;
    const STATUS_PRE_ONLINE             = 3;
    const STATUS_ON_SALE                = 4;
    const STATUS_HAS_UNSALABLE          = 5;
    const STATUS_WAIT_CLEARANCE         = 6;
    const STATUS_STOP_SELLING           = 7;
    const STATUS_QE_CENSOR				= 8;//QE审核
    
    const STOCK_UP_STATUS_YES			= 1;	//备货
    const STOCK_UP_STATUS_NO            = 0;	//不备货

    const PRODUCT_TYPE_ONE              = 1;    //产品类型---普通
    const PRODUCT_TYPE_TWO              = 2;    //产品类型---捆绑销售

    /**
	 * Security Level
	 */
	const STATUS_SECURITY				= 'A';
	const STATUS_POSSIBLE_INFRINGEMENT	= 'B';
	const STATUS_INFRINGEMENT 			= 'C';
	const STATUS_VIOLATION				= 'D';
	const STATUS_UNALLOCATED			= 'E';
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

	public function getDbKey()
	{
		return 'db_oms_product_slave';
	}
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_product';
    }

	/**
	 * @return string
	 *
	 * 返回带有数据库前缀的表名
	 */
    public function fullTableName()
	{
		return 'ueb_product.ueb_product';
	}

    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableNameDescription() {
        return 'ueb_product_description';
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
     * @desc  根据条件获取产品列表
     * @param unknown $conditions
     * @param unknown $params
     * @param string $limits
     * @param string $select
     * @return mixed
     */
    public function getProductListByCondition($conditions, $params, $limits = "", $select = "*"){
    	$command = $this->getDbConnection()->createCommand()
				    	->from($this->tableName())
				    	->where($conditions, $params)
				    	->select($select);
    	if($limits){
    		$limitsarr = explode(",", $limits);
    		$limit = isset($limitsarr[1]) ? trim($limitsarr[1]) : 0;
    		$offset = isset($limitsarr[0]) ? trim($limitsarr[0]) : 0;
    		$command->limit($limit, $offset);
    	}
    	return $command->queryAll();
    }
    /**
     * @desc 获取产品简单信息
     * @param unknown $sku
     * @return mixed
     */
    public function getProductBySku($sku, $fields = "*"){
    	return $this->dbConnection->createCommand()->select($fields)->from(self::tableName())->where('sku = "'.$sku.'"')->queryRow();
    }
	/**
	 * @desc 获取产品信息
	 * @param unknown $sku
	 */ 
	public function getProductInfoBySku($sku){
		if(empty($sku)) return array();
		$params = array();
		if( !isset($params[$sku]) ){
		    $productInfo = array();
		    $productInfo = $this->dbConnection->createCommand()->select('*')->from(self::tableName())->where('sku = "'.$sku.'"')->queryRow();
		    if($productInfo){
		        /* foreach($baseInfo as $key=>$val){
		            $productInfo[$key] = $val;
		        } */
		        //获取多语言标题,描述,Include
		        $descriptions = $this->dbConnection->createCommand()->select('*')->from('ueb_product_description')->where('sku = "'.$sku.'"')->queryAll();
		        foreach($descriptions as $item){
		            $productInfo['title'][$item['language_code']]         = $item['title'];
		            $productInfo['description'][$item['language_code']]   = $item['description'];
		            $productInfo['included'][$item['language_code']]      = $item['included'];
		        }
		        //获取品牌信息
		        $productInfo['brand_info'] = array();
		        if($productInfo['product_brand_id']){
		        	$productInfo['brand_info'] = self::model('ProductBrand')->getBrandInfoByBrandId($productInfo['product_brand_id']);
		        }
		        //获取产品的库存信息
		        	
		        //获取产品的属性信息(自然属性)
		        	
		        //获取产品绑定关系
		        $productInfo['combine_product'] = array();
		        if($productInfo['product_combine_code']){
		            $split = explode('*', trim($productInfo['product_combine_code']));
		            if( count($split)==2 ){
		                $productInfo['combine_product']['sku'] = current($split);
		                $productInfo['combine_product']['quantity'] = end($split);
		            } 
		        }
		        
		    }
		    $params[$sku] = $productInfo;
		}
		return $params[$sku];
	}

	/**
	 * @desc 根据子SKU获取父SKU
	 * @param unknown $variationSKU
	 */
	public function getMainSkuByVariationSku($variationSKU){
		return UebModel::model('ProductSelectAttribute')->getMainSku(null, $variationSKU);
	}
	
    /**
     * @desc 取产品的描述信息
     * @param string $sku
     * @param number $ret
     */
    public  function  getProductDescriptionbysku($sku=null,$language_code=null,$field='*'){
        if(!$sku)return null;
        $ret = $this->dbConnection->createCommand()
		            ->select($field)
		            ->from(self::tableNameDescription())
		            ->where('sku="'.$sku.'" and language_code="'.$language_code.'" ')
		            ->queryRow();
        return $ret;
    }

	/**
	 * @desc 换算sku(针对1*A = 5*B的情况)
	 * @param string $sku
	 * @param number $qty
	 */
	public function getRealSkuList($sku, $qty=1){
	    $skuInfo = $this->getProductInfoBySku($sku);
	    if( !empty($skuInfo['combine_product']) && intval($skuInfo['combine_product']['quantity']) > 1){
	        $newSku['sku'] = $skuInfo['combine_product']['sku'];
	        $newSku['quantity'] = intval($skuInfo['combine_product']['quantity']) * $qty;
	    }else{
	        $newSku['sku'] = $sku;
	        $newSku['quantity'] = $qty;
	    }
	    return $newSku;
	}
	
	/**
	 * @desc 获取sku主图
	 * @param string $sku
	 */
	public function getImgListold($sku,$type='zt'){
	    $params = array('sku' => $sku);
	    $api = Yii::app()->erpApi;
	    if($type=='zt'){
	        $function = 'getZtList';
	    }elseif($type=='ft'){
	        $function = 'getFtLists';
	    }
	    $result = $api->setServer('oms')->setFunction('Products:Productimage:'.$function)->setRequest($params)->sendRequest()->getResponse();
		if( $api->getIfSuccess()  ){
	        return $result;
	    }else{
	        throw new CException("Get img failure!!!");
	    }
	}
	/**
	 * @desc 新加
	 * @param unknown $sku
	 * @param string $type
	 * @return unknown
	 */
	public function getImgList($sku,$type='zt'){
		$params = array('sku' => $sku);
		if($type=='zt'){
			$function = 'getZtList';
		}elseif($type=='ft'){
			$function = 'getFtLists';
		}
		$result = $this->$function($sku);
		return $result;
	}
	//获取副图列表
	public function getFtLists($sku,$m=0){
		$productImag = new Productimage;
		$imgConfig = $productImag->img_config;
		$ass_path = Yii::getPathOfAlias('webroot').$imgConfig['img_local_path'].$imgConfig['img_local_assistant_path'];
		$first = substr($sku,0,1);
		$second = substr($sku,1,1);
		$third = substr($sku,2,1);
		$four = substr($sku,3,1);
		$filepath = $ass_path.'/'.$first.'/'.$second.'/'.$third.'/'.$four;
		return $this->getImageList($filepath,$sku);//$this->getImageList($filepath,$sku,0,$m);
	}
	
	//获取主图列表
	///var/www/html/upload/image/main/1/0/0/0/100016-2.jpg
	public function getZtList($sku){
		$productImag = new Productimage;
		$imgConfig = $productImag->img_config;
		$ass_path = Yii::getPathOfAlias('webroot').$imgConfig['img_local_path'].$imgConfig['img_local_main_path'];
		$first = substr($sku,0,1);
		$second = substr($sku,1,1);
		$third = substr($sku,2,1);
		$four = substr($sku,3,1);
		$filepath = $ass_path.'/'.$first.'/'.$second.'/'.$third.'/'.$four;
		return $this->getImageList($filepath,$sku);
	}
	
	/*
	 * 功能：获取某sku的图片列表
	* $filepath，sku绝对路径
	* $sku:SKU
	* $first:0为取所有，1，获取第一张图
	* $m:从第几张开始取
	*/
	public function getImageList($filepath,$sku,$first=0,$m=0){
		$imageList = array();
		if(!is_dir($filepath)){
			return $imageList;
		}
		$productImag = new Productimage;
		$imgConfig = $productImag->img_config;
		$search_qty = $imgConfig['img_max_qty'];
		$types = explode(',', $imgConfig['img_allowed_ext']);
		$i = $m;
		while($i<$search_qty){
			if($i==0){
				$filename = $sku;
			}else{
				$filename = $sku.'-'.$i;
			}
			foreach($types as $type){
				$fullname = $filename.'.'.$type;
				$local_path = $filepath.'/'.$fullname;
				if(file_exists($local_path)){
					$local_path = str_replace(Yii::getPathOfAlias('webroot'),'',$local_path);
					if($first){
						//return $local_path;
						$imageList[$i] = $local_path;
						return $imageList;
					}else{
						$imageList[$filename] = $local_path;
					}
					break;
				}
			}
			$i++;
		}
		return $imageList;
	}
	
	/**
	 * @desc 根据sku获取属性
	 * @param unknown $sku
	 */
	public function getAttributeBySku($sku, $code = ''){
		return ProductSelectAttribute::model()->getAttIdsBySku($sku, $code); 
		// $params = array('sku' => $sku, 'code' => $code );
	 //    $api = Yii::app()->erpApi;
	 //    $result = $api->setServer('oms')->setFunction('Products:ProductSelectAttribute:getAttIdsBySku')->setRequest($params)->sendRequest()->getResponse();
	 //    MHelper::printvar($result);
	 //    if( $api->getIfSuccess() ){
	 //        return $result;
	 //    }else{
	 //        return array();
	 //    }
	}
	
	/**
	 * @return array relational rules.
	 */
	public function relations() {
		return array(
				'product_desc'   => array(self::HAS_MANY, 'Productdesc', array('product_id')),
		);
	}	
	
	public function attributeLabels() {
		return array(
			'title' 					=> Yii::t('product', 'Product Name'),
			'sku'						=> Yii::t('product', 'Sku'),
			'product_category_id' 		=> Yii::t('product', 'Proudct Category'),
			'gross_product_weight'		=> '净重(g)(产品净重量)',
			'product_weight'        	=> '毛重(g)(带原包装重量)', 
			'product_freight'			=> Yii::t('product', 'freight'),
			'product_size'				=> '产品尺寸(mm)',
			'product_length'        	=> '长',
            'product_width'         	=> '宽',
            'product_height'        	=> '高',
            'pack_size'					=> '包装尺寸(mm)',
            'pack_product_length'		=> '长',
			'pack_product_width'		=> '宽',
			'pack_product_height'		=> '高',
			'product_pack_code'     	=> Yii::t('product', 'Packing materials'),
            'product_package_code'  	=> Yii::t('product', 'Packaging'),
            'product_label_proces'		=> '贴标加工包装',
            'product_original_package'  => Yii::t('product', 'Whether to bring the original packaging'),
            'product_is_storage'        => Yii::t('product', 'Whether for location'),
		);
	}
	
	/**
	 * @desc 查询产品列表
	 * @return CActiveDataProvider
	 */
	public function searchProduct() {
		$sort = new CSort();
		$sort->attributes = array(
			'defaultOrder' => 't.create_time'
		);
		$criteria = new CDbCriteria();
		$criteria->select = "t.id, t.sku, t1.title, t.product_category_id";
		$criteria->join = "left join ueb_product_description t1 on (t.id = t1.product_id)";
		$criteria->addCondition("t1.language_code = 'Chinese'");
		return parent::search(get_class($this), $sort, '', $criteria);
	}
	
	public function filterOptions() {
		$data = ProductCategory::model()->getListOptions();
		$return = array(
			array(
				'name' => 'sku',
				'type' => 'text',
				'search' => 'LIKE',
				'prefix' => 'prefix',
				'htmlOptions' => array(),
				'value' => isset($_REQUEST['sku']) ?  $_REQUEST['sku'] : '',
				'alias' => 't',
			),
			array(
				'name' => 'product_category_id',
				'type' => 'multiSelect',
				'search' => 'IN',
				'dialog' => 'products/productcat/index',
				'htmlOptions' => array(
					'width' => '1050',
					'height' => '500',		
				),
				'alias'	=> 't',
			),	
		);
		return $return;
	}
	
	/**
	 * getAttribute Id And Name By attributeId
	 * @return array
	 * @author ltf 2015-09-19
	 */
	public function getAttributeIdAndNameList(){
		$return = array();
		$attributeId = $this->getDbConnection()->createCommand()
					->select('id,attribute_name,attribute_showtype_value')
					->from('ueb_product.ueb_product_attribute')
					->where("id = '3'")
					->queryAll();
		foreach ($attributeId as $value){
			$attributeValueIds = self::getAttributeList($value['id']);
			foreach ($attributeValueIds as $key => $val){
				$attribute_value_name_cn = self::getAttributeNameByCode($val['attribute_value_name'],CN);
				$return[$value['attribute_name']]['type']=$value['attribute_showtype_value'];
				$return[$value['attribute_name']]['attribute'][$val['id']] = $attribute_value_name_cn ? $attribute_value_name_cn : $val['attribute_value_name'];
			}
		}
		return $return;
	}
	
	/**
     * get attribute list by category id
     *
     * @param Integer $categoryId
     * @return array $data
     * @author ltf 2015-09-19
     */
    public function getAttributeList($attributeId) {
    	$data = array();
    	$list = $this->getDbConnection()->createCommand()
		    	->select('b.id,b.attribute_value_name')
		    	->from('ueb_product.ueb_product_attribute_map' . ' a')
		    	->join('ueb_product.ueb_product_attribute_value' . ' b', "a.attribute_value_id = b.`id`")
		    	->where("a.attribute_id = '{$attributeId}'")
		    	->queryAll();
    	foreach ($list as $key => $val) {
    		$data[$val['id']] = $val;
    	}
    	return $data;
    }
    
    /**
     * get attribute name by attrrbute code
     * @param string $attrrbute_code
     * @param string $language
     * @return string
     * @author ltf 2015-09-19
     */
    public function getAttributeNameByCode($attrrbute_code,$language=CN){
    	$string = '';
    	$data	= $this->getDbConnection()->createCommand()
    			->select('*')
    			->from('ueb_product.ueb_product_attribute_value_lang')
    			->where('attribute_value_name=:attribute_value_name and language_code=:language_code', array(':attribute_value_name'=>$attrrbute_code,':language_code'=>$language))
    			->queryAll();
    	if($data)
    		return $data[0]['attribute_value_lang_name'];
    	else
    		return '';
    }
	/*
	 * @Todo get product attribute name list by product sku
	 * @params String $sku
	 * @author liht
	 * @since 20151124
	 */
	public function getAttributeNameBySku($sku){

		$sql = "SELECT a.sku,b.attribute_value_name FROM ueb_product_select_attribute a JOIN ueb_product_attribute_value b ON a.attribute_value_id=b.id JOIN " . $this->tableName() . " c ON c.id=a.multi_product_id WHERE c.sku=" . $sku . " AND c.product_is_multi=" . self::PRODUCT_MULTIPLE_MAIN;

		$result = $this->getDbConnection()->createCommand($sql)->queryAll();

		return $result;

	}
    /**
     * get Attribute Status Data by attributeid
     * @author ltf 2015-09-19
     */
    public function getAttributeStatusData($attributeValueId){
    	$arrString = implode(',', $attributeValueId);
    	return $this->getDbConnection()->createCommand()
			    	->select('p.sku')
			    	->from('ueb_product.ueb_product_select_attribute' . ' s')
			    	->join('ueb_product.ueb_product' . ' p', "p.`id` = s.product_id")
			    	->group('p.sku')
			    	->where(array('IN', 'attribute_value_id', $arrString))
			    	->queryColumn();
    }
    
	/**
	 * @desc 产品状态
	 * @param string $status
	 * @return Ambigous <string>|multitype:string
	 */
	public static function getProductStatusConfig($status=null) {
		$statusInfo= array(
				self::STATUS_NEWLY_DEVELOPED => Yii::t('product', 'Newly developed'),
				self::STATUS_EDITING => Yii::t('product', 'Editing'),
				self::STATUS_QE_CENSOR	=>	Yii::t('product', 'QE Censor'),
				self::STATUS_PRE_ONLINE => Yii::t('product', 'Pre online'),
				self::STATUS_ON_SALE => Yii::t('product', 'On sale'),
				self::STATUS_HAS_UNSALABLE => Yii::t('product', 'Has unsalable'),
				self::STATUS_WAIT_CLEARANCE => Yii::t('product', 'Wait for the clearance'),
				self::STATUS_STOP_SELLING => Yii::t('product', 'Stop selling'),
		);
		if($status!==null){
			return $statusInfo[$status];
		}else{
			return $statusInfo;
		}
	}


	public static function getProductListingStatus()
	{
		return array(
			self::STATUS_WAIT => Yii::t('product', 'Listing Waiting'),
			self::STATUS_PROCESS => Yii::t('product', 'Listing Processing'),
			self::STATUS_SCUCESS => Yii::t('product', 'Listing Sucess'),
		);
	}

	/**
	 * @desc 根据条件查询指定字段
	 */
	public function getProductByCondition( $condition,$field = '*' ){
		$condition = empty($condition)?'1=1':$condition;
		$ret = $this->dbConnection->createCommand()
				->select( $field )
				->from( $this->tableName() )
				->where( $condition )
				->queryAll();
	
		return $ret;
	}
	
	/**
	 * @desc 根据sku获取相应平台的分类(暂时使用)
	 * @param string $sku
	 * @param string $platformCode
	 */
	public function getPlatformCategoryBySku($sku,$platformCode){
	    if($platformCode==Platform::CODE_EBAY){
	        $category = $this->dbConnection->createCommand()
	                   ->select('categoryname')
	                   ->from('ueb_ebay_product')
	                   ->where('siteid = 0 AND status = 1 AND listingduration = "GTC" AND sku = "'.$sku.'"')
	                   ->order('starttime DESC')
	                   ->queryScalar();
	    }
	    if( isset($category) ){
	        $category = str_replace(':', ' ', $category);
	        $category = str_replace('&', '', $category);
	        return explode(' ', $category);
	    }else{
	        return array();
	    }
	}
	
	/**
	 * @desc 检测sku是否存在于系统
	 * 
	 */
	public function checkSkuIsExisted($sku=''){
		if($sku=='') return false;
		return $this->exists('sku=:sku',array(':sku'=>$sku));
	}
	
	/**
	 * @desc 获取是否备货列表
	 * @param string $key
	 * @return Ambigous <string, Ambigous <string, string, unknown>>|multitype:string Ambigous <string, string, unknown>
	 */
	public static function getStockUpStatusList($key = null) {
		$list = array(
			self::STOCK_UP_STATUS_YES => Yii::t('system', 'Yes'),
			self::STOCK_UP_STATUS_NO => Yii::t('system', 'No'),
		);
		if (!is_null($key) && array_key_exists($key, $list))
			return $list[$key];
		return $list;
	}
	
	/**
	 * get by sku
	 * @param string $sku
	 */
	public function getBySku($sku) {
		static $param = array();
		if( !isset($param[$sku]) ){
			$param[$sku] = $this->find("sku = :sku", array(':sku' => $sku));
		}
		return $param[$sku];
	}
	
	/**
	 * @desc 得到产品信息 [中英标题]
	 * @param	string	$sku
	 * @param	string	$language_code
	 */
	public function getProductInfoTitleBySku($sku,$language_code=''){
		if(empty($sku))
			return array();
		$arr = array();
		$baseInfo = $this->getBySku($sku);
		if($baseInfo){
			foreach($baseInfo as $key=>$val){
				$arr[$key] = $val;
			}
			if (!empty($language_code)) {
				$arrProductDesc = UebModel::model('Productdesc')->getDescriptionInfoByProductIdAndLanguageCode($baseInfo['id'],$language_code);
			}else{
				$arrProductDesc = UebModel::model('Productdesc')->getDescriptionInfoByProductIdAndLanguageCode($baseInfo['id'],CN);
				$arrProductDescen = UebModel::model('Productdesc')->getDescriptionInfoByProductIdAndLanguageCode($baseInfo['id'],EN);
				$arrProductDesc = $arrProductDesc?$arrProductDesc:$arrProductDescen;
			}
			$arr['price']=$baseInfo['product_cost'];
			$arr['title']=$arrProductDesc['title'];
			$arr['enname'] = $arrProductDescen['title'];
		}
		return $arr;
	}
	/**
	 * @desc 根据给出的产品id数组获取对应产品信息
	 * @param unknown $productIds
	 */
	public function getProductInfoListByIds($productIds){
		return $this->getDbConnection()->createCommand()
					->from(self::tableName())
					->where(array('in', 'id', $productIds))
					->queryAll();								
	}
	/**
	 * @desc 根据单个产品id获取信息
	 * @param unknown $productId
	 * @return mixed
	 */
	public function getProductInfoById($productId){
		return $this->getDbConnection()->createCommand()
				->from(self::tableName())
				->where('id=:id', array(':id'=>$productId))
				->queryRow();
	}
	
	/**
	 * get adapter attributes by sku
	 * @param string $sku
	 * @return array
	 */
	public function getAdapterAttrBySku($sku){
		$adapterAttribute = array();
		$skuObj = $this->find('sku=:sku',array(':sku'=>$sku));
		if ($skuObj) {
			$skuId = $skuObj->id;
	
			$productAttributeId = UebModel::model('ProductAttribute')->find('attribute_code=:attribute_code',
					array(':attribute_code'=>ProductAttribute::ADAPTER_CODE))->id;
	
			$queryAttribute = array(
					'product_id'	=>$skuId,
					'attribute_id'	=>$productAttributeId
			);
			$listAttr = UebModel::model('ProductSelectAttribute')->findAllByAttributes($queryAttribute);
			//check the sku is adapter attribute
			if ($listAttr) {
				foreach($listAttr as $key=>$val){
					$adapterAttribute[] = $val['attribute_value_id'];
				}
			}
		}
		return $adapterAttribute;
	}
	
	/**
	 * @desc 获取产品多属性列表
	 * @param string $type
	 * @return Ambigous <string, Ambigous <string, string, unknown>>|multitype:string Ambigous <string, string, unknown>
	 */
	static public function getProductMultiList($type = null) {
		$list = array(
			self::PRODUCT_MULTIPLE_NORMAL => Yii::t('product', 'PRODUCT_MULTIPLE_NORMAL'),
			self::PRODUCT_MULTIPLE_MAIN => Yii::t('product', 'PRODUCT_MULTIPLE_MAIN'),
			self::PRODUCT_MULTIPLE_VARIATION => Yii::t('product', 'PRODUCT_MULTIPLE_VARIATION'),
		);
		if (array_key_exists($type, $list))
			return $list[$type];
		return $list;
	}
	
	/**
	 * @desc 获取SKU老系统分类
	 * @param unknown $sku
	 * @return Ambigous <mixed, string, unknown>
	 */
	public function getSkuOldCategory($sku) {
		return $this->dbConnection->createCommand()
			->from("ueb_product_category_sku_old")
			->select("classid")
			->where("sku = :sku", array(':sku' => $sku))
			->queryScalar();
	}
	
	/**
	 * @desc 检测销售人员是否有权限销售该SKU
	 * @param string  $platformCode   平台code
	 * @param unknown $saleID
	 * @param unknown $sku
	 * @return boolean
	 */
	public function checkAccessToSaleSKU($saleID, $sku, $platformCode=''){
		// 判断是否是超级管理员
		$isAdmin = UserSuperSetting::model()->checkSuperPrivilegeByUserId($saleID);
		if($isAdmin){
			return true;
		}

		//验证平台权限
		$res = AuthAssignment::model()->checkPlatformByUserIdAndPlatformCode($saleID,$platformCode);
		if($res) return true;

		$skuInfo = $this->getProductBySku($sku);
		if(empty($skuInfo)) return false;
		$isVariation  = (isset($skuInfo['product_is_multi']) && $skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_VARIATION) ? true : false;
		if($isVariation){
			$mainSKU = Product::model()->getMainSkuByVariationSku($sku);
		}else{
			$mainSKU = $sku;
		}
		
		## 旧的权限判断
		// 	$relationSKU = UebModel::model("ProductToSellerRelation")->getSKUSellerRelation($sku, $saleID);
		// 	if(!$relationSKU) {
		// 		$relationSKU = UebModel::model("ProductToSellerRelation")->getSKUSellerRelation($mainSKU, $saleID);
		// 	}
		// 	if(!$relationSKU) {
		// 		return false;
		// 	}
		// 	return true;

		## 新的权限判断 2016-12-14
		$sellerAccounts = UebModel::model("ProductMarketersManager")->getSellerAccounts($saleID,$platformCode);
		if (!empty($sellerAccounts)) {
			foreach ($sellerAccounts as $accountID) {
				$relationSKU = UebModel::model("ProductToAccountSellerPlatform")->getSKUSellerRelation($sku, $saleID, $accountID, $platformCode);
				if(empty($relationSKU)) {
					$relationSKU = UebModel::model("ProductToAccountSellerPlatform")->getSKUSellerRelation($mainSKU, $saleID, $accountID, $platformCode);
				}

				//MHelper::writefilelog($platformCode.'-checkAccessToSaleSKU.txt', $saleID.' ## '. $sku.' -- '.$platformCode.' @@ '.json_encode($relationSKU)."\r\n");	

				if(!empty($relationSKU)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @desc 检测销售人员是否有权限销售该SKU
	 * @param string  $platformCode   平台code
	 * @param unknown $saleID
	 * @param unknown $sku
	 * @return boolean
	 */
	public function checkAccessToSaleSKUNew($saleID, $sku, $accountID, $platformCode, $siteID=''){
		// 判断是否是超级管理员
		$isAdmin = UserSuperSetting::model()->checkSuperPrivilegeByUserId($saleID);
		if($isAdmin){
			return true;
		}

		//验证平台权限
		$res = AuthAssignment::model()->checkPlatformByUserIdAndPlatformCode($saleID,$platformCode);
		if($res) return true;

		$skuInfo = $this->getProductBySku($sku);
		if(empty($skuInfo)) return false;
		$isVariation  = (isset($skuInfo['product_is_multi']) && $skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_VARIATION) ? true : false;
		if($isVariation){
			$mainSKU = Product::model()->getMainSkuByVariationSku($sku);
		}else{
			$mainSKU = $sku;
		}

		//站点ID转站点简称
		if (in_array($platformCode,array(Platform::CODE_EBAY,Platform::CODE_LAZADA))) {
			$site = ProductToAccountRelation::model()->getSiteNameMappingSiteIDWithPlatform($platformCode,$siteID);
		} else {
			$site = '';
		}
		
		## 新的权限判断 2016-12-14
		$relationSKU = UebModel::model("ProductToAccountSellerPlatform")->getSKUSellerRelation($sku, $saleID, $accountID, $platformCode, $site);
		if(empty($relationSKU)) {
			$relationSKU = UebModel::model("ProductToAccountSellerPlatform")->getSKUSellerRelation($mainSKU, $saleID, $accountID, $platformCode,$site);
		}

		//MHelper::writefilelog($platformCode.'-checkAccessToSaleSKUNew.txt', $saleID.' ## '. $sku.' -- '.$platformCode.' -- '.$siteID.' @@ '.json_encode($relationSKU)."\r\n");	

		if(empty($relationSKU)) {
			return false;
		}
		return true;
	}	
	
	
	// public function checkAccessToSaleSKUOld($saleAccountID, $sku){
		// $skuInfo = $this->getProductBySku($sku);
		// if(empty($skuInfo)) return false;
		// $onlineCategoryID = $skuInfo['online_category_id'];//品类ID
		// if(empty($onlineCategoryID)) return false;
		// //找出分类id
		// $classCategory = ProductClassToOnlineClass::model()->find("online_id='{$onlineCategoryID}'");
		// if(empty($classCategory)) return false;
		// //匹配根据分类找到对应的销售人员
		// $saleAccount = SellerUserToClass::model()->find("class_id='{$classCategory['category_id']}' and seller_user_id='{$saleAccountID}' and is_del=0 ");
		// if(!$saleAccount){
		// 	return false;
		// }
		// return true;
	// }
	
	/**
	 * @desc 检测当前销售人员的sku销售权限
	 * @param unknown $sku
	 * @return boolean
	 */
	public function checkCurrentUserAccessToSaleSKU($sku, $platformCode = ''){
		if ($sku == '' ||  $platformCode == '') {
			return false;
		}
		$saleAccountID = Yii::app()->user->id;
		return $this->checkAccessToSaleSKU($saleAccountID, $sku, $platformCode);
	}

	/**
	 * @desc 检测当前销售人员的sku销售权限
	 * @param unknown $sku
	 * @return boolean
	 */
	public function checkCurrentUserAccessToSaleSKUNew($sku, $accountID, $platformCode,$siteID=''){
		//MHelper::writefilelog('my.txt', Yii::app()->user->id.' @@ '.print_r(array($sku, $accountID, $platformCode),true)."\r\n");
		if ($sku == '' || $accountID == '' ||  $platformCode == '') {
			return false;
		}
		$saleID = Yii::app()->user->id;
		return $this->checkAccessToSaleSKUNew($saleID, $sku, $accountID, $platformCode, $siteID);
	}	
	
	/**
	 * @desc 检测是否可以有相应刊登SKU的权限
	 * @param unknown $platformCode
	 * @param unknown $sku
	 * @param unknown $accountID
	 * @param string $site
	 * @return boolean
	 */
	// public function checkSellerAccessToSaleSKU($platformCode, $sku, $accountID, $siteId = null){
		// $sellerID = Yii::app()->user->id;
		// $return = array(
		// 		'flag'		=>	false,
		// 		'message'	=>	'',
		// );
		// //找出SKU是否是主SKU
		// //如果有子SKU则循环判断子SKU
		// $skuInfo = $this->getProductBySku($sku);
		// if($skuInfo){
		// 	//获取站点对应的站点名称
		// 	$site = ProductToAccountRelation::model()->getSiteNameMappingSiteIDWithPlatform($platformCode);
		// 	//获取表名
		// 	$tbname = ProductToAccountRelation::model()->getProductToAccountRelationTableByPlatform($platformCode);
		// 	//获取子SKU
		// 	$skuAttributeList = array();
		// 	if($skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
		// 		$productSelectedAttribute = new ProductSelectAttribute();
		// 		$skuAttributeList = $productSelectedAttribute->getSelectedAttributeSKUListByMainProductId($skuInfo['id']);
		// 	}
		// 	if($skuInfo['product_is_multi'] != Product::PRODUCT_MULTIPLE_MAIN || empty($skuAttributeList)){
		// 		$result =	$this->getDbConnection()
		// 				->createCommand()
		// 				->from($tbname)
		// 				->select('sku')
		// 				->where("sku=:sku and account_id=:account_id and seller_user_id=:seller_user_id", array(':sku'=>$sku, ':account_id'=>$accountID, ':seller_user_id'=>$sellerID))
		// 				->andWhere($site === null ? "1" : "site='{$site}'")
		// 				->queryRow();
		// 		if($result){
		// 			$return = array(
		// 					'flag'		=>	true,
		// 					'message'	=>	''
		// 			);
		// 		}else{
		// 			$return = array(
		// 					'flag'		=>	false,
		// 					'message'	=>	'没有找到对应SKU:'.$sku ."刊登权限"
		// 			);
		// 		}
		// 	}else{
		// 		$skus = array();
		// 		foreach ($skuAttributeList as $sku){
		// 			$skus[$sku['product_id']] = $sku['sku'];
		// 		}
		// 		$bindSkus =	$this->getDbConnection()
		// 						->createCommand()
		// 						->from($tbname)
		// 						->select('sku')
		// 						->where("account_id=:account_id AND seller_user_id=:seller_user_id", array(':account_id'=>$accountID, ':seller_user_id'=>$sellerID))
		// 						->andWhere($site === null ? "1" : "site='{$site}'")
		// 						->andWhere(array('IN', 'sku', $skus))
		// 						->queryColumn();
		// 		$diffSkus = array_diff($skus, $bindSkus);
		// 		if($diffSkus){
		// 			$return = array(
		// 					'flag'		=>	false,
		// 					'message'	=>	'没有找到对应SKU:'. implode(",", $diffSkus) ."刊登权限"
		// 			);
		// 		}else{
		// 			$return = array(
		// 					'flag'		=>	true,
		// 					'message'	=>	''
		// 			);
		// 		}
				
		// 	}
		// }else{
		// 	$return = array(
		// 		'flag'		=>	false,
		// 		'message'	=>	'没有找到对应SKU:'.$sku
		// 	);
		// }
		// return $return;
	// }


	/**
	 * 通过ID查询sku信息
	 * @param  $id 
	 */
	public function loadModel($id) {          
        $model = UebModel::model('Product')->findByPk((int) $id);
        if ( $model === null )
        	exit("sku不存在");
            // throw new CHttpException(404, Yii::t('app', 'The requested page does not exist.'));            
        return $model;
    }


    /**
	 * @return array Product Security List
	 */
	public function getProductSecurityList(){
		return array(
				self::STATUS_SECURITY 				=> Yii::t('product', 'Security'),
				self::STATUS_POSSIBLE_INFRINGEMENT 	=> Yii::t('product', 'Possible infringement'),
				self::STATUS_INFRINGEMENT 			=> Yii::t('product', 'Infringement'),
				self::STATUS_VIOLATION 				=> Yii::t('product', 'Violation'),
				self::STATUS_UNALLOCATED 			=> Yii::t('product', 'Unallocateds'),
		);
	}


	/**
	 * get by original_material_type_id
	 * @param string $typeid
	 */
	public function getByMaterialTypeId($typeid) {
		$result=array();
		$result['0'] = Yii::t('system', 'Please Select');
		$data = $this->getProductsByTypeid($typeid);
		foreach($data as $v)
		{
			$result[$v['sku']] = $v['title'];
		}
		return $result;
	}


	/**
	 * find title
	 */
	public function getProductsByTypeid($typeid){
		 
		return $this->getDbConnection()->createCommand()
		->select('p.sku,d.title')
		->from( $this->tableName() .' p' )
		->leftJoin( UebModel::model('Productdesc')->tableName() . ' d', 'd.product_id=p.id')
		->where('p.original_material_type_id=:id', array(':id'=>$typeid))
		->andWhere('d.language_code=:code', array(':code'=>CN))
		->queryAll();
	}


	/**
	 * @desc 显示产品类型
	 * @param integer $num
	 */
	public function getProductType($num=null){		
		$productType = array(	
			self::PRODUCT_TYPE_ONE     	 =>	'普通',
			self::PRODUCT_TYPE_TWO    	 =>	'捆绑销售'
		);

		if($num!==null){
			return $productType[$num];
		}

		return $productType;
	}


	public function getSkuByProductId($productId){
   	 	$data= $this->getDbConnection()->createCommand()
		   	 	->select('sku')
		   	 	->from($this->tableName())
		   	 	->where("id = '{$productId}'")
		   	 	->queryRow();
   	 	return $data['sku'];
   	}


   	/**
	 * @desc 检测主SKU是否符合刊登条件：
	 * 1、产品库中主sku又没有创建子SKU的，不能刊登，拦截后提示【异常主sku】
	 * 2、产品库中主sku有子sku的，主SKU不能当做单品进行刊登，重点排查刊登时可以选择单品多属性刊登的，拦截后提示【主sku不能当做单品刊登】
	 * @param unknown $publishType 刊登类型
	 * @param unknown $skuInfo     sku信息
	 */
	public function checkPublishSKU($publishType, $skuInfo = null){
		if(!$skuInfo){
			echo $this->failureJson(array('message' => "没有找到对应SKU信息"));
			Yii::app()->end();
		}

		//判断是否是主sku
		if($skuInfo['product_is_multi'] == Product::PRODUCT_MULTIPLE_MAIN){
			$productSelectedAttribute = new ProductSelectAttribute();
			$skuAttributeList = $productSelectedAttribute->getChildSKUListByProductID($skuInfo['id']);
			if(!$skuAttributeList){
				echo $this->failureJson(array('message' => "异常主sku"));
				Yii::app()->end();
			}

			if($publishType == 1){
				echo $this->failureJson(array('message' => "主sku不能当做单品刊登"));
				Yii::app()->end();
			}
		}

		/**
		 * 春节期间根据条件判断是否能刊登sku
		 * 条件1：sku创建时间在1月8日之后的不允许刊登
		 * 条件2：sku审核时间在1月10日后的，也不允许刊登
		 * 条件3：2月6日再放开这个限制
		 */
		$times = time();
        $twoSix = strtotime('2017-02-06 00:00:00');
        if($times < $twoSix){
        	//条件1判断
        	if($skuInfo['create_time'] >= '2017-01-08 00:00:00'){
        		echo $this->failureJson(array('message' => "此SKU被限制刊登，2月6日后解除"));
				Yii::app()->end();
        	}

        	//条件2判断
        	$qeInfo = ProductQeCheckRecord::model()->getOneBySKU($skuInfo['sku']);
        	if(isset($qeInfo['qe_check_time']) && $qeInfo['qe_check_time'] >= '2017-01-10 00:00:00'){
        		echo $this->failureJson(array('message' => "此SKU被限制刊登，2月6日后解除"));
				Yii::app()->end();
        	}
        }
	}


	public function failureJson($data) {
        $data['statusCode'] = 300;
        header("Content-Type:text/html; charset=utf-8");
        return json_encode($data);
    }


    /**
     * 验证最低利润率
     * @param unknown $currency 		币种
     * @param unknown $platformCode 	销售平台
     * @param unknown $sku 				sku
     * @param unknown $salePrice 		产品价格
     * @param unknown $commissionRate 	佣金比例
     * @param unknown $shipWarehouseID 	仓库ID
     * @return boolen
     */
    public function checkProfitRate($currency, $platformCode, $sku, $salePrice, $commissionRate = null, $shipWarehouseID = null){
    	$result = true;
    	//通过平台缩写取出最小毛利率
    	$salePriceInfo = SalePriceScheme::model()->getSalePriceSchemeByPlatformCode($platformCode);
    	if($salePriceInfo){
	    	$priceCal = new CurrencyCalculate();
			$priceCal->setCurrency($currency);
			$priceCal->setPlatform($platformCode);
			$priceCal->setSku($sku);
			if ($shipWarehouseID){
				$priceCal->setWarehouseID($shipWarehouseID);
			}			
			$priceCal->setSalePrice($salePrice);
			if($commissionRate){
				$priceCal->setCommissionRate($commissionRate);
			}			

			$profitRate = $priceCal->getProfitRate();
			if($profitRate < $salePriceInfo['lowest_profit_rate']){
				$result = false;
			}
		}

		return $result;
    }    
  
	
}