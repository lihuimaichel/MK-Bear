<?php
/**
 * 所有的配置文件一个参考范例
 * 
 return array(
		'apiconfig'=>array(
				//ueb.newfrog.com
				'soap_url' => 'http://ueb.newfrog.com/api/?wsdl' ,
				//test.newfrog.com
				// 		'soap_url' => 'http://test.newfrog.com/api/?wsdl' ,
				'api_user' => 'newfrog_api' ,
				'api_key' => '68ceb170b3e06e50',
		),
		'models'=> array(
				'product' => 'NewfrogProduct',
				'log' => 'NewfrogLog',
		)
);
 * 
 */
return array(
		'apiconfig'=>array(
				//ueb.newfrog.com
// 				'soap_url' => 'http://ueb.newfrog.com/api/?wsdl' ,
				'soap_url' => 'http://www.newfrog.com/api/?wsdl' ,
				'api_user' => 'newfrog_api' ,
				'api_key' => '68ceb170b3e06e50',
		),
		'models'=> array(
				//product
				'product' 					=> 'NewfrogProduct',
				'productattributes' 		=> 'NewfrogProductAttributes',
				'uebproduct' 				=> 'UebProductModelForNewfrog',
				'category' 					=> 'NewfrogCategory',
				'categoryindex' 			=> 'NewfrogCategoryIndex',
				'log' 						=> 'NewfrogLog',
		)
);