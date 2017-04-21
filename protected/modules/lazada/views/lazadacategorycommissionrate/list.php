<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'lazadacategorycommissionrate-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'toolBar' => array(
		array(
			'text'          => Yii::t('lazada', 'Batch delete messages'),
			'url'           => '/lazada/lazadacategorycommissionrate/delete',
			'htmlOptions'   => array(
					'class'     => 'delete',
					'title'     => Yii::t('system', 'Really want to delete these records?'),
					'target'    => 'selectedTodo',
					'mask'		=>true,
					'rel'       => 'lazadacategorycommissionrate-grid',
					'postType'  => 'string',
					'warn'      => Yii::t('system', 'Please Select'),
					'callback'  => 'navTabAjaxDone',
			)
		),
		array (
			'text' => Yii::t ( 'system', 'Add' ),
			'url' => '/lazada/lazadacategorycommissionrate/add',
			'htmlOptions' => array (
					'class' => 'add',
					'target' => 'dialog',
					'rel' => 'lazadacategorycommissionrate-grid',
					'postType' => '',
					'callback' => '',
					'height' => '280',
					'width' => '650'
			)
		),
	),
	'columns'=>array(
			array(
					'class' => 'CCheckBoxColumn',
					'selectableRows' =>2,
					'value'	=> '$data->id',
					'htmlOptions' => array('style' => 'width:20px;'),
			),			
			array(
					'name'  => 'category_id',
					'value' => '$data->category_name',
					'htmlOptions' => array('style' => 'width:290px;'),
			),
			array(
					'name'  => 'site_id',
					'value' => 'LazadaSite::model()->getSiteList($data->site_id)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name'  => 'commission_rate',
					'value' => '$data->commission_rate',
					'htmlOptions' => array('style' => 'width:90px;'),
			),
			array(
					'name'  => 'create_user_id',
					'value' => 'User::model()->getUserNameScalarById($data->create_user_id)',
					'htmlOptions' => array('style' => 'width:180px;'),
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

<script type="text/javascript">
$(document).ready(function(){
	$("#category_id").parent('.h25').css({'width':'330px'});
});
</script>