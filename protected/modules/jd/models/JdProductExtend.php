<?php

class JdProductExtend extends JdModel {
	public function tableName(){
		
		return 'ueb_jd_product_extend';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
	
}

?>