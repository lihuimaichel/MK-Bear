<?php
/**
 * Created by PhpStorm.
 * User: laigc
 * Date: 2017/3/8
 * Time: 15:27
 */
?>


<div class="pageContent">
    <form method="post" action="<?php echo Yii::app()->createUrl('shopee/shopeeproduct/update'); ?>" class="pageForm required-validate"
          onsubmit="return validateCallback(this, dialogAjaxDone)">
        <input type="hidden" name="id" value="<?php echo $itemInfo['id']?>">
        <input type="hidden" name="action" value="<?php echo $action?>">
        <input type="hidden" name="isVariant" value="<?php echo $isVariant;?>">
        <div class="pageFormContent" layoutH="97">
            <div class="col-md-12">
                <label><?php echo Yii::t('shopee', 'Product Name') ?>:</label>
                <h3><?php echo $itemInfo['sku']?> --- <?php echo $itemInfo['name']?> <?php if (isset
                ($itemInfo['variation_option_name']))
                    :?> - <?php echo $itemInfo['variation_option_name']?><?php endif;?></h3>
            </div>
            <div class="divider"></div>
            <div class="col-md-12">
               <?php if ('updatePrice' ==$action):?>
                <dl>
                    <dt><?php echo Yii::t('shopee', 'Current Price')?>:   </dt>
                    <dd>
                        <label><?php echo $itemInfo['currency']?> <?php echo $itemInfo['price']?></label>
                    </dd>
                </dl>
                <dl>
                    <dt><?php echo Yii::t('shopee', 'New Price')?></dt>
                    <dd>
                        <input type="text" name="price" class="required number"/>
                    </dd>
                </dl>
                <?php elseif('updateStock' == $action):?>
                <dl>
                    <dt><?php echo Yii::t('shopee', 'Current Stock')?>:   </dt>
                    <dd>
                        <label><?php echo $itemInfo['stock']?></label>
                    </dd>
                </dl>
                <dl>
                    <dt><?php echo Yii::t('shopee', 'New Stock')?></dt>
                    <dd>
                        <input type="text" name="stock" class="required digits"/>

                    </dd>
                </dl>
                <?php endif;?>


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

