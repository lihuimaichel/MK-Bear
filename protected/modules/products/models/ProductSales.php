<?php
use Zend\Http\Header\From;
/**
 * @package Ueb.modules.productsales.models
 * 
 * @author ethan
 */
class ProductSales extends ProductsModel { 
	const DAY3 = 3;
	const DAY7 = 7;
	const DAY15 = 15;
	const DAY30 = 30;
	const DAY60 = 60;
	
	const STATISTICS_TYPE_PLATFORM = 1;//按平台统计销量
	const STATISTICS_TYPE_WAREHOUSE = 2;//按仓库统计销量
	const STATISTICS_TYPE_PLATFORM_SITE = 3;//按平台与站点统计(货币)
	const STATISTICS_TYPE_DATE = 8;		//按日期
	
	public $info;
	public $sku_create_time;
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
        return 'ueb_product_sales';
    }
    
    /**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{	      
		return array(
			array('platform_code,sku','required'),
			array('day3,day7,day15,day30,day60,currency,day_type,sale_num,sale_amount,warehouse_id','safe'),
		);
	}
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
        	'id'                    		=> Yii::t('system', 'No.'),
            'platform_code'                 => Yii::t('system', 'Platform'),
            'cn_name'                 		=> Yii::t('products', 'Title'),
            'sku'                           => Yii::t('products', 'Sku'),
        		'product_cost'              => Yii::t('products', 'product_cost'),
        		'product_weight'            => Yii::t('products', 'product_weight'),
        		'volume'                       => Yii::t('products', 'volume'),
			'day3'                          => Yii::t('products', 'The average sales within 3 days'),
        	'day7'                          => Yii::t('products', 'The average sales within 7 days'),
			'day15'                         => Yii::t('products', 'The average sales within 15 days'),
        	'day30'                         => Yii::t('products', 'The average sales within 30 days'),
			'day_60'                        => Yii::t('products', 'The average sales within 60 days'),
        	'create_time'    				=> Yii::t('system', 'Create Time'),
        	'day_type'    					=> Yii::t('products', 'Day type'),
        	'sale_num'    					=> Yii::t('products', 'The average sales'),
        	'product_category'              => Yii::t('products','Product Category'),
            'sku_create_time'               => Yii::t('products','Sku Create Time'),
        );
    }

    
    /**
     * get search info
     */

	public function search() {	 
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'  => 'sale_num',
		);
		$criteria = $this->_setCDbCriteria();		 

		$dataProvider = parent::search(get_class($this), $sort, array(), $criteria);
		$data = $this->addition($dataProvider->data);		
		$dataProvider->setData($data);
		return $dataProvider;
	}
    
    public function addition($data){
    	$skuArr=array();
    	foreach ($data as $val) {
    		$skuArr[] = $val['sku'];
    	}
    	$map = UebModel::model('Productdesc')->getListPairs($skuArr,CN);
    	

    	if($map){
    		foreach($map as $k=>$v){     		
    			$map[$v['sku']] = $v['title'];
    		}
    	}
    	
    	
    	$dataList1 = $this->getDbConnection()->createCommand()
    	->select('*')
    	->from(UebModel::model('Product')->tableName())
    	->where(array('in', 'sku', $skuArr))
    	->queryAll();
    	 
    	foreach ($dataList1 as $val){
    		$nKey = $val['sku'];
    		$Infoext[$nKey]['product_cost'] = $val['product_cost'];
    		$Infoext[$nKey]['product_weight'] = $val['product_weight'];
    		$Infoext[$nKey]['volume'] = $val['product_length'].'*'.$val['product_width'].'*'.$val['product_height'];
    	}

    	$dataList = $this->getDbConnection()->createCommand()
    		->select('*')
    		->from(self::tableName())
    		->where(array('in', 'sku', $skuArr))
    		->queryAll();
    	

   	
   	
    	foreach ($dataList as $val){  	
    		$nKey = $val['sku'].'_'.$val['platform_code'];
    		$Info[$nKey]['sku'] = $val['sku'];
    		$Info[$nKey]['platform_code'] = $val['platform_code'];
    		$Info[$nKey]['day_'.$val['day_type']] = $val['sale_num'];
    		$Info[$nKey]['cn_name']=$map[$val['sku']];
    		$ceate_time = Product::model()->getInfomationBysku($val['sku'],'create_time');
    		$Info[$nKey]['sku_create_time']=$ceate_time['create_time'];
    		
    		$Info[$nKey]['product_cost'] = $Infoext[$val['sku']]['product_cost'];
    		$Info[$nKey]['product_weight'] =$Infoext[$val['sku']]['product_weight'];
    		$Info[$nKey]['volume'] = $Infoext[$val['sku']]['volume'];;
    	}  



        foreach ($data as $key=>$val) {
    		$data[$key]->info = $Info[$val['sku'].'_'.$val['platform_code']];
 
    	}
    	
    	return $data;    	
    }
    
    protected function _setCDbCriteria($addCondition=array()) {
    	$criteria = new CDbCriteria();
    	$criteria->group = 'sku,platform_code'; 
    	return $criteria;
    }
    
    /**
     * order field options
     * @return $array
     */
    public function orderFieldOptions() {
    	return array(
    		'sale_num'
    	);
    }
    
    public function dataSave($attributes){
    	$model = new self();
    	$model->setIsNewRecord(true);
    	$model->attributes = $attributes;
    	$model->setAttribute('create_time', date('Y-m-d H:i:s'));
    	if($model->save()){
    		return true;
    	}else{
    		print_r($model->getErrors());
    	}
    }
    /**
     * sale Day Type
     * @param string $type
     * @return array
     */
    public function saleDayType($type = null){
    	$config = array(
    		self::DAY3 	=>Yii::t('products', 'The average sales within 3 days'),
    		self::DAY7 	=>Yii::t('products', 'The average sales within 7 days'),
    		self::DAY15 =>Yii::t('products', 'The average sales within 15 days'),
    		self::DAY30 =>Yii::t('products', 'The average sales within 30 days'),
    		self::DAY60 =>Yii::t('products', 'The average sales within 60 days'),
    	);
    	if ($type !== null) {
    		return $config[$type];
    	}
    	return $config;
    	
    }

    public function saleDay($type = null){
    	$config = array(
    			self::DAY3 	=>Yii::t('products', '3 天'),
    			self::DAY7 	=>Yii::t('products', '7 天'),
    			self::DAY15 =>Yii::t('products', '15天'),
    			self::DAY30 =>Yii::t('products', '30天'),
    			self::DAY60 =>Yii::t('products', '60天'),
    	);
    	if ($type !== null) {
    		return $config[$type];
    	}
    	return $config;
    }
    
    /**
     * @desc 根据条件查询指定字段
     */
    public function getProductSalesByCondition( $condition,$field = '*' ){
    	$condition = empty($condition)?'1=1':$condition;
    	$ret = $this->dbConnection->createCommand()
		    	->select( $field )
		    	->from( $this->tableName() )
		    	->where( $condition )
		    	->queryAll();
    
    	return $ret;
    }
    
    
}