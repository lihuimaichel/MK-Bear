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
        <div class="pd5" >
			<div class="row">
                <label for="u_status">状态</label>
                <select name="status" id="u_status">
                    <option value="0" <?php if($info['status']==0){?>selected<?php }?>>未断货</option>
                    <option value="1" <?php if($info['status']==1){?>selected<?php }?>>已断货</option>
                    <option value="2" <?php if($info['status']==2){?>selected<?php }?>>来货取消</option>
                </select>
                <?php echo $form->error($model, 'status'); ?> 
            </div>

            <div class="row">
                <label for="u_remark">备注</label>
                <textarea name="remark" id="u_remark" cols="40" rows="5"><?php echo $info['remark'];?></textarea>       
                <?php echo $form->error($model, 'remark'); ?> 
            </div>
            <input type="hidden" name="id" value="<?php echo $id; ?>">  
        </div>
    </div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                        <button type="submit"><?php echo Yii::t('system', '更改')?></button>                     
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