<?php

class ProductQeCheckRecord extends ProductsModel
{
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
		return 'ueb_product_qe_check_record';
	}


    /**
     * 根据sku获取信息
     * @return array
     */
    public function getOneBySKU($sku){
        $data = $this->getDbConnection()->createCommand()
                ->select('*')
                ->from(self::tableName())
                ->where("sku = '{$sku}'")
                ->queryRow();
        return $data;
    }
}