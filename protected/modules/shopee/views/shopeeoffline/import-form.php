<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageFormContent" layoutH="97">
    <div class="col-md-12">
        <div class="pageHeader" style="border:1px #B8D0D6 solid">
            <form id="pagerForm"
                  class="pageForm required-validate" onsubmit="return validateForm(this);"
                  enctype="multipart/form-data"
                  action="<?php echo $this->createUrl('shopeeoffline/importexcludelist') ?>"
                  method="post">
                <div>

                    <label>File:</label><input type="file" class="textInput" id="upload-file" name="file" rows="10">
                    <button type="submit" style="margin-left: 20px; padding: 5px;" class="btn"
                            id="ebay-push-item-btn"><?php echo Yii::t('shopee', 'Import') ?></button>
                    <div class="divider"></div>
                    <label>SKU:</label><textarea cols="20" rows="10" name="sku" id="input-sku"></textarea>
                    <div class="divider"></div>


                    <div class="divider"></div>


                    <div class="">

                            <?php foreach($accountList as $id=> $account):?>
                                <label><input type="checkbox" required value="<?php echo $id?>"
                                                          name="account[]"><?php echo
                                        $account;?></label>
                            <?php endforeach;?>


                    </div>

                </div>


            </form>
        </div>

    </div>



</div>
<script>
    function validateForm(obj) {
        var sku = $(obj).find('#input-sku').val();
        var file = $(obj).find("#upload-file").val();
        var account = $(obj).find('input[name="account\[\]"]:checked');

        if (!file && !sku) {
            alertMsg.warn(
                '<?php echo Yii::t('shpee', 'Please input sku or import xls file')?>'
            );
            return false;

        }
        if (account.length == 0) {
            alertMsg.warn(
                '<?php echo Yii::t('shpee', 'Please select account')?>'
            );
            return false;
        }




        return iframeCallback(obj);
    }
</script>