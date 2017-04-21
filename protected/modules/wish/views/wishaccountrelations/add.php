<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent">
    <form method="post" action="<?php echo Yii::app()->createUrl('wish/wishaccountrelations/save');?>"
          class="pageForm required-validate"
    onsubmit="return validateCallback
    (this, dialogAjaxDone)">

    <div class="pageFormContent nowrap" layoutH="97">
        <dl>
            <dt><?php echo Yii::t('wish', 'Department');?></dt>
            <dd>
                <select id="accountRelationDepartments" name="department" required class="required">
                    <option value=""><?php echo Yii::t('wish', 'Please select');?></option>
                    <?php foreach($departments as $id=> $name) :?>
                        <option value="<?php echo $id?>"><?php echo $name?></option>
                    <?php endforeach;?>
                </select>
            </dd>
        </dl>
        <dl>
            <dt><?php echo Yii::t('wish', 'Account');?></dt>
            <dd>
                 <select name="account" id="accountRelationAccounts" required class="required">
                     <option value=""><?php echo Yii::t('wish', 'Please select');?></option>
                    <?php foreach($accounts as $id=> $account) :?>
                     <option value="<?php echo $id?>"><?php echo $account?></option>
                     <?php endforeach;?>
                 </select>
            </dd>
        </dl>
        <dl>
            <dt><?php echo Yii::t('wish', 'Period');?></dt>
            <dd>
                <select name="datePeriod" required class="required">
                    <option value=""><?php echo Yii::t('wish', 'Please select');?></option>
                    <?php foreach($datePeriod as $k=> $v) :?>
                        <option value="<?php echo $k;?>"><?php echo $v;?></option>
                    <?php endforeach;?>
                </select>
            </dd>
        </dl>
        <dl>
            <dt><?php Yii::t('wish', 'Seller list')?></dt>
            <dd id="accountRelationSellers">

            </dd>
        </dl>
    </div>
    <div class="formBar">
        <ul>
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">
                        <button type="submit"><?php echo Yii::t('wish', 'Save');?></button>
                    </div>
                </div>
            </li>
            <li>
                <div class="button">
                    <div class="buttonContent">
                        <button type="button" class="close"><?php echo Yii::t('wish', 'Close');?></button>
                    </div>
                </div>
            </li>
        </ul>
    </div>
    </form>
</div>

<script>
    $(function () {
       $('#accountRelationDepartments').change(function (o){
            var departmentId = $(o.target).val();
            if (!departmentId) {
                return false;
            }
           var accountRelationAccounts = $('#accountRelationAccounts');

            accountRelationAccounts.find('option').each(function(i, e){
                if ($(e).val()) {
                    $(e).remove();
                }
            });
           var accountRelationSellers = $('#accountRelationSellers');
           accountRelationSellers.html('');
            var url = '<?php echo Yii::app()->createUrl('wish/wishaccountrelations/accountList', array
            ('departmentId'=> 'objectId'));?>';
            url = url.replace('objectId', departmentId);
            $.getJSON(url, function (resp){
                if (resp.statusCode != 200 ) {
                    alertMsg.error(resp.message);
                    return false;
                }
                if (resp.accountList) {
                    $.each(resp.accountList, function (i, e) {
                        $('<option>').val(e.id).text(e.short_name).appendTo(accountRelationAccounts);
                    });
                }
                if (resp.sellerList) {
                    $.each(resp.sellerList, function (i, e) {
                        accountRelationSellers.append($('<label>').text(e.user_full_name).append(
                            $('<input>').attr('type', 'checkbox')
                                .attr('required', 'required')
                                .attr('name', 'seller[]')
                                .val(e.id)
                        ));
                    });
                }

                if (resp.relationList) {
                    accountRelationAccounts.change(function(t) {
                        var accountId = $(t.target).val();

                        if (resp.relationList[accountId]) {
                            var existsSellerId = resp.relationList[accountId];
                            if (existsSellerId) {
                                accountRelationSellers.find('input[type="checkbox"]').each(function (k, e) {
                                    var id = $(e).val();
                                    if ($.inArray(id, existsSellerId) != -1) {
                                        $(e).attr('disabled', 'disabled');
                                    }
                                });
                            }
                        }
                    });
                }
            });
       });
    });
</script>
