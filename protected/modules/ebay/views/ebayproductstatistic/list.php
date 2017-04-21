<?php
Yii::app()->clientScript->scriptMap['jquery.js'] = false;
$this->widget('UGridView', array(
	'id' => 'ebay_product_statistic',
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
	'toolBar' => array(
		array(
			'text' => Yii::t('aliexpress_product_statistic', 'Batch Publish'),
			'url' => '/ebay/ebayproductstatistic/batchpublish/account_id/'.$accountID.'/site_id/'.$siteID,
			'htmlOptions' => array(
				'class' => 'add',
				'target' => 'selectedTodo',
				'rel' => 'ebay_product_statistic',
				'postType' => 'string',
				'callback' => 'navTabAjaxDone',
				'title' => Yii::t('lazada_product_statistic', 'Are You Sure to Publish?'),
				'onclick' => 'beforeSubmit(this)',
				'attrurl' => '/ebay/ebayproductstatistic/batchpublish/account_id/'.$accountID.'/site_id/'.$siteID,
			),
		),

		array(
			'text' => Yii::t ( 'system', '批量导入' ),
			'url'  => '/ebay/ebayproductstatistic/import/listing_duration/'.$duration,
			'htmlOptions' => array (
					'class' => 'add',
					'target' => 'dialog',
					'rel' => 'ebay_product_statistic-grid',
					'postType' => '',
					'callback' => '',
					'height' => '480',
					'width' => '650'
			)
		),		

		array(
			'text' => Yii::t ( 'system', '海外仓批量导入' ),
			'url'  => '/ebay/ebayproductstatistic/overseaimport/listing_duration/'.$duration,
			'htmlOptions' => array (
					'class' => 'add',
					'target' => 'dialog',
					'rel' => 'ebay_product_statistic',
					'postType' => '',
					'callback' => '',
					'height' => '480',
					'width' => '650'
			)
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

function beforeSubmit(obj){
	var duration = $("#search_listing_duration").val();
	var listtype = $("#search_listing_type").val();
	var auctionStatus = $("#search_auction_status").val();
	var auctionPlanDay = $("#search_auction_plan_day").val();
	var auctionRule = $("#search_auction_rule").val();
	var configType = $("#search_config_type").val();
	var url = $(obj).attr('attrurl');
	url += "/listing_duration/"+duration+"/listing_type/"+listtype+"/auction_status/"+auctionStatus+"/auction_plan_day/"+auctionPlanDay+"/auction_rule/"+auctionRule+"/config_type/"+configType;
	$(obj).attr('href', url);
	return true;
}
/**
 * 获取对应账号列表
 */
function getAccountList(obj){
	var site = $(obj).val();
	var html = '<option value="">所有</option>';
	if(site == ''){
		$("#search_account_id").html(html);
		return;
	}
	$.ajax({
		type: "GET",
		url: "/ebay/ebayaccount/getaccountbysite",
		data: "site_id="+site,
		dataType:'json',
		success: function(result){
			if(result.statusCode == 200){
				//组装数据
				$.each(result.data, function(i, n){
					html += '<option value="'+i+'">'+n+'</option>';
				});
				$("#search_account_id").html(html);
			}else{
				alert(result.message);
				$("#search_account_id").html(html);
			}
		}
	});
}

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


function getListingTypeOption(obj){
	var listingType = $(obj).val();
	var auditonDuration = <?php echo json_encode($auctionListingDurations);?>;
	var fixpriceDuration = <?php echo json_encode($fixpriceListingDurations);?>;
	var options = "";
	if(listingType == 1){
		$.each(auditonDuration, function(i, n){
			options += "<option value='"+i+"'>"+n+"</option>";
		});
	}else{
		$.each(fixpriceDuration, function(i, n){
			options += "<option value='"+i+"'>"+n+"</option>";
		});
	}
	$("#search_listing_duration").html(options);
}
</script>