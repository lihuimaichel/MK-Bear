<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$config = array(
		'id' => 'lazadaparamtemplate-grid',
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
		'htmlOptions' => array('style' => 'width:25px;'),
);
$config['columns'][2] = array(
		'name' => 'tpl_code',
		'value' => '$data->tpl_code',
		'htmlOptions' => array('style' => 'width:50px;'),
);
$config['columns'][3] = array(
		'name' => 'tpl_name',
		'value' => '$data->tpl_name',
		'htmlOptions' => array('style' => 'width:200px;'),
);
$config['columns'][4] = array(
		'name' => 'taxes',
		'value' => 'LazadaParamTemplate::getTaxes($data->taxes)',
		'htmlOptions' => array('style' => 'width:80px;'),
);
$config['columns'][5] = array(
		'name' => 'shipping_time_min',
		'htmlOptions' => array('style' => 'width:100px;'),
);
$config['columns'][6] = array(
		'name' => 'shipping_time_max',
		'htmlOptions' => array('style' => 'width:100px;'),
);
$config['columns'][7] = array(
		'name' => 'warranty_type',
		'value' => 'LazadaParamTemplate::getWarrantyType($data->warranty_type)',
		'htmlOptions' => array('style' => 'width:150px;'),
);
$config['columns'][8] = array(
		'name' => 'warranty_period',
		'value' => 'LazadaParamTemplate::getWarrantyPeriod($data->warranty_period)',
		'htmlOptions' => array('style' => 'width:120px;'),
);
$config['columns'][9] = array(
		'name' => 'image_width',
		'htmlOptions' => array('style' => 'width:100px;'),
);
$config['columns'][10] = array(
		'name' => 'image_height',
		'htmlOptions' => array('style' => 'width:100px;'),
);
$config['columns'][11] = array(
		'name' => 'is_enable',
		'value'	=> 'VHelper::getStatusLable($data->is_enable)',
		'htmlOptions' => array('style' => 'width:100px;'),
);
$config['columns'][12] = array(
		'name' => 'create_time',
		'htmlOptions' => array('style' => 'width:120px;'),
);
$config['columns'][13] = array(
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
							'multLookup' => 'lazadaparamtemplate-grid_c0[]',
							'rel' =>'{target:"'.$modelName.'_template_name", url: "lazada/lazadaparamtemplate/getcode"}',
							'onclick' => false
					)
			),
	);
	
}else{
	$config['columns'][14] = array(
			'name' => 'modify_time',
			'htmlOptions' => array('style' => 'width:120px;'),
	);
	$config['columns'][15] = array(
			'name' => 'modify_user_id',
			'value' => 'MHelper::getUsername($data->modify_user_id)',
			'htmlOptions' => array('style' => 'width:50px;'),
	);
	$config['columns'][16] = array(
            'header' => Yii::t('system', 'Operation'),
            'class' => 'CButtonColumn',
            'headerHtmlOptions' => array('style' => 'width:100px;', 'align' => 'center'),
            'template' => '{edit}',
            'buttons' => array(
                'edit' => array(
                    'url'       => 'Yii::app()->createUrl("/lazada/lazadaparamtemplate/update", array("id"=>$data->id))',
                    'label'     => Yii::t('system', 'Edit'),
                    'options'   => array(
                        'target'    => 'dialog',
                        'class'     =>'btnEdit',
                        'rel' => 'lazadaparamtemplate-grid',
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
					'url' => '/lazada/lazadaparamtemplate/create',
					'htmlOptions' => array(
							'class' => 'add',
							'target' => 'dialog',
							'rel' => 'lazadaparamtemplate-grid',
							'mask'  	=> true,
							'width' => '800',
							'height' => '600',
							'onclick' => false
					)
			),
			array(
					'text' => Yii::t('system', 'Delete'),
					'url' => '/lazada/lazadaparamtemplate/delete',
					'htmlOptions' => array(
							'class' => 'delete',
							'title' => Yii::t('system', 'Really want to delete these records?'),
							'target' => 'selectedTodo',
							'rel' => 'lazadaparamtemplate-grid',
							'postType' => 'string',
							'callback' => 'navTabAjaxDone',
							'onclick' => false
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