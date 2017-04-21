<script type="text/javascript">
    $("a[id^=treeItem_]").menuCmDialog(); 
</script>
<div class="pageContent" >
<div class="panelBar">
    <ul class="toolBar">
        <li></li>       
    </ul>
</div>
<div layoutH="50" style="float:left; display:block; overflow:auto; width:600px; border:solid 1px #CCC; line-height:21px; background:#fff">   
    <ul class="tree treeFolder" rel="treeCm" >
        <li>
            <a id="treeItem_0" ><?php echo Yii::t('system', 'Root')?></a>
            <?php echo $this->renderPartial('systems.components.views.MenuTree', array( 'type' => 'menu','menuId'=>'0')); ?>
        </li>
    </ul>
</div>	




