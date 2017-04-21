<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<table class="dataintable_inquire" width="100%" cellspacing="1" cellpadding="3" border="0" id="producttoaccountid">
	<thead>
    	<tr>
            <td colspan='3'>
           <a href="/products/producttosellerrelation/list/target/dialog/type/producttoseller/key/<?php echo $key;?>" id='add_keys' target="dialog"  mask='true' onclick='return shows(this);' width='700' height='650'>
            <button style="float:left;" type="button"><?php echo Yii::t('product', 'Select Product')?></button>
            </a>
            </td>
            <td colspan='4'  id='inquire_error' style="text-align:right;padding-right:10px;border-left:0;color:red;">
            <?php echo CHtml::hiddenField("sku_is_exist",'', array('id'=>'sku_is_exist', 'size' => 8))?>
            <?php echo CHtml::hiddenField("num_is_empty",'1', array('id'=>'num_is_empty', 'size' => 8))?>
            <span style="display:none;" id="Inquire_sku_inquire_em_" class="errorMessage"></span>
            </td>
        </tr>
	        <tr>
	  						<th>SKU</th>
				            <th width="100">公司分类</th>
				            <th width="100">产品状态</th>
				            <th width="60"><?php echo Yii::t('system', 'Operation')?></th>
	        </tr>
    </thead>
    <tbody>
    <?php $key = 0;?>
    	<?php echo $this->renderPartial('_addrequire', array()); ?>
    </tbody>
</table>
<script>
    var index = parseInt('<?php echo $key;?>');
    function skuExists(obj,key){
		$('#Inquire_sku_inquire_em_').hide();
		var curObj = $('#'+obj.id);
		var cursku = $.trim(curObj.val());
		if(cursku==''){
			$('#Inquire_sku_inquire_em_').html('<?php echo Yii::t('products','Please enter the sku');?>');
			$('#Inquire_sku_inquire_em_').show();
			$('#sku_is_exist').val('0');
			return false;
		}
		var isRepeat = false;
		var arrSku= $(".skuInquire");
		arrSku.each(function(){
			if(this.id != obj.id){
				if($(this).val() == cursku){
					isRepeat = true;
					return false;
				}
			}
		});
		if(isRepeat==true){
			$('#Inquire_sku_inquire_em_').html($.regional.products.msg.cannotAddAgain);
			$('#Inquire_sku_inquire_em_').show();
			return false;
		}
		$.ajax({
            type: "get",
            url: "/products/product/GetSku/sku/"+cursku+"/key/"+key,            
            dataType:'html',
            success: function(data) {
                if(data=='nodata'){
                	$('#Inquire_sku_inquire_em_').text('<?php echo Yii::t('products','You enter the sku does not exist');?>');
            		$('#title_'+key).html('');
            		$('#reality_num_'+key).val('');
            		$('#product_id_'+key).val('');
            		$('#Inquire_sku_inquire_em_').show();
            	}else{
                	var data = eval("("+data+")");
                	$('#product_id_'+key).val(data.id);
            		$('#title_'+key).html(data.title);
            		$('#Inquire_sku_inquire_em_').text('<?php echo Yii::t('products','Input Correct');?>');
            		$('#Inquire_sku_inquire_em_').hide();
            		
                }
            }
        });
	}
    var addSku = function(obj) {
		var parentObj = $(obj).parents('.dataintable_inquire').parent();
		var key = $(".dataintable_inquire tr:last").attr('id');
		if(typeof(key)==='undefined'){
			key=0;
		}else{
     		key = parseInt(key)+1;
		}
        $.ajax({
           type: "post",
           url: "/purchases/PurchaseRequire/addrequire/tctype/1",
           data: {index: key},
           async: false,   
           dataType:'html',
           success: function(data) {
              $(parentObj).find('tbody').append(data);
           }
       });
    }
    function checkReality(key){
        $('#Inquire_sku_inquire_em_').hide();
        if($.trim($('#sku_'+key).val())==''){
        	$('#Inquire_sku_inquire_em_').html('<?php echo Yii::t('products','Please enter the sku');?>');
			$('#Inquire_sku_inquire_em_').show();
			return false;
        }
        var reg = /^(([1-9]\d*)|\d)?$/;
        $('#num_is_empty').val('1');
        if( $.trim($('#reality_num_'+key).val())=='' || $.trim($('#reality_num_'+key).val())==0 || !reg.test($.trim($('#reality_num_'+key).val())) ){
        	$('#Inquire_sku_inquire_em_').html($.regional.purchase.msg.purchaseNumRequire);
			$('#Inquire_sku_inquire_em_').show();
			$('#num_is_empty').val('0');
			return false;
		}else{
			$('#num_is_empty').val('1');
		}
	}
	function shows(){
		var arrSku= $("#producttoaccountid .skuInquire");
		arrSku.each(function(){
			if($(this).val() == ''){
				$(this).parent().parent().remove();
			}
		});
		var key = $("#producttoaccountid .dataintable_inquire tr:last").attr('id');
		if(typeof(key)==='undefined'){
			key=-1;//因为从选择产品时会加1
		}
		var sellerId = $("#producttoaccountForm #ProductToAccountRelation_seller_user_id").val();
		var MarketersManager_emp_dept = $("#producttoaccountForm #ProductToAccountRelation_dept").val();
		if(sellerId == null){
// 			$("#producttoaccountid #add_keys").click(function() {
// 				alert('请先选择销售员');
// 				$("#producttosellerrelation-grid").parents('.dialog').find(".close").trigger('click');
// 			});
	
// 			return false;
		}else{
			$('#producttoaccountid #add_keys').attr('href','/products/producttosellerrelation/list/target/dialog/type/producttoseller/key/'+key+'/seller_id/'+sellerId+'/MarketersManager_emp_dept/'+MarketersManager_emp_dept);
			
		}
		//$("#producttoaccountid #add_keys").attr("target","dialog");
		}
</script>
