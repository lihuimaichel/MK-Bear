<style>
    .pageFormContent #domestic label, .pageFormContent #international label {
        width: auto;
    }

    .pageFormContent #domestic select, .pageFormContent #international select {
        float: none;
    }

</style>
<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent">

    <div class="pageFormContent" layoutH="56">
        <div class="bg14 pdtb2 dot">
            <strong><?php echo Yii::t('system', 'Basic Information') ?></strong>
        </div>
        <div class="pd5" style="height:auto;">
            <form method="post" id="accountOrderForm" action="<?php echo Yii::app()->createUrl('/wish/wishorder/manualsaveorder');?>"
                  onsubmit="return validateCallback(this, dialogAjaxDone);">
            <fieldset>
                <legend><?php echo Yii::t('wish', 'Pull order with account'); ?></legend>
                <div class="row">

                    <div style="float: left; width: 50%;">
                    <label for="manualOrderSiteId">账号</label>
                    <select name="account_id" id="manualOrderSiteId" required>
                        <option value=""> 请选择</option>
                        <?php foreach (WishAccount::getIdNamePairs() as $key => $val): ?>
                            <option value="<?php echo $key; ?>">   <?php echo $val; ?>    </option>
                        <?php endforeach; ?>

                    </select>
                    </div>
                    <div style="float: left; width: 50%;">
                    <label style="margin-left: 50px;"><?php echo Yii::t('wish', 'Date period'); ?></label>
                    <select name="datePeriod" required>
                        <option value=""><?php echo Yii::t('wish', 'Please select date period') ?></option>
                        <?php foreach ($datePeriod as $date): ?>
                            <option value="<?php echo $date ?>"><?php echo $date ?></option>
                        <?php endforeach; ?>
                    </select>
                    </div>
                </div>
                <div class="pd5" style="height:auto;">
                    <div class="row" style="float: right; margin-top: 30px;">
                        <div class="buttonActive" style="margin-right:80px;">
                            <div class="buttonContent"><button  type="button"  class="saveBtn" onClick="saveOrderInfo
                            ('accountOrderForm', 1);"
                                                          href="javascript:void(0)">拉取订单</button></div>
                        </div>
                        <div class="buttonActive">
                            <div class="buttonContent"><button type="button" class="saveBtn" onClick="saveOrderInfo
                            ('accountOrderForm', 2);"
                                                          href="javascript:void(0)">拉取并同步oms</button></div>
                        </div>
                        <input type="hidden" value="1" id="accountOrderFormType" name="type_id">
                    </div>
                </div>
            </fieldset>
            </form>
            <form method="post" id="orderForm" action="<?php echo Yii::app()->createUrl('/wish/wishorder/manualsaveorder');?>"
                  onsubmit="return validateCallback(this, dialogAjaxDone);">
            <fieldset>
                <legend><?php echo Yii::t('wish', 'Pull order with order ID'); ?></legend>
                <div class="row">
                    <div style="float:left; width: 50%;">
                    <label for="manualOrderSiteId">账号</label>
                    <select name="account_id" id="manualOrderSiteId" required>
                        <option value=""> 请选择</option>
                        <?php foreach (WishAccount::getIdNamePairs() as $key => $val): ?>
                            <option value="<?php echo $key; ?>">   <?php echo $val; ?>    </option>
                        <?php endforeach; ?>

                    </select>
                    </div>
                    <div style="float:left; width: 50%;">
                    <label style="margin-left: 50px; for=" manualOrder">Order ID</label>
                    （wish平台上的订单ID，每个订单ID用半角逗号(,)隔开）<br>
                    <textarea name="order_id" id="manualOrder" cols="50" rows="6" required></textarea>
                    </div>
                </div>
                <div class="pd5" style="height:auto;">
                    <div class="row" style="float: right; margin-top: 30px;">
                        <div class="buttonActive" style="margin-right:80px;">
                            <div class="buttonContent"><button  type="button"  class="saveBtn" onClick="saveOrderInfo
                            ('orderForm', 1);"
                                                                href="javascript:void(0)">拉取订单</button></div>
                        </div>
                        <div class="buttonActive">
                            <div class="buttonContent"><button type="button" class="saveBtn" onClick="saveOrderInfo
                            ('orderForm', 2);"
                                                               href="javascript:void(0)">拉取并同步oms</button></div>
                        </div>
                        <input type="hidden" value="1" id="orderFormType" name="type_id">
                    </div>
                </div>
            </fieldset>
            </form>
        </div>

    </div>

</div>
<style>
    fieldset {
        padding: 20px;
        margin: 20px 0;
    }
    fieldset legend{
        padding: 10px;
    }
    span.error{
        display: inline-block;
        clear: both;
        position: relative;
        top:0;
        left:0;
    }
</style>
<script type="text/javascript">
    //保存刊登数据
    function saveOrderInfo(obj, type_id) {

        if (!type_id) {
            alertMsg.error("错误");
            return;
        }
        $("#"+obj+"Type").val(type_id);//赋值类型

        $('#'+obj).submit();
    }
</script>