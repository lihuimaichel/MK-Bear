<?php
/**
 * @package Ueb.modules.ProductAdapter.models
 * 
 * @author Bob <zunfengke@gmail.com>
 */
class ProductAdapter extends ProductsModel {   
	public $product_title = null;
// 	const IS_ADAPTER =1;//是否是转接头
// 	const NO_ADAPTER =0;//不是转接头

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
        return 'ueb_product_adapter';
    }
    /*
     * return column name
    */
    public function columnName() {
    	return MHelper::getColumnsArrByTableName(self::tableName());
    }
    
    /**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{	      
		return array(
			array('sku,attribute_id', 'required'), 
            array('state_id,country_id', 'safe'),
            array('attribute_id', 'length', 'max'=>30),
//             array('sku', 'exist', 'attributeName' => 'sku', 'className' => 'Product'),
		);
	}
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array( 
            'id'                            => Yii::t('system', 'No.'),
            'sku'                           => Yii::t('products', 'Sku'),
        	'product_title'					=> Yii::t('system', 'Title'),
            'attribute_id'                	=> Yii::t('products', 'Product standard'),
            'country_id'          			=> Yii::t('products', 'Country'),
            'state_id'                  	=> Yii::t('products', 'Subordinate to the state'),          
            'modify_user_id'                => Yii::t('system', 'Modify User'),          
        	'create_time'           		=> Yii::t('system', 'Create Time'),
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
        	'sku',  
        		    
        );      
        $dataProvider = parent::search(get_class($this), $sort);         
//         $data = $this->addition($dataProvider->data);
//         $dataProvider->setData($data);
        
       return $dataProvider;
    }
    
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
        return array(
            array(             
                'name'          => 'sku',               
                'type'          => 'text',
                'search'        => 'LIKE',          
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
    			'sku','create_time','modify_time'
    	);
    }

    /**
     * addition information
     * 
     * @param type $dataProvider
     */
    public function addition($data) {
        $countryIdArr = array();       
        foreach ($data as $key => $val) {
            $countryIdArr[$key] = $val->country_id;
            
        }
       
        return $data;
    }
    

    /**
     * get index nav tab id 
     * 
     * @return type
     */
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/products/productadapter/list');
    }
    
    
    
    /**
     * ===============================interface function=========================================
     */
    
    /**
     * get adapter sku by order sku and country
     * @param string $order_sku
     * @param string $en_name:发货国家英文名称
     * @return string sku
     */
    public function getAdapterSkuByOrderSku($order_sku,$en_name){
	    $returnSku = '';
	    $result = array();

	    $dataCountry = UebModel::model('Country')->find('en_name=:en_name',array(':en_name'=>$en_name));
	   
	    if (empty($dataCountry)) return false;
	    $adapterAttribute = UebModel::model('Product')->getAdapterAttrBySku($order_sku);
	    if ($adapterAttribute) {
	    	//检测只要存在一个转接头就不需要再配其他的了
	    	$result = $this->getDbConnection()->createCommand()
				    	->select(self::columnName())
				    	->from(self::tableName())
				    	->where(array('in', 'attribute_id',$adapterAttribute) )
				    	->andwhere("find_in_set('".$dataCountry->id."',country_id)")
				    	->queryRow();
	    	if(empty($result)){
		    	$result = $this->getDbConnection()->createCommand()
						    	->select(self::columnName())
						    	->from(self::tableName())
						    	->where(array('in', 'attribute_id',$adapterAttribute) )
						    	->andwhere("find_in_set('".$dataCountry->continent."',state_id)")
						    	->queryRow();
	    	
	    	}
	    	if(empty($result)){
		    	$result = $this->getDbConnection()->createCommand()
						    	->select(self::columnName())
						    	->from(self::tableName())
						    	->where(array('not in', 'attribute_id',$adapterAttribute) )
						    	->andwhere("find_in_set('".$dataCountry->id."',country_id)")
						    	->queryRow();
	
		    	if (empty($result)){
		    		$result = $this->getDbConnection()->createCommand()
				    		->select(self::columnName())
				    		->from(self::tableName())
				    		->where(array('not in', 'attribute_id',$adapterAttribute) )
				    		->andwhere("find_in_set('".$dataCountry->continent."',state_id)")
				    		->queryRow();
				}
	    	}else{
	    		$result = array();
	    	}
	    	if (!empty($result)){
	    		$returnSku = $result['sku'];
	    	}
		}
	    return $returnSku;
    }
    
    

}