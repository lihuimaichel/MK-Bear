<?php 
$baseUrl = Yii::app()->request->baseUrl;
Yii::app()->clientScript->registerCssFile($baseUrl.'/themes/default/style.css', 'screen');
Yii::app()->clientScript->registerCssFile($baseUrl.'/themes/css/core.css', 'screen');
Yii::app()->clientScript->registerCssFile($baseUrl.'/themes/css/mouseright.css', 'screen');
Yii::app()->clientScript->registerCssFile($baseUrl.'/themes/css/print.css', 'print'); 
Yii::app()->clientScript->registerCssFile($baseUrl.'/themes/css/pageform.css', 'screen');
Yii::app()->clientScript->registerCssFile($baseUrl.'/js/kindeditor-4.1.7/themes/default/default.css');

Yii::app()->clientScript->registerCssFile($baseUrl.'/js/colorbox/colorbox.css');
Yii::app()->clientScript->registerCssFile($baseUrl.'/js/pikachoose/base.css');
Yii::app()->clientScript->registerCssFile($baseUrl.'/js/pikachoose/left-without.css');

Yii::app()->clientScript->registerCoreScript('jquery');
Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/jquery.cookie.js', CClientScript::POS_HEAD);
Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/jquery.bgiframe.js',CClientScript::POS_HEAD);
Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/jquery.validate.js',CClientScript::POS_HEAD);

Yii::app()->clientScript->registerScriptFile($baseUrl . "/js/kindeditor-4.1.7/kindeditor.js", CClientScript::POS_HEAD);
Yii::app()->clientScript->registerScriptFile($baseUrl . "/js/kindeditor-4.1.7/lang/zh_CN.js", CClientScript::POS_HEAD);
Yii::app()->clientScript->registerScriptFile($baseUrl . "/js/colorbox/jquery.colorbox.js", CClientScript::POS_HEAD);
Yii::app()->clientScript->registerScriptFile($baseUrl . "/js/pikachoose/jquery.jcarousel.min.js", CClientScript::POS_HEAD);
Yii::app()->clientScript->registerScriptFile($baseUrl . "/js/pikachoose/jquery.pikachoose.min.js", CClientScript::POS_HEAD);
Yii::app()->clientScript->registerScriptFile($baseUrl . "/js/pikachoose/jquery.pikachoose.min.js", CClientScript::POS_HEAD);

//Yii::app()->clientScript->registerScriptFile($baseUrl . "/js/highcharts/js/highcharts.js", CClientScript::POS_HEAD);
//Yii::app()->clientScript->registerScriptFile($baseUrl . "/js/highcharts/js/modules/exporting.js", CClientScript::POS_HEAD);//图表导出
//Yii::app()->clientScript->registerScriptFile($baseUrl . "/js/highstock/js/highstock.js", CClientScript::POS_HEAD);
//Yii::app()->clientScript->registerScriptFile($baseUrl . "/js/highcharts/js/export-csv.js", CClientScript::POS_HEAD);


Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/jquery.yiilistview.js',CClientScript::POS_HEAD);

if ( Env::DEVELOPMENT == YII_ENV ) {
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.core.js', CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.util.date.js', CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.validate.method.js',CClientScript::POS_HEAD);
    
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.barDrag.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.drag.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.tree.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.accordion.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.ui.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.theme.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.switchEnv.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.alertMsg.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.contextmenu.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.navTab.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.tab.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.resize.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.dialog.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.dialogDrag.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.sortDrag.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.cssTable.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.stable.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.taskBar.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.ajax.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.pagination.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.database.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.datepicker.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.effects.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.panel.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.checkbox.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.combox.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.history.js',CClientScript::POS_HEAD);
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.print.js',CClientScript::POS_HEAD);   
} else {
    Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/core/dwz.min.js', CClientScript::POS_HEAD);
}
Yii::app()->clientScript->registerCssFile($baseUrl.'/css/jquery.classynotty.css', 'screen');
Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/notty/jquery.classynotty.js',CClientScript::POS_HEAD);

$lang = !empty(Yii::app()->language) ? Yii::app()->language : 'en';
Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/lang/ueb.regional.'.$lang.'.js',CClientScript::POS_HEAD);
Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/custom/ueb.system.js', CClientScript::POS_HEAD);
Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/custom/ueb.common.js', CClientScript::POS_HEAD);

Yii::app()->clientScript->registerCssFile($baseUrl.'/css/chosen.css', 'screen');
Yii::app()->clientScript->registerScriptFile($baseUrl.'/js/custom/chosen.jquery.js');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" " http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo Yii::t('app', Yii::app()->name)?></title>
<style type="text/css">
	#header{height:85px}
	#leftside, #container, #splitBar, #splitBarProxy{top:90px}
</style>
<script type="text/javascript" >
var copy_content;
var TOP_MSG_IDS = [];      
$(function(){
	DWZ.init("<?php echo $baseUrl;?>/themes/dwz.frag.xml", {
		loginUrl:"login_dialog.html", loginTitle:"登录",	// 弹出登录对话框
		statusCode:{ok:200, error:300, timeout:301}, //【可选】
		pageInfo:{pageNum:"pageNum", numPerPage:"numPerPage", orderField:"orderField", orderDirection:"orderDirection"}, //【可选】
		debug:true,	// 调试模式 【true|false】
		callback:function(){
			initEnv();
			$("#themeList").theme({themeBase:"themes", themeName: '<?php echo Yii::app()->params['theme']?>'}); // themeBase 相对于index页面的主题base路径
		}
	});

	//1.禁用鼠标右键
	$(document).ready(function(){  
		$(document).bind("contextmenu",function(e){   
			return false;   
		});
	});
	//2.鼠标右键后弹出自己的Div
	$('.gridTbody').live('mousedown',function(e){
		var id=$(".treeFolder .selected a").attr('id').substring(5);
		var newId=$('div#mouse_right_menu').attr({id:"mouse_right_menu_"+id});
		if(e.which == 3){
	        var	xx= e.pageX;  
	       	var	yy= e.pageY;    	 
	       	$('div#mouse_right_menu_'+id).css({
				top:(yy-120)+'px',
				left:(xx-210)+'px'		
	        });
	       	$('div#mouse_right_menu_'+id).show();
	    }else{
	    	$('div#mouse_right_menu_'+id).hide();
		}	      	
	 });

	$('.trigger').live('click',function(){
		var id=$(".treeFolder .selected a").attr('id').substring(5);
		var newId=$('div#mouse_right_menu').attr({id:"mouse_right_menu_"+id});
		$('div#mouse_right_menu_'+id).hide();
	});   
	document.onmouseup=function(e){
		copy_content=window.getSelection().toString();
	}
});
	function turnOff(obj){
		var id=$("#tree-menu .selected a").attr('id').substring(5);
		var newId=$('div#mouse_right_menu').attr({id:"mouse_right_menu_"+id});
		$('div#mouse_right_menu_'+id).hide();
	}
	

	function copy(){
		var id=$("#tree-menu .selected a").attr('id').substring(5);			
 		var newId=$('div#mouse_right_menu').attr({id:"mouse_right_menu_"+id});
		 if (window.clipboardData){  //IE
			 window.clipboardData.clearData();
				if(copy_content){			
					window.clipboardData.setData("Text",copy_content);					
			 		$('div#mouse_right_menu_'+id).hide();
			 		alert('复制成功！！');
				}else{					
			 		$('div#mouse_right_menu_'+id).hide();
					alert('未选中内容！！');
				}	     
		} else if (window.netscape){//火狐	
			if(copy_content){											
		 		$('div#mouse_right_menu_'+id).hide();		 		
			}else{					
		 		$('div#mouse_right_menu_'+id).hide();
				alert('未选中内容！！');
			}	     		
	    }		
   }
</script>

</head>
<body scroll="no">
	<div id="layout">
        <?php echo $this->renderPartial('//layouts/header');?>
        <?php echo $this->renderPartial('//layouts/leftside');?>
		<div id="container">
			<div id="navTab" class="tabsPage">
				<div class="tabsPageHeader">
					<div class="tabsPageHeaderContent">
						<ul class="navTab-tab">
                            <li tabid="main" class="main"><a href="javascript:;"><span><span class="home_icon"><?php echo Yii::t('app', 'Dashboard')?></span></span></a></li>
						</ul>
					</div>
					<div class="tabsLeft">left</div>
					<div class="tabsRight">right</div>
					<div class="tabsMore">more</div>
				</div>
				<ul class="tabsMoreList">
					<li><a href="javascript:;"></a></li>
				</ul>
				<div class="navTab-panel tabsPageContent layoutBox">
                    <?php echo $content;?>
				</div>
			</div>
		</div>
	</div>
	<div id="footer">
       <?php echo Yii::app()->params['copyrightInfo'];?>	
    </div>
</body>
</html>