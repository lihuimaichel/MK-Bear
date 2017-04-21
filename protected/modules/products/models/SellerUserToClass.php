<?php
/**
 * @desc 销售人员与类目关系
 * @author lihy
 *
 */
class SellerUserToClass extends ProductsModel{
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
	
	public function tableName(){
		return "ueb_seller_user_to_class";
	}
}