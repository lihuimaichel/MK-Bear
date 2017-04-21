<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'platformshopeeaccount-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'toolBar' => array(	
			array(
					'text'          => Yii::t('system', 'Add New Account'),
					'url'           => Yii::app()->createUrl('/platformaccount/platformshopeeaccount/add'),
					'htmlOptions'   => array(
							'class'     => 'add',
							'target'    => 'dialog',
							'rel'       => 'platformshopeeaccount-grid',
							'postType'  => '',
							'callback'  => '',
							'height'   => '500',
							'width'    => '580'
					)
			),

			array(
				'text' => Yii::t('platformaccount', 'Sync account to shopee table'),
				'url'  => Yii::app()->createUrl('/platformaccount/platformshopeeaccount/sync'),
				'htmlOptions' => array (
					'class'    => 'edit',
					'target'   => 'selectedToDo',
					'rel'      => 'platformshopeeaccount-grid',
					'title'	   => Yii::t('shopee', 'Confirm to sync?'),
					'postType' => 'string',
					'callback' => '',
					'height'   => '',
					'width'    => ''
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
            'htmlOptions' => array('style' => 'width:120px;'),
        ),
			array(
					'name' => 'short_name',
					'value' => '$data->short_name',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name' => 'site_code',
					'value' => '$data->site_code',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name' => 'status',
					'value' => 'PlatformEbayAccount::getStatus($data->status)',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
        array(
            'name' => 'open_time',
            'value' => '$data->open_time',
            'htmlOptions' => array('style' => 'width:120px;'),
        ),
        array(
            'name' => 'created_at',
            'value' => '$data->created_at',
            'htmlOptions' => array('style' => 'width:150px;'),
        ),
			array(
					'name' => 'updated_at',
					'value' => '$data->updated_at',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'to_oms_status',
					'value' => 'PlatformEbayAccount::getOmsStatus($data->to_oms_status)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'to_oms_time',
					'value' => '$data->to_oms_time',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'header' => Yii::t('system', 'Operation'),
					'class' => 'CButtonColumn',
					'headerHtmlOptions' => array('width' => '200', 'align' => 'center'),
					'template' => '{updates}<br>{retoken}',
					'buttons' => array(
						'updates' => array(
								'url'   => 'Yii::app()->createUrl("/platformaccount/platformshopeeaccount/editform", array("id" => $data->id))',
								'label' => Yii::t('system', 'Edit'),
								'headerHtmlOptions' => array('height' => '20', 'align' => 'center'),
								'options' => array(
									'target'    => 'dialog',
									'mask'		=>true,
									'rel' 		=> 'platformshopeeaccount-grid',
									'width'     => '580',
									'height'    => '450',
								),
						),

                        'retoken' => array(
                            'url' => 'Yii::app()->createUrl("/platformaccount/platformshopeeaccount/retokenform", array("id" => $data->id))',
                            'label' => Yii::t('platformaccount', 'Retoken'),
                            'headerHtmlOptions' => array('height' => '20', 'align' => 'center'),
                            'options' => array(
                                'target'    => 'dialog',
                                'mask'		=>true,
                                'rel' 		=> 'platformshopeeaccount-grid',
                                'width'     => '580',
                                'height'    => '450',
                            ),
                            'visible'=> '$data->status !=0'
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

<style type="text/css">
    .pageFormContent dl.nowrap dd, .nowrap dd{width: 400px !important;}
</style>

