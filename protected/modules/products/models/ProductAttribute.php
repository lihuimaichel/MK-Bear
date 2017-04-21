<?php
/**
 * @package Ueb.modules.products.models
 * 
 * @author Bob <zunfengke@gmail.com>
 */
class ProductAttribute extends ProductsModel {   
    
    public $attribute_value_ids = null;
    
    public $attribute_value_names = null;

    const PRODUCT_FEATURES_CODE = 'product_features';//attrubute_code
    const ADAPTER_CODE  		= 'ProductAdapter';
    const PACKAGE_TYPE 			= 'package_type';
    const COLOR					= 'color';
    const SIZE     				= 'size';
    const STYLE     			= 'style';
    const IMAGE_ATTR			= 'image_attr';
    public  $eub_special_attribute = array(1,2,3,4,5,10,11,12,6907,6918,6919,6920,6921,6923); //易邮宝特殊属性配置
    public 	$battery_arr = array(4,5,12);
    public 	$wish_special_attribute = array(1,3,4,5,10,11,12);	//wish特殊属性
    public 	$amazon_special_attribute = array(2,3,4,5,10,11,12); //amazon特殊属性
	public 	$aliexpress_special_attribute =  array(4,5,12,10,2,3); //速卖通特殊属性
    /**
     * Returns the static model of the specified AR class.
     * @return CActiveRecord the static model class
     */
    public static function model($className = __CLASS__) {     
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'ueb_product_attribute';
    }
    
    /**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{	      
		return array(
			array('attribute_name,attribute_code,attribute_input_type,attribute_showtype_value,attribute_value_ids', 'required'), 
            array('attribute_is_public', 'numerical', 'integerOnly'=>true),
            array('attribute_code', 'length', 'max'=>30),
            array('attribute_name', 'unique'),
		);
	}
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array( 
            'id'                            => Yii::t('system', 'No.'),
            'attribute_name'                => Yii::t('products', 'Attribute name'),
            'attribute_code'                => Yii::t('products', 'Attribute code'),
            'attribute_input_type'          => Yii::t('products', 'Attribute input type'),
            'product_cost'                  => Yii::t('products', 'Product Cost'),          
            'attribute_showtype_value'      => Yii::t('products', 'Attribute show type'), 
            'attribute_value_names'         => Yii::t('products', 'Attribute value name'),
            'attribute_value_ids'           => Yii::t('products', 'Attribute value list'),
            'attribute_is_public'           => Yii::t('products', 'Whether the public attribute'),
            'modify_user_id'                => Yii::t('system', 'Modify User'),            
            'modify_time'                   => Yii::t('system', 'Modify Time'),
        );
    }
    
    /**
	 * @return array relational rules.
	 */
	public function relations() {
        return array();       
    }
    
    /**
     * get search info
     */
    public function search() {                
        $sort = new CSort();  
        $sort->attributes = array(  
            'defaultOrder'  => 'modify_time',   
            'modify_time', 
        	'attribute_name',  
        		    
        );      
        $dataProvider = parent::search(get_class($this), $sort);         
        $data = $this->addition($dataProvider->data);
        $dataProvider->setData($data);
        
       return $dataProvider;
    }
    
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
        return array(
            array(             
                'name'          => 'attribute_name',               
                'type'          => 'text',
                'search'        => '=',          
            ),              
            array(               
                'name'          => 'modify_time',               
                'type'          => 'text',
                'search'        => 'RANGE',
                'htmlOptions'   => array(                    
                    'class'     => 'date',
                    'dateFmt'   => 'yyyy-MM-dd HH:mm:ss',
                ),
            ),                      
        );
    }
    
    /**
     * order field options
     * @return $array
     */
    public function orderFieldOptions() {
    	return array(
    		'attribute_name','attribute_input_type','attribute_showtype_value','modify_time'
    	);
    }
    
    /**
     * addition information
     * 
     * @param type $dataProvider
     */
    public function addition($data) {
        $ids = array();       
        foreach ($data as $key => $val) {
            $ids[$key] = $val->id;
        }
        $map = UebModel::model('ProductAttributeMap')->getListValuesPairs($ids);       
        foreach ($data as $key => $val) {             
           $data[$key]->attribute_value_ids = isset($map[$val->id]['value_ids']) ? $map[$val->id]['value_ids'] : '';           
           $data[$key]->attribute_value_names = isset($map[$val->id]['value_names']) ? $map[$val->id]['value_names'] : '';
        }
        
        return $data;
    }
    
    /**
     * get public attribute list
     */
    public function getPublicAttributeList() {
        $data = array();
        $list =  $this->findAllByAttributes(array('attribute_is_public' => 1));
        foreach ($list as $key => $val) {
            $data[$val['id']] = $val;
        }
        return $data;
    }
    
    
    
    /**
     * get private attribute list
     */
    public function getPrivateAttributeList() {
    	$data = array();
    	$list =  $this->findAllByAttributes(array('attribute_is_public' => 0));
    	foreach ($list as $key => $val) {
    		$data[$val['id']] = $val;
    	}
    	return $data;
    }
    
    
    /**
     * render append option
     * 
     * @param type $attributeId
     * @param type $attributeValueId
     * @param type $attributeValName
     * @return string
     */
    public function appendOption($attributeId, $attributeValueId, $attributeValName) {
        $option = '';
        $row = $this->findByPk($attributeId);
        switch ($row['attribute_showtype_value']) {
            case 'list_box':
                $option = '<option value="'.$attributeValueId.'">'.$attributeValName.'</option>'; 
                break;
            case 'check_box':
                $option = $attributeValName .' <input id="attr_'. $attributeValueId .'" type="checkbox" name="attr['.$attributeId.'][]" value="'.$attributeValueId.'">';
                break;
            default:
                break;
        }
        
        return $option;
    }

    /**
     * get index nav tab id 
     * 
     * @return type
     */
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/products/productattr/list');
    }
    
    //Nick 2013-9-17 多语言翻译
    public function getMoreLanguage($id) {
    	$info = $this->findByPk($id);
    	$valueLangList = UebModel::model('ProductAttributeLang')->getListByName($info['attribute_name']);
    	$langList = UebModel::model('MultiLanguage')->getListByAttributed(1);
    	if (! empty($valueLangList) && ! empty($langList)) {
    		foreach ($langList as $key => &$val) {
    			$val['attribute_lang_name'] = isset($valueLangList[$val['language_code']]) ? $valueLangList[$val['language_code']] : '';
    		}
    	}
    	return $langList;
    }
    
    /**
     * after save
     * @author Nick 2013-10-08
     */
    public function afterSave() {
    	parent::afterSave();
    	foreach ( $this->getAttributes() as $key => $val ) {
    		if ( ! $this->getIsNewRecord() && $val == $this->beforeSaveInfo[$key] ) {
    			continue;
    		}
    		$label = $this->getAttributeLabel($key);
    		if (in_array($key, array( 'modify_user_id', 'modify_time', 'id'))) {
    			continue;
    		} 
    		else if($key == 'attribute_input_type'){
    			if ( $this->getIsNewRecord() ) {
    				$attribute_input_type = VHelper::getAttributeInputTypeConfig();
    				$msg = MHelper::formatInsertFieldLog($label, $attribute_input_type[$val]);
    			} else {
    				$attribute_input_type = VHelper::getAttributeInputTypeConfig();
    				$msg = MHelper::formatUpdateFieldLog($label, $attribute_input_type[$this->beforeSaveInfo[$key]], $attribute_input_type[$val]);
    			}
    		}else if($key == 'attribute_showtype_value'){
    			if ( $this->getIsNewRecord() ) {
    				$attribute_showtype_value = VHelper::getAttributeShowTypeConfig();
    				$msg = MHelper::formatInsertFieldLog($label, $attribute_showtype_value[$val]);
    			} else {
    				$attribute_showtype_value = VHelper::getAttributeShowTypeConfig();
    				$msg = MHelper::formatUpdateFieldLog($label, $attribute_showtype_value[$this->beforeSaveInfo[$key]], $attribute_showtype_value[$val]);
    			}
    		}else if($key == 'attribute_is_public'){
    			if ( $this->getIsNewRecord() ) {
    				$attribute_is_public = VHelper::getStatusConfig();
    				$msg = MHelper::formatInsertFieldLog($label, $attribute_is_public[$val]);
    			} else {
    				$attribute_is_public = VHelper::getStatusConfig();
    				$msg = MHelper::formatUpdateFieldLog($label, $attribute_is_public[$this->beforeSaveInfo[$key]], $attribute_is_public[$val]);
    			}
    		}
    		else {
    			if ( $this->getIsNewRecord() ) {
    				$msg = MHelper::formatInsertFieldLog($label, $val);
    			} else {
    				$msg = MHelper::formatUpdateFieldLog($label, $this->beforeSaveInfo[$key], $val);
    			}
    		}
    		$this->addLogMsg($msg);
    	}
    }
    
    
    /**
     * get AttributeCode by id
     * @return  AttributeName
     * @author Super 2014-10-24
     */
    public function getAttributeCode($id){
    	$name = $this->getDbConnection()->createCommand()
    	->select('attribute_code')
    	->from(self::tableName())
    	->where("id = '{$id}'")
    	->queryRow();
    	return $name['attribute_code'];
    }
    
    
    /**
     * get AttributeName by id
     * @return  AttributeName
     * @author Nick 2013-10-14
     */
    public function getAttributeName($id){
    	$name = $this->getDbConnection()->createCommand()
    	->select('attribute_name')
    	->from(self::tableName())
    	->where("id = '{$id}'")
    	->queryRow();
    	return $name['attribute_name'];
    }
    public function getAttributeNameById($id){
    	$name = $this->getDbConnection()->createCommand()
    	->select('attribute_name')
    	->from(self::tableName())
    	->where("id = '{$id}'")
    	->queryRow();
    	return $name['attribute_name'];
    }
    
    public function getAttributeIdByAttributeCode($attrCode){
    	$data = $this->getDbConnection()->createCommand()
    	->select('id')
    	->from(self::tableName())
    	->where("attribute_code = '{$attrCode}'")
    	->queryRow();
    	return $data['id'];
    }
    
    /**
     * getAttribute Id And Name By attributeId
     * @return array
     * @author Nick 2013-10-28
     */
    public function getAttributeIdAndName(){
    	$return = array();
    	$attributeId = $this->getDbConnection()->createCommand()
    	->select('id,attribute_name')
    	->from(self::tableName())
    	->where("attribute_is_public = '1'")
    	->queryAll();
    	foreach ($attributeId as $value){
    		$attributeValueIds = UebModel::model('ProductAttributeMap')->getAttributeList($value['id']);
    		foreach ($attributeValueIds as $key => $val){
    			//get cn name,add by ethan 2014.8.9
    			$attribute_value_name_cn = UebModel::model('ProductAttributeValueLang')->getAttributeNameByCode($val['attribute_value_name'],CN);
    			$return[$value['attribute_name']][$val['id']] = $attribute_value_name_cn ? $attribute_value_name_cn : $val['attribute_value_name'];
    		}
    	}
    	
    	return $return;
    }
    /**
     * getAttribute Id And Name By attributeId
     * @return array
     * @author Nick 2013-10-28
     */
    public function getAttributeIdAndNameList(){
    	$return = array();
    	$attributeId = $this->getDbConnection()->createCommand()
    	->select('id,attribute_name,attribute_showtype_value')
    	->from(self::tableName())
    	->where("attribute_is_public = '1'")
    	->queryAll();    	
    	foreach ($attributeId as $value){
    		$attributeValueIds = UebModel::model('ProductAttributeMap')->getAttributeList($value['id']);
    		foreach ($attributeValueIds as $key => $val){
    			$attribute_value_name_cn = UebModel::model('ProductAttributeValueLang')->getAttributeNameByCode($val['attribute_value_name'],CN);   			
    			$return[$value['attribute_name']]['type']=$value['attribute_showtype_value'];
    			$return[$value['attribute_name']]['attribute'][$val['id']] = $attribute_value_name_cn ? $attribute_value_name_cn : $val['attribute_value_name'];
    		}
    	}
    	return $return;
    }
    
    /**
     * Get The Attribute List of Specific Code 
     * @author Gordon
     * @param Code of Attr $attributeCode
     * @return Specific Code Attr List
     */
    public function getAttributeListByCode($attributeCode){
    	$attrList = array();
    	$attribute = $this->find("attribute_code=:attribute_code",array(':attribute_code'=>$attributeCode));
    	$attributeList = UebModel::model('ProductAttributeMap')->getAttributeList($attribute['id']);
    	foreach($attributeList as $value){
    		$attrList[$value['id']] = $value['attribute_value_name'];
    	}
    	
    	return $attrList;
    }

    public function getAttributeList($type=self::PRODUCT_FEATURES_CODE){
    	$attributeList = $this->getAttributeListByCode($type);
    	return $attributeList;
    }
    
    public function getAttributeIdByCode($code=self::PRODUCT_FEATURES_CODE){
    	$attribute = $this->find("attribute_code=:attribute_code",array(':attribute_code'=>$code));
    	return $attribute['id'];
    }

    /**
     * Get The product Attribute cn name List of Specific Code 
     * @author Gordon
     * @param Code of Attr $attributeCode
     * @return Specific Code Attr List
     */
    public function getProductAdapterAttributeByCode($attributeCode,$attributeId=null){
    	$result = array();
    	$attrList = $this->getAttributeListByCode($attributeCode);
    	if ($attrList) {
    		foreach ($attrList as $attribute_id=>$attribute_value_name){
    			$result[$attribute_id] = UebModel::model('ProductAttributeValueLang')->getAttributeNameByCode($attribute_value_name);
    		}
    	}
    	if ($attributeId !== null){
    		return $result[$attributeId];
    	}
    	return $result;
    }
    
    public function getNopublicAttr(){
    	$noPuclicAttr = $this->getDbConnection()->createCommand()
    	->select('*')
    	->from(self::tableName())
    	->where("attribute_is_public = '0'")
    	->queryAll();

    	return $noPuclicAttr;
    }
	public function getNopublicAttrById($id){
		$noPuclicAttrById = $this->getDbConnection()->createCommand()
		->select('*')
		->from(self::tableName())
		->where("id = '{$id}'")
		->queryRow();
		return $noPuclicAttrById;
	}
	/**
	 * 获取非公共属性Id
	 */
	public function getNotPublicAttrId(){
		$noPuclicAttrById = $this->getDbConnection()->createCommand()
		->select('id')
		->from(self::tableName())
		->where("attribute_is_public = 0")
		->queryAll();
		$arrAttrId=array();
		if(!empty($noPuclicAttrById)){	
			foreach ($noPuclicAttrById as $key=>$val){
				$arrAttrId[$key]=$val['id'];
			}
		}
		return $arrAttrId;
	}
	
	/**
	 * 获取公共属性Id
	 */
	public function getPublicAttrId(){
		$noPuclicAttrById = $this->getDbConnection()->createCommand()
		->select('id')
		->from(self::tableName())
		->where("attribute_is_public = 1")
		->queryAll();
		$arrAttrId=array();
		if(!empty($noPuclicAttrById)){
			foreach ($noPuclicAttrById as $key=>$val){
				$arrAttrId[$key]=$val['id'];
			}
		}
		return $arrAttrId;
	}
	
	/**
	 * @desc 根据id数组来获取对应的属性数据
	 * @param array $ids
	 * @param string $fields
	 * @return mixed
	 */
	public function getAttributeListByIds($ids, $fields = '*'){
		return $this->getDbConnection()->createCommand()
		->select($fields)
		->from(self::tableName())
		->where(array('in', 'id', $ids))
		->queryAll();
	}


	public function getAttributeOptions($attributeCode)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->from($this->tableName(). ' as a')
            ->join(ProductAttributeMap::model()->tableName(). ' as m', 'a.id = m.attribute_id')
            ->join(ProductAttributeValue::model()->tableName() . ' as v', 'v.id = m.attribute_value_id')
            ->where('a.attribute_code =:attributeCode', array(':attributeCode'=> $attributeCode));
        #echo $queryBuilder->getText();
        return $queryBuilder->queryAll();

    }
}
