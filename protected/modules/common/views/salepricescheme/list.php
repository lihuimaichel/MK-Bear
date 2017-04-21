<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$config = array(
		'id' => 'salepricescheme_id',
		'dataProvider' => $model->search(),
		'filter' => $model,
		'tableOptions' => array(
				'layoutH' => 145,
				'tableFormOptions' => true
		),
		'pager' => array(),
);

$config['columns'] = array();
$config['columns'][0] = array(
		'class' => 'CCheckBoxColumn',
		'value' => '$data->id',
		'selectableRows' => 2,
		'headerHtmlOptions' => array(
				'align' => 'center',
				'style' => 'width:25px'
		),
		'htmlOptions' => array(
				'style' => 'width:25px'
		)
);
$config['columns'][1] = array(
		'name' => 'id',
		'value' => '$row+1',
		'htmlOptions' => array(
				'align' => 'center',
				'style' => 'width:50px',
		),
		'headerHtmlOptions' => array(
				'class' => 'center',
		),
);
$config['columns'][2] = array(
		'name' => 'scheme_name',
		'value' => '$data->scheme_name',
		'headerHtmlOptions' => array(
				'class' => 'center'
		),
		'htmlOptions' => array(
				'style' => 'width:200px',
		),
);
$config['columns'][3] = array(
		'name' => 'platform_code',
		'type' => 'raw',
		'value' => 'CHtml::dropDownList("platform_code[$data->id]", "$data->platform_code", UebModel::model("Platform")->getPlatformList())',
		'headerHtmlOptions' => array(
				'class' => 'center'
		),
		'htmlOptions' => array(
				'style' => 'width:100px',
		),
);
$config['columns'][4] = array(
		'name' => 'profit_calculate_type',
		'type' => 'raw',
		'value' => 'CHtml::dropDownList("profit_calculate_type[$data->id]", "$data->profit_calculate_type", SalePriceScheme::getProfitCalculateTypeList())',
		'headerHtmlOptions' => array(
				'align' => 'center'
		),
		'htmlOptions' => array(
				'style' => 'width:150px',
		),
);
$config['columns'][5] = array(
		'name' => 'standard_profit_rate',
		'type' => 'raw',
		'value' => 'CHtml::textField("standard_profit_rate[$data->id]", $data->standard_profit_rate, array("size" => 6, "onChange" => "checkValueType(this)", "data" => "$data->standard_profit_rate"))',
		'headerHtmlOptions' => array(
				'class' => 'center',
		),
		'htmlOptions' => array(
				'style' => 'width:100px',
				'align' => 'center',
		),
);
$config['columns'][6] = array(
		'name' => 'lowest_profit_rate',
		'type' => 'raw',
		'value' => 'CHtml::textField("lowest_profit_rate[$data->id]", $data->lowest_profit_rate, array("size" => 6, "onChange" => "checkValueType(this)", "data" => "$data->lowest_profit_rate"))',
		'headerHtmlOptions' => array(
				'class' => 'center',
		),
		'htmlOptions' => array(
				'style' => 'width:100px',
				'align' => 'center',
		),
);
$config['columns'][7] = array(
		'name' => 'floating_profit_rate',
		'type' => 'raw',
		'value' => 'CHtml::textField("floating_profit_rate[$data->id]", $data->floating_profit_rate, array("size" => 6, "onChange" => "checkValueType(this)", "data" => "$data->floating_profit_rate"))',
		'headerHtmlOptions' => array(
				'class' => 'center',
		),
		'htmlOptions' => array(
				'style' => 'width:100px',
				'align' => 'center',
		),
);
$config['columns'][8] = array(
		'name' => 'status',
		'value' => 'UebModel::model("SalePriceScheme")->getStatusList($data->status)',
		'headerHtmlOptions' => array(
				'class' => 'center',
		),
		'htmlOptions' => array(
				'style' => 'width:100px',
				'align' => 'center',
		),
);
$config['columns'][9] = array(
		'name' => 'create_user_id',
		'value' => 'MHelper::getUsername($data->create_user_id)',
		'headerHtmlOptions' => array('class' => 'center',),
		'htmlOptions' => array(
				'style' => 'width:100px',
		)
);
$config['columns'][10] = array(
		'name' => 'create_time',
		'value' => '$data->create_time',
		'headerHtmlOptions' => array('class' => 'center',),
		'htmlOptions' => array(
				'style' => 'width:200px',
		)
);

if ( Yii::app()->request->getParam('target') == 'dialog' ) {
	$config['toolBar'] = array(
			array(
					'text' => Yii::t('system', 'Please Select'),
					'type' => 'button',
					'url' => '',
					'htmlOptions' => array(
							'target' => '',
							'class' => 'edit',
							'multLookup' => 'salepricescheme_id_c0[]',
							'rel' =>'{target:"'.$modelName.'_template_name", url: "common/salepricescheme/getcode"}',
							'onclick' => false
					)
			)
	);

}else{
	$config['columns'][11] = array(
			'name' => 'modify_user_id',
			'value' => 'MHelper::getUsername($data->modify_user_id)',
			'headerHtmlOptions' => array('class' => 'center',),
			'htmlOptions' => array(
					'style' => 'width:100px',
			)
	);
	$config['columns'][12] = array(
			'name' => 'modify_time',
			'value' => '$data->modify_time',
			'headerHtmlOptions' => array('class' => 'center',),
			'htmlOptions' => array(
					'style' => 'width:200px',
			)
	);
	$config['toolBar'] = array(
			array(
					'text' => Yii::t('sale_price_scheme', 'Add Scheme'),
					'url' => Yii::app()->createUrl('common/salepricescheme/create'),
					'htmlOptions' => array(
							'class' => 'add',
							'target' => 'dialog',
							'mask' => true,
							'width' => 450,
							'height' => 275,
							'onclick' => ''
					),
			),
			array(
					'text' => Yii::t('system', 'Save'),
					'url' => "javascript:void(0)",//Yii::app()->createUrl('common/salepricescheme/update'),
					'htmlOptions' => array(
							'class' => 'delete',
							'title' => Yii::t('system', 'Really want to Save these records?'),
							'onclick' => 'batchSave()'
					),
			),
			array(
					'text' => Yii::t('system', 'Delete'),
					'url' => Yii::app()->createUrl('common/salepricescheme/delete'),
					'htmlOptions' => array(
							'class' => 'delete',
							'title' => Yii::t('system', 'Really want to delete these records'),
							'target' => 'selectedTodo',
							'rel' => 'salepricescheme_id',
							'postType' => 'string',
							'callback' => 'navTabAjaxDone',
							'onclick' => '',
					),
			)
	);
}



$this->widget('UGridView', $config);
?>
<script type="text/javascript">
//批量保存卖价方案数据
function batchSave() {
	var checkboxs = $('input[name=salepricescheme_id_c0\\[\\]]');
	var hasChosen = false;
	var ids = '';
 	checkboxs.each(function(){
		if (this.checked)
			ids += this.value + ',';
 	});
	if (ids == '') {
		alertMsg.error('<?php echo Yii::t('system', 'Please select a record');?>');
		return false;
	}
	//将数据发送到服务端
	var serializeData = '';
	serializeData = 'ids=' + ids;
	serializeData += '&' + $('input[name^=standard_profit_rate]').serialize();
	serializeData += '&' + $('input[name^=lowest_profit_rate]').serialize();
	serializeData += '&' + $('input[name^=floating_profit_rate]').serialize();
	serializeData += '&' + $('select[name^=profit_calculate_type]').serialize();
	serializeData += '&' + $('select[name^=platform_code]').serialize();
	$.ajax({
		'type': 'POST',
		'url': '<?php echo Yii::app()->createUrl('common/salepricescheme/update');?>',
		'dataType': 'json',
		'global' : 'false',
		'data': serializeData,
		'success' : function(data){
			navTabAjaxDone(data);
		},
		'error' : DWZ.ajaxError,
	});
}
//检查输入框值的类型是不是小数
function checkValueType(t) {
	var value = t.value;
	var v = parseFloat(value);
	if (isNaN(v)) {
		alertMsg.error('<?php echo Yii::t('sale_price_scheme', 'Please Add Valid Number');?>');
		t.value = $(t).attr('data');
	} else {
		t.value = v;
	}
}
</script>