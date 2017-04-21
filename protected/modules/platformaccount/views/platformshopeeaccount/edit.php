<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent">
    <form method="post" action="<?php echo Yii::app()->createUrl('platformaccount/platformshopeeaccount/update');?>"
          class="pageForm required-validate"
    onsubmit="return validateCallback
    (this, dialogAjaxDone)">
    <input type="hidden" name="id" value="<?php echo $accountInfo['id']?>">
    <div class="pageFormContent nowrap" layoutH="97">
        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Account Name');?></dt>
            <dd>
                <label><?php echo $accountInfo['account_name']?></label>
            </dd>
        </dl>
        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Site Code');?></dt>
            <dd>

                <label><?php echo $accountInfo['site_code']?></label>
            </dd>
        </dl>
        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Short Name');?></dt>
            <dd>
                <input type="text" name="short_name" class="required" value="<?php echo $accountInfo['short_name']?>" />

            </dd>
        </dl>

        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Account status');?></dt>
            <dd>
                <select name="status" required class="required">
                    <option value=""><?php echo Yii::t('platformaccount', 'Please select');?></option>
                    <?php foreach($statusList as $status=> $text) :?>
                        <option value="<?php echo $status?>" <?php if ($status == $accountInfo['status']):?>
                        selected<?php endif;?>><?php
                            echo
                        $text?></option>
                    <?php endforeach;?>
                </select>

            </dd>
        </dl>
        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Department');?></dt>
            <dd>
                <select name="department" required class="required">
                    <option value=""><?php echo Yii::t('platformaccount', 'Please select');?></option>
                    <?php foreach($departmentList as $id=> $name) :?>
                        <option value="<?php echo $id?>"<?php if ($id == $accountInfo['department_id']):?>
                            selected<?php endif;?>><?php echo $name?></option>
                    <?php endforeach;?>
                </select>

            </dd>
        </dl>
        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Shop ID');?></dt>
            <dd>

                <label><?php echo $accountInfo['shop_id']?></label>
            </dd>
        </dl>

            <dl>
            <dt><?php echo Yii::t('platformaccount', 'Open time');?></dt>
            <dd>
                <input type="text" value="<?php echo $accountInfo['open_time']?>"  name="open_time" class="required date
                textInput" required readonly>
            </dd>
        </dl>
        <!--


        <dl>
            <dt><?php /*echo Yii::t('platformaccount', 'Partner name');*/?></dt>
            <dd>
                <input type="text" name="partner_name" class="required" value="<?php /*echo $accountInfo['partner_name']*/?>"/>

            </dd>
        </dl>


        <dl>
            <dt><?php /*echo Yii::t('platformaccount', 'Partner ID');*/?></dt>
            <dd>
                <input type="text" name="partner_id" class="required" value="<?php /*echo $accountInfo['partner_id']*/?>"/>

            </dd>
        </dl>

        <dl>
            <dt><?php /*echo Yii::t('platformaccount', 'Client Secret');*/?></dt>
            <dd>
                <label><?php /*echo $accountInfo['client_secret']*/?></label>
            </dd>
        </dl>-->

    </div>
    <div class="formBar">
        <ul>
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">
                        <button type="submit"><?php echo Yii::t('system', 'Save');?></button>
                    </div>
                </div>
            </li>
            <li>
                <div class="button">
                    <div class="buttonContent">
                        <button type="button" class="close"><?php echo Yii::t('system', 'Close');?></button>
                    </div>
                </div>
            </li>
        </ul>
    </div>
    </form>
</div>

<style>
    .pageFormContent dl.nowrap dd, .nowrap dd{width: 400px;}
</style>
<script type="text/javascript">
</script>