<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$config = array(
		'id' => 'descriptiontemplate_id',
		'filter' => $model,
		'dataProvider' => $model->search(null),
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
		'htmlOptions' => array(
				'width' => 5
		)
);
$config['columns'][1] = array(
		'name' => 'id',
		'value' => '$row+1',
		'headerHtmlOptions' => array('class' => 'center',),
		'htmlOptions' => array(
				'style' => 'width:25px',
		),
);
$config['columns'][2] = array(
		'name' => 'platform_code',
		'value' => '$data->platform_code',
		'headerHtmlOptions' => array('class' => 'center',),
		'htmlOptions' => array(
				'style' => 'width:100px',
		)
);
$config['columns'][3] = array(
		'name' => 'template_name',
		'value' => '$data->template_name',
		'headerHtmlOptions' => array('class' => 'center',),
		'htmlOptions' => array(
				'style' => 'width:200px',
		)
);
$config['columns'][4] = array(
		'name' => 'template_content',
		'type' => 'raw',
		'value' => 'CHtml::link(Yii::t("description_template" , "Template Preview"), "common/descriptiontemplate/preview/id/" . $data->id, array("style"=>"color:blue","target"=>"_blank"))',
		'headerHtmlOptions' => array('class' => 'center',),
		'htmlOptions' => array(
				'style' => 'width:150px',
				'align' => 'center'
		)
);
$config['columns'][5] = array(
		'name' => 'title_prefix',
		'value' => '$data->title_prefix',
		'headerHtmlOptions' => array('class' => 'center',),
		'htmlOptions' => array(
				'style' => 'width:150px',
		)
);
$config['columns'][6] = array(
		'name' => 'title_suffix',
		'value' => '$data->title_suffix',
		'headerHtmlOptions' => array('class' => 'center',),
		'htmlOptions' => array(
				'style' => 'width:150px',
		)
);
$config['columns'][7] = array(
		'name' => 'status',
		'value' => 'UebModel::model("Descriptiontemplate")->getStatusList($data->status)',
		'headerHtmlOptions' => array('class' => 'center',),
		'htmlOptions' => array(
				'style' => 'width:100px',
				'align' => 'center'
		)
);
$config['columns'][8] = array(
		'name' => 'create_user_id',
		'value' => 'MHelper::getUsername($data->create_user_id)',
		'headerHtmlOptions' => array('class' => 'center',),
		'htmlOptions' => array(
				'style' => 'width:100px',
		)
);
$config['columns'][9] = array(
		'name' => 'create_time',
		'value' => '$data->create_time',
		'headerHtmlOptions' => array('class' => 'center',),
		'htmlOptions' => array(
				'style' => 'width:200px',
		)
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
							'multLookup' => 'descriptiontemplate_id_c0[]',
							'rel' =>'{target:"'.$modelName.'_template_name", url: "common/descriptiontemplate/getcode"}',
							'onclick' => false
					)
			),
	);

}else{
	$config['columns'][10] = array(
			'name' => 'modify_user_id',
			'value' => 'MHelper::getUsername($data->modify_user_id)',
			'headerHtmlOptions' => array('class' => 'center',),
			'htmlOptions' => array(
					'style' => 'width:100px',
			)
	);
	$config['columns'][11] = array(
			'name' => 'modify_time',
			'value' => '$data->modify_time',
			'headerHtmlOptions' => array('class' => 'center',),
			'htmlOptions' => array(
					'style' => 'width:200px',
			)
	);
	$config['columns'][12] = array(
			'header' => Yii::t('system', 'Operation'),
			'class' => 'CButtonColumn',
			'template' => '{modify}',
			'headerHtmlOptions' => array('width' => 85, 'align' => 'center'),
			'buttons' => array(
					'modify' => array(
							'label' => Yii::t('description_template', 'Modify'),
							'url' => 'Yii::app()->createUrl("common/descriptiontemplate/update", array("id" => $data->id))',
							'visable' => true,
							'options' => array('target' => 'dialog', 'mask' => true, 'width' => '750', 'height' => '650'),
					),
			),
	);
	$config['toolBar'] = array(
			array(
					'text' => Yii::t('description_template', 'Add'),
					'url' => Yii::app()->createUrl('common/descriptiontemplate/create'),
					'htmlOptions' => array(
							'class' => 'add',
							'target' => 'dialog',
							'mask' => true,
							'width' => 750,
							'height' => 600,
							'onclick' => '',
					),
			),
			array(
					'text' => Yii::t('system', 'Delete'),
					'url' => Yii::app()->createUrl(('common/descriptiontemplate/delete')),
					'htmlOptions' => array(
							'class' => 'delete',
							'title' => Yii::t('system', 'Really want to delete these records?'),
							'target' => 'selectedTodo',
							'rel' => 'descriptiontemplate_id',
							'postType' => 'string',
							'warn' => Yii::t('system', 'Please Select'),
							'callback' => 'navTabAjaxDone',
							'onclick' => '',
					)
			)
	);
	
}
$this->widget('UGridView', $config);
?>