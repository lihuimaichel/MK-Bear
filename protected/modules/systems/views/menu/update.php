<?php echo $this->renderPartial('_form', array('model' => $model, 'action' => 'update')); ?>
<script type="text/javascript">
    $(function(){
        var selectedId = <?php echo $model->menu_parent_id;?>, 
            id = <?php echo $model->id;?>, 
            menuTreeObj = $('#menu_tree_seleced');             
        setTimeout(function(){
            var selectedObj = menuTreeObj.find('#treeItem_'+selectedId);
            $(selectedObj).parent('div').addClass('selected');
        }, 200); 
        $("a", menuTreeObj).click(function(){          
            var tempSelectedId = $(this).attr('id').split('_')[1];  
            if ( parseInt(id) == parseInt(tempSelectedId) ) {              
                alertMsg.warn('<?php echo Yii::t('system', 'The parent menu cannot be the same as a sub menu')?>');
                return false;
            } 
            $('#Menu_menu_parent_id').val(tempSelectedId);          
        });
    });      
</script>