<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'=>'wish_listing_widget',
	'filter'=>$model,
	'dataProvider'=>$model->search(),
	'selectableRows' => 2,
	'columns'=>array(
			array(
					'class'=>'CCheckBoxColumn',
					'value'=>'$data->id',
					'selectableRows' => 2,
					'htmlOptions'=>array(
						'style'=>'width:20px',
						'align'=>'center',
					),
					'headerHtmlOptions'=>array(
							'align'=>'center',
							'onclick'=>'allSelectWish(this)',
					),
					'checkBoxHtmlOptions'=>array(
						'onchange'=>'checkSelect(this)',
						//'onclick'=>'checkSelect(this)',
						'oninput'=>'checkSelect(this)',
						'onpropertychange'=>'checkSelect(this)'
					)
			),
			array(
					'name'=>'seller_name',
					'value'=>array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:55px',
							'align'=>'center'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),			
			array(
					'name'=>'account_name',
					'value'=>'$data->account_name',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:60px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			array(
					'name'=>'sku',
					'value'=>'CHtml::link($data->sku,"/products/product/viewskuattribute/sku/".$data->sku,
    				array("title"=>$data->sku,"style"=>"color:blue","target"=>"dialog","width"=>1100,"mask"=>true,"height"=>600))',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:55px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			
			array(
					'name'=>'main_image',
					'value'=>'$data->main_image_url',
					'type'=>'raw',
					'htmlOptions'=>array(
                        'style'=>'width:80px',
                        'onmouseover'=> 'showLarge(this)',
                        'onmouseout'=>  'hideLarge(this)',
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
				
			array(
					'name'=>'name',
					//'value'=>'$data->name',
                     'value'=>'CHtml::link($data->name,Yii::app()->request->baseUrl."/wish/wishlisting/edit/id/".$data->id,
    				array("title"=>$data->sku,"style"=>"color:blue","target"=>"navTab"))',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:120px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			array(
					'name'=>'product_id',
					'value'=>'$data->product_link',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:200px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			
			array(
					'name'=>'review_status_text',
					'value'=>'$data->review_status_text',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:50px'
					),
			),
			
			array(
					'name'=>'promoted_text',
					'value'=>'$data->promoted_text',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:50px'
					),
			),
			
			array(
					'name'=>'num_sold',
					'value'=>'$data->num_sold_total',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:35px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'num_saves',
					'value'=>'$data->num_saves_total',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:35px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),

			array(
					'name'=>'send_warehouse',
					'value'=>'$data->send_warehouse',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:50px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),			
			
			array(
					'name'=>'date_uploaded',
					'value'=>'$data->date_uploaded',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:80px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),

			array(
					'name'=>'parent_sku',
					'value'=>'$data->parent_sku',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:55px;word-wrap:break-word;word-break:break-all;'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			array(
					'name'=>'variants_id',
					'value'=> array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
						'name'=>'wish_varants_ids',
						'style'=>'width:20px',
						'type'=>'checkbox',
						'click_event'=>'checkSubSelect(this)',
						'disabled'=>'return $v["enabled"];'
					),
					
			),
			
			array(
					'name'=>'online_sku',
					'value'=>array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
						'style'=>'width:150px;word-wrap:break-word;word-break:break-all;',
						'align'=>'center'
					),
					'headerHtmlOptions'=>array(
						'align'=>'center'
					),
			),
			array(
					'name'=>'subsku',
					'value'=>array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
						'style'=>'width:55px',
						'align'=>'center'
					)
			),
			array(
					'name'=>'sale_property',
					'value'=>array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:80px',
							'align'=>'center'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'inventory',
					'value'=>array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:50px',
							'align'=>'center'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'price',
					'value'=>array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:50px',
							'align'=>'center'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'shipping',
					'value'=>array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:40px',
							'align'=>'center'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
//			array(
//					'name'=>'msrp',
//					'value'=>array($this, 'renderGridCell'),
//					'type'=>'raw',
//					'htmlOptions'=>array(
//							'style'=>'width:100px',
//							'align'=>'center'
//					),
//					'headerHtmlOptions'=>array(
//							'align'=>'center'
//					),
//			),
			array(
					'name'=>'staus_text',
					'value'=>array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:55px',
							'align'=>'center'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),			
			array(
					'name'=>'oprator',
					'value'=>array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:80px',
							'align'=>'center'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			)
	),
	'toolBar'=>array(
			array(
					'text' => Yii::t('wish_listing', 'Batch Offline'),
					'url' => 'javascript:void(0)',
					'htmlOptions' => array(
							'class' => 'delete',
							'onclick' => 'batchOffline()',
					),
			),
			array(
					'text' => Yii::t('amazon_product', 'Import CSV offline'),
					'url' => Yii::app()->createUrl('/wish/wishlisting/importcsvoffline'),
					'htmlOptions' 	=> array(
							'class' 	=> 'add',
							'target' 	=> 'dialog',
							'mask'		=>true,
							'rel' 		=> 'wish_listing_widget',
							'width' 	=> '900',
							'height' 	=> '600',
							'onclick' 	=> '',
					)
			),
			
			array(
					'text' => Yii::t('wish_listing', 'Create Encry Sku'),
					'url' => Yii::app()->createUrl('/wish/wishlisting/createsku'),
					'htmlOptions' 	=> array(
							'class' 	=> 'add',
							'target' 	=> 'dialog',
							'mask'		=>true,
							'rel' 		=> 'wish_listing_widget',
							'width' 	=> '400',
							'height' 	=> '260',
							'onclick' 	=> '',
					)
			),
			
			array(
					'text' => '复制刊登',
					'url' => 'javascript:void(0)',
					'htmlOptions' => array (
							'class' => 'add',
							'rel' => 'wish_listing_widget',
							'onclick' => 'copylisting()'
					)
			),

			array(
					'text' => Yii::t('system', 'Batch delete messages'),
					'url' => Yii::app()->createUrl('/wish/wishlisting/batchdelete'),
					'htmlOptions' 	=> array(
							'class' 	=> 'delete',
							'title'		=>	Yii::t('system', 'Really want to delete these records?'),
							'target' 	=> 'selectedTodo',
							'mask'		=>true,
							'rel' 		=> 'wish_listing_widget',
							'width' 	=> '',
							'height' 	=> '',
							'onclick' 	=> '',
							'postType' => 'string',
							'warn' => Yii::t ( 'system', 'Please Select' ),
							'callback' => 'navTabAjaxDone'
					)
			),
	),
	'pager'=>array(
		
	),
	'tableOptions'=>array(
		'layoutH'	=>	150,
		'tableFormOptions'	=>	true
	)
		
));
?>


<script type="text/javascript">

$('#subsku').parent().css({"width":"260px"});
$('#online_sku').parent().css({"width":"260px"});
$('#parent_sku').parent().css({"width":"260px"});
$('#product_id').parent().css({"width":"260px"});
$('#date_uploaded_0').parent().css({"width":"320px"});

function allSelectWish(obj){
	var chcked = !!$(obj).find("input").attr("checked");
	$("input[name='wish_varants_ids[]']").not(":disabled").each(function(){
		this.checked = chcked;
	});
}
function checkSelect(obj) {
	console.log(obj);
	if (!!$(obj).attr('checked')) {
		$(obj).parents('tr').find("input[name='wish_varants_ids[]']").not(":disabled").each(function(){
			this.checked = true;
		});
	} else {
		$(obj).parents('tr').find("input[name='wish_varants_ids[]']").not(":disabled").each(function(){
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

/**
 * 下线
 */
function offLine(obj, id){
	var confirmMsg = '', url = '', t;
	t = $(obj).val();
	if(t == 'online'){
		url = '<?php echo Yii::app()->createUrl('wish/wishlisting/online/')?>';
		confirmMsg = '<?php echo Yii::t('amazon_product', 'Really want to onSelling these product?');?>';
	}else if(t == 'offline') {
        url = "<?php echo Yii::app()->createUrl('wish/wishlisting/offline');?>";
        confirmMsg = '<?php echo Yii::t('system', 'Really want to offline these product?');?>';
    }else{
		return false;
	}
	if(confirm(confirmMsg)){
		var param = {id:id};
		$.post(url, param, function(data){
				if(data.statusCode == '200'){
					alertMsg.correct(data.message, data);	
				} else {
					alertMsg.error(data.message, data);
				}
		},'json');
	}
	return false;
}

/**
 * 批量下线
 */
function batchOffline(t){
	var noChkedMsg = confirmMsg = '', url = '';
	if(t == 'on'){
		noChkedMsg = "<?php echo Yii::t('amazon_product', 'Not Specify Sku Which Need To Active');?>";
		url = '<?php echo Yii::app()->createUrl('/wish/wishlisting/batchonselling/')?>';
		confirmMsg = '<?php echo Yii::t('amazon_product', 'Really want to onSelling these product?');?>';
	}else{
		noChkedMsg = "<?php echo Yii::t('amazon_product', 'Not Specify Sku Which Need To Inactive');?>";
		url = '<?php echo Yii::app()->createUrl('/wish/wishlisting/batchoffline/')?>';
		confirmMsg = '<?php echo Yii::t('system', 'Really want to offline these product?');?>';
	}
	//检测
	var chkednum = 1*$("input[name='wish_varants_ids\[\]']:checked").length;
	if(chkednum<=0 || chkednum==undefined){
		alertMsg.error(noChkedMsg);
		return false;
	}
	/*进行确认操作*/
	if(confirm(confirmMsg)){
		postData = $("input[name='wish_varants_ids[]']:checked").serialize();
		$.post(url, postData, function(data){
			if (data.statusCode == '200') {
				alertMsg.correct(data.message);				
			} else {
				alertMsg.error(data.message);
			}
		}, 'json');
	}
}

//复制刊登
function copylisting(){
	var ids = "";
    var arrChk= $("input[name='wish_listing_widget_c0[]']:checked");
    if(arrChk.length==0){
        alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
        return false;
    }
    for (var i=0;i<arrChk.length;i++)
    {
        ids += arrChk[i].value+',';
    }
    ids = ids.substring(0,ids.lastIndexOf(','));
    var url ='/wish/wishlisting/copytoproductaddlist/ids/'+ids;
	$.pdialog.open(url, 'copylisting', '复制刊登', {width:980, height:400});
    return false;
}

//显示大图
    function showLarge(obj)
    {

        var height = '450px';
        var width = '450px';


        var parentHeight = $(obj).parent().parent().height();

        var top = $(obj).parent().parent().position().top;
       // $(obj).parent('tr').css('position', 'relative').css('overflow', 'initial');
        console.log(top);
        console.log(parentHeight+top);
        var imgObj = $(obj).find('img')[0];
        var child = $('<div>').css('position', 'absolute')
            .css('height', height)
            .css('width', width)
            .css('top', parentHeight- (parentHeight+top)+'px')
            .css('left', '320px')
            .addClass('large-image').append($('<img>').css('width', width ).css('height', height).attr('src', imgObj.src));
        $(obj).append(child);

    }
    function hideLarge(obj) {
        //return true;
        $(obj).find("div.large-image").each(function (){
            $(this).remove();
        });
    }

</script>

