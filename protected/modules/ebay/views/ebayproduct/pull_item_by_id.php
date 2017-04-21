<?php
/**
 * @desc 拉取Ebay Item
 * @author ketu.lai
 * @date 2017/02/20
 */
?>

<div class="pageContent">
    <form method="post" action="<?php echo Yii::app()->createUrl($this->route); ?>" class="pageForm required-validate"
          onsubmit="return validateCallback(this, dialogAjaxDone)">

        <div class="pageFormContent" layoutH="97">
            <div class="col-md-12">
                <label><?php echo Yii::t('ebay', 'Please select account') ?></label>

                <input name="id" type="hidden">
                <input class="required" disabled name="storeName"  readonly type="text">
                <a class="btnLook" href="<?php echo Yii::app()->createUrl('ebay/ebayaccount/accountlookup'); ?>"
                   lookupGroup="">&nbsp;</a>


            </div>
            <div class="divider"></div>
            <div class="col-md-12"><?php echo Yii::t("ebay", "Note: Pull all listing from account will take lots time");?></div>
            <div class="divider"></div>
            <div class="col-md-12">

                <div style="clear:both; margin-top: 20px;">
                    <table class="list nowrap itemDetail"
                           addButton="<?php echo Yii::t('ebay', 'Add more input fields'); ?>" width="100%">
                        <thead>
                        <tr>
                            <th type="text" name="items[]" size="12"
                                fieldClass="digits"><?php echo Yii::t("ebay", 'Item ID') ?></th>
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
                            id="ebay-push-item-btn"><?php echo Yii::t('ebay', 'Pull') ?></button>
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