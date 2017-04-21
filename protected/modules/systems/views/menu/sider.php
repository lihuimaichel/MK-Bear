<div class="accordion" fillSpace="sideBar">   
	<div class="accordionHeader">
		<h2><span>Folder</span><?php echo $data->menu_display_name;?></h2>
	</div>
	<div class="accordionContent">	     
        <?php echo $this->renderPartial('systems.components.views.MenuSider', array('parentId' => $data->id)); ?>
	</div>
	<?php echo $this->renderPartial('systems.components.views.NomalSider'); ?>
    <span id="historySpan"><?php echo $this->renderPartial('systems.components.views.HistorySider');?></span>
</div>
