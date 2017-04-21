<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->beginWidget('UGridView', array(
	'id' => 'create_privileges_id',
	'filter' => $productModel,
	'dataProvider' => $model->searchProduct(),
	'columns' => array(
		array(
			'class' => 'CCheckBoxColumn',
			'selectableRows' => 2,
			'value' => '$data->sku',
			'checked' => '$data->has_privileges ? true : false',
			'htmlOptions' => array(
			),
			'headerHtmlOptions' => array(
				'align' => 'center',
				'style' => 'width:25px',
			),
		),
		array(
			'name' => 'id',
			'value' => '$row+1',
			'headerHtmlOptions' => array('class' => 'center',),
			'htmlOptions' => array(
				'style' => 'width:25px',
			),
		),
		array(
			'name' => 'sku',
			'value' => '$data->sku',
			'headerHtmlOptions' => array('class' => 'center',),
			'htmlOptions' => array(
				'style' => 'width:100px',
			),						
		),
		array(
			'header' => Yii::t('sku_privileges', 'Title'),
			//'name' => 'title',
			'value' => '$data->title',
			'headerHtmlOptions' => array('class' => 'center',),
			'htmlOptions' => array(
				'style' => 'width:375px',
			),					
		),			
	),
	'toolBar' => array(
		array(
			'text' => Yii::t('sku_privileges', 'Grant Privileges'),
			'url' => 'javascript:void(0);',
			'htmlOptions' => array(
				'class' => 'add',
				'onclick' => 'if (confirm("' . Yii::t('sku_privileges', 'Really want to Change SKU Privileges?') . '")) createPrivileges();',
			),
		),
		array(
			'text' => Yii::t('sku_privileges', 'Grant Privileges By Search Condition'),
			'url' => 'javascript:void(0);',
			'htmlOptions' => array(
				'class' => 'add',
				'onclick' => 'if (confirm("' . Yii::t('sku_privileges', 'Really want to Change SKU Privileges?') . '")) createPrivilegesBySeachCondition();',
			),
		),
		array(
			'text' => Yii::t('sku_privileges', 'Revoke Privileges By Search Condition'),
			'url' => 'javascript:void(0);',
			'htmlOptions' => array(
				'class' => 'delete',
				'onclick' => 'if (confirm("' . Yii::t('sku_privileges', 'Really want to Change SKU Privileges?') . '")) revokePrivilegesBySeachCondition();',
			),
		),				
	),
	'pager' => array(),
));
$this->endWidget();
?>
<?php 
$skuList = $model->skuList;
if ($skuList) {
	foreach ($skuList as $sku) {
		echo CHtml::hiddenField('skus[]', $sku);
	} 
} else {
	echo CHtml::hiddenField('skus[]', '');
}
?>
<script type="text/javascript">
function createPrivileges() {
	var selectCheckbox = $('input[name=create_privileges_id_c0\\[\\]]:checked');
	//if (selectCheckbox.length <= 0) {
		//alertMsg.error('<?php //echo Yii::t('system', 'Please select a record')?>');
		//return false;
	//}
	var postData = 'action=checkbox-do' + '&' + selectCheckbox.serialize();
	postData += '&' + $('input[name=skus\\[\\]]').serialize();
	return createPrivilegesAjax(postData);
	
}

function createPrivilegesBySeachCondition() {
	var sku = $('#sku').val();
	var productCategoryId = $('input[name=product_category_id]').val();
	if (sku == '' && (productCategoryId == '' || productCategoryId == -1)) {
		alertMsg.error('<?php echo Yii::t('sku_privileges', 'No Search Condition');?>');
		return false;
	}
	var postData = 'action=search-do-grant&sku=' + sku + '&product_category_id=' + productCategoryId;
	return createPrivilegesAjax(postData);
}

function revokePrivilegesBySeachCondition() {
	var sku = $('#sku').val();
	var productCategoryId = $('input[name=product_category_id]').val();
	if (sku == '' && (productCategoryId == '' || productCategoryId == -1)) {
		alertMsg.error('<?php echo Yii::t('sku_privileges', 'No Search Condition');?>');
		return false;
	}
	var postData = 'action=search-do-revoke&sku=' + sku + '&product_category_id=' + productCategoryId;
	return createPrivilegesAjax(postData);	
}

function createPrivilegesAjax(data) {
	var url = '<?php echo Yii::app()->createUrl($this->route, array('user_id' => $model->user_id, 'account_id' => $model->account_id, 'platform_id' => $model->platform_id));?>';	
	$.ajax({
		'type': 'POST',
		'dataType': 'json',
		'url': url,
		'data': data,
		'success': function(data) {
			if (data.statusCode != '200') {
				alertMsg.error(data.message);
				return false;
			} else {
				alertMsg.correct(data.message);
				navTab.reloadFlag('tabCreatePrivileges');
				return true;
			}
		},
		'error': function(xhr, errno, error) {
			alertMsg.error('<?php echo Yii::t('sku_privileges', 'Request Error, Please Try Again')?>');
			return false;
		}
	});
}
</script>