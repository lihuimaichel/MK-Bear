<script type="text/javascript">
    //$("a[id^=dep_]").depCmDialog();
</script>
<style type="text/css">
    ul.rightTools {float:right; display:block;}
    ul.rightTools li{float:left; display:block; margin-left:5px}   
</style>
<div class="pageContent" style="padding:5px;">         
    <div>
        <div layoutH="38" style="float:left; height:auto;display:block; overflow:auto; width:240px; border:solid 1px #CCC; line-height:21px; background:#fff">
            <div class="panelBar">
                <ul class="toolBar">
                    <li></li>          
                </ul>
            </div>
          
            <?php echo $this->renderPartial('users.components.views.deptTree', array( 'class' => 'tree treeFolder', 'id' => 'depTreePanel','menuId' => '0')); ?>
           
        </div>
        <div id="jbsxBox" class="unitBox" style="margin-left:246px;"></div>
    </div>                    
</div>
<script type="text/javascript">
    $(function(){           
        setTimeout(function(){
            $('#depTreePanel a:first').trigger('click');
        }, 10);
    });
</script>


