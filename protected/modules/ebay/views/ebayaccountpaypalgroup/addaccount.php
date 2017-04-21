<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<style>
.account_span{width: 62px;height:24px;line-height:24px;display:inline-block;}
.account_span .account_label{float: right;width:32px;}
</style>
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
            <table class="dataintable_inquire" width="98%" cellspacing="1" cellpadding="3" border="0">
            <tbody>
                <tr>
                    <td>
                        <b>当前组(<?php echo $currentInfo['group_name'];?>)</b>
                        <input type="hidden" name="id" value="<?php echo $currentInfo['id'];?>">
                        <?php echo $form->error($model,'group_name');?>
                    </td>
                    <td>
                        <?php foreach ($currentPayPalList as $currentPayPal):?>
                            <span><?php echo $currentPayPal['short_name']?></span>&nbsp;&nbsp;&nbsp;&nbsp;
                        <?php endforeach;?>
                    </td>
                </tr>
                <tr>
                    <td>未选择帐号</td>
                    <td>
                        <?php foreach ($noPayPalList as $noPayPal):?>
                        <span class="account_span">
                            <input id="sel_<?php echo $noPayPal['short_name']?>" name="account[]" value="<?php echo $noPayPal['id'];?>" type="checkbox"/>
                            <label for="sel_<?php echo $noPayPal['short_name'];?>" class="account_label"><?php echo $noPayPal['short_name'];?></label>
                        </span>
                        <?php endforeach;?>
                    </td>
                </tr>
            
                <?php foreach ($groupList as $key=>$group):?>
                <tr>
                    
                    <td><?php echo $group['group_name'];?></td>
                    <td>
                        <?php foreach ($group['account_list'] as $account):?>
                            <span class="account_span">
                            <input id="sel_<?php echo $account['short_name']?>" name="account[]" value="<?php echo $account['id'];?>" type="checkbox"/>
                            <label for="sel_<?php echo $account['short_name'];?>" class="account_label"><?php echo $account['short_name'];?></label>
                        </span>
                        <?php endforeach;?>
                    </td>
                </tr>
                <?php endforeach;?>
                
            </tbody>
        </table>                     
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
                    <input type="hidden" name="group_select_account" value="submit">
                    <div class="button"><div class="buttonContent"><button type="button" class="close"><?php echo Yii::t('system', 'Cancel')?></button></div></div>
                </li>
            </ul>
        </div>
        <?php $this->endWidget(); ?>
    </div>
        