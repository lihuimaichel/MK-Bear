<?php
/**
 * Created by PhpStorm.
 * User: laigc
 * Date: 2017/3/8
 * Time: 15:27
 */
?>

<div class="pageContent">
    <form method="post" action="<?php echo Yii::app()->createUrl($this->route); ?>" class="pageForm required-validate"
          onsubmit="return validateCallback(this, dialogAjaxDone)">

        <div class="pageFormContent" layoutH="97">
            <div class="col-md-12">
                <label><?php echo Yii::t('shopee', 'Please select account') ?></label>
                <select name="accountId" class="select" required>
                    <option value=""><?php echo Yii::t('system', 'Select'); ?></option>
                    <?php foreach ($accountList as $account): ?>
                        <option value="<?php echo $account['id']; ?>"><?php echo $account['short_name']; ?></option>
                    <?php endforeach; ?>
                </select>


            </div>
            <div class="divider"></div>
            <div class="col-md-12"><?php echo Yii::t("shopee", "Note: Pull all listing from account will take lots time"); ?></div>
            <div class="divider"></div>
            <div class="col-md-12">

                <div style="clear:both; margin-top: 20px;">
                    <table class="list nowrap itemDetail"
                           addButton="<?php echo Yii::t('shopee', 'Add more input fields'); ?>" width="100%">
                        <thead>
                        <tr>
                            <th type="text" name="items[]" size="12"
                                fieldClass="digits"><?php echo Yii::t("shopee", 'Item ID') ?></th>
                            <th type="del" width="60">&nbsp;</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr style="background: none !important;">

                            <td><input type="text" name="items[]" size="12"
                                       class="digits textInput"></td>
                            <td>&nbsp;</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        <div class="formBar">
            <div class="buttonActive pull-left">
                <div class="buttonContent pull-left">
                    <button type="submit"
                            id="shopee-push-item-btn"><?php echo Yii::t('shopee', 'Pull') ?></button>
                </div>
            </div>
        </div>
    </form>
</div>


<style>
    span.error {
        top: 10px;
        left: 290px;
    }
</style>