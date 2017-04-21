<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$form = $this->beginWidget('ActiveForm', array(
    'id' => 'UpdateProductPriceForm',
    'enableAjaxValidation' => false,
    'enableClientValidation' => true,
    'focus' => array($model, ''),
    'clientOptions' => array(
        'validateOnSubmit' => true,
        'validateOnChange' => true,
        'validateOnType' => false,
        'afterValidate' => 'js:afterValidate',
    	//'additionValidate' => 'js:checkOption-afterValidate',
    ),
    'action' => Yii::app()->createUrl($this->route,array('id'=>$model->id)),
    'htmlOptions' => array(
        'class' => 'pageForm',
    )
));
?>

<div class="pageContent"> 
	<div class="pageFormContent" layoutH="55">
	<?php if($changway == 'common'):?>
		<div class="row">
	        <?php echo $form->labelEx($model,'price'); ?>
            <?php echo $form->textField($model,'price',array('size'=>20));?>
            <?php echo $form->error($model,'price'); ?>
        </div>
    <?php else:?>
    	<div class="row">
            <?php echo $form->labelEx($model, 'changeway'); ?>
            <?php echo $form->dropDownList($model, 'changeway', UebModel::model('LazadaProductUpdate')->getPriceWay(), array('empty' => Yii::t('system', 'Please Select'), 'style' => 'width:150px;')); ?>                  
            <?php echo $form->error($model, 'changeway'); ?>
        </div>
    	<div class="row">
	        <?php echo $form->labelEx($model,'variable_price'); ?>
            <?php echo $form->textField($model,'variable_price',array('size'=>20));?>
            <?php echo $form->error($model,'variable_price'); ?>
        </div>
    <?php endif;?>
	</div>
	<div class="formBar">
	    <ul>
	    	<li>
	        	<div class="buttonActive">
	            	<div class="buttonContent">
	                	<button type="submit"><?php echo Yii::t('system', 'Save') ?></button>
	            	</div>
	            </div>
	        </li>
	        <li>
	            <div class="button">
	                <div class="buttonContent">
	                    <button type="button" class="close"><?php echo Yii::t('system', 'Cancel') ?></button>
	                </div>
	            </div>
	    	</li>
		</ul>
	</div>
	<?php $this->endWidget(); ?>
</div>

<script type="text/javascript">

</script>
