<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$config = array(
		'id' => 'aliexpressparamtemplate-grid',
	    'dataProvider' => $model->search(null),
	    'filter' => $model,
		'tableOptions' => array(
				'layoutH' => 145,
				'tableFormOptions' => true
		),
		'pager' => array(),
);
$config['columns'] = array();
$config['columns'][0] = array(
		'class' => 'CCheckBoxColumn',
    	'selectableRows' => 2,
    	'value' => '$data->id',
    	'htmlOptions' => array('width' => '10'),
);
$config['columns'][1] = array(
		'name' => 'id',
		'value'=> '$row+1',
		'htmlOptions' => array('style' => 'width:30px;'),
);
$config['columns'][2] = array(
		'name' => 'tamplate_name',
		'value'=> '$data->tamplate_name',
		'htmlOptions' => array('style' => 'width:50px;'),
);
$config['columns'][3] = array(
		'name' => 'delivery_time',
		'value' => '$data->delivery_time',
		'htmlOptions' => array('style' => 'width:50px;'),
);
$config['columns'][4] = array(
		'name' => 'promise_template_id',
		'value' => '$data->promise_template_id',
		'htmlOptions' => array('style' => 'width:58px;'),
);
$config['columns'][5] = array(
		'name' => 'freight_template_Id',
		'value' => '$data->freight_template_Id',
		'htmlOptions' => array('style' => 'width:58px;'),
);
$config['columns'][6] = array(
		'name' => 'product_unit',
		'value' => 'AliexpressParamTemplate::getProductUnit($data->product_unit)',
		'htmlOptions' => array('style' => 'width:80px;'),
);
$config['columns'][7] = array(
		'name' => 'package_type',
		'value' => '$data->package_type',
		'htmlOptions' => array('style' => 'width:46px;'),
);
$config['columns'][8] = array(
		'name' => 'ws_valid_num',
		'value' => '$data->ws_valid_num',
		'htmlOptions' => array('style' => 'width:78px;'),
);
$config['columns'][9] = array(
		'name' => 'bulk_order',
		'value' => '$data->bulk_order',
		'htmlOptions' => array('style' => 'width:72px;'),
);
$config['columns'][10] = array(
		'name' => 'bulk_discount',
		'value' => '$data->bulk_discount',
		'htmlOptions' => array('style' => 'width:87px;'),
);
$config['columns'][11] = array(
		'name' => 'reduce_strategy',
		'value' => '$data->reduce_strategy',
		'htmlOptions' => array('style' => 'width:120px;'),
);
$config['columns'][12] = array(
		'name' => 'stock_num',
		'value' => '$data->stock_num',
		'htmlOptions' => array('style' => 'width:50;'),
);
$config['columns'][13] = array(
		'name' => 'template_status',
		'value' => 'VHelper::getStatusLable($data->template_status)',
		'htmlOptions' => array('style' => 'width:50;'),
);
$config['columns'][14] = array(
		'name' => 'create_time',
		'value' => '$data->create_time',
		'htmlOptions' => array('style' => 'width:120px;'),
);
$config['columns'][15] = array(
		'name' => 'create_user_id',
		'value' => 'MHelper::getUsername($data->create_user_id)',
		'htmlOptions' => array('style' => 'width:50px;'),
);
if ( Yii::app()->request->getParam('target') == 'dialog' ) {
	$config['toolBar'] = array(
			array(
					'text' => Yii::t('system', 'Please Select'),
					'type' => 'button',
					'url' => '',
					'htmlOptions' => array(
							'target' => '',
							'class' => 'edit',
							'multLookup' => 'aliexpressparamtemplate-grid_c0[]',
							'rel' =>'{target:"'.$modelName.'_template_name", url: "aliexpress/aliexpressparamtemplate/getcode"}',
							'onclick' => false
					)
			),
	);

}else{
	$config['columns'][15] = array(
			'name' => 'modify_time',
			'value' => '$data->modify_time',
			'htmlOptions' => array('style' => 'width:120px;'),
	);
	$config['columns'][16] = array(
			'name' => 'modify_user_id',
			'value' => 'MHelper::getUsername($data->modify_user_id)',
			'htmlOptions' => array('style' => 'width:50px;'),
	);
	
	$config['columns'][17] = array(
			'header' => Yii::t('system', 'Operation'),
			'class' => 'CButtonColumn',
			'headerHtmlOptions' => array('style' => 'width:100px;', 'align' => 'center'),
			'template' => '{edit}',
			'buttons' => array(
					'edit' => array(
							'url'       => 'Yii::app()->createUrl("/aliexpress/aliexpressparamtemplate/update", array("id"=>$data->id))',
							'label'     => Yii::t('system', 'Edit'),
							'options'   => array(
									'target'    => 'dialog',
									'class'     =>'btnEdit',
									'rel' => 'aliexpressparamtemplate-grid',
									'mask'  	=> true,
									'width'     => '800',
									'height'    => '600',
							),
					),
	
			),
	);
	$config['toolBar'] = array(
			array(
					'text' => Yii::t('system', 'Add'),
					'url' => '/aliexpress/aliexpressparamtemplate/create',
					'htmlOptions' => array(
							'class' 	=> 'add',
							'target' 	=> 'dialog',
							'rel' 		=> 'aliexpressparamtemplate-grid',
							'mask'  	=> true,
							'width' 	=> '800',
							'height' 	=> '600',
							'onclick'   => false
					)
			),
			array(
					'text' => Yii::t('system', 'Delete'),
					'url' => '/aliexpress/aliexpressparamtemplate/delete',
					'htmlOptions' => array(
							'class' 	=> 'delete',
							'title' 	=> Yii::t('system', 'Really want to delete these records?'),
							'target'	=> 'selectedTodo',
							'rel' 		=> 'aliexpressparamtemplate-grid',
							'postType'  => 'string',
							'callback'  => 'navTabAjaxDone',
							'onclick' 	=> false
					)
			)
	);
	
}


$this->widget('UGridView', $config);

?>
<script type="text/javascript">

</script>

<style type="text/css">
    .grid .gridTbody td div{
        height:auto;
        padding-top:2px;
    }
</style>