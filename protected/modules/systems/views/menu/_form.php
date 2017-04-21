<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'menuForm',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
    	'focus' => array($model, 'menu_display_name'),
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => array('/systems/menu/'.$action.'/id/'.$model->id), 
        'htmlOptions' => array(        
            'class' => 'pageForm',         
           )
    ));
    ?>   
    <div class="pageFormContent" layoutH="56">            
        <div class="row">
            <?php echo $form->labelEx($model, 'menu_display_name'); ?>
            <?php echo $form->textField($model, 'menu_display_name', array( 'size' => 30)); ?>
            <?php echo $form->error($model, 'menu_display_name'); ?>          
        </div>
        <?php if ( $action != 'create'):?>
        <div class="row">
            <?php echo $form->labelEx($model, 'menu_parent_id'); ?>    
            <div style=" float:left; display:block;  overflow:auto; width:190px; height:200px; border:solid 1px #CCC; line-height:21px; background:#FFF;"> 
                <ul class="tree expand" id="menu_tree_seleced" >
                    <li>
                        <a id="treeItem_0" ><?php echo Yii::t('system', 'Root')?></a>
                        <?php echo $this->renderPartial('systems.components.views.MenuTree', array('type' => 'menu','menuId'=>$model->id)); ?> 
                    </li>
                </ul>                             
            </div>
            <?php echo $form->error($model, 'menu_parent_id'); ?> 
        </div>
        <?php endif;?>
        <div class="row">
            <?php echo $form->labelEx($model, 'menu_url'); ?>
            <?php echo $form->textField($model, 'menu_url', array( 'size' => 30)); ?>
            <?php echo $form->error($model, 'menu_url'); ?>
        </div>
        <div class="row">
            <?php echo $form->labelEx($model, 'menu_description'); ?>
            <?php echo $form->textArea($model, 'menu_description', array('cols' => 27)); ?>      
            <?php echo $form->error($model, 'menu_description'); ?>
        </div>
         <div class="row">
            <?php echo $form->labelEx($model, 'menu_status'); ?>                 
            <?php echo $form->dropDownList($model, 'menu_status', array(1 => Yii::t('system', 'Enable'), 0 => Yii::t('system', 'Disable'))); ?>
            <?php echo $form->error($model, 'menu_status'); ?>          
        </div>
        <div class="row">       
            <?php echo $form->labelEx($model, 'menu_order'); ?>
            <?php echo $form->textField($model, 'menu_order',array( 'size' => 5)); ?>
            <?php echo $form->error($model, 'menu_order'); ?>                
        </div>
         <div class="row">
            <?php echo $form->labelEx($model, 'menu_is_menu'); ?>
            <?php echo $form->dropDownList($model, 'menu_is_menu', array(1 => Yii::t('system', 'Yes'), 0 => Yii::t('system', 'No'))); ?>
            <?php echo $form->error($model, 'menu_is_menu'); ?>              
        </div>
    </div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                         
                        <?php echo $form->hiddenField($model, 'menu_parent_id', array('type' => "hidden")); ?>                      
                        <button type="submit"><?php echo Yii::t('system', 'Save')?></button>                     
                    </div>
                </div>
            </li>
            <li>
                <div class="button"><div class="buttonContent"><button type="button" class="close"><?php echo Yii::t('system', 'Cancel')?></button></div></div>
            </li>
        </ul>
    </div>
    <?php $this->endWidget(); ?>
</div>


