<?php
/**
 * 所有的配置文件一个参考范例
 * 
return array(
		'apiconfig'=>array(
				'soap_url' 	=> 'http://www.ecoolbuy.com/api/?wsdl' ,
				'api_user' 	=> 'ecoolbuy_api' ,
				'api_key' 	=> '68ceb170b3e06e50',
		),
		'models'=> array(
				//product
				'product' 		=> 'EcoolbuyProduct',
				'uebproduct' 	=> 'UebProductModelForEcoolbuy',
				'category' 		=> 'EcoolbuyCategory',
				'categoryindex' => 'EcoolbuyCategoryIndex',
				'log' 			=> 'EcoolbuyLog',
		)
);
 * 
 */
return array(
		'apiconfig'=>array(
// 				'soap_url' 	=> 'http://ueb.ecoolbuy.com/api/?wsdl' ,
				'soap_url' 	=> 'http://www.ecoolbuy.com/api/?wsdl',
				'api_user' 	=> 'ecoolbuy_api' ,
				'api_key' 	=> '68ceb170b3e06e50',
		),
		'models'=> array(
				//product
				'product' 					=> 'EcoolbuyProduct',
				'productattributes' 		=> 'EcoolbuyProductAttributes',
				'uebproduct' 				=> 'UebProductModelForEcoolbuy',
				'category' 					=> 'EcoolbuyCategory',
				'categoryindex' 			=> 'EcoolbuyCategoryIndex',
				'log' 						=> 'EcoolbuyLog',
		)
);