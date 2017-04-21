<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'			=>	'aliexpressoutofstock-grid',
	'filter'		=>	$model,
	'dataProvider'	=>	$model->search(),
	'selectableRows'=>	2,
	'toolBar'		=>	array(
		array(
			'text' 			=> Yii::t('ebay', 'Set OutOfStock'),
			'url' 			=> 'aliexpress/aliexpressoutofstock/setoutofstock/',
			'htmlOptions' 	=> array(
					'class'     => 'add',
					'title'     => Yii::t('system', 'Sure you want to do this'),
					'target'    => 'selectedTodo',
					'rel'       => 'aliexpressoutofstock-grid',
					'postType'  => 'string',
					'warn'      => Yii::t('system', 'Please Select'),
					'callback'  => 'navTabAjaxDone',
			),
		),
		array(
			'text' 			=> Yii::t('ebay', 'Cancel OutOfStock'),
			'url' 			=> 'aliexpress/aliexpressoutofstock/canceloutofstock/',
			'htmlOptions' 	=> array(
					'class' 	=> 'delete',
					'title'     => Yii::t('system', 'Sure you want to do this'),
					'target'    => 'selectedTodo',
					'rel'       => 'aliexpressoutofstock-grid',
					'postType'  => 'string',
					'warn'      => Yii::t('system', 'Please Select'),
					'callback'  => 'navTabAjaxDone',
			),
		),
	),
	'columns'		=>	array(
		array(
			'class'	=>'CCheckBoxColumn',
			'value'	=>'$data->sku',
			'selectableRows' => 2,
			'htmlOptions' 		=> array(
					'style' => 'width:20px;',
			),
			'headerHtmlOptions' 	=> array(
					'align' 	=> 'center',
					'style' 	=> 'width:20px;',
					'onclick'	=>'selectAll(this)'
			),
			'checkBoxHtmlOptions' => array(
					'onchange' 	=> 'checkSelect(this)',
					'onpropertychange' => 'checkSelect(this)',
					'oninput' 	=> 'checkSelect(this)',
			),
		),
		array(
			'name'  =>'no',
			'value' => '$row+1',
			'type'  =>'raw',
			'htmlOptions' 		=> array(
					'style' => 'width:50px;',
			),
			'headerHtmlOptions' => array(
					'align' => 'center',
			),
		),
		array(
			'name'  =>'sku',
			'value' => '$data->sku',
			'type'  =>'raw',
			'htmlOptions' 		=> array(
					'style' => 'width:100px;',
			),
			'headerHtmlOptions' => array(
					'align' => 'center',
			),
		),
		array(
			'name'  =>'is_outofstock',
			'value' => '$data->is_outofstock ? AliexpressOutofstock::getOutOfStockOption($data->is_outofstock) : ""',
			'type'  =>'raw',
			'htmlOptions' 		=> array(
					'style' => 'width:120px;',
			),
			'headerHtmlOptions' => array(
					'align' => 'center',
			),
		),
		array(
			'name'  =>'ack',
			'value' => '$data->ack ? AliexpressOutofstock::getAckOption($data->ack) : ""',
			'type'  =>'raw',
			'htmlOptions' 		=> array(
					'style' => 'width:100px;',
			),
			'headerHtmlOptions' => array(
					'align' => 'center',
			),
		),					
		array(
			'name'  =>'message',
			'value' => '$data->message',
			'type'  =>'raw',
			'htmlOptions' 		=> array(
					'style' => 'width:300px;',
			),
			'headerHtmlOptions' => array(
					'align' => 'center',
			),
		),			
		array(
			'name'  =>'operator',
			'value' => '$data->operator ? User::model()->getUserFullNameScalarById($data->operator) : ""',
			'type'  =>'raw',
			'htmlOptions' 		=> array(
					'style' => 'width:100px;',
			),
			'headerHtmlOptions' => array(
					'align' => 'center',
			),
		),
		array(
			'name'	=>'operate_time',
			'value' => '$data->operate_time',
			'type'	=>'raw',
			'htmlOptions' 		=> array(
					'style' => 'width:100px;',
			),
			'headerHtmlOptions' => array(
					'align' => 'center',
			),
		),
		array(
			'name'  =>'operate_note',
			'value' => '$data->operate_note',
			'type'  =>'raw',
			'htmlOptions' 		=> array(
					'style' => 'width:300px;',
						
			),
			'headerHtmlOptions' => array(
					'align' => 'center',
			),
		)	
	),
	'pager' 		=> array(),
	'tableOptions' 	=> array(
		'layoutH' 			=> 150,
		'tableFormOptions' 	=> true,
	),
));

?>

<script type="text/javascript">
function selectAll(obj){
	var chcked = !!$(obj).find("input").attr("checked");
	$("input[name='aliexpress_outofstock_list_c0[]']").not(":disabled").each(function(){
		this.checked = chcked;
	});
}

function checkSelect(obj) {
	if (!!$(obj).attr('checked')) {
		$(obj).parents('tr').find("input[name='aliexpress_outofstock_list_c0[]']").not(":disabled").each(function(){
			this.checked = true;
		});
	} else {
		$(obj).parents('tr').find("input[name='aliexpress_outofstock_list_c0[]']").not(":disabled").each(function(){
			this.checked = false;
		});
	}
}
</script>