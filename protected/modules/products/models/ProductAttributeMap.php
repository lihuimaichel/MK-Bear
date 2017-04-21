<?php
/**
 * @desc oms 产品属性&属性值映射关系 model
 * @author wx
 * 2015-09-22
 */
class ProductAttributeMap extends ProductsModel {      

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
        return 'ueb_product_attribute_map';
    }
    
    /**
     * @desc 获取产品属性信息
     * @param integer $attributeId 
     */
    public function getAttributeValList( $attributeId ){
    	
    	$ret = $this->dbConnection->createCommand()
    			->select( 'b.id,b.attribute_value_name' )
    			->from( $this->tableName().' as a' )
    			->join(UebModel::model('ProductAttributeValue')->tableName().' as b', 'a.attribute_value_id = b.id')
    			->where('a.attribute_id="'.$attributeId.'"')
    			->queryAll();
    	
    	return empty($ret)?null:$ret;
    	
    }


    /**
     * @desc 获取产品属性信息
     * @param integer $attributeId 
     */
    public function getAttributeValListArray( $attributeId ){
        $attributeArr = array();
        $ret = $this->dbConnection->createCommand()
                ->select( 'b.id,b.attribute_value_name' )
                ->from( $this->tableName().' as a' )
                ->join(UebModel::model('ProductAttributeValue')->tableName().' as b', 'a.attribute_value_id = b.id')
                ->where('a.attribute_id="'.$attributeId.'"')
                ->queryAll();
        
        if($ret){
            foreach ($ret as $key => $value) {
                $attributeArr[$value['id']] = $value['attribute_value_name'];
            }
        }

        return $attributeArr;
        
    }


    /**
     * get list values
     * @param array $values
     * @return array
     */
    public function getListValues($values) {    
        $joinTable = UebModel::model('ProductAttributeValue')->tableName();
        return $this->getDbConnection()->createCommand()
                    ->select('m.attribute_id, GROUP_CONCAT(m.attribute_value_id) AS value_ids, GROUP_CONCAT(v.attribute_value_name) AS value_names')
                    ->from(self::tableName() . ' m')
                    ->join($joinTable . ' v', "v.`id` = m.attribute_value_id")
                    ->group('m.attribute_id')
                    ->where(array('IN', 'attribute_id', $values))                            
                    ->queryAll();      
    }


    /**
     * get list value options
     * @param array $values
     * @return array $data
     */
    public function getListValueData($values) {
        $data = array();
        $list = $this->getListValues($values);       
        foreach ($list as $key => $val) {
            $options = array();
            $attributeId = $val['attribute_id'];
            if ( strpos($val['value_ids'], ",") !== false ) {
                $valueIds = explode(",", $val['value_ids']);
                $valueNames = explode(",", $val['value_names']);
                foreach ($valueIds as $key2 => $val2) {
                    $options[$val2] = $valueNames[$key2];
                }
            } else {
                $options[$val['value_ids']] = $val['value_names'];
            }
           
           $data[$attributeId] = $options;          
        }
        return $data;
    }
    

}