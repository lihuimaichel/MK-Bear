<?php echo $this->renderPartial('_form', array('model' => $model, 'action' => 'update', 'categoryAttribute' => $categoryAttribute, 'isLeaf' => $isLeaf)); ?>
<script type="text/javascript">
    $(function(){
        var selectedId = <?php echo $model->category_parent_id;?>, 
            id = <?php echo $model->id;?>, 
            catTreeObj = $('#cat_tree_seleced'); 
        var selectedObj = catTreeObj.find('#catTreeItem_'+selectedId);
        setTimeout(function(){          
            $(selectedObj).parent('div').addClass('selected');
        }, 200); 
        $("a", catTreeObj).click(function(){          
            var tempSelectedId = $(this).attr('id').split('_')[1];  
            if ( parseInt(id) == parseInt(tempSelectedId) ) {                                                                      
                alertMsg.warn('<?php echo Yii::t('products', 'The parent category cannot be the same as a sub category')?>');
                setTimeout(function(){          
                    catTreeObj.find('#catTreeItem_'+id).parent('div').removeClass('selected');
                    catTreeObj.find('#catTreeItem_'+selectedId).parent('div').addClass('selected');
                }, 200); 
                return false;
            }           
            $('#ProductCategory_category_parent_id').val(tempSelectedId);          
        });
    });      
</script>