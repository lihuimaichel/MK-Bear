<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>

<div class="pageContent">
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'warehouseShelfRulesForm',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
    	'focus' => array($model, ''),
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
//         'action' => Yii::app()->createUrl($this->route).'/id/'.$model->id, 
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
    ?>
        <div class="pageFormContent" layoutH="56">   
            <div class="pd5" style="height:150px;">
                <div id="create_location_rule" style="clear:both;">
    		         <div class="row">
			            <?php echo $form->labelEx($model,   'user_name'); ?>
			            <?php echo $form->textField($model, 'user_name',array('size'=>28,'maxlength'=>128));?>
			            <?php echo $form->error($model, 'user_name'); ?>
        			</div>     
                </div> 
            </div>                      
        </div>
        <div class="formBar">
            <ul>              
                <li>
                    <div class="buttonActive">
                        <div class="buttonContent">                        
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
        