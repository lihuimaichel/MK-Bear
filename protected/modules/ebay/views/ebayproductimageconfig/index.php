<style type="text/css">
.grid .gridTbody td div{
	height:auto;
	padding-top:2px;
}
</style>
<?php
$row = 0;
Yii::app ()->clientscript->scriptMap ['jquery.js'] = false;
$this->widget ( 'UGridView', array (
		'id' => 'ebayproductimageconfig-grid',
		'dataProvider' => $model->search(null),
		'filter' => $model,
		
		'toolBar' => array (
				array (
						'text' => Yii::t ( 'system', 'Add' ),
						'url' => '/ebay/ebayaccount/create',
						'htmlOptions' => array (
								'class' => 'add',
								'target' => 'dialog',
								'rel' => 'ebayaccount-grid',
								'postType' => '',
								'callback' => '',
								'height' => '680',
								'width' => '850' 
						) 
				) 
		),
		'columns'=>array(
				array(
						'class' => 'CCheckBoxColumn',
						'selectableRows' =>2,
						'value'=> '$data->id',
				),
				array(
						'name'=> 'id',
						'value'=>'$row+1',
				),
				array(
						'name' => 'user_name',
						'value' => '$data->user_name',
				),
				array(
						'name' => 'store_name',
						'value'=> '$data->store_name',
				),
				array(
						'name' => 'email',
						'value'=> '$data->email',
				),
				array(
						'name' => 'use_status',
						'value' =>'VHelper::getStatusLable($data->status)',
				),
				array(
						'name' => 'frozen_status',
						'value' => 'EbayAccount::getAccountLockStatus($data->is_lock)',
				),
				array(
						'name' => 'group_id',
						'value' => '$data->group_id',
				),
				array(
						'header' => Yii::t('system', 'Operation'),
						'class' => 'CButtonColumn',
						'template' => '{edit}',
						'buttons' => array(
								'edit' => array(
										'url'       => 'Yii::app()->createUrl("/ebay/ebayaccount/update", array("id" => $data->id))',
										'label'     => Yii::t('system', 'Edit Paypal Account'),
										'options'   => array(
												'target'    => 'dialog',
												'class'     =>'btnEdit',
												'width'     => '850',
												'height'    => '680',
										),
								),
				
						),
				),
			
		),
		'tableOptions' => array (
				'layoutH' => 90 
		),
	    'pager' => array () 
));

?>