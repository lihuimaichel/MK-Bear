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
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget('UGridView', array(
	'id' => 'lazada_product_list',
	'filter' => $model,
	'dataProvider' => $model->search(),
	'selectableRows' => 2,
	'columns' => array(
		array(
			'class' => 'CCheckBoxColumn',
			'value' => '$data->sku',
			'selectableRows' => 2,
			'htmlOptions' => array(
				'style' => 'width:20px;',
			),
			'headerHtmlOptions' => array(
				'align' => 'center',
				'style' => 'width:20px;',
				'onclick'=> 'allSelectLazada(this)'	
			),
			'checkBoxHtmlOptions' => array(
				'onchange' => 'checkSelect(this)',
				'onpropertychange' => 'checkSelect(this)',
				'oninput' => 'checkSelect(this)',
			),
		),

		array(
			'name' => Yii::t('system', 'id'),
			'value' => '$row+1',
			'headerHtmlOptions' => array(
				'align' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:20px',
			),
		),

		array(
			'name' => 'sku',
			'type' => 'raw',
			'value' => 'CHtml::link($data->sku,"/products/product/viewskuattribute/sku/".$data->sku,
    				array("title"=>$data->sku,"style"=>"color:blue","target"=>"dialog","width"=>1100,"mask"=>true,"height"=>600))',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:80px',
			),
		),

		array(
			'name' => 'create_time',
			'type' => 'raw',
			'value' => array($this, 'renderGridCell'),
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:100px;height:auto',
			),
		),

		array(
			'name' => 'listing_id',
			'type' => 'raw',
			'value' => array($this, 'renderGridCell'),
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:25px;height:auto',
				'type' => 'checkbox',
				'click_event'=>'checkSubSelect(this)'
			),
		),
		
		array(
			'name' => 'seller_sku',
			'type' => 'raw',
			'value' => array($this, 'renderGridCell'),
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:120px;height:auto;word-wrap:break-word;word-break:break-all;',
			),
		),

        array(
			'name' => 'shop_sku',
			'type' => 'raw',
			'value' => array($this, 'renderGridCell'),
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:220px;height:auto;word-wrap:break-word;word-break:break-all;',
			),
		),

		array(
			'name' => 'name',
			'type' => 'raw',
			'value' => array($this, 'renderGridCell'),
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:100px;height:auto',
			),
		),		

		array(
			'name' => 'site_id',
			'type' => 'raw',
			'value' => array($this, 'renderGridCell'),
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:30px;height:auto',
			),
		),

		array(
			'name' => 'account_name',
			'type' => 'raw',
			'value' => array($this, 'renderGridCell'),
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:100px;height:auto',
			),
		),

		array(
			'name' => 'quantity',
			'type' => 'raw',
			'value' => array($this, 'renderGridCell'),
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:50px;height:auto;text-align:center',
			),		
		),

		array(
			'name' => 'price',
			'type' => 'raw',
			'value' => array($this, 'renderGridCell'),
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:75px;height:auto',
			),
		),

		array(
			'name' => 'sale_price',
			'type' => 'raw',
			'value' => array($this, 'renderGridCell'),
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:75px;height:auto',
			),
		),

		array(
			'name' => 'sale_start_date',
			'type' => 'raw',
			'value' => array($this, 'renderGridCell'),
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:100px;height:auto;text-align:center',
			),
		),

		array(
			'name' => 'sale_end_date',
			'type' => 'raw',
			'value' => array($this, 'renderGridCell'),
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:100px;height:auto;text-align:center',
			),
		),

		array(
			'name' => 'primary_category',
			'type' => 'raw',
			'value' => array($this, 'renderGridCell'),
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:100px;height:auto;text-align:center;',
			),
		),

		array(
			'name' => 'profit_margin',
			'type' => 'raw',
			'value' => array($this, 'renderGridCell'),
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:100px;height:auto;text-align:center;',
			),
		),

		array(
			'name' => 'status',
			'type' => 'raw',
			'value' => array($this, 'renderGridCell'),
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:55px;height:auto;text-align:center',
			),
		),

		array(
			'name' => 'offline',
			'type' => 'raw',
			'value' => array($this, 'renderGridCell'),
			'headerHtmlOptions' => array(
					'class' => 'center',
			),
			'htmlOptions' => array(
					'style' => 'width:100px;height:auto',
			),
		),
			
		array(
				'name' => 'seller_name',
				'type' => 'raw',
				'value' => array($this, 'renderGridCell'),
				'headerHtmlOptions' => array(
						'class' => 'center',
				),
				'htmlOptions' => array(
						'style' => 'width:100px;height:auto',
				),
		)
	),
	'toolBar' => array(
		array(
			'text' => Yii::t('lazada_product', 'Batch Delete'),
			'url' => 'javascript:void(0)',
			'htmlOptions' => array(
					'class' => 'delete',
					'onclick' => 'batchDelete()',
				),
		),	
		array(
			'text' => Yii::t('lazada_product', 'Batch Offline'),
			'url' => 'javascript:void(0)',
			'htmlOptions' => array(
					'class' => 'delete',
					'onclick' => 'batchOffline()',
				),
		),
		array(
			'text' => Yii::t('lazada_product', 'Batch Offline Import'),
			'url' => '/lazada/lazadaproduct/offlineimport',
			'htmlOptions' => array(
				'class' 	=> 'add',
				'target' 	=> 'dialog',
				'mask'  	=> true,
				'width' 	=> '800',
				'height' 	=> '600',
				'onclick'   => false
			)
		),
		array (
			'text' => '复制刊登',
			'url' => 'javascript:void(0)',
			'htmlOptions' => array (
				'class' => 'add',
				'onclick' => 'copylisting()'
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
$(document).ready(function(){
	$("#account_id").parent('.h25').css({'width':'240px'});
	$("#category_level_one").parent('.h25').css({'width':'390px'});
});

function allSelectLazada(obj){
	var chcked = !!$(obj).find("input").attr("checked");
	$("input[name='listing_id[]']").not(":disabled").each(function(){
		this.checked = chcked;
	});
}
function checkSelect(obj) {
	if ($(obj).attr('checked')) {
		$(obj).parents('tr').find('input[name=listing_id\\[\\]]').each(function(){
			this.checked = true;
		});
	} else {
		$(obj).parents('tr').find('input[name=listing_id\\[\\]]').each(function(){
			this.checked = false;
		});
	}
}

function checkSubSelect(obj){
	var curstatus = !!$(obj).attr("checked");
	//查找当前级别下所有的checkbox的选中情况
	var l = $(obj).closest("table").find("tr input:checked").length;
	var parenObj = $(obj).closest("table").closest("tr").find("input:first");
	if(l>0){
		parenObj.attr("checked", true);
	}else{
		parenObj.attr("checked", false);
	}
}

//批量删除在线产品
function batchDelete() {
	if (confirm('<?php echo Yii::t('lazada_product', 'Are You Sure Delete These Products?');?>')) {
		postData = $('input[name=listing_id\\[\\]]:checked').serialize();
		if (postData == '') {
			alertMsg.error('<?php echo Yii::t('system', 'Please select a record');?>');
			return false;
		}
		var url = '<?php echo Yii::app()->createUrl('/lazada/lazadaproduct/batchdelete/')?>';
		$.ajax({
			'type': 'POST',
			'url': url,
			'dataType': 'json',
			'data': postData,
			'success': function(data) {
				if (data.statusCode != '200') {
					alertMsg.error(data.message);
				} else {
					alertMsg.correct(data.message);
				}
			},
		});
	}
	return false;
}

/**
 * 下线
 */
function offLine(obj,id){ 
	if(confirm('<?php echo Yii::t('system', 'Really want to offline these product?');?>')){
	 	var url='/lazada/lazadaproduct/updatelazadaproduct/';
	 	$.ajax({
			'type': 'POST',
			'url': url,
			'dataType': 'json',
			'data': {'id':id},
			'success': function(data) {
				if (data.statusCode != '200') {
					alertMsg.error(data.message);
				} else {
					alertMsg.correct(data.message);
				}
			},
		});
	}
	return false;
}

function batchOffline() {
	/*检测是否有选中的 */
	var chkednum = 1*$("input[name='listing_id[]']:checked").length;
	if(chkednum <= 0 || chkednum == undefined){
		alertMsg.error('<?php echo Yii::t('lazada_product', 'Not Specify Sku Which Need To Inactive');?>');
		return false;
	}
	/*进行确认操作*/
	if(confirm('<?php echo Yii::t('system', 'Really want to offline these product?');?>')){
		postData = $("input[name='listing_id[]']:checked").serialize();
		var url = '<?php echo Yii::app()->createUrl('/lazada/lazadaproduct/batchoffline/')?>';
		$.post(url, postData, function(data){
			if (data.statusCode == '200') {
				alertMsg.correct(data.message);				
			} else {
				alertMsg.error(data.message);
			}
		}, 'json');
	}
	return false;
}
/**
 * 上线
 */
function onLine(obj,id){ 
	if(confirm('<?php echo Yii::t('system', 'Really want to online these product?');?>')){
	 	var url='/lazada/lazadaproduct/onlinelazadaproduct/';
	 	$.ajax({
			'type': 'POST',
			'url': url,
			'dataType': 'json',
			'data': {'id':id},
			'success': function(data) {
				if (data.statusCode != '200') {
					alertMsg.error(data.message);
				} else {
					alertMsg.correct(data.message);
				}
			},
		});
	}
	return false;
}

//复制刊登
function copylisting(){
	var ids = "";
    var arrChk= $("input[name='listing_id[]']:checked");
    if(arrChk.length==0){
        alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
        return false;
    }
    for (var i=0;i<arrChk.length;i++)
    {
        ids += arrChk[i].value+',';
    }
    ids = ids.substring(0,ids.lastIndexOf(','));
    var url ='/lazada/lazadaproduct/copylisting/ids/'+ids;
	$.pdialog.open(url, 'copylisting', '复制刊登', {width:980, height:400});
    return false;
}
</script>