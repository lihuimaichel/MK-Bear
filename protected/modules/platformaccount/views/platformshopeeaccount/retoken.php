<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent">
    <form method="post" action="<?php echo Yii::app()->createUrl('platformaccount/platformshopeeaccount/updatetoken')
    ;?>"
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
            <dt><?php echo Yii::t('platformaccount', 'Client Secret');?></dt>
            <dd>
                <input type="text" name="client_secret" value="<?php echo $accountInfo['client_secret']?>"
                class="required"/>

            </dd>
        </dl>



        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Partner name');?></dt>
            <dd>
                <input type="text" name="partner_name" class="required" value="<?php echo $accountInfo['partner_name']?>"/>

            </dd>
        </dl>


        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Partner ID');?></dt>
            <dd>
                <input type="text" name="partner_id" class="required digits" value="<?php echo $accountInfo['partner_id']?>"/>

            </dd>
        </dl>

        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Access Token');?></dt>
            <dd>
                <input type="text" name="access_token"  value="<?php echo $accountInfo['access_token']?>"/>

            </dd>
        </dl>
        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Service Url');?></dt>
            <dd>
                <input type="text" name="service_url"  value="<?php echo $accountInfo['service_url']?>"/>

            </dd>
        </dl>
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

