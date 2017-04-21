<form id="pagerForm" action="<?php echo Yii::app()->createUrl("/ebay/ebayproductadd/getexcludeshippinglocationlookup/site_id/{$siteID}");?>">
	<input type="hidden" name="pageNum" value="1" />
	<input type="hidden" name="numPerPage" value="${model.numPerPage}" />
	<input type="hidden" name="orderField" value="${param.orderField}" />
	<input type="hidden" name="orderDirection" value="${param.orderDirection}" />
</form>

<div class="pageHeader">
	<form rel="pagerForm" method="post" action="<?php echo Yii::app()->createUrl("/ebay/ebayproductadd/getexcludeshippinglocationlookup/site_id/{$siteID}");?>" onsubmit="return dwzSearch(this, 'dialog');">
		<div class="searchBar">
			<div class="subBar">
				<ul>
					<li><div class="button"><div class="buttonContent"><button type="button" rel="" multLookup="code3" warn="请选择屏蔽国家">确定返回</button></div></div></li>
				</ul>
			</div>
		</div>
	</form>
</div>
<div class="pageContent">


	
		<table class="table" layoutH="23" targetType="dialog" width="100%">
		<thead>
			<tr>
				<th width="30">所属大洲</th>
				<th><input type="checkbox" class="checkboxCtrl" group="code3" /></th>
			</tr>
		</thead>
		<tbody>
			<?php if($excludeShippingLocation):?>
			<?php foreach ($excludeShippingLocation as $con=>$countrys):?>
			<tr>
				<td><?php echo $con;?> <input type="checkbox" class="checkCurrencyCon" onclick="checkCurrencyCon(this)" onpropertychange="checkCurrencyCon(this)"/></td>
				<td>
				<?php foreach ($countrys as $country):?>
					<span style="width: 160px;height:40px;line-height:24px;display:inline-block;">
						<input id="continents_<?php echo $con;?>_<?php echo $country['code'];?>" type="checkbox" name="code3" <?php if(in_array($country['code'], $selectedCountry)):?> checked<?php endif;?> value='{code:"<?php echo $country['code'];?>", name:"<?php echo $country['name'];?>"}'>
						<label for="continents_<?php echo $con;?>_<?php echo $country['code'];?>"><?php echo $country['name'];?></label>
					</span>
				<?php endforeach;?>
				</td>
			</tr>
			<?php endforeach;?>
			<?php endif;?>
		</tbody>
	</table>
</div>

<script type="text/javascript">
function checkCurrencyCon(obj){
	$(obj).parent("td").next("td").find("input[type=checkbox]").attr("checked", !!$(obj).attr("checked"));
};
</script>