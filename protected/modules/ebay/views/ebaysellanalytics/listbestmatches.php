<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'ebaysellanalytics-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
	'toolBar' => array(
	),
	'columns' => array(
			array(
					'name' => 'num',
					'value' => '$row+1',
					'htmlOptions' => array('style' => 'width:30px;'),
			),
			array(
					'name' => 'account_id',
					'value' => '$data->account_id',
					'htmlOptions' => array('style' => 'width:50px;'),
			),
			// array(
			// 		'name' => 'best_match_id',
			// 		'value' => '$data->best_match_id',
			// 		'htmlOptions' => array('style' => 'width:100px;'),
			// ),
			array(
					'name' => 'item_id',
					'value' => '$data->item_id_link',
					'type' => 'raw',
					'htmlOptions' => array('style' => 'width:100px;'),
					//'headerHtmlOptions' => array('align' => 'center'),
			),						
			array(
					'name' => 'meta_categ_name',
					'value' => '$data->meta_categ_name',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'categ_lvl2_name',
					'value' => '$data->categ_lvl2_name',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'categ_lvl3_name',
					'value' => '$data->categ_lvl3_name',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'leaf_categ_name',
					'value' => '$data->leaf_categ_name',
					'htmlOptions' => array('style' => 'width:150px;'),
			),												
			array(
					'name' => 'watch_count',
					'value' => $data->watch_count,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'price',
					'value' => $data->price,
					'htmlOptions' => array('style' => 'width:150px;'),
			),

			array(
					'name' => 'currency',
					'value' => $data->currency,
					'htmlOptions' => array('style' => 'width:150px;'),
			),			
			array(
					'name' => 'item_site_id',
					'value' => $data->item_site_id,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'srp_cnt',
					'value' => $data->srp_cnt,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'top3_cnt',
					'value' => $data->top3_cnt,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'top10_cnt',
					'value' => $data->top10_cnt,
					'htmlOptions' => array('style' => 'width:150px;'),
			),	
			array(
					'name' => 'top_keyword1',
					'value' => $data->top_keyword1,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'top_keyword_traffic1',
					'value' => $data->top_keyword_traffic1,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'top_keyword2',
					'value' => $data->top_keyword_traffic2,
					'htmlOptions' => array('style' => 'width:150px;'),
			),	
			array(
					'name' => 'top_keyword_traffic2',
					'value' => $data->top_keyword_traffic2,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'top_keyword3',
					'value' => $data->top_keyword_traffic3,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'top_keyword_traffic3',
					'value' => $data->top_keyword_traffic3,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'vi_nonbot_hits',
					'value' => $data->vi_nonbot_hits,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'vi_srp',
					'value' => $data->vi_srp,
					'htmlOptions' => array('style' => 'width:150px;'),
			),	
			array(
					'name' => 'vi_direct_click_ebay',
					'value' => $data->vi_direct_click_ebay,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'vi_other_source',
					'value' => $data->vi_other_source,
					'htmlOptions' => array('style' => 'width:150px;'),
			),	
			array(
					'name' => 'vi_pc',
					'value' => $data->vi_pc,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'vi_portable',
					'value' => $data->vi_portable,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'ck_nonbot_hits',
					'value' => $data->ck_nonbot_hits,
					'htmlOptions' => array('style' => 'width:150px;'),
			),	
			array(
					'name' => 'ck_srp',
					'value' => $data->ck_srp,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'ck_direct_click_ebay',
					'value' => $data->ck_direct_click_ebay,
					'htmlOptions' => array('style' => 'width:150px;'),
			),	
			array(
					'name' => 'ck_other_source',
					'value' => $data->ck_other_source,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'ck_pc',
					'value' => $data->ck_pc,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'ck_portable',
					'value' => $data->ck_portable,
					'htmlOptions' => array('style' => 'width:150px;'),
			),	
			array(
					'name' => 'cal_date',
					'value' => $data->cal_date,
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'update_time',
					'value' => $data->update_time,
					'htmlOptions' => array('style' => 'width:150px;'),
			),														
	),
	'tableOptions' => array(
		'layoutH' => 90,
	),
	'pager' => array(),
));
?>


<script type="text/javascript">

/**
 * 获取产品在线品类
 */
function getEbayCategoryList(obj){
	var siteId = $(obj).val();
	var html = '<option value="">所有</option>';
	var html2 = '<option value="">所有</option>';
	if(siteId == ''){
		$("#search_meta_categ_id").html(html);
		$("#search_categ_lvl2_id").html(html2);
		return;
	}
	$.ajax({
		type: "GET",
		url: "/ebay/ebaysellanalytics/getebaycategorybysite",
		data: "site_id="+siteId,
		dataType:'json',
		success: function(result){
			if(result.statusCode == 200){
				//一级品类
				$.each(result.data.catLevel1, function(i, n){
					html += '<option value="'+n['category_id']+'">'+n['category_name']+'</option>';
				});
				$("#search_meta_categ_id").html(html);
				//二级品类
				$.each(result.data.catLevel2, function(i, n){
					html2 += '<option value="'+n['category_id']+'">'+n['category_name']+'</option>';
				});
				$("#search_categ_lvl2_id").html(html2);
			}else{
				alert(result.message);
				$("#search_meta_categ_id").html(html);
				$("#search_categ_lvl2_id").html(html2);
			}
		}
	});
}

</script>