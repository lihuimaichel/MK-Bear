<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent">
    <form method="post" action="<?php echo Yii::app()->createUrl('platformaccount/platformshopeeaccount/save');?>"
          class="pageForm required-validate"
    onsubmit="return validateCallback
    (this, dialogAjaxDone)">

    <div class="pageFormContent nowrap" layoutH="97">
        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Account Name');?></dt>
            <dd>
                <input type="text" name="name" class="required"/>

            </dd>
        </dl>
        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Short Name');?></dt>
            <dd>
                <input type="text" name="short_name" class="required" />

            </dd>
        </dl>
        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Site Code');?></dt>
            <dd>
                 <select name="site" required class="required">
                     <option value=""><?php echo Yii::t('platformaccount', 'Please select');?></option>
                    <?php foreach($siteList as $site) :?>
                     <option value="<?php echo $site?>"><?php echo $site?></option>
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
                        <option value="<?php echo $id?>"><?php echo $name?></option>
                    <?php endforeach;?>
                </select>

            </dd>
        </dl>
        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Status');?></dt>
            <dd>
                <select name="status" required class="required">
                    <option value=""><?php echo Yii::t('platformaccount', 'Please select');?></option>
                    <?php foreach($statusList as $status=> $text) :?>
                        <option value="<?php echo $status?>"><?php echo $text?></option>
                    <?php endforeach;?>
                </select>

            </dd>
        </dl>

        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Shop ID');?></dt>
            <dd>
                <input type="text" name="shop_id" class="required digits"/>

            </dd>
        </dl>

        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Partner name');?></dt>
            <dd>
                <input type="text" name="partner_name" class="required"/>

            </dd>
        </dl>


        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Partner ID');?></dt>
            <dd>
                <input type="text" name="partner_id"  class="required digits"/>

            </dd>
        </dl>

        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Client Secret');?></dt>
            <dd>
                <input type="text" name="client_secret" class="required"/>

            </dd>
        </dl>
        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Open time');?></dt>
            <dd>
                <input type="text" name="open_time" class="required date textInput" required readonly>
            </dd>
        </dl>
        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Access Token');?></dt>
            <dd>
                <input type="text" name="access_token"  value=""/>

            </dd>
        </dl>
        <dl>
            <dt><?php echo Yii::t('platformaccount', 'Service Url');?></dt>
            <dd>
                <input type="text" name="service_url"  value=""/>

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
