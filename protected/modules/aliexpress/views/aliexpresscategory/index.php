<?php
Yii::app()->clientScript->registerScriptFile('/js/custom/aliexpresscategorylist.js', CClientScript::POS_HEAD);
?>
<script type="text/javascript">
    $("a[id^=categoryId_]").aliexpressCategoryListAjax();
</script>
<style type="text/css">
    ul.rightTools {float:right; display:block;}
    ul.rightTools li{float:left; display:block; margin-left:5px} 
    .setRate{padding-right:10px; line-height: 22px; cursor:pointer; float:right;}
    .updateCate{padding-left:10px; line-height: 22px; cursor:pointer; float:left;}
</style>
<div class="pageContent" style="padding:5px;width:1800px;">         
	<div layoutH="10" style="float:left; display:block; overflow:auto; width:380px; border:solid 1px #CCC; line-height:21px; background:#fff">
		<div class="panelBar">
			<ul class="toolBar">
				<li style="width:100%;"><div class="updateCate" onclick="updateCategory(0)">同步顶级和下属所有栏目</div><div class="setRate" onclick="setCommissionRate()">设置佣金比例</div></li>          
			</ul>
		</div>
		<ul class="tree treeFolder" id="tree-menu" >
			<?php
				$str = '';
				foreach ($categoryList as $key => $val) {
					$commission = '';
					if($key != 36){
						$commission = AliexpressCategoryCommissionRate::getCommissionRateText($key);
					}
					$str .= "<li id = >";
					$str .= '<a href="javascript::void(0);" id =categoryId_' . $key.' rel="'.$key.'">'.$val.$commission.'</a>';
					$str .= '</li>';
				}
				echo $str;
			?>
		</ul>
	</div>
<div id="aliexpressCategoryAccessPanel" class="unitBox" style="margin-left:246px;"></div>
</div>
<script type="text/javascript">
/**
 * 设置栏目的佣金比例
 */
function setCommissionRate(){
    var categoryId = $('.pageContent div.selected a').attr("rel");
    if(isNaN(categoryId) == true){
        alertMsg.error('请选择类目');
        return false;
    }

    //判断是否是珠宝饰品及配件ID
    if(categoryId == 36){
    	alertMsg.error('珠宝饰品及配件请设置二级类目');
        return false;
    }

    var url ='/aliexpress/aliexpresscategory/setcommissionrate/categoryId/' + categoryId;
    $.pdialog.open(url, 'setCommissionRate', '设置栏目的佣金比例', {width:360, height:150});
}
</script>