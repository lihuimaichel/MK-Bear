<?php
/**
 * Created by PhpStorm.
 * User: laigc
 * Date: 2017/3/8
 * Time: 15:27
 */
?>


<div class="pageContent">
    <form method="post" action="<?php echo Yii::app()->createUrl('shopee/shopeeproduct/batchupdate'); ?>"
          class="pageForm required-validate"
          onsubmit="return validateCallback(this, dialogAjaxDone)">
        <input type="hidden" name="action" value="<?php echo $action ?>">
        <div class="pageFormContent container batch-update-form" layoutH="128">
            <div class="row">
                <div class="col-md-2">
                    <input type="text" class="number" id="batch-set-value">
                </div>
                <div class="col-md-2">
                    <a class="button edit" onclick="batchSetValue();" href="javascript:void(0)"><span><?php echo Yii::t('shopee', 'Batch Set');?></span></a>
                </div>
            </div>

            <div class="row">
                <?php if ('updatePrice' == $action): ?>
                    <table class="list" width="98%">
                        <thead>
                        <tr>

                            <th><?php echo Yii::t('shopee', 'Product Name') ?></th>
                            <th width="150">&nbsp;</th>
                            <th width="150"><?php echo Yii::t('shopee', 'New Price') ?></th>

                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($listings as $listing): ?>

                            <tr>
                                <td>
                                <?php echo $listing['sku']?> --- <?php echo $listing['name'] ?> <?php if
                                ($listing['variation_option_name']): ?> - <?php echo $listing['variation_option_name'] ?><?php endif; ?>
                                </td>
                                <td>
                                    <?php echo Yii::t('shopee', 'Current Price') ?>
                                    : <?php echo $listing['currency']?><?php echo $listing['price']; ?> </td>
                                <td>
                                    <input style="float:none;" type="text" name="data[<?php echo $listing['variation_id']?>][value]" class="required number"/>
                                    <input type="hidden" name="data[<?php echo $listing['variation_id']?>][isVariant]" value="<?php echo $listing['has_variation']?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        </tbody>
                    </table>


                <?php elseif ('updateStock' == $action): ?>

                    <table class="list" width="98%">
                        <thead>
                        <tr>

                            <th><?php echo Yii::t('shopee', 'Product Name') ?></th>
                            <th width="150">&nbsp;</th>
                            <th width="150"><?php echo Yii::t('shopee', 'New Stock') ?></th>

                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($listings as $listing): ?>
                            <tr>
                                <td>
                                    <?php echo $listing['sku']?> --- <?php echo $listing['name'] ?> <?php if
                                    ($listing['variation_option_name']): ?> - <?php echo $listing['variation_option_name'] ?><?php endif; ?>
                                </td>
                                <td>
                                    <?php echo Yii::t('shopee', 'Current Stock') ?>
                                    : <?php echo $listing['stock']; ?> </td>
                                <td>
                                    <input style="float:none;" type="text" name="data[<?php echo $listing['variation_id']?>][value]" class="required digits"/>
                                    <input type="hidden" name="data[<?php echo $listing['variation_id']?>][isVariant]" value="<?php echo $listing['has_variation']?>">
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
                            id="ebay-push-item-btn"><?php echo Yii::t('shopee', 'Update') ?></button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    function batchSetValue()
    {
        var batchValue = $("#batch-set-value").val();
        if ($.isNumeric(batchValue)) {
            $('.batch-update-form table tr td input[name$="[value]"]').each(function (i, el) {
                this.value = batchValue;
            });
        }
    }
</script>