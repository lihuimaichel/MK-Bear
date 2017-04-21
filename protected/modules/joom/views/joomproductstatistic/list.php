<?php

Yii::app()->clientScript->scriptMap['jquery.js'] = false;

$this->widget('UGridView', array(
	'id' => 'joom_product_statistic',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
		array(
			'class' => 'CCheckBoxColumn',
			'value' => '$data->sku',
			'selectableRows' => 2,
			'headerHtmlOptions' => array(
				'style' => 'width:25px',
			),
		),
		array(
			'name' => 'id',
			'value' => '$row+1',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:25px',
			),
		),
		array(
			'name' => 'sku',
			'value' => '$data->sku',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'color' => 'blue','width' => '40', 'align' => 'center',
			),
		),
		array(
			'name' => 'en_title',
			'value' => 'VHelper::getBoldShow(!empty($data->en_title) ? $data->en_title : $data->cn_title)',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center','align' => 'center'
			),
			'htmlOptions' => array(
				'style' => 'width:275px',
			),
		),
		array(
			'name' => 'product_cost',
			'value'=> 'VHelper::getRedBoldShow($data->product_cost)',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'width' => '40', 'align' => 'center',
			),
		),
	),
	//joom产品刊登功能待开发
	'toolBar' => array(
		array(
			'text' => Yii::t('lazada_product_statistic', 'Batch Publish'),
			'url' => Yii::app()->createUrl('joom/joomproductstatistic/batchpublish/', array( 'account_id' => $accountID)),
			'htmlOptions' => array(
				'class' => 'add',
				'target' => 'selectedTodo',
				'rel' => 'joom_product_statistic',
				'postType' => 'string',
				'callback' => 'navTabAjaxDone',
				'title' => Yii::t('lazada_product_statistic', 'Are You Sure to Publish?'),
				'onclick' => '',
			),
		),
	),
	'pager' => array(),
	'tableOptions' => array(
		'layoutH' => 150,
		'tableFormOptions' => true
	),
));

?>

<script>

	/**
	 * joom产品表单数据校验
	 * author liht
	 * since 20151118
	 */

	(function(form) {

		//sku校验
		var checkSku = function(check) {

			var skuVal = $('#sku').val();

			if(skuVal != '') {
				//用户输入sku，校验sku
				if(isNaN(skuVal)) {
					check.preventDefault();
					alertMsg.error("<?php echo Yii::t('joom_product_statistic', 'Invalid sku');?>");
					$('#sku').focus();
					return false;
				}
			}

		};
		//价格区间校验
		var checkPostCost = function(check) {
			var cost_val_1 = parseInt($('#product_cost_1').val()),
				cost_val_0 = parseInt($('#product_cost_0').val());

			if(cost_val_1 != '' || cost_val_0 != '') {
				//价格区间不能小于0
				if (cost_val_1 < 0 || cost_val_0 < 0) {
					errorCostInput(check);
				}
				//第二个价格区间必需大于第一个价格区间
				if(cost_val_1 < cost_val_0) {
					errorCostInput(check);
				}
			}

		};

		var errorCostInput = function(formObj) {
			alertMsg.error("<?php echo Yii::t('joom_product_statistic', 'Invalid product cost input');?>");
			$('#product_cost_1').focus();
			formObj.preventDefault();
			return false;
		}

		form.bind('click',function(check) {
			checkSku(check);
			checkPostCost(check);
		});


	})($(':submit'));


	/**
	 * 获取产品在线品类
	 */
	function getProductOnlineCategory(obj){
		var clsId = $(obj).val();
		var html = '<option value="">所有</option>';
		if(clsId == ''){
			$("#search_online_category_id").html(html);
			return;
		}
		$.ajax({
			type: "GET",
			url: "/ebay/ebayproductstatistic/getonlinecatebyclsid",
			data: "cls_id="+clsId,
			dataType:'json',
			success: function(result){
				if(result.statusCode == 200){
					//组装数据
					$.each(result.data, function(i, n){
						html += '<option value="'+i+'">'+n+'</option>';
					});
					$("#search_online_category_id").html(html);
				}else{
					alert(result.message);
					$("#search_online_category_id").html(html);
				}
			}
		});
	}
</script>