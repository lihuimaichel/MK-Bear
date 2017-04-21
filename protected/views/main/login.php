<?php $baseUrl = Yii::app()->request->baseUrl; ?>
<div class="loginForm form">   
    <?php
    $form = $this->beginWidget('CActiveForm', array(
        'id' => 'login-form',
        'enableAjaxValidation' => true,
    ));
    ?>
    <span class="a1"></span>  
    <?php echo $form->textField($model, 'user_name', array( 'class' => 'input_login')); ?>
    <?php echo $form->error($model, 'user_name',array( 'class' => 'login-ts b1')); ?>  
    <span class="a2"></span>
    <?php echo $form->passwordField($model, 'user_password', array('class' => 'input_password')); ?>
    <?php echo $form->error($model, 'user_password'); ?>
    
    <?php if($model->useCaptcha && CCaptcha::checkRequirements()):?>
    <span class="a3"></span>
    <?php  echo $form->textField($model,'verifyCode', array('class' => 'input_yz')); ?>
    <?php
        //echo '<p class="hint">'.Yii::t('app','Please enter the letters in the image above.').'</p>';
        echo $form->error($model, 'verifyCode');  
    ?>  
    <span class="a4"></span>
    <div class="login-img-yz row">
        <div class="row" style="margin-top:5px;">
            <?php   
            echo '<div>';
            $this->widget('CCaptcha',array(
                'clickableImage'=>true,
                'showRefreshButton'=>false,
                'imageOptions'=>array(
                    'style'=>'display:block;cursor:pointer;',
                    'title'=>Yii::t('app','Click to get a new image')
                )
            )); echo '</div>';                            
            ?>
        </div>
    </div>
    <?php endif;?>
    <span class="a5"></span>
    <?php echo CHtml::submitButton('', array('class' => 'btn_login')); ?>        
    <?php $this->endWidget(); ?>
</div>

