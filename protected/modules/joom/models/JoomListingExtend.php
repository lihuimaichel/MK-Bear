<?php
class JoomListingExtend extends JoomModel{
	public function tableName(){
		return 'ueb_joom_listing_extend';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
}