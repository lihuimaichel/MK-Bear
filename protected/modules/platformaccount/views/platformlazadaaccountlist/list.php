<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'platformlazadaaccountlist-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'toolBar' => array(	
			array(
					'text'          => Yii::t('system', 'Add New Account'),
					'url'           => '/platformaccount/platformlazadaaccountlist/add',
					'htmlOptions'   => array(
							'class'     => 'add',
							'target'    => 'dialog',
							'rel'       => 'platformlazadaaccountlist-grid',
							'postType'  => '',
							'callback'  => '',
							'height'   => '190',
							'width'    => '620'
					)
			),
	),
	'columns' => array(
			array(
					'class' => 'CCheckBoxColumn',
					'selectableRows' =>2,
					'value'=> '$data->id',
					'htmlOptions' => array('style' => 'width:30px;'),
			),
			array(
					'name' => 'account_name',
					'value' => '$data->account_name',
					'htmlOptions' => array('style' => 'width:220px;'),
			),
			array(
					'name'  => 'create_user_id',
					'value' => 'MHelper::getUsername($data->create_user_id)',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:90px;',
					),						
			),
			array(
					'name' => 'create_time',
					'value' => '$data->create_time',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name'  => 'modify_user_id',
					'value' => 'MHelper::getUsername($data->modify_user_id)',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:90px;',
					),						
			),
			array(
					'name' => 'modify_time',
					'value' => '$data->modify_time',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'header' => Yii::t('system', 'Operation'),
					'class' => 'CButtonColumn',
					'headerHtmlOptions' => array('width' => '100', 'align' => 'center'),
					'template' => '{updates}',
					'buttons' => array(
						'updates' => array(
								'url'   => 'Yii::app()->createUrl("/platformaccount/platformlazadaaccountlist/edit", array("id" => $data->id))',
								'label' => Yii::t('system', 'Edit'),
								'headerHtmlOptions' => array('height' => '20', 'align' => 'center'),
								'options' => array(
									'target'    => 'dialog',
									'mask'		=>true,
									'rel' 		=> 'platformlazadaaccountlist-grid',
									'width'     => '',
									'height'    => '',
								),
						),
				    ),
			),
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
));
?>