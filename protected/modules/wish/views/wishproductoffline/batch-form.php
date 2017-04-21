<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent">
    <div id="jbsxBox2" class="unitBox" style="float:left; display:block; overflow:auto; width:514px;">
        <div class="pageHeader" style="border:1px #B8D0D6 solid">
            <form id="pagerForm"
                  class="pageForm required-validate" onsubmit="return formValidate(this);"
                  enctype="multipart/form-data"
                  action="<?php echo $this->createUrl('wishproductoffline/searchlisting') ?>"
                  method="post">
                <div class="searchBar">
                    <table class="searchContent" width="100%">
                        <tr>
                            <td>
                                <?php echo Yii::t('wish', 'Account') ?></td>
                            <td><select name="account" id="search-account">
                                    <option value=""><?php echo Yii::t('wish', 'Please select'); ?></option>
                                    <?php foreach ($accountList as $account): ?>

                                        <option value="<?php echo $account['id'] ?>"><?php echo $account['account_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php echo Yii::t('wish', 'Warehouse') ?></td>
                            <td><select name="warehouse" id="search-warehouse">
                                    <option value=""><?php echo Yii::t('wish', 'Please select'); ?></option>
                                    <?php foreach ($warehouseList as $id => $warehouse): ?>

                                        <option value="<?php echo $id ?>"><?php echo $warehouse ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php echo Yii::t('wish', 'SKU') ?></td>
                            <td><textarea class="textInput" name="sku" rows="10" id="input-sku"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>
                                <?php echo Yii::t('wish', 'Upload CSV file') ?></td>
                            <td><input type="file" class="textInput" id="upload-file" name="file" rows="10">
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>
                                <div class="buttonActive">
                                    <div class="buttonContent">
                                        <button type="submit"><?php echo Yii::t('wish', 'Search'); ?></button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </form>
        </div>
    </div>

    <div class="unitBox" style="margin-left:520px;">

        <div class="pageContent" style="border-left:1px #B8D0D6 solid;border-right:1px #B8D0D6 solid">
            <div class="panelBar pageContent-header hide">
                <ul class="toolBar">
                    <li><input type="checkbox" class="select-all"></li>
                    <li><a class="edit batch-offline" onclick="batchOffline()"
                           mask="true"><span><?php echo Yii::t('wish', 'Batch offline'); ?></span></a></li>
                </ul>
            </div>
            <div class="pageContent-list">

            </div>
        </div>
    </div>

</div>

<script>
    function setupResponse(resp) {
        $('.pageContent-header').show();
        $('.pageContent-list').html(resp);
        $('.select-all').attr('checked', false);// = false;
    }
    function formValidate(obj) {

        var accountId = $('#search-account').val();

        var sku = $('#input-sku').val();

        var uploadFile = $('#upload-file').val();

        if (!accountId && !sku && !uploadFile) {
            alertMsg.warn('<?php echo Yii::t('wish', 'Please select account or input sku or upload csv file')?>');
            return false;
        }

        return iframeCallback(obj, setupResponse);
    }
    $(function () {
        $('.select-all').click(function () {
            var checked = this.checked;
            $('.row-checkbox').each(function (i, e) {
                e.checked = checked;
            });
        });
    });

</script>

