<?php
Yii::app()->clientScript->registerScriptFile('/js/custom/aliexpresscategoryforproductlist.js', CClientScript::POS_HEAD);
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
	<div layoutH="10" style="float:left; display:block; overflow:auto; width:338px; border:solid 1px #CCC; line-height:21px; background:#fff">
		<div class="panelBar">
			<ul class="toolBar">
				<li style="width:100%;">
				    
				</li>          
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
					$str .= '<a href="javascript::void(0);" id =categoryId_' . $key.' rel="'.$key.'">'.$val.'</a>';
					$str .= '</li>';
				}
				echo $str;
			?>
		</ul>
	</div>
<div id="aliexpressCategoryAccessPanel" class="unitBox" style="margin-left:246px;"></div>
</div>
