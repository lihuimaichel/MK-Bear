<div class="pageContent" style="width: 800px;">


    <div class="pageFormContent" layoutH="97">
        <?php foreach ($tags as $tag): ?>
            <label style="float:left;">
                <input type="checkbox" name="tags-suggestion" value="<?php echo $tag ?>">
                <?php echo $tag ?>
            </label>
        <?php endforeach; ?>
    </div>
    <div class="formBar">
        <ul>
            <!--<li><a class="buttonActive" href="javascript:;"><span>保存</span></a></li>-->

            <li>
                <div class="buttonActive">
                    <div class="buttonContent">
                        <button type="button" class="btn-tags-lookup-all"><?php echo Yii::t('wish', 'Set to all account') ?></button>
                    </div>
                </div>
            </li>
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">
                        <button type="button" class="btn-tags-lookup"><?php echo Yii::t('wish', 'Select') ?></button>
                    </div>
                </div>
            </li>
            <li>
                <div class="button">
                    <div class="buttonContent">
                        <button type="button" class="close"><?php echo Yii::t('wish', 'Cancel') ?></button>
                    </div>
                </div>
            </li>
        </ul>
    </div>


</div>

<script>
    $(function () {
        //baseinfo[38][subject]
        var targetAccount = '<?php echo $account;?>';
        $('.btn-tags-lookup').click(function () {
            var checkedTags = $('input[name="tags-suggestion"]:checked');
            if (checkedTags.length > 0) {
                rebuildTags(targetAccount);
                $.pdialog.close(($(this).parents('div.dialog')));
            }
        });

        $('.btn-tags-lookup-all').click(function (){
            var checkedTags = $('input[name="tags-suggestion"]:checked');
            if (checkedTags.length > 0) {
                rebuildTags();
                $.pdialog.close(($(this).parents('div.dialog')));
            }
        });


        function rebuildTags(accountIdField)
        {
            var maxLimit = 10;
            var checkedTags = $('input[name="tags-suggestion"]:checked');
            var allTags = $('input[name="tags-suggestion"]');
            var accountFields = [];
            var allAccountFields = $('#wish-title-tags-lookup input[name="account_id[]"]');
            if (accountId) {
                for(var a=0; a< allAccountFields.length; a++) {
                    if ($(allAccountFields[a]).val() == accountId) {
                        accountFields.push($(allAccountFields[a]));
                    }
                }
            } else {
                accountFields = allAccountFields;
            }
            var randomIntFromInterval = function(min,max) {
                return Math.floor(Math.random() * (max - min + 1)) + min;
            };

            for(var i=0; i<accountFields.length; i++) {
                var inputTags = [];
                var accountId = $(accountFields[i]).val();
                var inputFields = $('#wish-title-tags-lookup input[name="baseinfo['+accountId+'][tags][]"]');
                if (checkedTags.length< maxLimit) {
                    for (var j = 0; j < inputFields.length; j++) {
                        var tagValue = $(inputFields[j]).val();
                        if (checkedTags[j]) {
                            tagValue = $(checkedTags[j]).val();
                        }
                        inputTags.push(tagValue);
                    }
                    if (inputTags.length > 0) {
                        inputTags = $.grep(inputTags, function (el, index) {
                            return index === $.inArray(el, inputTags);
                        });
                        //inputTags  = uniqueInputTags;
                    }

                    if (maxLimit - inputTags.length > 0) {

                        var unCheckedTags = [];
                        for (var k = 0; k < allTags.length; k++) {
                            if ($.inArray($(allTags[k]).val(), inputTags) === -1) {
                                unCheckedTags.push($(allTags[k]).val());
                            }
                        }

                        if (unCheckedTags.length > 0) {
                            var inputTagsLength = inputTags.length;

                            for (var r = 0; r < maxLimit - inputTagsLength; r++) {
                                var it = randomIntFromInterval(0, unCheckedTags.length);
                                //console.log(unCheckedTags[it]);
                                inputTags.push(unCheckedTags[it]);
                                unCheckedTags.splice(it, 1);
                            }
                        }
                    }
                }
                for(var f=0; f < inputFields.length; f++) {
                    if (!inputTags[f]) {
                        break;
                    }
                    $(inputFields[f]).val(inputTags[f]);
                }
            }
            //return inputTags;
        }

    })
</script>