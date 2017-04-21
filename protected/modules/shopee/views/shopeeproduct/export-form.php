<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageFormContent" layoutH="97">
    <div class="col-md-12">
        <div class="pageHeader" style="border:1px #B8D0D6 solid; padding: 50px 0;">
            <form id="pagerForm"
                  class="pageForm required-validate" onsubmit="return formValidate(this);"
                  enctype="multipart/form-data"
                  action="<?php echo $this->createUrl('shopeeproduct/exportlisting') ?>"
                  method="post">
                <div>
                    <label><?php echo Yii::t('shopee', 'CSV File');?>:</label><input type="file" class="textInput"
                                                                         id="upload-file"
                                                                 name="file" rows="10">

                </div>
                <div>
                    <label><?php echo Yii::t('shopee', 'Export Format')?>:</label>
                    <select name="format">
                        <option value="default"><?php echo Yii::t('shopee', 'Default Format')?></option>
                        <option value="shopee"><?php echo Yii::t('shopee', 'Shopee Format')?></option>
                    </select>
                </div>
                <div>
                    <button type="submit" style="margin-left: 20px; padding: 5px;" class="btn"
                            id="ebay-push-item-btn"><?php echo Yii::t('shopee', 'Export') ?></button>
                </div>




            </form>
        </div>

    </div>


</div>
