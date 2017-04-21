<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'catForm',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
    	'focus' => array($model, 'category_cn_name'),
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => array(Yii::app()->createUrl($this->route, array( 'id' => $model->id))), 
        'htmlOptions' => array(        
            'class' => 'pageForm',         
           )
    ));
   // var_dump($model->id);
 //   $info = UebModel::model('procuctCategory')->getSubArr();
    ?>   
    <div class="pageFormContent" layoutH="56">            
        <div class="row">
            <?php echo $form->labelEx($model, 'category_cn_name'); ?>
            <?php echo $form->textField($model, 'category_cn_name', array( 'size' => 30)); ?>
            <?php echo $form->error($model, 'category_cn_name'); ?>          
        </div>
        <div class="row">
            <?php echo $form->labelEx($model, 'category_en_name'); ?>
            <?php echo $form->textField($model, 'category_en_name', array( 'size' => 30)); ?>
            <?php echo $form->error($model, 'category_en_name'); ?>          
        </div>
        <div class="row">
            <?php echo $form->labelEx($model, 'category_code'); ?>
            <?php echo $form->textField($model, 'category_code', array( 'size' => 30)); ?>
            <?php echo $form->error($model, 'category_code'); ?>          
        </div> 
        <?php if(1!=1)://暂不显示分类修改父分类?>
        <?php if ( $action != 'create'):?>
        <div class="row">
            <?php echo $form->labelEx($model, 'category_parent_id'); ?>    
            <div style=" float:left; display:block;  overflow:auto; width:190px; height:200px; border:solid 1px #CCC; line-height:21px; background:#FFF;"> 
                <ul class="tree expand" id="cat_tree_seleced" >
                    <li>
                        <a id="catTreeItem_0" ><?php echo Yii::t('system', 'Root')?></a>
                        <?php echo $this->renderPartial('products.components.views.CatTree', array('type' => 'menu','Id'=>$model->id)); ?> 
                    </li>
                </ul>                             
            </div>
            <?php echo $form->error($model, 'category_parent_id'); ?> 
        </div>
        <?php endif;?>
        <?php endif;?>
        <?php if ( !empty($isLeaf)):?>
        <div class="row">
            <?php echo $form->labelEx($model, 'category_attribute'); ?> 
            <?php echo $this->renderPartial('_attr', array('categoryAttribute' => isset($categoryAttribute) ? $categoryAttribute : '','category_id' => !empty($model->id) ? $model->id : '0')); ?> 
            <?php echo $form->error($model, 'category_attribute'); ?>
        </div>
        <?php endif;?>
        <div class="row">
            <?php echo $form->labelEx($model, 'category_description'); ?>
            <?php echo $form->textArea($model, 'category_description', array('cols' => 27)); ?>      
            <?php echo $form->error($model, 'category_description'); ?>
        </div>
         <div class="row">
            <?php echo $form->labelEx($model, 'category_status'); ?>                 
            <?php echo $form->dropDownList($model, 'category_status', array(1 => Yii::t('system', 'Enable'), 0 => Yii::t('system', 'Disable'))); ?>
            <?php echo $form->error($model, 'category_status'); ?>          
        </div>
        <div class="row">       
            <?php echo $form->labelEx($model, 'category_order'); ?>
            <?php echo $form->textField($model, 'category_order',array( 'size' => 5)); ?>
            <?php echo $form->error($model, 'category_order'); ?>                
        </div>     
    </div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                         
                        <?php echo $form->hiddenField($model, 'category_parent_id', array('type' => "hidden")); ?>                      
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


