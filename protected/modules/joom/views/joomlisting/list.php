<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
    'id' => 'joom_listing_widget',
    'filter' => $model,
    'dataProvider' => $model->search(),
    'selectableRows' => 2,
    'columns' => array(
        array(
            'class' => 'CCheckBoxColumn',
            'value' => '$data->id',
            'selectableRows' => 2,
            'htmlOptions' => array(
                'style' => 'width:20px',
                'align' => 'center',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
                'onclick' => 'allSelectJoom(this)',
            ),
            'checkBoxHtmlOptions' => array(
                'onchange' => 'checkSelect(this)',
                //'onclick'=>'checkSelect(this)',
                'oninput' => 'checkSelect(this)',
                'onpropertychange' => 'checkSelect(this)'
            )
        ),


        array(
            'name' => 'variants_id',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'name' => 'joom_varants_ids',
                'style' => 'width:20px',
                'type' => 'checkbox',
                'click_event' => 'checkSubSelect(this)',
                'disabled' => 'return $v["enabled"];'
            ),

        ),
        array(
            'name' => 'image',
            'value' => '$data->image',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'account_name',
            'value' => '$data->account_name',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'sku',
            'value' => 'CHtml::link($data->sku,"/products/product/viewskuattribute/sku/".$data->sku,
    				array("title"=>$data->sku,"style"=>"color:blue","target"=>"dialog","width"=>1100,"mask"=>true,"height"=>600))',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'parent_sku',
            'value' => '$data->parent_sku',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'online_sku',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'subsku',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px',
                'align' => 'center'
            )
        ),
        array(
            'name' => 'name',
            'value' => '$data->name',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:300px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'num_sold',
            'value' => '$data->num_sold_total',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:50px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'num_saves',
            'value' => '$data->num_saves_total',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:50px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),

        array(
            'name' => 'sale_property',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'inventory',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'price',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'profit_rate',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:50px',
                'align' => 'center',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
                'dd' => 'dd'
            ),
        ),
        array(
            'name' => 'shipping',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:50px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'msrp',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:50px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'review_status_text',
            'value' => '$data->review_status_text',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:60px'
            ),
        ),
        array(
            'name' => 'staus_text',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'date_uploaded',
            'value' => '$data->date_uploaded',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'oprator',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        )
    ),
    'orderBar' => array(
        'orderOptions' => array(
            'num_sold' => Yii::t('joom', 'Total Sold'),
            'num_saves' => Yii::t('joom', 'Total Saves'),
        )
    ),
    'toolBar' => array(
        array(
            'text' => Yii::t('joom_listing', 'Batch Offline'),
            'url' => 'javascript:void(0)',
            'htmlOptions' => array(
                'class' => 'delete',
                'onclick' => 'batchOffline()',
            ),
        ),
        array(
            'text' => Yii::t('amazon_product', 'Import CSV offline'),
            'url' => Yii::app()->createUrl('/joom/joomlisting/importcsvoffline'),
            'htmlOptions' => array(
                'class' => 'add',
                'target' => 'dialog',
                'mask' => true,
                'rel' => 'joom_listing_widget',
                'width' => '900',
                'height' => '600',
                'onclick' => '',
            )
        ),

        array(
            'text' => Yii::t('joom_listing', 'Create Encry Sku'),
            'url' => Yii::app()->createUrl('/joom/joomlisting/createsku'),
            'htmlOptions' => array(
                'class' => 'add',
                'target' => 'dialog',
                'mask' => true,
                'rel' => 'joom_listing_widget',
                'width' => '400',
                'height' => '260',
                'onclick' => '',
            )
        ),
        array(
            'text' => Yii::t('joom', 'Batch Update Price'),
            'url' => 'javascript:void(0)',
            'htmlOptions' => array(
                'class' => 'edit',
                'onclick' => 'batchUpdatePrice()',
            ),
        ),
        array(
            'text' => Yii::t('joom', 'Batch Update Stock'),
            'url' => 'javascript:void(0)',
            'htmlOptions' => array(
                'class' => 'edit',
                'onclick' => 'batchUpdateStock()',
            ),
        ),
    ),
    'pager' => array(),
    'tableOptions' => array(
        'layoutH' => 150,
        'tableFormOptions' => true
    )

));
?>


<script type="text/javascript">


    function allSelectJoom(obj) {
        var chcked = !!$(obj).find("input").attr("checked");
        $("input[name='joom_varants_ids[]']").not(":disabled").each(function () {
            this.checked = chcked;
        });
    }
    function checkSelect(obj) {
        console.log(obj);
        if (!!$(obj).attr('checked')) {
            $(obj).parents('tr').find("input[name='joom_varants_ids[]']").not(":disabled").each(function () {
                this.checked = true;
            });
        } else {
            $(obj).parents('tr').find("input[name='joom_varants_ids[]']").not(":disabled").each(function () {
                this.checked = false;
            });
        }
    }

    function checkSubSelect(obj) {
        var curstatus = !!$(obj).attr("checked");
        //查找当前级别下所有的checkbox的选中情况
        var l = $(obj).closest("table").find("tr input:checked").length;
        var parenObj = $(obj).closest("table").closest("tr").find("input:first");
        if (l > 0) {
            parenObj.attr("checked", true);
        } else {
            parenObj.attr("checked", false);
        }
    }

    /**
     * 下线
     */
    function offLine(obj, id) {
        var confirmMsg = '', url = '', t;
        t = $(obj).val();
        if (t == 'online') {
            url = '<?php echo Yii::app()->createUrl('joom/joomlisting/online/')?>';
            confirmMsg = '<?php echo Yii::t('amazon_product', 'Really want to onSelling these product?');?>';
        } else if (t == 'offline') {
            url = "<?php echo Yii::app()->createUrl('joom/joomlisting/offline');?>";
            confirmMsg = '<?php echo Yii::t('system', 'Really want to offline these product?');?>';
        } else {
            return false;
        }
        if (confirm(confirmMsg)) {
            var param = {id: id};
            $.post(url, param, function (data) {
                if (data.statusCode == '200') {
                    alertMsg.correct(data.message, data);
                } else {
                    alertMsg.error(data.message, data);
                }
            }, 'json');
        }
        return false;
    }

    /**
     * 批量下线
     */
    function batchOffline(t) {
        var noChkedMsg = confirmMsg = '', url = '';
        if (t == 'on') {
            noChkedMsg = "<?php echo Yii::t('amazon_product', 'Not Specify Sku Which Need To Active');?>";
            url = '<?php echo Yii::app()->createUrl('/joom/joomlisting/batchonselling/')?>';
            confirmMsg = '<?php echo Yii::t('amazon_product', 'Really want to onSelling these product?');?>';
        } else {
            noChkedMsg = "<?php echo Yii::t('amazon_product', 'Not Specify Sku Which Need To Inactive');?>";
            url = '<?php echo Yii::app()->createUrl('/joom/joomlisting/batchoffline/')?>';
            confirmMsg = '<?php echo Yii::t('system', 'Really want to offline these product?');?>';
        }
        //检测
        var chkednum = 1 * $("input[name='joom_varants_ids\[\]']:checked").length;
        if (chkednum <= 0 || chkednum == undefined) {
            alertMsg.error(noChkedMsg);
            return false;
        }
        /*进行确认操作*/
        if (confirm(confirmMsg)) {
            postData = $("input[name='joom_varants_ids[]']:checked").serialize();
            $.post(url, postData, function (data) {
                if (data.statusCode == '200') {
                    alertMsg.correct(data.message);
                } else {
                    alertMsg.error(data.message);
                }
            }, 'json');
        }
    }

    function modifyProductData(obj, targetType) {
        var targetObj = $('#' + $(obj).data('target'));
        var newValue = targetObj.val();
        if (!newValue) {
            return false;
        }
        var id = targetObj.data('target');
        $.post(
            '<?php echo Yii::app()->createUrl('/joom/joomlisting/modifyproductdata');?>',
            {
                id: id,
                value: newValue,
                target: targetType
            },
            function (resp) {
                resp = $.parseJSON(resp);
                alertMsg.correct(resp.message);
            }
        ).fail(function (resp) {
            resp = $.parseJSON(resp);
            alertMsg.error(resp.message);
        });
    }

    $(function () {

        setTimeout(function () {
            $('.show-profit-rate').each(function (i, e) {
                var container = $(e).parent();
                $.ajax({
                    method: 'GET',
                    url: $(e).attr('href'),
                    global: false,
                    dataType: 'json'
                }).then(function (resp) {
                    container.html(resp.data.profitRate + '%');
                });
            });
        }, 3000);
    });

    function batchUpdateStock() {
        var selectedIds = [];
        var selectedListing = $("input[name='joom_varants_ids[]']:checked");

        if (selectedListing.length == 0) {
            alertMsg.error('<?php echo Yii::t('shopee', 'Please Select Listing'); ?>');
            return false;
        }
        $.each(selectedListing, function (k, e) {
            selectedIds.push(e.value);
        });
        var url = '<?php echo Yii::app()->request->baseUrl;?>/joom/joomlisting/batchUpdateForm/action/updateStock/id/'
            + selectedIds.join(",");
        $.pdialog.open(url, 'Batch Update Form', '<?php echo Yii::t("joom", "Batch Update")?>', {
            width: 980,
            height: 800,
            mask: true
        });
        return true;
    }
    function batchUpdatePrice() {
        var selectedIds = [];
        var selectedListing = $("input[name='joom_varants_ids[]']:checked");

        if (selectedListing.length == 0) {
            alertMsg.error('<?php echo Yii::t('joom', 'Please Select Listing'); ?>');
            return false;
        }
        $.each(selectedListing, function (k, e) {
            selectedIds.push(e.value);
        });
        var url = '<?php echo Yii::app()->request->baseUrl;?>/joom/joomlisting/batchUpdateForm/action/updatePrice/id/' + selectedIds.join(",");
        $.pdialog.open(url, 'Batch Update Form', '<?php echo Yii::t("joom", "Batch Update")?>', {
            width: 980,
            height: 800,
            mask: true
        });
        return true;
    }
</script>

<style>
    #joom_listing_widget .searchContent div.left{
        margin-bottom:10px;
    }
</style>
