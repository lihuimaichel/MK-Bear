<style>
	.chooseUserDiv {
		width:100%;
		padding:25px;
		margin:0;
	}
	.chooseUserDiv .deptDiv {
		margin:0;
		padding:0;
		width:275px;
		float:left;
	}
	.userDiv {
		width:50%;
		float:right;
		height:100%;
	}
</style>
<div class="chooseUserDiv">
	<div class="deptDiv">
		<?php echo $this->renderPartial('users.components.views.DeptTree', array( 'class' => 'tree treeFolder', 'id' => 'depTreePanel','menuId' => '0')); ?>
	</div>
	<div class="userDiv"></div>
</div>