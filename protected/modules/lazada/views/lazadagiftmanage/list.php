<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'lazadagiftmanage-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
	'toolBar' => array(
		array(
			'text'          => '批量停用',
			'url'           => '/lazada/lazadagiftmanage/openorshutdown/status/1',
			'htmlOptions'   => array(
					'class'     => 'delete',
					'title'     => '要批量停用吗？',
					'target'    => 'selectedTodo',
					'mask'		=>true,
					'rel'       => 'lazadagiftmanage-grid',
					'postType'  => 'string',
					'warn'      => Yii::t('system', 'Please Select'),
					'callback'  => 'navTabAjaxDone',
			)
		),
		array(
			'text'          => '批量启用',
			'url'           => '/lazada/lazadagiftmanage/openorshutdown/status/0',
			'htmlOptions'   => array(
					'class'     => 'edit',
					'title'     => '要批量启用吗？',
					'target'    => 'selectedTodo',
					'mask'		=>true,
					'rel'       => 'lazadagiftmanage-grid',
					'postType'  => 'string',
					'warn'      => Yii::t('system', 'Please Select'),
					'callback'  => 'navTabAjaxDone',
			)
		),
		array (
			'text' => Yii::t ( 'system', 'Add' ),
			'url' => '/lazada/lazadagiftmanage/add',
			'htmlOptions' => array (
					'class' => 'add',
					'target' => 'dialog',
					'rel' => 'lazadagiftmanage-grid',
					'postType' => '',
					'callback' => '',
					'height' => '280',
					'width' => '650'
			)
		),
	),
	'columns' => array(
			array(
					'class' => 'CCheckBoxColumn',
					'selectableRows' =>2,
					'value'=> '$data->id',
					'htmlOptions' => array('style' => 'width:5px;'),
			),
			array(
					'name' => 'id',
					'value' => '$row+1',
					'htmlOptions' => array('style' => 'width:30px;'),
			),
			array(
					'name' => 'account',
					'value' => '$data->account',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'sku',
					'value' => '$data->sku',
					'htmlOptions' => array('style' => 'width:150px;'),
			),	
			array(
					'name' => 'sys_sku',
					'value' => '$data->sys_sku',
					'htmlOptions' => array('style' => 'width:150px;'),
			),						
			array(
					'name' => 'gift_sku',
					'value' => '$data->gift_sku',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'is_delete',
					'value' => 'LazadaGiftManage::getStatusLable($data->is_delete)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'create_user',
					'value' => $data->create_user,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'create_time',
					'value' => $data->create_time,
					'htmlOptions' => array('style' => 'width:150px;'),
			),	
			array(
					'name' => 'update_user',
					'value' => $data->update_user,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'update_time',
					'value' => $data->create_time,
					'htmlOptions' => array('style' => 'width:150px;'),
			),				
			array(
					'header' => Yii::t('system', 'Operation'),
					'class' => 'CButtonColumn',
					'headerHtmlOptions' => array('width' => '60', 'align' => 'center'),
					'htmlOptions' => array('align' => 'center',),
					'template' => '{changbrand}',
					'buttons' => array(
							'changbrand' => array(
									'label' => Yii::t('system', 'Edit'),
									'url' => 'Yii::app()->createUrl("/lazada/lazadagiftmanage/edit", array("id" => $data->id))',
									'title' => Yii::t('system', 'Edit'),
									'options' => array('target' => 'dialog','class'=>'btnEdit','mask'=>true,),
							),
					),
			)
	),
	'tableOptions' => array(
		'layoutH' => 90,
	),
	'pager' => array(),
));
?>