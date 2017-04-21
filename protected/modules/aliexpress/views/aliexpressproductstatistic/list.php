<?php
Yii::app()->clientScript->scriptMap['jquery.js'] = false;
$this->widget('UGridView', array(
	'id' => 'aliexpress_product_statistic',
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
		array(
			'name' => 'skuinaccount',
				'value'=> '$data->skuInAccount',
				'type'  => 'raw',
				'headerHtmlOptions' => array(
						'class' => 'center',
				),
				'htmlOptions' => array(
						'width' => '40', 'align' => 'center',
				),
		),
		array(
			'name' => 'productcategorystring',
				'value'=> '$data->ProductCategoryString',
				'type'  => 'raw',
				'headerHtmlOptions' => array(
						'class' => 'center',
				),
				'htmlOptions' => array(
						'width' => '150', 'align' => 'center',
				),
		),
	),
	'toolBar' => array(
		array(
			'text' => Yii::t('aliexpress_product_statistic', 'Batch Publish'),
			'url' => Yii::app()->createUrl('aliexpress/aliexpressproductstatistic/batchpublish/', array( 'account_id' => $accountID)),
			'htmlOptions' => array(
				'class' => 'add',
				'target' => 'selectedTodo',
				'rel' => 'aliexpress_product_statistic',
				'postType' => 'string',
				'callback' => 'navTabAjaxDone',
				'title' => Yii::t('lazada_product_statistic', 'Are You Sure to Publish?'),
				'onclick' => '',
			),
		),

		array(
			'text' => '批量发布到其他账号',
			'url' => Yii::app()->createUrl('/aliexpress/aliexpressproductstatistic/batchpublishselectaccountsave/', array( 'account_id' => $accountID, 'add_account_id'=>$publishAccountID, 'publish_group_id'=>$publishGroupID, 'module_id'=>$moduleId, 'freight_template_id'=>$freightTemplateId)),
			'htmlOptions' => array(
				'class' => 'add',
				'target' => 'selectedTodo',
				'rel' => 'aliexpress_product_statistic',
				// 'onclick' => 'skuSelectAccount()'
				'postType' => 'string',
				'callback' => 'navTabAjaxDone',
				'title' => '运行时间会较长，确定要批量发布吗',
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

<script type="text/javascript">
$(document).ready(function(){
	$("#is_online").parent('.h25').css({'width':'130px'});
	$("#publish_group_id").parent('.h25').css({'width':'430px'});
	$("#product_category").parent('.h25').css('width','430px');
	$("#product_category_two").parent('.h25').css({'width':'580px'});
	$("#product_category_three").parent('.h25').css('width','600px');
	$("#product_category_four").parent('.h25').css('width','430px');
	$("#product_is_multi").parent('.h25').css({'width':'130px'});
	$("#product_stock_0").parent('.h25').css({'width':'210px'});
	$("#product_cost_0").parent('.h25').css({'width':'210px'});
	$("#module_id").parent('.h25').css('width','570px');
	$("#freight_template_id").parent('.h25').css('width','430px');

	//触发了要发布的账号
	$("#publish_account_id").change(function(){
		var selectedId = $(this).find("option:selected").val();
		var ajaxurl ='/aliexpress/aliexpressproductstatistic/groupajaxdata/accountId/' + selectedId;
		htmlobj=$.ajax({url:ajaxurl,async:false});
	    option = htmlobj.responseText;
	    $('#publish_group_id').empty();
	    $('#publish_group_id').append(option);

	    //获取产品信息模块
	    var moduleUrl = '/aliexpress/aliexpressproductstatistic/moduleajaxdata/accountId/' + selectedId;
	    moduleObj = $.ajax({url:moduleUrl,async:false});
	    moduleText = moduleObj.responseText;
	    $('#module_id').empty();
	    $('#module_id').append(moduleText);

	    //获取运费模板
	    var freightUrl = '/aliexpress/aliexpressproductstatistic/freighttemplateajaxdata/accountId/' + selectedId;
	    freightObj = $.ajax({url:freightUrl,async:false});
	    freightText = freightObj.responseText;
	    $('#freight_template_id').empty();
	    $('#freight_template_id').append(freightText);
		return false;
	});

	//默认插入的要发布的产品分组
	var publishAccountId = "<?php echo $publishAccountID; ?>";
	var publishGroupId = "<?php echo $publishGroupID; ?>";
	if(parseInt(publishAccountId) > 0 && parseInt(publishGroupId) > 0){
		var ajaxurl ='/aliexpress/aliexpressproductstatistic/groupajaxdata/accountId/' + publishAccountId + '/publishGroupId/' + publishGroupId;
		htmlobj=$.ajax({url:ajaxurl,async:false});
	    option = htmlobj.responseText;
	    $('#publish_group_id').empty();
	    $('#publish_group_id').append(option);
	}

	//触发了产品类目一级分类
	$("#product_category").change(function(){
		var parentCategoryId = $(this).find("option:selected").val();
		var ajaxurl ='/aliexpress/aliexpressproductstatistic/categorylevelajaxdata/parentCategoryId/' + parentCategoryId;
		htmlobj=$.ajax({url:ajaxurl,async:false});
	    option = htmlobj.responseText;
	    $('#product_category_two').empty();
	    $('#product_category_two').append(option);
	});

	//触发了产品类目二级分类
	$("#product_category_two").change(function(){
		var parentCategoryId = $(this).find("option:selected").val();
		var ajaxurl ='/aliexpress/aliexpressproductstatistic/categorylevelajaxdata/parentCategoryId/' + parentCategoryId;
		htmlobj=$.ajax({url:ajaxurl,async:false});
	    option = htmlobj.responseText;
	    $('#product_category_three').empty();
	    $('#product_category_three').append(option);
	});

	//触发了产品类目三级分类
	$("#product_category_three").change(function(){
		var parentCategoryId = $(this).find("option:selected").val();
		var ajaxurl ='/aliexpress/aliexpressproductstatistic/categorylevelajaxdata/parentCategoryId/' + parentCategoryId;
		htmlobj=$.ajax({url:ajaxurl,async:false});
	    option = htmlobj.responseText;
	    $('#product_category_four').empty();
	    $('#product_category_four').append(option);
	});
});
</script>