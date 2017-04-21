<?php
/**
 * Created by PhpStorm.
 * User: laigc
 * Date: 2017/3/8
 * Time: 15:27
 */
?>


<div class="pageContent">
    <form method="post" action="<?php echo Yii::app()->createUrl('joom/joomlisting/batchupdate'); ?>"
          class="pageForm required-validate"
          onsubmit="return validateCallback(this, dialogAjaxDone)">
        <input type="hidden" name="action" value="<?php echo $action ?>">
        <div class="pageFormContent container batch-update-form" layoutH="128">
            <?php if ('updatePrice' == $action): ?>
            <div class="row">
                <div class="col-md-2">
                    <input type="text" class="number" id="batch-set-profit"
                           placeholder="<?php echo Yii::t('joom', 'Profit Rate'); ?>">
                </div>
                <div class="col-md-2">
                    <a class="button edit" onclick="batchSetWithProfitRate();"
                       href="javascript:void(0)"><span><?php echo Yii::t('joom', 'Set with profit rate');
                       ?></span></a>
                </div>
            </div>
            <?php endif;?>
            <div class="row">

                <div class="col-md-2">
                    <input type="text" class="number" id="batch-set-value"">
                </div>
                <div class="col-md-2 col-md-offset-1">
                    <a class="button edit" onclick="batchSetValue();"
                       href="javascript:void(0)"><span><?php echo Yii::t('joom', 'Batch Set'); ?></span></a>
                </div>
            </div>

            <div class="row">
                <?php if ('updatePrice' == $action): ?>
                    <table class="list" width="98%">
                        <thead>
                        <tr>

                            <th><?php echo Yii::t('joom', 'Product Name') ?></th>
                            <th width="150">&nbsp;</th>
                            <th width="150"><?php echo Yii::t('joom', 'New Price') ?></th>

                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($listings as $listing): ?>

                            <tr>
                                <td>
                                    <?php echo $listing['sub_sku'] ?>  ---   <?php echo $listing['name'] ?> <?php if ($listing['variant_option_name']): ?> - <?php echo $listing['variant_option_name'] ?><?php endif; ?>
                                </td>
                                <td>
                                    <?php echo Yii::t('joom', 'Current Price') ?>
                                    : <?php echo $listing['currency'] ?><?php echo $listing['price']; ?> </td>
                                <td>
                                    <input style="float:none;" type="text"
                                           name="data[<?php echo $listing['variant_id'] ?>][value]"
                                           class="required number" data-target="<?php echo $listing['sub_sku']?>"/>

                                </td>
                            </tr>
                        <?php endforeach; ?>

                        </tbody>
                    </table>


                <?php elseif ('updateStock' == $action): ?>

                    <table class="list" width="98%">
                        <thead>
                        <tr>

                            <th><?php echo Yii::t('joom', 'Product Name') ?></th>
                            <th width="150">&nbsp;</th>
                            <th width="150"><?php echo Yii::t('joom', 'New Stock') ?></th>

                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($listings as $listing): ?>
                            <tr>
                                <td>
                                <?php echo $listing['sub_sku'] ?>  --- <?php echo $listing['name'] ?> <?php if
                                    ($listing['variant_option_name']):
                                        ?> - <?php echo $listing['variant_option_name'] ?><?php endif; ?>
                                </td>
                                <td>
                                    <?php echo Yii::t('joom', 'Current Stock') ?>
                                    : <?php echo $listing['inventory']; ?> </td>
                                <td>
                                    <input style="float:none;" type="text"
                                           name="data[<?php echo $listing['variant_id'] ?>][value]"
                                           class="required digits"/>

                                </td>
                            </tr>
                        <?php endforeach; ?>

                        </tbody>
                    </table>
                <?php endif; ?>


            </div>


        </div>
        <div class="formBar">
            <div class="buttonActive pull-left">
                <div class="buttonContent pull-left">
                    <button type="submit"
                            id="ebay-push-item-btn"><?php echo Yii::t('joom', 'Update') ?></button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    function batchSetValue() {
        var batchValue = $("#batch-set-value").val();
        if ($.isNumeric(batchValue)) {
            $('.batch-update-form table tr td input[name$="[value]"]').each(function (el) {
                this.value = batchValue;
            });
        }
    }

    function batchSetWithProfitRate(){
        var batchValue = $("#batch-set-profit").val();
        if ($.isNumeric(batchValue)) {

            var url =  '<?php echo Yii::app()->createUrl('joom/joomlisting/CalculateSalePrice');?>';
            $('.batch-update-form table tr td input[name$="[value]"]').each(function (i, el) {
                //this.value = batchValue;
                var targetInput = this;
                var sku = $(el).data('target');

                if (sku) {
                    $.getJSON(
                        url,
                        {
                            sku:sku,
                            profit:batchValue
                        },
                        function (resp){
                            $(targetInput).val(parseFloat(resp.salePrice));
                        }
                    );
                }
            });
        }
    }
</script>