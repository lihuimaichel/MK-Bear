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
		'id' => 'ebaysite-grid',
		'dataProvider' => $model->search(null),
		'filter' => $model,
		'toolBar' => array (
				array (
						'text' => Yii::t ( 'ebay', 'Open Site' ),
						'url' => '/ebay/ebaysite/open',
						'htmlOptions' => array (
								'class' => 'delete',
								'target' => 'selectedTodo',
								'rel' => 'ebaysite-grid',
								'postType' => 'string',
								'warn' => Yii::t ( 'system', 'Please Select' ),
								'callback' => 'navTabAjaxDone' 
						) 
				),
				array (
						'text' => Yii::t ( 'ebay', 'Close Site' ),
						'url' => '/ebay/ebaysite/close',
						'htmlOptions' => array (
								'class' => 'add',
								'target' => 'selectedTodo',
								'rel' => 'ebaysite-grid',
								'postType' => 'string',
								'callback' => 'navTabAjaxDone' 
						) 
				),
				array (
						'text' => Yii::t ( 'ebay', 'Add Site' ),
						'url' => '/ebay/ebaysite/create',
						'htmlOptions' => array (
								'class' => 'add',
								'target' => 'dialog',
								'rel' => 'ebaysite-grid',
								'postType' => '',
								'callback' => '',
								'height' => '450',
								'width' => '750' 
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
						'name' => 'site_id',
						'value'=> '$data->site_id',
				),
				array(
						'name' => 'site_name',
						'value'=> '$data->site_name',
				),
				array(
						'name' => 'is_open',
						'value'=> 'UebModel::model("Ebaysite")->getSiteStatus($data->is_open)',
				),
				array(
						'header' => Yii::t('system', 'Operation'),
						'class' => 'CButtonColumn',
						'template' => '{edit}',
						'buttons' => array(
								'edit' => array(
										'url'       => 'Yii::app()->createUrl("/ebay/ebaysite/update", array("id" => $data->id))',
										'label'     => Yii::t('ebay', 'Edit Site'),
										'options'   => array(
												'target'    => 'dialog',
												'class'     =>'btnEdit',
												'width'     => '500',
												'height'    => '300',
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
<script language="javascript"></script>

