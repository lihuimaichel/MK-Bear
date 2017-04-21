<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'wishcopylistingseller-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'toolBar' => array(
			array (
					'text' => Yii::t ( 'system', 'Add' ),
					'url' => '/wish/wishcopylistingseller/add',
					'htmlOptions' => array (
							'class' => 'add',
							'target' => 'dialog',
							'postType' => '',
							'callback' => '',
							'height' => '280',
							'width' => '650'
					)
			),
			array(
					'text' => Yii::t('wish_listing', 'Batch Delete'),
					'url' => Yii::app()->createUrl('/wish/wishcopylistingseller/batchdel'),
					'htmlOptions' => array(
							'class' => 'delete',
							'title'     => '',
							'target'    => 'selectedTodo',
							'rel'       => 'wishcopylistingseller-grid',
							'postType'  => 'string',
							'callback'  => 'navTabAjaxDone',
							'onclick'	=>	''
					)   
			),
	),
	'columns'=>array(
			array(
					'class' => 'CCheckBoxColumn',
					'selectableRows' =>2,
					'value'	=> '$data->id',
			),			
			array(
					'name' => 'seller_user_id',
					'value' => 'User::model()->getUserNameScalarById($data->seller_user_id)',
					'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:80px;'),
			),
			array(
					'name' => 'create_time',
					'value' => '$data->create_time',
					'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:180px;'),
			)
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
);

$this->widget('UGridView', $options);
?>