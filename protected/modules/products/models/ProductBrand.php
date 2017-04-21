<?php
/**
 * @desc oms 产品品牌
 * @author lihy
 * @since 2015-11-12
 */
class ProductBrand extends ProductsModel { 
    
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
        return 'ueb_product_brand';
    }
    
    /**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{	      
		return array(
			
		);
	}
   /**
    * @desc 获取品牌信息
    * @param unknown $brandId
    */
	public function getBrandInfoByBrandId($brandId){
		return $this->getDbConnection()->createCommand()
					->from(self::tableName())
					->where('id=:brand_id', array(':brand_id'=>$brandId))
					->queryRow();
	}


    /**
     * @desc 获取所有品牌信息
     * @return array
     */
    public function getListOptions($lang = 'cn') {
        $selectObj = $this->getDbConnection()->createCommand()
        ->select('id,brand_name,brand_en_name,brand_logo,url')
        ->from(self::tableName());
        $list = $selectObj->order("sort asc, id desc")
                ->query();
        $data = array('0'=>'');
        foreach($list as $k=>$v){
            $data[$v['id']] = $lang == 'cn' ? $v['brand_name'] : $v['brand_en_name'];
        }
        return $data;
    }
}	 