<?php
/* Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'productinfomodule-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
	'columns'=>array(
				array(
						'class' => 'CCheckBoxColumn',
						'selectableRows' =>2,
						'value'=> '$data->id',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:25px;',
						),
				),
				array(
						'name'=> 'id',
						'value'=>'$row+1',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:50px;',
						),
				),
				array(
						'name' => 'name',
						'value' => '$data->name',
				        'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:275px;'),
				),
				array(
						'type' => '',
						'value' => '$data->type',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:100px;'),
				),
			
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
)); */
?>
<style>
table.grid {
	width:100%;
	heith:auto;
	margin:0;
	padding:0;
}
table.grid tr {
	margin:0;
	padding:0;
}
table.grid tr th, table.grid tr td {
	padding:7px;
	border-bottom:1px #d2d2d2 solid;
}
.searchRow {
	margin:0 auto 10px;
	overflow:hidden;
}
.searchInputRow {
	float:left;
	margin-right:25px;
}
.paginatorRow a{
	text-decoration:none;
	padding:5px 3px;
}
.paginatorRow span {
	padding:5px 3px;
	font-weight:bold;
}
</style>
<?php 
$dataProvider = $model->search(null);
$pages = $dataProvider->getPagination();
$currentPage = isset($_REQUEST['pageNum']) && !empty($_REQUEST['pageNum']) ? (int)$_REQUEST['pageNum'] : 1;
$pageSize = $pages->getPageSize();
$total = $dataProvider->getTotalItemCount();
$totalPage = ceil($total / $pageSize);
?>
<div class="searchRow">
	<div class="searchInputRow">
		<label for="module_name"><?php echo Yii::t('aliexpress', 'Module Name');?></label>
		<input type="text" size="18" id="module_name" name="module_name" value="<?php echo isset($_REQUEST['module_name']) ? $_REQUEST['module_name'] : '';?>" />
	</div>
	<div class="buttonActive">
	    	<div class="buttonContent">
	    	<button onclick="searchData()" type="submit">查 询</button>
	    	</div>
	    	<div class="buttonContent" style="margin-left:5px;">
	    	<button onclick="syncData()" type="submit">同步</button>
	    	</div>
	</div>
</div>
<table class="grid" cellspacing="0" cellpadding="0">
	<thead>
		<tr>
			<th width="5%"> </th>
			<th width="5%"><?php echo Yii::t('system', 'id');?></th>
			<th width="60%"><?php echo Yii::t('aliexpress', 'Module Name');?></th>
			<th width="30%"><?php echo Yii::t('aliexpress', 'Module Type');?></th>
		</tr>
	</thead>
	<tbody>
	<?php if (!empty($dataProvider->data)) { ?>
	<?php foreach ($dataProvider->data as $key => $row) { ?>
		<tr>
			<td><input type="checkbox" value="<?php echo $row->module_id;?>" name="productinfo_module_id[]" /></td>
			<td><?php echo $key+1;?></td>
			<td><?php echo $row->name;?></td>
			<td><?php echo Aliexpressproductinfomodule::getModuleType($row->type);?></td>
		</tr>
	<?php } ?>
	<?php } else { ?>
		<tr>
			<td align="center" colspan="4"><?php echo Yii::t('aliexpress', 'No Data');?></td>
		</tr>
	<?php } ?>
	</tbody>
	<?php if ($totalPage > 1) { ?>
	<tfoot>
		<tr>
			<td colspan="4" align="right">
				<div class="paginatorRow">
				<?php for($i=1; $i<=$totalPage; $i++) { 
					if ($currentPage == $i) {
				?>
					<span><?php echo $i;?></span>
				<?php } else { ?>
					<a onclick="searchData(<?php echo $i;?>)" href="javascript:void(0)"><?php echo $i;?></a>
				<?php 
					}
				} ?>
				</div>
			</td>
		</tr>
	</tfoot>
	<?php } ?>
</table>