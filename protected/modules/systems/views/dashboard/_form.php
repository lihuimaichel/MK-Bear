<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false; 
$form = $this->beginWidget('ActiveForm', array(
		'id' => 'dashBoardForm',
		'enableAjaxValidation' => false,
		'enableClientValidation' => true,
		'focus' => array($model, ''),
		'clientOptions' => array(
				'validateOnSubmit' => true,
				'validateOnChange' => true,
				'validateOnType' => false,
				'afterValidate'=>'js:afterValidate',
		),
		'action' => Yii::app()->createUrl($this->route.'/id/'.$model->id),
		'htmlOptions' => array(
				'class' => 'pageForm',
		)
));

?>

<div class="pageContent">   
    <div class="tabs"> 
	    <div class="tabsHeader"> 
	 		<div class="tabsHeaderContent"> 
	 			<ul> 
	 				<li class="selected"><a href="#"><span><?php echo Yii::t('system', 'Basic Information')?></span></a></li>
	 			</ul> 
	 		</div> 
	 	</div>
	 	<div class="tabsContent" style="height:300px;">
 			<div class="pageFormContent">
	        	<div class="row">
	                <?php echo $form->labelEx($model, 'dashboard_title'); ?>
	                <?php echo $form->textField($model, 'dashboard_title', array( 'size' => 38)); ?>
	                <?php echo $form->error($model, 'dashboard_title'); ?>
	            </div>
	            <div class="row">
	                <?php echo $form->labelEx($model, 'dashboard_url'); ?>
	                <?php echo $form->textField($model, 'dashboard_url', array( 'size' => 38)); ?>
	                <?php echo $form->error($model, 'dashboard_url'); ?>
	            </div> 
	            <div class="row">
                    <?php echo $form->labelEx($model, 'is_global'); ?>
                    <?php echo $form->dropDownList($model, 'is_global', $model->getMyConfig("is_global")); ?>
                    <?php echo $form->error($model, 'is_global'); ?>
                </div>
                <div class="row">
                    <?php echo $form->labelEx($model, 'status'); ?>
                    <?php echo $form->dropDownList($model, 'status', $model->getMyConfig("status")); ?>
                    <?php echo $form->error($model, 'status'); ?>
                </div>
                <div class="row">
                    <?php echo $form->labelEx($model, 'type'); ?>
                    <?php echo $form->dropDownList($model, 'type', $model->getMyConfig("type")); ?>
                    <?php echo $form->error($model, 'type'); ?>
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