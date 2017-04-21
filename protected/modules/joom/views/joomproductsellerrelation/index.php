<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'joomproductsellerrelation-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'toolBar' => array(
			array (
					'text' => Yii::t ( 'system', 'Add' ),
					'url' => '/joom/joomproductsellerrelation/import',
					'htmlOptions' => array (
							'class' => 'add',
							'target' => 'dialog',
							'rel' => 'joomproductsellerrelation-grid',
							'postType' => '',
							'callback' => '',
							'height' => '480',
							'width' => '650'
					)
			),
			array (
					'text' => Yii::t ( 'system', '批量更改账号销售' ),
					'url' => '/joom/joomproductsellerrelation/batchchangetoseller',
					'htmlOptions' => array (
							'class' => 'add',
							'target' => 'dialog',
							'rel' => 'joomproductsellerrelation-grid',
							'postType' => '',
							'callback' => '',
							'height' => '480',
							'width' => '650'
					)
			),
			
			
			array (
					'text' => Yii::t ( 'system', '批量删除' ),
					'url' => '/joom/joomproductsellerrelation/batchdel',
					'htmlOptions' => array (
							'class' => 'delete',
							'target' => 'selectedToDo',
							'rel' => 'joomproductsellerrelation-grid',
							'title'	=>	'确认要删除这些吗?',
							'postType' => 'string',
							'callback' => '',
							'height' => '',
							'width' => ''
					)
			),
			
			
			array (
					'text' => Yii::t ( 'system', '未绑定销售人员列表' ),
					'url' => '/joom/joomproductsellerrelation/unbindseller',
					'htmlOptions' => array (
							'class' => 'add',
							'target' => 'navtab',
							'rel' => 'joomproductsellerrelation-grid',
							'height' => '',
							'width' => ''
					)
			),

			array (
					'text' => Yii::t ( 'system', '批量导出' ),
					'url'  => 'javascript:void(0)',
					'htmlOptions' => array (
							'class' => 'add',
							'target' => '',
							'rel' => '',
							'onclick' => 'bindSkuSellerDownCSV()'
					)
			),
	),
	'columns'=>array(
				array(
						'class' => 'CCheckBoxColumn',
						'selectableRows' =>2,
						'value'	=> '$data->id',
						'htmlOptions'=>array(
								'style' => 'width:60px;'
						)
				),
				
			
				array(
						'name' => 'item_id',
						'value' => '$data->item_id',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:380px;'),
				),
				array(
						'name' => 'sku',
						'value' => '$data->sku',
				        'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:180px;'),
				),
	
             	array(
						'name'  => 'online_sku',
						'value' => '$data->online_sku',
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:210px;',
						),
				),
				

				array(
						'name' => 'seller_id',
						'value' => 'User::model()->getUserNameScalarById($data->seller_id)',
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
				
				/* array(
						'name'  => 'site_id',
						'value' => '$data->site_id',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:90px;',
						),
				), */
	
			array(
					'header' => Yii::t('system', 'Operation'),
					'class' => 'CButtonColumn',
					'template' => '{edit}',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:150px;',
					),
					'buttons' => array(
							'edit' => array(
									'url'       => 'Yii::app()->createUrl("/joom/joomproductsellerrelation/update", array("id" => $data->id))',
									'label'     => Yii::t('joom_listing', '修改'),
									'options'   => array(
											'target'    => 'dialog',
											'class'     => 'btnEdit',
											'rel' 		=> 'joomproductsellerrelation-grid',
											'postType' 	=> '',
											'callback' 	=> '',
											'height' 	=> '',
											'width' 	=> ''
									),
							),
					),
			),
			
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
);

$this->widget('UGridView', $options);

?>

<script type="text/javascript">

function bindSkuSellerDownCSV(){
    var request = "<?php echo $request; ?>";
    var url ='/joom/joomproductsellerrelation/bindsellerexportxls?' + request;
    var ajaxurl ='/joom/joomproductsellerrelation/bindsellerexportxlsajax?' + request;
    var checkType = 0;

    htmlobj=$.ajax({url:ajaxurl,async:false});
    checkType = parseInt(htmlobj.responseText);
	if(checkType == 1){
		window.open(url);
	}else{
		alertMsg.error('无数据');
		return;
	}
	
}

</script>