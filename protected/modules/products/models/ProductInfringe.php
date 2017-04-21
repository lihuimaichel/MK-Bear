<?php
/**
 * @desc 产品侵权信息Model
 * @author Gordon
 */
class ProductInfringe extends ProductsModel {
    
    const INFRINGE_NORMAL   = 1;//正常不侵权
    const INFRINGE_YES      = 2;//违规
    const INFRINGE_ILLEGAL  = 3;//违规
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_product_infringement';
    }

    /**
     * @desc 判断sku是否侵权
     */
    public function getProductIfInfringe($sku){
        $infringe = $this->dbConnection->createCommand()
                    ->select('infringement')
                    ->from(self::tableName())
                    ->where('sku = "'.$sku.'"')
                    ->queryScalar();
        if( empty($infringe) || $infringe==self::INFRINGE_NORMAL ){
            return false;
        }else{
            return true;
        }
    }


    /**
     * @return array Product Infringement List
     */
    public function getProductInfringementList($num=null){      
        $Infringement= array(   
                self::INFRINGE_NORMAL         =>Yii::t('product', 'Nomal'),
                self::INFRINGE_YES            =>Yii::t('product', 'Is Infringe'),
                self::INFRINGE_ILLEGAL        =>Yii::t('product', 'Is Violation'),
        );
        if($num!==null){
            return $Infringement[$num];
        }else{
            return $Infringement;
        }
    }
    
}