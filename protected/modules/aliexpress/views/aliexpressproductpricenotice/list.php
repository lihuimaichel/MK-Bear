<?php

Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$settingParams = '
                    array(
                        array(
                            "url" => "/aliexpress/aliexpressproductpricenotice/batchchangeprice/ids/$data->id",
                            "title" => "标记处理",
                            "params" => array(
                                 
                            ),
                            "style" => "w",
                            "target" => " target=\'dialog\'",
                        )    
                    )
                 ';
$row = 0;
$this->widget('UGridView', array(
	'id' => 'AliexpressCostChangeNotice-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'toolBar' => array(	
	    array(
	        'text' => '批量标记',
	        'url' => 'javascript:void(0)',
	        'htmlOptions' => array(
	            'class' => 'add',
	            'onclick' => 'batchChangePrice()',
	        ),
	    ),
	),
	'columns' => array(
			array(
				'class' => 'CCheckBoxColumn',
				'selectableRows' =>2,
				'value'=> '$data->id',
			),
			array(
					'name' => 'sku',
					'value' => '$data->sku',
    			    'headerHtmlOptions' => array(
    			        'align'   => 'center',
    			    ),
					'htmlOptions' => array(
					    'style' => 'width:66px;',
					    'align' => 'center'
					),
			),

			array(
					'name' => 'online_sku',
					'value' => '$data->online_sku',
    			    'headerHtmlOptions' => array(
    			        'align'   => 'center',
    			    ),			    
					'htmlOptions' => array(
					    'style' => 'width:66px;',
					    'align' => 'center'
					),
			),
			array(
					'name' => 'aliexpress_product_id',
					'value' => '$data->aliexpress_product_id',
    			    'headerHtmlOptions' => array(
    			        'align'   => 'center',
    			    ),			    
					'htmlOptions' => array(
					    'style' => 'width:118px;',
					    'align' => 'center'
					),
			),
    	    array(
        	        'name' => 'account_id',
        	        'value' => '$data->account_id',
        	        'headerHtmlOptions' => array(
        	            'align'   => 'center',
        	        ),    	        
        	        'htmlOptions' => array(
        	            'style' => 'width:66px;',
        	            'align' => 'center'
        	        ),
    	    ),
    	    array(
        	        'name' => 'seller_name',
        	        'value' => '$data->seller_name',
        	        'headerHtmlOptions' => array(
        	            'align'   => 'center',
        	        ),    	        
        	        'htmlOptions' => array(
        	            'style' => 'width:66px;',
        	            'align' => 'center'
        	        ),
    	    ),
    	    array(
        	        'name' => 'avg_price',
        	        'value' => 'AliexpressProductPriceAutoNotice::commonHtml("avg_price",$data)',
        	        'headerHtmlOptions' => array(
        	            'align'   => 'center',
        	        ),    	        
        	        'htmlOptions' => array(
        	            'style' => 'width:118px;',
        	            'align' => 'center'
        	        ),
    	    ),
    	    array(
        	        'name' => 'standard_price',
        	        'value' => '$data->standard_price',
        	        'headerHtmlOptions' => array(
        	            'align'   => 'center',
        	        ),    	        
        	        'htmlOptions' => array(
        	            'style' => 'width:66px;',
        	            'align' => 'center'
        	        ),
    	    ),
    	    array(
        	        'name' => 'standard_profit_rate',
        	        'value' => '$data->standard_profit_rate',
        	        'headerHtmlOptions' => array(
        	            'align'   => 'center',
        	        ),    	        
        	        'htmlOptions' => array(
        	            'style' => 'width:66px;',
        	            'align' => 'center'
        	        ),
    	    ),
    	    array(
        	        'name' => 'profit_rate',
        	        'value' => 'AliexpressProductPriceAutoNotice::profitRate($data)',
        	        'headerHtmlOptions' => array(
        	            'align'   => 'center',
        	        ),    	        
        	        'htmlOptions' => array(
        	            'style' => 'width:188px;',
        	            'align' => 'center'
        	        ),
    	    ),
    	    array(
        	        'name' => 'status',
        	        'value' => '$data->status',
        	        'headerHtmlOptions' => array(
        	            'align'   => 'center',
        	        ),    	        
        	        'htmlOptions' => array(
        	            'style' => 'width:66px;',
        	            'align' => 'center'
        	        ),
    	    ),
    	    array(
        	        'name' => 'log_date',
        	        'value' => '$data->log_date',
        	        'headerHtmlOptions' => array(
        	            'align'   => 'center',
        	        ),    	        
        	        'htmlOptions' => array(
        	            'style' => 'width:99px;',
        	            'align' => 'center'
        	        ),
    	    ),
    	    array(
        	        'name' => 'change_time',
        	        'value' => '$data->change_time',
        	        'headerHtmlOptions' => array(
        	            'align'   => 'center',
        	        ),    	        
        	        'htmlOptions' => array(
        	            'style' => 'width:99px;',
        	            'align' => 'center'
        	        ),
    	    ),
    	    array(
        	        'name' => 'change_user_id',
        	        'value' => '$data->change_user_id',
        	        'headerHtmlOptions' => array(
        	            'align'   => 'center',
        	        ),    	        
        	        'htmlOptions' => array(
        	            'style' => 'width:99px;',
        	            'align' => 'center'
        	        ),
    	    ),
    	    array(
        	        'name' => 'change_now_price',
        	        'value' => '$data->change_now_price',
        	        'headerHtmlOptions' => array(
        	            'align'   => 'center',
        	        ),    	        
        	        'htmlOptions' => array(
        	            'style' => 'width:99px;',
        	            'align' => 'center'
        	        ),
    	    ),	    
    	    array(
        	        'name' => 'setting',
        	        'value' => 'AliexpressAccountBindCategory::model()->mergeUrl('.$settingParams.')',
        	        'htmlOptions' => array('style' => 'width:150px;'),
        	        'headerHtmlOptions' => array(
        	            'align'   => 'center',
        	        ),
    	    ),
		),
	'tableOptions' 	=> array(
		'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
));
?>
<script>
	function displayCategoryBindDetail(accountId){
		$.ajax({
			type: 'post',
			url: '/aliexpress/aliexpress_account_bind_category/get_all_bind_category',
			data:{
				accountID:accountId
			},
			success:function(result){
				//alert(result);
				//console.log(result);
				alertMsg.confirm(result);
			},			
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				 //alert(XMLHttpRequest.status);
				console.log(XMLHttpRequest.status);
				 if (XMLHttpRequest.status == 500){
					 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
				 } else if (XMLHttpRequest.status == 404){
					 alertMsg.error('请求的页面不存在，404错误！');
				 } else if (XMLHttpRequest.status == 200){
					 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
				 }
			},		
			dataType:'html'
		});	
	}
	
	$(function(){
		$('.profit_rate_five').parent().hide();
		$('.profit_rate_ten').parent().hide(); 
		$('.profit_rate_fifteen').parent().hide();
		$('.profit_rate_twenty').parent().hide();
		$('.profit_rate_twenty_five').parent().hide();
		$('.profit_rate_fifty').parent().hide();
		$('.profit_rate_select').val().length > 0 && $('.' + $('.profit_rate_select').val()).parent().show();
		
		$('.sku_id').parent().css('width','168px');
		$('.online_sku_id').parent().css('width','168px');
		$('.account_id_id').parent().css('width','168px');
		$('.status_id').parent().css('width','168px');
		$('.profit_rate_five').parent().css('width','168px');
		$('.profit_rate_ten').parent().css('width','168px');
		$('.profit_rate_fifteen').parent().css('width','168px');
		$('.profit_rate_twenty').parent().css('width','168px');
		$('.profit_rate_twenty_five').parent().css('width','168px');
		$('.profit_rate_fifty').parent().css('width','168px');		
		$('.profit_rate_select').parent().css('width','128px');
		$('.log_date_id').parent().css('width','228px');
		$('.profit_rate_select').change(function(){
			$(this).attr('hasSelected','true');
			if ($(this).val() == 'profit_rate_five') {
				$('.profit_rate_five').parent().show();
				$('.profit_rate_ten').parent().hide();
				$('.profit_rate_fifteen').parent().hide();
				$('.profit_rate_twenty').parent().hide();
				$('.profit_rate_twenty_five').parent().hide();
				$('.profit_rate_fifty').parent().hide();
				$('.profit_rate_ten').val('');
				$('.profit_rate_fifteen').val('');
				$('.profit_rate_twenty').val('');
				$('.profit_rate_twenty_five').val('');
				$('.profit_rate_fifty').val('');
			}
			else if ($(this).val() == 'profit_rate_ten') {
				$('.profit_rate_ten').parent().show();
				$('.profit_rate_five').parent().hide();
				$('.profit_rate_fifteen').parent().hide();
				$('.profit_rate_twenty').parent().hide();
				$('.profit_rate_twenty_five').parent().hide();
				$('.profit_rate_fifty').parent().hide();
				$('.profit_rate_five').val('');
				$('.profit_rate_fifteen').val('');
				$('.profit_rate_twenty').val('');
				$('.profit_rate_twenty_five').val('');
				$('.profit_rate_fifty').val('');
			}
			else if ($(this).val() == 'profit_rate_fifteen') {
				$('.profit_rate_fifteen').parent().show();
				$('.profit_rate_five').parent().hide();
				$('.profit_rate_ten').parent().hide();
				$('.profit_rate_twenty').parent().hide();
				$('.profit_rate_twenty_five').parent().hide();
				$('.profit_rate_fifty').parent().hide();
				$('.profit_rate_five').val('');
				$('.profit_rate_ten').val('');
				$('.profit_rate_twenty').val('');
				$('.profit_rate_twenty_five').val('');
				$('.profit_rate_fifty').val('');
			}
			else if ($(this).val() == 'profit_rate_twenty') {
				$('.profit_rate_twenty').parent().show();
				$('.profit_rate_five').parent().hide();
				$('.profit_rate_ten').parent().hide();
				$('.profit_rate_fifteen').parent().hide();
				$('.profit_rate_twenty_five').parent().hide();
				$('.profit_rate_fifty').parent().hide();
				$('.profit_rate_five').val('');
				$('.profit_rate_ten').val('');
				$('.profit_rate_fifteen').val('');
				$('.profit_rate_twenty_five').val('');
				$('.profit_rate_fifty').val('');
			}
			else if ($(this).val() == 'profit_rate_twenty_five') {
				$('.profit_rate_twenty_five').parent().show();
				$('.profit_rate_five').parent().hide();
				$('.profit_rate_ten').parent().hide();
				$('.profit_rate_fifteen').parent().hide();
				$('.profit_rate_twenty').parent().hide();
				$('.profit_rate_fifty').parent().hide();
				$('.profit_rate_five').val('');
				$('.profit_rate_ten').val('');
				$('.profit_rate_fifteen').val('');
				$('.profit_rate_twenty').val('');
				$('.profit_rate_fifty').val('');
			}
			else if ($(this).val() == 'profit_rate_fifty') {
				$('.profit_rate_fifty').parent().show();
				$('.profit_rate_five').parent().hide();
				$('.profit_rate_ten').parent().hide();
				$('.profit_rate_fifteen').parent().hide();
				$('.profit_rate_twenty').parent().hide();
				$('.profit_rate_twenty_five').parent().hide();
				$('.profit_rate_five').val('');
				$('.profit_rate_ten').val('');
				$('.profit_rate_fifteen').val('');
				$('.profit_rate_twenty').val('');
				$('.profit_rate_twenty_five').val('');
			} else {
				$('.profit_rate_fifty').parent().hide();
				$('.profit_rate_five').parent().hide();
				$('.profit_rate_ten').parent().hide();
				$('.profit_rate_fifteen').parent().hide();
				$('.profit_rate_twenty').parent().hide();
				$('.profit_rate_twenty_five').parent().hide();
			}
		});
	});

	function batchDeleteCheck($this){
		return true;
	}
	
	function batchChangePrice(){
		var ids = '';
		var hrefBak = $('.batchChangePriceHref').attr('hrefBak');
		$("input[name^='AliexpressCostChangeNotice-grid']").each(function(){
			if ($(this).is(":checked")){
				ids += $(this).val() + ',';
			}
		});
		if (ids != ''){
			console.log(ids);
			ids = ids.substr(0,ids.length - 1);
			$('.batchChangePriceHref').attr('href',hrefBak + ids);
			$('.batchChangePriceHref').click();
		} else {
			alertMsg.error('请选择信息！');
		}
	}
</script>
<div style="position:absolute; top:18%; left:8%; width:68%; height:58%; background:#f6f6f6; z-index:666666; display:none; padding:28px; font-size:18px; line-height:28px;" 
     id="errorBox" 
     onClick="$(this).html(''); $(this).fadeOut(666);"
><div>
<a 
    class="batchChangePriceHref" 
    href="" 
    hrefBak="/aliexpress/aliexpressproductpricenotice/batchchangeprice/ids/" 
    target="dialog" 
    id="ajun_666_0" 
    rel="Ajun6660" 
    style="display: none
">dialog</a>

    













