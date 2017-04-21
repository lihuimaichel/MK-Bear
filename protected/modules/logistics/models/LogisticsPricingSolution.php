<?php
/**
 * @package Ueb.modules.logistics.models
 * 
 * @author Gordon
 */
class LogisticsPricingSolution extends LogisticsModel { 
	
	const PRODUCT_FEATURES_CODE = 'product_features';
    public $ship_type='';
    /**
     * Returns the static model of the specified AR class.
     * @return CActiveRecord the static model class
     */
    public static function model($className = __CLASS__) {     
        return parent::model($className);
    }
    
    public function getModelName(){
    	return str_replace('Controller', "", get_class($this));
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'ueb_logistics_pricing_solution';
    }
    
    /**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{	      
		return array(
			array('logistics_id,ship_group,ship_min_weight,ship_max_weight,ship_first_weight,ship_first_price,
					ship_step,ship_step_price,ship_weight_diff,ship_discount,ship_add_price,ship_add_price_discount,ship_attribute','numerical'),
            array('ship_currency,ship_area','length', 'max'=>10), 
			array('ship_include_countries,ship_exclude_countries,ship_remark','default'),       
		);
	}
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array( 
        	'id'										=> Yii::t('logistics', 'ID'),		
        	'is_main'									=> Yii::t('logistics', 'Main Case'),
        	'ship_min_weight'							=> Yii::t('logistics', 'Min Weight'),
        	'ship_max_weight'							=> Yii::t('logistics', 'Max Weight'),
        	'ship_first_weight'							=> Yii::t('logistics', 'First Weight'),
        	'ship_first_price'							=> Yii::t('logistics', 'First Weight Price'),
        	'ship_step'									=> Yii::t('logistics', 'Ship Step'),
        	'ship_step_price'							=> Yii::t('logistics', 'Ship Step Price'),
        	'ship_weight_diff'							=> Yii::t('logistics', 'Weight Diff'),
        	'ship_discount'								=> Yii::t('logistics', 'Discount'),
        	'ship_add_price'							=> Yii::t('logistics', 'Addition Price'),
        	'ship_add_price_discount'					=> Yii::t('logistics', 'Addition Discount'),
        	'ship_attribute'							=> Yii::t('logistics', 'Ship Attribute'),
        	'ship_currency'								=> Yii::t('logistics', 'Currency'),
        	'ship_area'									=> Yii::t('logistics', 'Area'),
        	'ship_include_countries'					=> Yii::t('logistics', 'Include Countries'),
        	'ship_exclude_countries'					=> Yii::t('logistics', 'Exclude Countries'),
        	'ship_remark'								=> Yii::t('logistics', 'Remark'),
        	'logistics_id'								=>	Yii::t('logistics', '物流方式'),
        	'ship_group'								=>	Yii::t('logistics', '方案组'),
        );
    }
	public function search() {
		$sort = new CSort();
		$sort->attributes = array(
            'defaultOrder'  => 'id',                     
		);
		$with = array();
		$dataProvider = parent::search(get_class($this), $sort);
		$dataProvider->setData($data);	
		return $dataProvider;
	}	
    public function filterOptions() {
    	$result=array(
    		array(
    			'name'          => 'id',
    			'type'          => 'text',
    			'search'        => '=',
    			'htmlOptions'   => array()
    		),
    		array(
    			'name'          => 'logistics_id',
				'type'          => 'dropDownList',
				'search'        => '=',
				'data'          => UebModel::model('Logistics')->getAlllogistics(),
				'htmlOptions'   => array()
    		),		
    	); 
    	return $result;
    }

    /**
     * save the logistics pricing solution information
     * @param Insert or update data $pricingData
     * @param Logistics ID $logistics_id
     * @return boolean
     */
    public function savePrcingInfo($pricingData,$logistics_id,$keyArr=array()){
    	$result = true;
    	$this->deleteAll('logistics_id = '.$logistics_id);
    	foreach($pricingData['ship_min_weight'] as $key=>$val){
    		$model = new self();
    		$model->setAttribute('logistics_id', $logistics_id);
    		$model->setAttribute('ship_min_weight', $val);
    		foreach($pricingData as $col=>$dataArr){
    			if($col == 'ship_attribute'){
    				$pricingData[$col][$key] = isset($keyArr[$pricingData[$col][$key]]) ? $keyArr[$pricingData[$col][$key]] : reset($keyArr);
    			}
    			$model->setAttribute($col, $pricingData[$col][$key]);
    		}
    		$model->setIsNewRecord(true);
    		$result = $result && $model->save();
    	}
    	return $result;
    }
    /**
     * get Pricing Solution by logistics ID
     * @param logistics ID $id
     * @param Sql Column $param
     */
    public function getPricingSolutionByLogisticsId($id,$param=array()){
    	if( !isset($param['order']) ){
    		$param['order'] = 'id';
    	}
    	
    	return $list = $this->getDbConnection()->createCommand()
    			->select(isset($param['select']) ? implode(",",$param['select']) : '*')
    			->from(self::tableName())
    			->where("logistics_id=:logistics_id",array(":logistics_id"=>$id))
    			->order($param['order'])
    			->queryAll();
    }
    
    public static function getIndexNavTabId() {
    	return Menu::model()->getIdByUrl('/logistics/logisticspricingsolution/list');
    }
    /**
     * @desc 获取物流指定的所有国家
     * @author Super
     * @since 2015-04-14
     * @return array $countryArr
     */
    public function getAllIncludeCountry(){
    	 $list = $this->getDbConnection()->createCommand()
				    	->select('ship_include_countries')
				    	->from(self::tableName())
				    	->where("")
				    	->group('ship_include_countries')
				    	->queryAll();
    	 $countryArr = array();
    	 foreach($list as $key=>$val){
    	 	$val['ship_include_countries'] = str_replace(',',' ',$val['ship_include_countries']);
    	 	$val['ship_include_countries'] = str_replace('，',' ',$val['ship_include_countries']);
    	 	$countrys = explode(' ',$val['ship_include_countries']);
    	 	if($countrys){
    	 		foreach ($countrys as $country){
    	 			$country = trim($country);
    	 			
    	 			$countryArr[$country] = $country;
    	 		}
    	 	}
    	 }
    	 unset($countryArr['']);
    	 return $countryArr;
    }
    
    /**
     * ship_attribute
     * 
     */
    public function shipAttributes($id,$shipAttr=NULL,$type=NULL){
    	$logisticsAttr=UebModel::model('logisticsAttribute')->getAttributeByLogisticsId($id);
    	 $list=array();
    	 $num=1;
    	 foreach ($logisticsAttr as $val){	 	
    	 	$list[$val['id']]='属性'.$num;
    	 	$num++;
    	 }
    	 if($type=='list'){
    	 	return $list[$shipAttr];
    	 }
    	 return $list;
    }
    
    /**
     * 暂存计价方案 
     * 文件暂存　[以后可放入 Redis]
     */
    public static function recordPricingSolution($content, $packageId) {
    	if (empty($packageId)) {
    		return false;
    	}
    	error_log(serialize($content), 3, 'log/price_solution/'.$packageId.'.txt');
    }
    
    /**
     * 读取暂存的计价方案
     */
    public static function readPricingSolutionRecord($packageId) {
    	if (empty($packageId)) {
    		return false;
    	}
    	$filename = 'log/price_solution/'.$packageId.'.txt';
    	$fp = fopen($filename, 'r');
    	$contents = fread($fp, filesize($filename));
    	fclose($fp);
    	if ($contents) {
    		return unserialize($contents);
    	}
    	return false;
    }
    
    /**
     * 删除暂存的计价方案
     */
    public static function delPricingSolutionRecord($packageId) {
    	//暂时为移动，后续改为删除
    	$filename = 'log/price_solution/'.$packageId.'.txt';
    	$filename2 = 'log/price_solution_del/'.$packageId.'.txt';
    	//rename($filename, $filename2);
    	unlink($filename);
    }
    
}


