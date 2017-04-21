<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
    <?php
//     $currentUrl = Yii::app()->createUrl($this->route);
//     if ( $action != 'create') {
//         $currentUrl .= '/id/'.$model->filterName($model->name);
//     }

    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'groupsForm',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
    	'focus' => array($model, 'group_name'),
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => Yii::app()->createUrl($this->route,array('id'=>$model->filterName($model->group_name))), 
        'htmlOptions' => array(        
            'class' => 'pageForm',         
           )
    ));
    ?>   
    <div class="pageFormContent" layoutH="56">            
        <div class="row">
            <?php echo $form->labelEx($model, 'group_name'); ?>
            <?php echo $form->textField($model, 'group_name', array( 'size' => 30)); ?>
            <?php echo $form->error($model, 'group_name'); ?>        
        </div>
        <?php if ( $action != 'create'):?>
        <div class="row">
            <?php echo $form->labelEx($model, 'parent_id'); ?>    
            <div style=" float:left; display:block;  overflow:auto; width:190px; height:200px; border:solid 1px #CCC; line-height:21px; background:#FFF;"> 
                <?php echo $this->renderPartial('aliexpress.components.views.GroupTree', array('class' => 'tree expand', 'id' => 'group_tree_seleced', 'root' => Yii::t('system', 'Root'),'menuId' => $model->group_name)); ?>                                            
            </div>
            <?php echo $form->error($model, 'parent_id'); ?>
        </div>
        <?php endif;?>
    </div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                                                  
                        <?php echo $form->hiddenField($model, 'parent_id', array('type' => "hidden","value"=>$model->parentId)); ?>
                        <?php echo $form->hiddenField($model, 'account_id', array('type' => "hidden","value"=>$model->accountId)); ?>                        
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


