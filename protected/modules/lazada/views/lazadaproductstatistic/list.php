<script type="text/javascript">
function getAccountList(obj) {
	var siteID = $(obj).val();
	var url = 'lazada/lazadaaccount/getaccountlist/site_id/' + siteID;
	$.get(url, function(data) {
		var html = '<option value=""><?php echo Yii::t('system', 'All');?></option>' + "\n";
		html += data;
		$('#account_id').html(html);
	}, 'html')
}
</script>
<?php
Yii::app()->clientScript->scriptMap['jquery.js'] = false;
$this->widget('UGridView', array(
	'id' => 'lazada_product_statistic',
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
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:100px',
			),				
		),
		array(
			'name' => 'en_title',
			'value' =>  '!empty($data->en_title) ? $data->en_title : $data->cn_title',
			'headerHtmlOptions' => array(
				'class' => 'center','align' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:275px',
			),				
		),
		array(
			'name' => 'product_cost',
			'value' => '$data->product_cost',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:100px',
			),				
		),
// 		array(
// 				'name' => 'available_qty',
// 				'value' => '$data->available_qty',
// 				'headerHtmlOptions' => array(
// 						'class' => 'center',
// 				),
// 				'htmlOptions' => array(
// 						'style' => 'width:100px',
// 				),
// 		),
	),
	'toolBar' => array(
//		array(
//			'text' => Yii::t('lazada_product_statistic', 'Batch Publish'),
//			'url' => Yii::app()->createUrl('lazada/Lazadaproductstatistic/batchpublish/', array('site_id' => $siteID, 'account_id' => $accountID, 'online_category_id' =>$online_category_id)),
//			'htmlOptions' => array(
//				'class' => 'add',
//				'target' => 'selectedTodo',
//				'rel' => 'lazada_product_statistic',
//				'postType' => 'string',
//				'callback' => 'navTabAjaxDone',
//				'title' => Yii::t('lazada_product_statistic', 'Are You Sure to Publish?'),
//				'onclick' => '',
//			),
//		),
                array(
                        'text' => Yii::t('lazada_product_statistic', 'Batch Publish'),
                        'url' => 'javascript:void(0)',
                        'htmlOptions' => array(
                                'class' => 'add',
                                'onclick' => 'batchPublish()',
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
        var online_category_id = $("input[name='online_category_id']").val();
        if(online_category_id >0 ){
            $.ajax({
                    type:'post',
                    url:'lazada/lazadacategory/GetBreadcrumbCategory',
                    data:{category_id:online_category_id},
                    success:function(result){
                        $('#LazadaProductStatistic_online_category_id').parent().width(700);
                        $('#LazadaProductStatistic_online_category_id').after('<span style="width:510px;"  id="'+online_category_id+'" class="dbl mb5"><a  href="#">' +result+'</a></span>');
                    },
                  dataType:'json'
            });
        }
        
        //批量刊登
	function batchPublish(){
		//选择分类
		var online_category_id = $("input[name='online_category_id']").val();
		if(online_category_id == '' || online_category_id == 'undefined' || online_category_id == 0 ){
		//if(!online_category_id ){
			alertMsg.error('请选择分类');
			return false;
		}
		//检测站点和账号
		var site_id = '<?php echo $siteID;?>';
		var account_id = '<?php echo $accountID;?>';
		if(site_id<=0 || account_id<=0){
			alertMsg.error('请选择站点和账号');
                        return false;
		}
                
                //检查要刊登的sku
                var vid = new Array();
                $("input[name='lazada_product_statistic_c0\[\]']:checked").each(function(i, n){
                        vid[i] = $(n).val();
                });
                var vids = vid.join(',');
                if(vids == ''){
                    alertMsg.error('请选择sku');
                    return false;
                }
                
		if(confirm('确定要批量刊登吗?')){
			
			var url = '<?php echo Yii::app()->createUrl('lazada/Lazadaproductstatistic/batchpublish/')?>';
			var postData = {'site_id':site_id, 'account_id':account_id, 'online_category_id':online_category_id,ids:vids};
			$.post(url, postData, function(data){
				if (data.statusCode == '200') {
					alertMsg.correct(data.message);				
				} else {
					alertMsg.error(data.message);
				}
			}, 'json');
		}
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
</script>