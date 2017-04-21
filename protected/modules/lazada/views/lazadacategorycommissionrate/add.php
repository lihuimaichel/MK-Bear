<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent">
<?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'menuForm',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => Yii::app()->createUrl($this->route),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
?> 
    <div class="pageFormContent" layoutH="56"> 
    	<div class="bg14 pdtb2 dot">
	         <strong>添加或修改类目佣金</strong>           
	    </div>
        <div class="pd5" style="height:120px;">        
        	<div class="row">
        		<?php  echo $form->labelEx($model,'category_id');?>
                <?php  echo $form->dropDownList($model,'category_id',$categoryLevenOne);?>
                <?php  echo $form->error($model,'category_id',array('style' => 'float: right;'));?>
        	</div>
			<div class="row">
                <?php  echo $form->labelEx($model,'site_id');?>
                <?php  echo $form->dropDownList($model,'site_id',$siteList);?>
                <?php  echo $form->error($model,'site_id',array('style' => 'float: right;'));?>
            </div>
            <div class="row">
                <label>佣金比例</label>
                <input type="text" name="commission_rate" class="textInput" value="">
                <span style="padding-left:10px;">请输入0-100的数值,不要输入%</span>
            </div>
        </div>
    </div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                        <button type="submit"><?php echo Yii::t('system', '添加或修改')?></button>                     
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