<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'wishaccountseller-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'toolBar' => array(
			array (
					'text' => Yii::t ( 'system', 'Oprator' ),
					'url' => '/wish/wishaccountseller/add',
					'htmlOptions' => array (
							'class' => 'edit',
							'target' => 'dialog',
							'rel' => 'wishaccountseller-grid',
							'postType' => '',
							'callback' => '',
							'height' => '480',
							'width' => '850'
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
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name'  => 'account_id',
					'value' => '$data->account_name',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:150px;',
					),
			),
			array(
					'name' => 'create_time',
					'value' => '$data->create_time',
					'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:200px;'),
			)
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
);

$this->widget('UGridView', $options);
?>