<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'joomaccountseller-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'toolBar' => array(
			array (
					'text' => Yii::t ( 'system', 'Add' ),
					'url' => '/joom/joomaccountseller/add',
					'htmlOptions' => array (
							'class' => 'add',
							'target' => 'dialog',
							'rel' => 'joomaccountseller-grid',
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
					'htmlOptions' => array('style' => 'width:80px;'),
			),
			array(
					'name'  => 'account_id',
					'value' => '$data->account_name',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:90px;',
					),
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