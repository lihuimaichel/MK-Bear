<?php 
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$form = $this->beginWidget('CActiveForm', array(
    'id' => $lang_code.'-Form',
    'enableAjaxValidation' => false,  
    'enableClientValidation' => true,
    'clientOptions' => array(
        'validateOnSubmit' => true,
        'validateOnChange' => true,
        'validateOnType' => false,
        'afterValidate'=>'js:afterValidate',
    ),
    //'action' => Yii::app()->createUrl($this->route),
	// 'action' => Yii::app()->createUrl('products/productdescription/index'),
    'htmlOptions' => array(
        'class' => 'pageForm',
       )
));
?>
<div class="pageFormContent descriptionContent" layoutH="100"> 
	  <div class="row">		
       <?php echo $form->labelEx($model, 'title');?>
       <?php $option = array('size' => 60,'id' => $lang_code.'_title');
       if($lang_code == 'english'){$option['readonly']='readonly';}
       ?>
       <?php echo $form->textField($model, 'title', $option); ?>      
       <?php echo $form->error($model, 'title'); ?>
    </div>
    <div class="row">
       <?php echo $form->labelEx($model, 'customs_name');?>
       <?php echo $form->textField($model, 'customs_name', array('size' => 60,'id' => $lang_code.'_customs_name')); ?>
       <?php echo $form->error($model, 'customs_name'); ?>
    </div>
    <div class="row">
       <?php echo $form->labelEx($model, 'description'); ?>
       <?php echo $form->textArea($model, 'description', array('cols' => 60,'id' => $lang_code.'_desc','style'=>'width:450px;height:250px;')); ?>
       <?php echo $form->error($model, 'description'); ?>
    </div>
    <br/>
    <div class="row">
       <?php echo $form->labelEx($model, 'included'); ?>
       <?php echo $form->textArea($model, 'included', array('cols' => 60,'id' => $lang_code.'_inc','style'=>'width:450px;height:250px;')); ?>
       <?php echo $form->error($model, 'included'); ?>
    </div>
    
</div>	

<div class="formBar">
    <ul> 
        <li>
            <div class="button"><div class="buttonContent"><button type="button" class="close" onclick="$.pdialog.closeCurrent();"><?php echo Yii::t('system', 'Closed')?></button></div></div>
        </li>
    </ul>
</div>
<?php $this->endWidget(); ?>
<script type="text/javascript">
var $p = $.pdialog.getCurrent();
var keditor = null;
var keditor1 = null;
$(function (){
	var id_desc = "<?php echo $lang_code.'_desc';?>";
	var id_inc = "<?php echo $lang_code.'_inc';?>";
	keditor = kedit(id_desc);
	keditor1 = kedit(id_inc);
});

function kedit(keid){ 
	var keditor =  KindEditor.create('#' + keid,{
		allowFileManager: true,
		width: '80%',
		afterCreate : function() {
         this.sync();
        },
        afterBlur:function(){
            this.sync();
        }
	});
	return keditor;
} 

</script>
