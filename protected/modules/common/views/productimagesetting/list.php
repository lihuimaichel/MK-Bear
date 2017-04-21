<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget('UGridView', array(
	'id' => 'productimagesetting_id',
	'filter' => $model,
	'dataProvider' => $model->search(),
	'columns' => array(
		array(
			'class' => 'CCheckBoxColumn',
			'selectableRows' => 2,
			'value' => '$data->id',
			'headerHtmlOptions' => array(
				'align' => 'center',
				'style' => 'width:25px',
			),
		),
		array(
			'name' => 'id',
			'value' => '$row+1',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 25px',
			),
		),
		array(
			'name' => 'platform_code',
			'value' => '$data->platform_code',
			'headerHtmlOptions' => array(
				'class' => 'center',	
			),
			'htmlOptions' => array(
				'style' => 'width: 120px',
			),	
		),
		array(
			'name' => 'account_id',
			'value' => '$data->account_name',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 120px',
			),					
		),
		array(
			'name' => 'watermark_path',
			'type' => 'raw',
			'value' => 'CHtml::link(Yii::t("product_image_setting", "Watermark View"), "/uploads/" . $data->watermark_path, array("style"=>"color:blue","target"=>"_blank"))',
			'headerHtmlOptions' => array(
				'align' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 100px',
			),			
		),
		array(
			'name' => 'zt_watermark',
			'type' => 'html',
			'value' => '$data->zt_watermark',
			'headerHtmlOptions' => array(
				'align' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 100px',
			),					
		),
		array(
			'name' => 'ft_watermark',
			'type' => 'html',				
			'value' => '$data->ft_watermark',
			'headerHtmlOptions' => array(
				'align' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 100px',
			),
		),
		array(
			'name' => 'watermark_position',
			'value' => '$data->watermark_position_x . "/" . $data->watermark_position_y',
			'headerHtmlOptions' => array(
				'align' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 75px',
			),
		),
		array(
			'name' => 'watermark_alpha',
			'value' => '$data->watermark_alpha',
			'headerHtmlOptions' => array(
				'align' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 75px',
			),
		),
		array(
			'name' => 'filename_prefix',
			'value' => '$data->filename_prefix',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 75px',
			),			
		),
		array(
			'name' => 'filename_suffix',
			'value' => '$data->filename_suffix',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 75px'
			),
		),
		array(
			'name' => 'create_user_id',
			'value' => 'MHelper::getUsername($data->create_user_id)',
			'headerHtmlOptions' => array('class' => 'center',),
			'htmlOptions' => array(
				'style' => 'width:100px',
			)
		),
		array(
			'name' => 'create_time',
			'value' => '$data->create_time',
			'headerHtmlOptions' => array('class' => 'center',),
			'htmlOptions' => array(
				'style' => 'width:150px',
			)
		),
		array(
			'name' => 'modify_user_id',
			'value' => 'MHelper::getUsername($data->modify_user_id)',
			'headerHtmlOptions' => array('class' => 'center',),
			'htmlOptions' => array(
				'style' => 'width:100px',
			)
		),
		array(
			'name' => 'modify_time',
			'value' => '$data->modify_time',
			'headerHtmlOptions' => array('class' => 'center',),
			'htmlOptions' => array(
				'style' => 'width:150px',
			)
		),
		array(
			'class' => 'CButtonColumn',
			'header' => Yii::t('system', 'Operation'),
			'headerHtmlOptions' => array('width' => 85, 'align' => 'center'),
			'template' => '{modify}',
			'buttons' => array(
				'modify' => array(
					'label' => Yii::t('product_image_setting', 'Modify'),
					'url' => 'Yii::app()->createUrl("common/productimagesetting/update/id/" . $data->id)',
					'visable' => true,
					'options' => array(
						'target' => 'dialog',
						'mask' => true,
						'width' => 750,
						'height' => 575,
					),
				),
			),
		),			
	),
	'toolBar' => array(
		array(
			'text' => Yii::t('system', 'Add'),
			'url' => Yii::app()->createUrl('common/productimagesetting/create'),
			'htmlOptions' => array(
				'class' => 'add',
				'target' => 'dialog',
				'width' => 750,
				'height' => 575,
				'mask' => true,
				'onclick' => '',
				 
			),
		),
		array(
			'text' => Yii::t('system', 'Delete'),
			'url' => Yii::app()->createUrl('common/productimagesetting/delete'),
			'htmlOptions' => array(
				'class' => 'delete',
				'target' => 'selectedTodo',
				'title' => Yii::t('system', 'Really want to delete these records?'),
				'callback'	=> 'navTabAjaxDone',
				'rel' => 'productimagesetting_id',
				'postType' => 'tring',
				'warn' => Yii::t('system', 'Please Select'),
				'onclick' => '',
			),		
		),		
	),
	'pager' => array(),
	'tableOptions' => array(
			'layoutH' => 150,
			'tableFormOptions' => true,
	),
));