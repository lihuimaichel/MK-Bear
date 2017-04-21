<?php
Yii::app()->clientScript->registerScriptFile('/js/custom/productgrouplist.js', CClientScript::POS_HEAD);
?>
<script type="text/javascript">
    $("a[id^=accountId_]").productGroupListAjax();
</script>
<style type="text/css">
    ul.rightTools {float:right; display:block;}
    ul.rightTools li{float:left; display:block; margin-left:5px}   
</style>
<div class="pageContent" style="padding:5px;">         
	<div layoutH="10" style="float:left; display:block; overflow:auto; width:240px; border:solid 1px #CCC; line-height:21px; background:#fff">
		<div class="panelBar">
			<ul class="toolBar">
				<li></li>          
			</ul>
		</div>
		<?php echo $this->renderPartial('aliexpress.components.views.AccountList', array('index' => $index)); ?>
	</div>
<div id="productgroupAccessPanel" class="unitBox" style="margin-left:246px;"></div>
</div>