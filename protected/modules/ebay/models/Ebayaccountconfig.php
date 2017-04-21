<?php
/**
* @desc Ebay帐号管理模型
* @author Michael
* @since 2015/07/20 20:28
* 
*/
class Ebayaccountconfig extends EbayModel{
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
	
	public function tableName(){
		return 'ueb_ebay_account_config';
	}
	
	public function rules()
	{
		return array();
	}
}
?>