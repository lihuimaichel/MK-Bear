<div id="siderBarExcelSchemebox" class="siderBarExcelSchemebox">
    <div class="div-header" >
        <?php $listData = UebModel::model('excelSchemeType')->queryPairs('id,type_name');?>        
        <?php echo CHtml::label(Yii::t('system', 'Excel template type'), 'excel_template_type').'：'?>    
        <?php echo CHtml::dropDownList('type_name', '', $listData, array( 'onchange'=>'getExcelSchemeName(this);', 'empty' => Yii::t('system', 'Please Select'), 'style' => 'width:120px;'));?>
    </div> 
    <div style="text-align: right;">      
        <?php echo CHtml::link(Yii::t('system', 'Add a custom format'), 'javascript:void(0)', array( 'onclick' => "createExcelTemplate(this)", 'class' => 'h26 lh26 mr5'));?>
    </div> 
   <div id="typeNameVal"></div>

</div>
<script>
$(function(){   
// 	var $p = navTab.getCurrentPanel();
//          $('#type_name', $p).change(function(){
//         	var schemeType = $('#type_name'+' option:selected').val();

// 			if(schemeType==''){
// 				alertMsg.warn('请选择Excel模板类型！');
// 				return false;
// 			}
//         	$.ajax({
//                 type: "post",
//                 url: "/systems/excelscheme/getExcelSchemeName",
//                 data: {schemeType: schemeType},
//                 async: false,   
//                 dataType:'html',
//                 success: function(data) {
//                 	$('#typeNameVal').html(data);
//                 }
//             }); 
//          });

	
});
var $p = navTab.getCurrentPanel();
var  getExcelSchemeName = function(obj){
	var $p = navTab.getCurrentPanel();
	var schemeType = $(obj, $p).val();
	if(schemeType==''){
		alertMsg.warn($.regional.excel.msg.pleaseSelectExcelTemplate);
		return false;
	}
	$.ajax({
        type: "post",
        url: "/systems/excelscheme/getAllSchemeName",
        data: {schemeType: schemeType},
        async: false,   
        dataType:'html',
        success: function(data) {
        	$('#typeNameVal', $p).html(data);
        }
    }); 
}

    var deleteArr = function(obj){
    	var $p = navTab.getCurrentPanel();
		if(confirm($.regional.excel.msg.deleteExcelExportPattern)){
			var schemeType = $('#type_name'+' option:selected', $p).val();
			var schemeName = $(obj).parent().parent().find('span :first').html();
			$.ajax({
		           type: "post",
		           url: "/systems/excelscheme/deleteRelatedData",
		           data: {schemeType: schemeType,
		        	   	  schemeName: schemeName},
		           async: false,   
		           dataType:'html',
		           success: function(data) {
		              if ( data ) {                  
		            	  $(obj).parent().parent().parent().parent().remove();
		      			  alertMsg.correct($.regional.excel.msg.deleteSuccess);
		              }else{
		            	  alertMsg.error($.regional.excel.msg.deleteFail);
			          }
		           }
		     }); 
		}	
    }
    
    var $p = navTab.getCurrentPanel();
    var changeColor = function(){
        $('#leftListTable tr', $p).each(function(i) {     
            $(this).click(function(e) {                
                $('.tboxSelect', $p).attr( 'class', 'bg4')                
                $(this).attr('class', 'tboxSelect');              
            });           
        });
    }

    var createListBox = function(id){
    	var $p = navTab.getCurrentPanel();
        var index = id.substr(-1,1);
        $(".t216").hide();
        $("#saveBtn_"+index).show();
        
    	var schemeId = $('#inputSchemeId'+index, $p).val();
    	var schemeType = $('#type_name'+' option:selected', $p).val();
    	var is_report = $('#is_report_'+index, $p).val();
    	var main_model = $('#main_model_'+index, $p).val();

                var $box = $('#excelSchemeListBox', $p); 
                $box.ajaxUrl({
                   type: "post",
                   url: 'systems/excelscheme/list', 
                   data: {
                	      schemeId: schemeId,
                	      is_report: is_report,
                	      main_model: main_model,
                	      schemeType :schemeType}, 
                   callback: function() {
                       $box.find("[layoutH]").layoutH();
                   }
               });
    }
    
    var inputBlur = function(obj){
    	var $p = navTab.getCurrentPanel();
    	if($(obj).val()==''){
    		alertMsg.warn($.regional.excel.msg.patternNameCannotBeEmpty);
			return false;
		}else{
			var inputText = $(obj).val();
			$(obj).parent().prev().html(inputText);
			$(obj).remove();
		}
    }
   
   var spanClick = function(id){
	   var $p = navTab.getCurrentPanel();
       var spanText = $('#'+id, $p).html();
 	   $('#'+id, $p).next().html('<input type="text" value="'+spanText+'" onblur="inputBlur(this);">');
 	   $('#'+id, $p).next().find('input').focus();
 	   $('#'+id, $p).html('');
 	   var index = id.substr(-1,1);
 	   $('#radio'+index, $p).click();
   }

   var saveText = function(id){
	    var $p = navTab.getCurrentPanel();
	    var schemeType = $('#type_name'+' option:selected', $p).val();
		var index = id.substr(-1,1);
		var schemeName = $('#spanText'+index, $p).html();
		var schemeId = $('#inputSchemeId'+index, $p).val();
		if(schemeName==''){
			alertMsg.warn($.regional.excel.msg.patternNameCannotBeEmpty);
			return false;
		}
		
		var is_empty = false;
		$("input[name^='inputTitle']", $p).each(function(i){
			if($(this).val() == ''){
				is_empty = true;
			}
		});
		if(is_empty){
			alertMsg.warn($.regional.excel.msg.columnTitleCannotBeEmpty);
			return false;
		}
		
		if($("#is_report").attr("checked")=='checked' && $("#main_table").val()==""){
			alertMsg.warn($.regional.excel.msg.pleaseSelectMainModel);
			return false;
		}
		var is_datatype = false;
		$("input[id^='condition_']").each(function(k,v){
			if($(v).attr("checked")=='checked' && $("#data_type_"+$(v).attr('id').replace('data_type_','')).val()==""){
				is_datatype = true;
			}
		});

		var having_flag = false;
		$("td[id^='calculate_'] input").each(function(k,v){
			if($(v).attr("checked")=='checked' && $(v).val()=='<?php echo ExcelSchemeColumn::_IS_HAVING;?>'){
				having_flag = true;
			}
		});

		var group_flag = false;
		$("td[id^='calculate_'] input").each(function(k,v){
			if($(v).attr("checked")=='checked' && $(v).val()=='<?php echo ExcelSchemeColumn::_IS_GROUP;?>'){
				group_flag = true;
			}
		});
		
		if(having_flag == true && group_flag == false){
			alertMsg.warn($.regional.excel.msg.pleaseSelectAGroupBy);
			return false;
		}

		if(is_datatype){
			alertMsg.warn($.regional.excel.msg.pleaseSelectDataType);
			return false;
		}
		
		$('#schemeType', $p).val(schemeType);
        $('#schemeName', $p).val(schemeName);
        $('#schemeId', $p).val(schemeId);

                
		 $.ajax({
	           type: "post",
	           url: "/systems/excelscheme/saveSchemeName",
	           data: $("form[id='ColumnForm']").serializeArray(),
	           async: false,   
	           dataType:'html',
	           success: function(data) {
	              if ( data ) {
		              // refresh data
	            	  getExcelSchemeName($("select[name='type_name']"));               
	            	  alertMsg.correct($.regional.excel.msg.saveSuccess);
	              }else{
	            	  alertMsg.error($.regional.excel.msg.saveFail);
		          }
	           }
	     }); 
   }

</script>
