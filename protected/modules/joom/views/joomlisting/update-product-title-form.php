<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>


<div class="pageContent">
    <form method="post" action="<?php echo Yii::app()->createUrl('joom/joomlisting/updateproducttitle');?>"
          class="pageForm
    required-validate"
          onsubmit="return validateCallback(this, navTabAjaxDone);">
        <input type="hidden" value="<?php echo $listing['id']?>" name="id">
        <div class="pageFormContent update-product-form" layoutH="97">
            <p>
               <?php echo $listing['name']?>
            </p>
            <p>
                <label><?php echo Yii::t('joom', 'New Title');?></label>
                <input name="name" class="required" type="text" size="50"/>
            </p>
        </div>
        <div class="formBar">
            <ul>
                <!--<li><a class="buttonActive" href="javascript:;"><span>保存</span></a></li>-->
                <li>
                    <div class="buttonActive">
                        <div class="buttonContent">
                            <button type="submit"><?php echo Yii::t('joom', 'Save')?></button>
                        </div>
                    </div>
                </li>
                <li>
                    <div class="button">
                        <div class="buttonContent">
                            <button type="button" class="close"><?php echo Yii::t('joom', 'Cancel')?></button>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </form>
</div>

<style>
    .update-product-form p{
        width:100%;
    }
</style>