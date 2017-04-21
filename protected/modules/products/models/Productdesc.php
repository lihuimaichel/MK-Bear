<?php

class Productdesc extends ProductsModel
{
	public $lang_code = array();
	public $customs_name = '';
	public $description = array();
	
	function __construct(){
		//get language info
		$lang_code = MultiLanguage::model()->getLangList();
		$this->lang_code = $lang_code;
	}
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
		return 'ueb_product_description';
	}
    
    public function rules() {
        $rules = array(
            array('title,sku,description', 'required'),
        	array('product_id', 'numerical', 'integerOnly' => true),
        	array('sku,language_code,customs_name', 'length', 'max' => 250),
        	array('title', 'length', 'max' => 75),
//         	array('customs_name', 'exist', 'attributeName' => 'customs_name', 'className' => get_class($this)),
        );
        return $rules;
    }
    
    /**
     * @return array relational rules.
     */
    public function relations()
    {
    	return array(
    		'product'   => array(self::BELONGS_TO, 'Product','product_id'),
    	);
    	
    }
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
			'id'                    => Yii::t('system', 'No.'),
            'sku'    				=> 'SKU',
            'title'					=> Yii::t('system', 'Title'),
            'customs_name'			=> Yii::t('product', 'Surname of the customs broker'),        		
        	'description'			=> Yii::t('system', 'Description'),
        	'included'            	=> 'Included',
        	'create_time'			=> Yii::t('users', 'Allocate time'),
        	'language_code'			=> Yii::t('system', 'Language Code'),
        	'language_code'			=> Yii::t('system', 'Language Code'),	
        );
    }
    /**
     * get search info
     */
    public function search() {
    	$sort = new CSort();
    	$sort->attributes = array(
    			'defaultOrder'  => '',
    			'id',
    	);
    	$with = array();
    	return parent::search(get_class($this), $sort);
    }
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
    	return array();
    }
    
       
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/products/Productdesc/list');
    } 
    public function db() {
    	return $this->getDbConnection();
    }
    
    /**
     * @desc 获取标题列表
     * @param unknown $sku
     * @param string $lang_code
     * @return mixed
     */
    public function getTitlesBySkuAndLanguageCode($sku, $lang_code = ''){
    	if(empty($lang_code)){
    		$lang_code = "english";
    	}
    	if(!is_array($sku)){
    		$sku = array($sku);
    	}
    	$obj = $this->getDbConnection()->createCommand()
			    	->select('sku,title')
			    	->from(self::tableName())
			    	->where(array("IN", "sku", $sku));
    	if($lang_code !=''){
    		$obj->andWhere('language_code=:language_code',array(':language_code'=>$lang_code));
    	}
    	$data =  $obj->queryAll();
    	return $data;
    }
    
    /**
     * get the description by sku
     */
    public function getDescriptionInfoBySkuAndLanguageCode($sku,$lang_code=''){
    	$defaultLangCode = "english";
    	if(empty($lang_code)){
    		$lang_code = $defaultLangCode;
    	}
    	$obj = $this->getDbConnection()->createCommand()
	    	->select('id,product_id,sku,language_code,title,customs_name,description,included')
	    	->from(self::tableName())
	    	->where("sku ='".$sku."'");
    	if($lang_code !=''){
    		$obj->andWhere('language_code=:language_code',array(':language_code'=>$lang_code));
    	}
	    $data =  $obj->queryRow();
	    if(empty($data)){
	    	$obj = $this->getDbConnection()->createCommand()
				    	->select('id,product_id,sku,language_code,title,customs_name,description,included')
				    	->from(self::tableName())
				    	->where("sku ='".$sku."'");
	    	$obj->andWhere('language_code=:language_code',array(':language_code'=>$defaultLangCode));
	    	$data =  $obj->queryRow();
	    }
	    return $data;
    }
    
    /**
     * get the description by product_id and language_code
     */
    public function getDescriptionInfoByProductIdAndLanguageCode($product_id, $lang_code=''){
    	$defaultLangCode = "english";
    	if(empty($lang_code)){
    		$lang_code = $defaultLangCode;
    	}
    	$obj =  $this->getDbConnection()->createCommand()
			    	->select('id,sku,product_id,language_code,title,customs_name,description,included')
			    	->from(self::tableName())
			    	->where("product_id =".$product_id);
    	if($lang_code !=''){
    		$obj->andWhere('language_code=:language_code',array(':language_code'=>$lang_code));
    	}
    	$data =  $obj->queryRow();
    	
    	if(empty($data)){
    		$obj =  $this->getDbConnection()->createCommand()
			    		->select('id,sku,product_id,language_code,title,customs_name,description,included')
			    		->from(self::tableName())
			    		->where("product_id =".$product_id);
    		$obj->andWhere('language_code=:language_code',array(':language_code'=>$defaultLangCode));
    		$data =  $obj->queryRow();
    	}
    	return $data;
    }
    
    /**
     * get sku by title
     */
    public function getSkuByTitle($title){
    	if($title=='') return array();
    	$lang_code = MultiLanguage::model()->getLangByCode(CN);//Yii::app()->languag
    	$data =  $this->getDbConnection()->createCommand()
			    	->select('sku')
			    	->from(self::tableName())
			    	->where("title like '%".$title."%'")
			    	->andwhere('language_code =:language_code',array(':language_code'=>$lang_code['Chinese']['language_code']))
			    	->queryAll();
    	$list = array();
    	if($data){
	    	foreach($data as $key=>$val){
	    		$list[]=$val['sku'];
	    	}
    	}
    	return $list;
    }
   
    /**
     * 任意标题搜索 获取sku
     *
     */
    public function getSkuByAllTitle($title){
    	if($title=='') return array();
    	$title=explode(' ',$title);
    	$newtitle=array();
    	foreach ($title as $val){
    		$newtitle[]='%'.$val.'%';
    	}
    	$data =  $this->getDbConnection()->createCommand()
    	->select('sku')
    	->from(self::tableName())
		->where(array('like', 'title',$newtitle))
    	->queryAll();
    	$list = array();
    	if($data){
    		foreach($data as $key=>$val){
    			$list[]=$val['sku'];
    		}
    	}
    	return $list;
    }
    /**
     * 保存描述信息
     * $arr：表单提交的数据
     */
    public function productDescriptionSave($arr){   
    	//把该sku的标题放到标题里面
		if(isset($arr['childSku'])){
			foreach ($arr['childSku'] as $val){
				$desc_info = $this->getDescriptionInfoBySkuAndLanguageCode($val['sku'],$arr['language_code']);							
				$model = new self();
				if($desc_info){
					$model = $this->findByPk($desc_info['id']);
					$model->setAttribute('modify_time', date('Y-m-d H:i:s'));
					$model->setAttribute('modify_user_id', Yii::app()->user->id);
				}else{
					$model->setIsNewRecord(true);
					$model->setAttribute('create_time', date('Y-m-d H:i:s'));
					$model->setAttribute('create_user_id', Yii::app()->user->id);
				}
				$model->setAttribute('product_id', $val['product_id']);
				$model->setAttribute('sku', $val['sku']);
				$model->setAttribute('language_code', $arr['language_code']);
				$model->setAttribute('title', $val['title']);
				$model->setAttribute('customs_name', $arr['customs_name']);
				$model->setAttribute('included', $arr['included']);
				$model->setAttribute('description', $arr['description']);
				$model->save();
			}
		}    	
		
		if($arr['language_code']!='Chinese'){			
			UebModel::model('ProductTitleWord')->saveSkuAndTitle($arr);
		}
		//get desc_info by product_id
		$desc_info = $this->getDescriptionInfoBySkuAndLanguageCode($arr['sku'],$arr['language_code']);			
    	
		$model = new self();
    	if($desc_info){
    		$model = $this->findByPk($desc_info['id']);
    		$model->setAttribute('modify_time', date('Y-m-d H:i:s'));
    		$model->setAttribute('modify_user_id', Yii::app()->user->id);
    	}else{
    		$model->setIsNewRecord(true);
    		$model->setAttribute('create_time', date('Y-m-d H:i:s'));
    		$model->setAttribute('create_user_id', Yii::app()->user->id);
    	}
    	$model->setAttribute('product_id', $arr['product_id']);
    	$model->setAttribute('sku', $arr['sku']);    	
    	$model->setAttribute('language_code', $arr['language_code']);
    	$model->setAttribute('title', $arr['title']);
    	$model->setAttribute('customs_name', $arr['customs_name']);
    	$model->setAttribute('included', $arr['included']);
    	$model->setAttribute('description', $arr['description']); 
    	$model->save();  
 
    }
    /**
     *功能：保存描述信息报关名
     *批量保存时用的
     */
    public function customsNameSave($ids,$value){
//     	$lang_code = MultiLanguage::model()->getLangByCode(CN);

    	foreach($ids as $product_id){
    		$model = new self();
	    	$descInfo = $this->getDescriptionInfoByProductIdAndLanguageCode($product_id,CN);
	    	
	    	if($descInfo){
	    		$model = $this->findByPk($descInfo['id']);
	    		$model->setAttribute('modify_time', date('Y-m-d H:i:s'));
	    		$model->setAttribute('modify_user_id', Yii::app()->user->id);
	    	}else{
	    		$model->setIsNewRecord(1);
	    		$arr = Uebmodel::model('Product')->getById($product_id);
	    		$model->setAttribute('sku', $arr['sku']);
	    		$model->setAttribute('title', 'Null');
	    		$model->setAttribute('description', 'Null');
	    		$model->setAttribute('included', '');
	    		$model->setAttribute('create_time', date('Y-m-d H:i:s'));
	    		$model->setAttribute('create_user_id', Yii::app()->user->id);
	    	}
	    	$model->setAttribute('product_id', $product_id);
	    	$model->setAttribute('customs_name', $value);
	    	$model->setAttribute('language_code', $lang_code['language_code']);
	    	$model->save();
    	}
    }
    
    
    /**
     * 保存产品标题
     * 
     */
    public function saveTitle($title,$sku,$id)
    {
    	$model = new self();
    	$model->setIsNewRecord(1);
    	$model->setAttribute('sku', $sku);
    	$model->setAttribute('product_id', $id);
    	$model->setAttribute('title', $title);
    	$model->setAttribute('description', 'Null');
	    $model->setAttribute('included', '');
	    $model->setAttribute('create_time', date('Y-m-d H:i:s'));
	    $model->setAttribute('create_user_id', Yii::app()->user->id);
	    $model->setAttribute('language_code', CN);
	    $model->save();
	    
    }
    
    /**
     * get producedescription by product info
     * $data:product info,   =>array()
     */
    public function getProductdescByProductInfo($data,$languageCode=CN){
    	$result = array();
    	$skuArr = array();
    	if($data){
    		foreach($data as $key=>$val){
    			$skuArr[$key] = $val['sku'];
    		}
    	}
    	return $this->getListPairs($skuArr,$languageCode);
    }
    
    /**
     * get list pairs
     *
     * @param array $skuArr
     * $languageCode:需要获取的指定语言描述信息,分字符串\数组两种形式
     * @return array $data
     */
    public function getListPairs($skuArr,$languageCode=array(CN,EN)) {
    	$selectObj = $this->getDbConnection()->createCommand()
    	->select('id,sku,product_id,language_code,title,description,included')
    	->from(self::tableName())
    	->where(array('in', 'sku', $skuArr));
    	if($languageCode){
    		if(is_string($languageCode)){
    			$selectObj->andwhere('language_code=:language_code',array(':language_code'=>$languageCode));
    		}elseif(is_array($languageCode) && !empty($languageCode)){
    			$selectObj->andwhere(array('in', 'language_code', $languageCode));
    		}else{}
    	}

    	$list = $selectObj->queryAll();
    	$data = $this->addGoogleCode($list,$languageCode);unset($list);
    	return $data;
    }
    /**
     *  
     * @param get $languageCode
     * @return multitype:string unknown
     */
    
    public function getProductLanguageCode($languageCode){	
    		$info= $this->getDbConnection()->createCommand()
    		->select('sku')
    		->from($this->tableName())
    		->where("language_code = '{$languageCode}'")
    		->queryAll();
    		$list=array();
    		if($info){
    			foreach ($info as $val){
    				$list[]=$val['sku'];
    			}
    		}else{
    			$list[]='';
    		}
    		return $list;   	
   	 }
    /**
     * get list pairs
     *
     * @param array $productIdArr
     * $languageCode:需要获取的指定语言描述信息,分字符串
     * @return array $data
     */
    public function getListPairsByproductIdArr($productIdArr,$languageCode='') {
    	
    	$selectObj = $this->getDbConnection()->createCommand()
    	->select('id,sku,product_id,language_code,title,description,included')
    	->from(self::tableName())
    	->where(array('in', 'product_id', $productIdArr));
    	if($languageCode !=''){
    		$selectObj->andwhere('language_code=:language_code',array(':language_code'=>$languageCode));
    	}
    	$list = $selectObj->queryAll();
    	return $list;
    }
    
    /**
     *
     * @param get title
     * @return multitype:string unknown
     */
    
    public function getProductTitle($title){
    	
    	$info= $this->getDbConnection()->createCommand()
    	->select('sku')
    	->from($this->tableName())
    	->where(array('in','language_code',array(CN,EN)))
    	->andWhere("title LIKE '%{$title}%'")
    	->queryAll();   	
    	$list=array();
    	if($info){
    		foreach ($info as $val){
    			$list[]=$val['sku'];
    		}
    	}else{
    		$list[]='';
    	}
    	return $list;
    }
    /**
     * 功能：向产品信息中加多语言描述
     * $data:产品的多维数组
     * $getType:指定所需获取的数据类型，为1则为数组，否则为对象
     */
    public function addProductDescription($data,$languageCode,$getType=0){
    	foreach ($data as $key => $val) {
    		$curSku = isset($getType) ? $val['sku'] : $val->sku;
    		$skuArr[$key] = $curSku;
    		$data[$key]['all_language_desc'] = $this->getProductDescBySkuAndLanguageCode($curSku,$languageCode);
    	}
    	return $data;
    	
    }
    
    /**
     * 功能：向产品信息中加单语言描述
    * $data:产品的多维数组
    * 
    */
    public function addProductCnTitle($data,$languageCode=CN){
    	foreach ($data as $key => $val) {
    		$skuArr[$key] = $val['sku'];
    		$data[$key]['cn_name'] = $this->getProductCnTitleBySkuAndLanguageCode($val['sku'],$languageCode);
    	}
    	return $data;
    	 
    }
    /**
     * 根据 language_code、sku获取指定的描述信息
    * $language_code:[1,字符串,CN,EN,DE;)
    * $sku:字符串
    */
    public function getProductCnTitleBySkuAndLanguageCode($sku,$languageCode=CN){
    	$result = array();
    	$selectObj = $this->getDbConnection()->createCommand()
    	->select('title')
    	->from(self::tableName())
    	->where('sku =:sku',array(':sku'=>$sku));
    	$selectObj->andwhere('language_code=:language_code',array(':language_code'=>$languageCode));
    	$result = $selectObj->queryRow();
    	return $result['title'] ? $result['title'] : '&nbsp;';
    }
    /**
     * 根据 language_code、product_id获取指定的描述标题信息
    * $language_code:[1,字符串,CN,EN,DE;)
    * $sku:字符串
    */
    public function getProductCnTitleByProductIdAndLanguageCode($productId,$languageCode=CN){
    	$result = array();
    	$selectObj = $this->getDbConnection()->createCommand()
    	->select('title')
    	->from(self::tableName())
    	->where('product_id =:product_id',array(':product_id'=>$productId));
    	$selectObj->andwhere('language_code=:language_code',array(':language_code'=>$languageCode));
    	$result = $selectObj->queryRow();
    	return $result['title'] ? $result['title'] : '&nbsp;';
    }
    
    /**
     * 获取语言信息
     */
    public function getLanguageType($languageCode){
//     	$lang_code_list = Yii::app()->params['multi_language'];
    	$lang_code = MultiLanguage::model()->getLangByCode($languageCode);
    	return $lang_code;
    }
    /**
     *  向产品描述信息中添加语言类别，即是中文还是英文
     *  $languageCode：只取指定语言的语言类别
     */
    public function addGoogleCode($data,$languageCode){
    	$google_code = $this->getLanguageType($languageCode);
    	foreach($data as $key=>$val){
    		$data[$key]['cn_code']=$google_code[$val['language_code']]['cn_code'];
    	}
    	return $data;
    }
    /**
     * 根据 language_code、sku获取指定的描述信息
     * $language_code:[1,字符串,CN,EN,DE; 2,数组：array(CN,EN,DE...)
     * $sku:数组
     */
    public function getDescBySkuAndLanguageCode($skuArr,$languageCode){
    	$selectObj = $this->getDbConnection()->createCommand()
    	->select('id,sku,product_id,language_code,title,description,included')
    	->from(self::tableName())
    	->where(array('in', 'sku', $skuArr));
    	if($languageCode){
    		if(is_string($languageCode)){
    			$selectObj->andwhere('language_code=:language_code',array(':language_code'=>$languageCode));
    		}elseif(is_array($languageCode) && !empty($languageCode)){
    			$selectObj->andwhere(array('in', 'language_code', $languageCode));
    		}else{}
    	}
//     	->andwhere('language_code =:language_code',array(':language_code'=>$lang_code));
    	$list = $selectObj->queryAll();
    	return $list;
    }
    /**
     * 根据 language_code、sku获取指定的描述信息
     * $language_code:[1,字符串,CN,EN,DE; 2,数组：array(CN,EN,DE...)
     * $sku:字符串
     */
    public function getProductDescBySkuAndLanguageCode($sku,$languageCode){
    	$result = array();
    	$selectObj = $this->getDbConnection()->createCommand()
    	->select('id,sku,product_id,language_code,title,description,included')
    	->from(self::tableName())
    	->where('sku =:sku',array(':sku'=>$sku));
    	if($languageCode){
    		if(is_string($languageCode)){
    			$selectObj->andwhere('language_code=:language_code',array(':language_code'=>$languageCode));
    		}elseif(is_array($languageCode) && !empty($languageCode)){
    			$selectObj->andwhere(array('in', 'language_code', $languageCode));
    		}else{}
    	}

    	$list = $selectObj->queryAll();
    	if($languageCode){
    		$list = $this->addGoogleCode($list,$languageCode);
    	}
    	foreach($list as $k=>$v){
    		$result[$v['language_code']] = $v;
    	}
    	return $result;
    }
    /**
     * getEnglishTitle
     */

    public function getEnglishByProductId($productId){
    	$info =  $this->getDbConnection()->createCommand()
    	->select('title')
    	->from($this->tableName())
    	->where("product_id = '{$productId}' AND language_code='english'")
    	->queryRow();
    	if($info){
    		return $info['title'];
    	}
    }
    /**
     * after save
     * @author Nick 2013-10-11
     */
    public function afterSave() {
    	parent::afterSave();
    	foreach ( $this->getAttributes() as $key => $val ) {
    		if ( ! $this->getIsNewRecord() && $val == $this->beforeSaveInfo[$key] ) {
    			continue;
    		}
    		$label = $this->getAttributeLabel($key);
    		if (in_array($key, array( 'sku','description','included','create_user_id','create_time','modify_user_id', 'modify_time', 'id'))) {
    			continue;
    		}
    		if ( $this->getIsNewRecord() ) {
    			$msg = MHelper::formatInsertFieldLog($label, $val);
    		} else {
    			$msg = MHelper::formatUpdateFieldLog($label, $this->beforeSaveInfo[$key], $val);
    		}
    		$this->addLogMsg($msg);
    	}
    }
    
    public function getInfoBySkuCode($packageCode,$cn='Chinese'){

    	$info = $this->getDbConnection()->createCommand()
    	->select('title')
    	->from($this->tableName())
    	->where("sku = '{$packageCode}' AND language_code = '{$cn}'")
    	->queryRow();
   
    	return $info;
    	
    }
    /**
     * 查看title是否存在
     * @param  $title
     * @return boolean
     */
    public function getCnTitle($title){
    	$info = $this->getDbConnection()->createCommand()
			    ->select('*')
			    ->from($this->tableName())
			    ->where("title ='".$title."' and language_code='Chinese'")
			    ->queryRow();
    	return $info['title'];   	
    } 
    public function getTitleBySku($sku){
      	$info =  $this->getDbConnection()->createCommand()
			    	->select('*')
			    	->from($this->tableName())
			    	->where("sku = :sku",array(':sku' => $sku))
			    	->queryAll();	
      	$title = array();
    	foreach($info as $v){
    		$title[$v['language_code']] = isset($v['title'])?$v['title']:'';
    	}
    	return $title;	
    }
    /**
     * 产品任务审核
     * 标题 编辑审核
     * @param  $title
     * @return boolean
     */
    
    public function getTitleDescBySku($sku){
    	$info =  $this->getDbConnection()->createCommand()
	    	->select('*')
	    	->from($this->tableName())
	    	->where("sku = :sku",array(':sku' => $sku))
	    	->queryAll();
    	$descArr=array();
    	if($info){
    		foreach ($info as $desc){
    			if($desc['language_code']='Chinese'){
    				$descArr['ctitle']=$desc['title'];
    				$descArr['cdesc']=$desc['description'];
    			}
    			if($desc['language_code']='english'){
    				$descArr['etitle']=$desc['title'];
    				$descArr['edesc']=$desc['description'];
    			}
    		}
    		if(!empty($descArr['ctitle']) && !empty($descArr['cdesc']) && !empty($descArr['etitle']) && !empty($descArr['edesc'])){
    			return true;
    		}else{
    			return false;
    		}
    	}else{
    		return false;
    	}
    	
    }
    /**
     * key转换为小写
     */
    public function getTitleBySkuK($sku){
    	$info = $this->getDbConnection()->createCommand()
    		->select('title, language_code')
    		->from($this->tableName())
    		->where("sku = :sku",array(':sku' => $sku))
    		->queryAll();
    	foreach($info as $v){
    		$title[strtolower($v['language_code'])] = $v['title'];
    	}
    	return $title;
    }
    
    /**
     * @desc 获取描述
     * @param unknown $sku
     * @return unknown
     */
    public function getDescBySku($sku){
    	$info = $this->getDbConnection()->createCommand()
    	->select('description, language_code')
    	->from($this->tableName())
    	->where("sku = :sku",array(':sku' => $sku))
    	->queryAll();
    	if(empty($info)) return false;
    	foreach($info as $v){
    		$description[strtolower($v['language_code'])] = $v['description'];
    	}
    	return $description;
    }
    
    /**
     * getEnglishTitle
     */
    
    public function getEnglishBySku($sku){
    	$info =  $this->getDbConnection()->createCommand()
    	->select('title')
    	->from($this->tableName())
    	->where("sku = '{$sku}' AND language_code='english'")
    	->queryRow();
    	if($info){
    		return $info['title'];
    	}
    }
    
    
    /**
     * 标题搜索 去掉标题中多余空格只保留一个
     * @param  $string
     *
     */
    static public function merge_spaces($string){
    	return preg_replace ("/\s(?=\s)/","\\1", $string);
    }
}