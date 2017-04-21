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
		'id' => 'ebaydepartmentaccountsite-grid',
		'dataProvider' => $model->search(null),
		'filter' => $model,
		'toolBar' => array (
				array (
						'text' => Yii::t ( 'system', 'Add' ),
						'url' => '/ebay/ebaydepartmentaccountsite/create',
						'htmlOptions' => array (
								'class' => 'add',
								'target' => 'dialog',
								'rel' => 'ebaydepartmentaccountsite-grid',
								'postType' => '',
								'callback' => '',
								'height' => '300',
								'width' => '680' 
						) 
				),
				array(
						'text' => Yii::t('ebay', '批量更新部门'),
			            'url' => "javascript:void(0)",
			            'htmlOptions' => array(
			                'class' => 'add',
			                'rel' => 'ebay_product_list',
			                'onclick' => 'updateDepart()',
			            )
				),
		),
		'columns'=>array(
				array(
		            'class'=>'CCheckBoxColumn',
		            'value'=>'$data->id',
		            'selectableRows' => 2,
		            'htmlOptions' => array(
		                    'style' => 'width:20px;',
		            
		            ),
		            'headerHtmlOptions' => array(
		                    'align' => 'center',
		                    'style' => 'width:20px;',
		                    'onclick'=>''
		            ),
		            'checkBoxHtmlOptions' => array(
		                    'onchange' => '',
		                    'onpropertychange' => '',
		                    'oninput' => '',
		            ),
		                
		        ),
				/*array(
						'class' => 'CCheckBoxColumn',
						'selectableRows' =>2,
						'value'=> '$data->id',
				),*/
				array(
						'name'=> 'id',
						'value'=>'$row+1',
						'htmlOptions'=>array(
								'style'=>'width:60px'
						),
						'headerHtmlOptions'=>array(
								'align'=>'center'
						),
				),
				array(
						'name' => 'department_id',
						'value'=> '$data->department_id',
						'htmlOptions'=>array(
								'style'=>'width:200px'
						),
						'headerHtmlOptions'=>array(
								'align'=>'center'
						),
				),
				array(
						'name' => 'account_id',
						'value'=> '$data->account_id',
						'htmlOptions'=>array(
								'style'=>'width:200px'
						),
						'headerHtmlOptions'=>array(
								'align'=>'center'
						),
				),
				array(
						'name' => 'site_id',
						'value'=> '$data->site_id',
						'htmlOptions'=>array(
								'style'=>'width:200px'
						),
						'headerHtmlOptions'=>array(
								'align'=>'center'
						),
				),
				array(
						'header' => Yii::t('system', 'Operation'),
						'class' => 'CButtonColumn',
						'template' => '{edit}',
						'buttons' => array(
								'edit' => array(
										'url'       => 'Yii::app()->createUrl("/ebay/ebaydepartmentaccountsite/update", array("id" => $data->id))',
										'label'     => '修改部门',
										'options'   => array(
												'target'    => 'dialog',
												'class'     =>'btnEdit',
												'width'     => '400',
												'height'    => '200',
										),
								),
				
						),
						'htmlOptions'=>array(
								'style'=>'width:80px'
						),
				),
			
		),
		'tableOptions' => array (
				'layoutH' => 90 
		),
	    'pager' => array () 
));
?>
<script>
function updateDepart() {
    var ids = "";
    var arrChk = $("input[name='ebaydepartmentaccountsite-grid_c0[]']:checked");
    if (arrChk.length == 0) {
        alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
        return false;
    }
    for (var i = 0; i < arrChk.length; i++) {
        ids += arrChk[i].value + ',';
    }
    ids = ids.substring(0, ids.lastIndexOf(','));
    var url = '/ebay/ebaydepartmentaccountsite/batchupdate/ids/' + ids;
    $.pdialog.open(url, 'batchupdate', '批量更新部门', {width: 400, height: 200});
    return false;
}
</script>