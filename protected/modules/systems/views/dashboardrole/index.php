<?php
Yii::app()->clientScript->registerScriptFile('/js/custom/ueb.dashboard.js', CClientScript::POS_HEAD);
?>
<script type="text/javascript">
    $("a[id^=roleAccessId_]").roleDashboardAjax();
</script>
<div class="pageContent" style="padding:5px">        
    <div>
        <div layoutH="10" style="float:left; display:block; overflow:auto; width:322px; border:solid 1px #CCC; line-height:21px; background:#fff; resize: both; ">
            <div class="panelBar"></div>
            <div  style="border:solid 1px #DFE8F6; overflow: auto; resize: both;  width: 320px;  height: 350px;  min-height: 650px; ">
                <?php echo $this->renderPartial('users.components.views.RoleTree', array( 'class' => 'tree treeFolder', 'id' => 'roleTreePanel', 'root' => Yii::t('system', 'All Roles'),'menuId' => '')); ?>
            </div>
        </div>  
        <div id="dashboardAccessPanel" class="unitBox" style="margin-left:246px;"></div>
    </div>
</div>