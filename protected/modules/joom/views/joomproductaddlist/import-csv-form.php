<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageFormContent" layoutH="97">
    <div class="col-md-12">
        <div class="pageHeader" style="border:1px #B8D0D6 solid">
            <form id="pagerForm"
                  class="pageForm required-validate" onsubmit="return formValidate(this);"
                  enctype="multipart/form-data"
                  action="<?php echo $this->createUrl('joomproductaddlist/uploadcsv') ?>"
                  method="post">
                <div style="padding: 50px 0;">
                    <label>File:</label><input type="file" class="textInput" id="upload-file" name="file" rows="10">
                    <button type="submit" style="margin-left: 20px; padding: 5px;" class="btn"
                            id="ebay-push-item-btn"><?php echo Yii::t('shopee', 'Import') ?></button>
                </div>


            </form>
        </div>

    </div>



</div>
