<div class="pageContent">
    <div class="tabs" eventType="click">
        <div class="tabsHeader">
            <div class="tabsHeaderContent">
                <ul>
                    <?php foreach ($platforms as $code => $name): ?>
                        <li><a href="javascript:;"><span><?php echo $name; ?></span></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="tabsContent" style="height:150px;">
            <?php foreach ($platforms as $code => $name): ?>
                <div>

                    <?php foreach ($titles[$code] as $title): ?>
                        <p>
                            <label>
                                <?php echo $title ?>
                            </label>
                            <button type="button" class="btn btn-title-lookup"
                                    data-title="<?php echo str_replace('"', '\"',
                                        $title);
                                    ?>"><?php echo Yii::t('wish', 'Select');?>
                            </button>
                        </p>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="tabsFooter">
            <div class="tabsFooterContent"></div>
        </div>
    </div>
</div>

<script>
    $(function () {
        //baseinfo[38][subject]
        var account = '<?php echo $account;?>';
        $('.btn-title-lookup').click(function () {
            var title = $(this).data('title');
            if (title) {
                var titleField = $('#wish-title-tags-lookup input[name="baseinfo['+account+'][subject]"');
                var backupTitle = $('#wish-title-tags-lookup #backup-title-'+account+':hidden');
                if (backupTitle) {
                    backupTitle.html(titleField.val()).show();
                }
                titleField.val(title);
            }
            $.pdialog.close(($(this).parents('div.dialog')));
        });

    })
</script>