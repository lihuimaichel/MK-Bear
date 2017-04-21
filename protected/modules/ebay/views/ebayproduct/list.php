<style type="text/css">
    .listul li {
        height: 23px;
        line-height: 23px;
        /*border-bottom:1px dashed #70b3fa;*/
    }

    td img {
        width: 80px;
        height: 80px;
    }

    table.tb_trbl td {
        background-color: #EFEFEF;
        border: 1px solid #AAAAAA;
        padding: 5px 15px 5px 5px;
        vertical-align: text-top;
    }
</style>
<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
    'id' => 'ebay_product_list',
    'filter' => $model,
    'dataProvider' => $model->search(),
    'selectableRows' => 2,
    'columns' => array(
        array(
            'class' => 'CCheckBoxColumn',
            'value' => '$data->id',
            'selectableRows' => 2,
            'htmlOptions' => array(
                'style' => 'width:20px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
                'style' => 'width:20px;',
                'onclick' => 'allSelectAmazon(this)'
            ),
            'checkBoxHtmlOptions' => array(
                'onchange' => 'checkSelect(this)',
                'onpropertychange' => 'checkSelect(this)',
                'oninput' => 'checkSelect(this)',
            ),
        ),
        array(
            'name' => 'item_id',
            'value' => '$data->item_id_link',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'sku',
            'value' => 'CHtml::link($data->sku,"/products/product/viewskuattribute/sku/".$data->sku,
				array("title"=>$data->sku,"style"=>"color:blue","target"=>"dialog","width"=>1100,"mask"=>true,"height"=>600))',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:46px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),

        array(
            'name' => 'gallery_url',
            'value' => '$data->gallery_url',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'title',
            'value' => 'CHtml::link($data->title,"/ebay/ebayproduct/revisetitle/id/".$data->id,
                array("title"=>$data->title,"style"=>"color:blue","target"=>"dialog","width"=>800,"mask"=>true,"height"=>200))',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'account_name',
            'value' => '$data->account_name',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:30px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'site_id',
            'value' => '$data->site_name',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:50px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'current_price',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:50px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'shipping_price',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:40px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'profit_rate',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:50px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'profit',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:50px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'quantity_available',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:50px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'quantity_sold',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:56px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'item_status',
            'value' => '$data->item_status_text',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:40px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'sku',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:60px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),


        array(
            'name' => 'sku_online',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:160px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'location',
            'value' => '$data->location',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:60px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'variants_id',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'name' => 'ebay_varants_ids',
                'style' => 'width:56px',
                'type' => 'checkbox',
                'click_event' => 'checkEbaySubSelect(this)',
                //'disabled'=>'return $v["enabled"];'
            ),
        ),
        array(
            'name' => 'opreator',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:90px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'available_qty',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'start_time',
            'value' => '$data->start_time',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'listing_type',
            'value' => '$data->listing_type_text',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:60px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'listing_duration',
            'value' => '$data->listing_duration',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:60px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'product_weight',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'product_cost',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
        array(
            'name' => 'current_price_currency',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),

        array(
            'name' => 'handing_time',
            'value' => '$data->handing_time',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),

        array(
            'name' => 'seller_name',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px;',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
            ),
        ),
    ),
    'toolBar' => array(
        array(
            'text' => Yii::t('aliexpress_product', 'Batch Offline'),
            'url' => Yii::app()->createUrl('/ebay/ebayproduct/batchoffline'),
            'htmlOptions' => array(
                'class' => 'delete',
                'title' => Yii::t('system', 'Really want to offline these product?'),
                'target' => 'selectedTodo',
                'rel' => 'ebay_product_list',
                'postType' => 'string',
                'warn' => Yii::t('system', 'Please Select'),
                'callback' => 'navTabAjaxDone'
            ),
        ),
        array(
            'text' => Yii::t('ebay', 'Batch Online'),
            'url' => Yii::app()->createUrl('/ebay/ebayproduct/batchonline'),
            'htmlOptions' => array(
                'class' => 'add',
                'title' => Yii::t('system', 'Really want to online these product?'),
                'target' => 'selectedTodo',
                'rel' => 'ebay_product_list',
                'postType' => 'string',
                'warn' => Yii::t('system', 'Please Select'),
                'callback' => 'navTabAjaxDone'
            ),
        ),
        array(
            'text' => Yii::t('lazada_product', 'Batch Offline Import'),
            'url' => '/ebay/ebayproduct/offlineimport',
            'htmlOptions' => array(
                'class' => 'add',
                'target' => 'dialog',
                'mask' => true,
                'width' => '800',
                'height' => '600',
                'onclick' => false
            )
        ),
        array(
            'text' => Yii::t('ebay', '批量更新详情和图片'),
            'url' => "javascript:void(0)",
            'htmlOptions' => array(
                'class' => 'add',
                'rel' => 'ebay_product_list',
                'onclick' => 'batchChangeDescAndPicture()',
            )
        ),

        array(
            'text' => Yii::t('ebay', '批量更新DispatchTime'),
            'url' => "javascript:void(0)",
            'htmlOptions' => array(
                'class' => 'add',
                'rel' => 'ebay_product_list',
                'onclick' => 'batchChangeDispatchTime()',
            )
        ),

        array(
            'text' => Yii::t('ebay', '批量更新Location'),
            'url' => "javascript:void(0)",
            'htmlOptions' => array(
                'class' => 'add',
                'rel' => 'ebay_product_list',
                'onclick' => 'batchChangeLocation()',
            )
        ),

        array(
            'text' => Yii::t('ebay', '复制刊登'),
            'url' => "javascript:void(0)",
            'htmlOptions' => array(
                'class' => 'add',
                'rel' => 'ebay_product_list',
                'onclick' => 'copylisting()',
            )
        ),
        array(
            'text' => Yii::t('wish_listing', 'Batch Variation Offline'),
            'url' => 'javascript:void(0)',
            'htmlOptions' => array(
                'class' => 'delete',
                'onclick' => 'batchVariationOffline()',
            ),
        ),
        array(
            'text' => Yii::t('ebay', '批量更新库存'),
            'url' => "javascript:void(0)",
            'htmlOptions' => array(
                'class' => 'add',
                'rel' => 'ebay_product_list',
                'onclick' => 'batchChangeStock()',
            )
        ),
        array(
            'text' => Yii::t('ebay', '批量更新发货方式'),
            'url' => "javascript:void(0)",
            'htmlOptions' => array(
                'class' => 'add',
                'rel' => 'ebay_product_list',
                'onclick' => 'batchChangeShip()',
            )
        ),


    ),
    'pager' => array(),
    'tableOptions' => array(
        'layoutH' => 150,
        'tableFormOptions' => true,
    ),
));


?>


<script type="text/javascript">
    $(document).ready(function () {
        $('#sub_sku_online').parent().css({"width": "280px"});	//固定宽度
        var departID = $('#department_id').find("option:selected").val();
        var accountID = $('#account_id').find("option:selected").val();
        var siteID = $('#site_id').find("option:selected").val();
        //判断账号是否选择
        if (departID != '') {
            var ajaxurl = '/ebay/ebaydepartmentaccountsite/getaccount/department_id/' + departID + '/account_id/' + accountID;
            htmlobj = $.ajax({url: ajaxurl, async: false});
            option = htmlobj.responseText;
            $('#account_id').empty();
            $('#account_id').append(option);
        }

        //判断站点是否选择
        if (departID != '' && accountID != '') {
            var ajaxurl = '/ebay/ebaydepartmentaccountsite/getsite/department_id/' + departID + '/account_id/' + accountID + '/site_id/' + siteID;
            htmlobj = $.ajax({url: ajaxurl, async: false});
            option = htmlobj.responseText;
            $('#site_id').empty();
            $('#site_id').append(option);
        }
    });

    function allSelectAmazon(obj) {
        var chcked = !!$(obj).find("input").attr("checked");
        $("input[name='amazon_product_ids[]']").not(":disabled").each(function () {
            this.checked = chcked;
        });
    }

    function checkSelect(obj) {
        if (!!$(obj).attr('checked')) {
            $(obj).parents('tr').find("input[name='amazon_product_ids[]']").not(":disabled").each(function () {
                this.checked = true;
            });
        } else {
            $(obj).parents('tr').find("input[name='amazon_product_ids[]']").not(":disabled").each(function () {
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
            url = '<?php echo Yii::app()->createUrl('/amazon/amazonproduct/online/')?>';
            confirmMsg = '<?php echo Yii::t('amazon_product', 'Really want to onSelling these product?');?>';
        } else if (t == 'offline') {
            url = "<?php echo Yii::app()->createUrl('amazon/amazonproduct/offline');?>";
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
            url = '<?php echo Yii::app()->createUrl('/ebay/ebayproduct/batchonselling/')?>';
            confirmMsg = '<?php echo Yii::t('amazon_product', 'Really want to onSelling these product?');?>';
        } else {
            noChkedMsg = "<?php echo Yii::t('amazon_product', 'Not Specify Sku Which Need To Inactive');?>";
            url = '<?php echo Yii::app()->createUrl('/ebay/ebayproduct/batchoffline/')?>';
            confirmMsg = '<?php echo Yii::t('system', 'Really want to offline these product?');?>';
        }
        //检测
        var chkednum = 1 * $("input[name='ebay_product_list_c0[]']:checked").length;
        if (chkednum <= 0 || chkednum == undefined) {
            alertMsg.error(noChkedMsg);
            return false;
        }
        /*进行确认操作*/
        if (confirm(confirmMsg)) {
            postData = $("input[name='ebay_product_list_c0[]']:checked").serialize();
            $.post(url, postData, function (data) {
                if (data.statusCode == '200') {
                    alertMsg.correct(data.message);
                } else {
                    alertMsg.error(data.message);
                }
            }, 'json');
        }
    }

    function batchVariationOffline() {
        var noChkedMsg = confirmMsg = '', url = '';
// 	if(t == 'on'){
//		noChkedMsg = "<?php echo Yii::t('amazon_product', 'Not Specify Sku Which Need To Active');?>";
//		url = '<?php echo Yii::app()->createUrl('/ebay/ebayproduct/batchonselling/')?>';
//		confirmMsg = '<?php echo Yii::t('amazon_product', 'Really want to onSelling these product?');?>';
// 	}else{
        noChkedMsg = "<?php echo Yii::t('amazon_product', 'Not Specify Sku Which Need To Inactive');?>";
        url = '<?php echo Yii::app()->createUrl('/ebay/ebayproduct/batchvariationoffline/')?>';
        confirmMsg = '<?php echo Yii::t('system', 'Really want to offline these product?');?>';
//	}
        //检测
        var chkednum = 1 * $("input[name='ebay_varants_ids[]']:checked").length;
        if (chkednum <= 0 || chkednum == undefined) {
            alertMsg.error(noChkedMsg);
            return false;
        }
        /*进行确认操作*/
        if (confirm(confirmMsg)) {
            postData = $("input[name='ebay_varants_ids[]']:checked").serialize();
            $.post(url, postData, function (data) {
                if (data.statusCode == '200') {
                    alertMsg.correct(data.message);
                } else {
                    alertMsg.error(data.message);
                }
            }, 'json');
        }

    }


    function offlineVaration(obj) {
        console.log(obj);
        var noChkedMsg = confirmMsg = '', url = '';
        noChkedMsg = "<?php echo Yii::t('amazon_product', 'Not Specify Sku Which Need To Inactive');?>";
        url = '<?php echo Yii::app()->createUrl('/ebay/ebayproduct/variationoffline/')?>';
        confirmMsg = '<?php echo Yii::t('system', 'Really want to offline these product?');?>';
        var id = $(obj).attr("data-id");
        console.log(id);
        var val = $(obj).val();
        console.log(val);
        if (val == '') return;

        /*进行确认操作*/
        if (confirm(confirmMsg)) {
            postData = {'id': id};
            $.post(url, postData, function (data) {
                if (data.statusCode == '200') {
                    alertMsg.correct(data.message);
                } else {
                    alertMsg.error(data.message);
                }
            }, 'json');
        } else {
            $(obj).find("option").eq(0).attr("selected", true);
        }

    }
    //批量更新详情和明细
    function batchChangeDescAndPicture() {

        var ids = "";
        var arrChk = $("input[name='ebay_product_list_c0[]']:checked");
        if (arrChk.length == 0) {
            alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
            return false;
        }
        for (var i = 0; i < arrChk.length; i++) {
            ids += arrChk[i].value + ',';
        }

        var url = '/ebay/ebayproduct/batchupdatedesc';
        var param = {'ids': ids};
        $.pdialog.open(url, 'EbayBatchupdatedesc', '批量更新详情和图片', {width: 600, height: 400});
        $.pdialog.reload(url, {data: param})

        return false;
    }

    //批量更新DispatchTime
    function batchChangeDispatchTime() {
        var url = '/ebay/ebayproduct/updatedispatchtime';
        var ids = "";
        var arrChk = $("input[name='ebay_product_list_c0[]']:checked");
        if (arrChk.length > 0) {
            for (var i = 0; i < arrChk.length; i++) {
                ids += arrChk[i].value + ',';
            }
            ids = ids.substring(0, ids.lastIndexOf(','));
            url += '/ids/' + ids;
        }
        $.pdialog.open(url, 'batchChangeDispatchTime', '批量更新DispatchTime', {width: 600, height: 400});
        return false;
    }

    //批量更新Location
    function batchChangeLocation() {
        var url = '/ebay/ebayproduct/updatelocation';
        var ids = "";
        var arrChk = $("input[name='ebay_product_list_c0[]']:checked");
        if (arrChk.length == 0) {
            alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
            return false;
        }
        for (var i = 0; i < arrChk.length; i++) {
            ids += arrChk[i].value + ',';
        }
        ids = ids.substring(0, ids.lastIndexOf(','));
        url += '/ids/' + ids;
        $.pdialog.open(url, 'batchChangeLocation', '批量更新Location', {width: 600, height: 400});
        return false;
    }

    //批量更新库存
    function batchChangeStock() {
        var url = '/ebay/ebayproduct/updatestock';
        var ids = "";
        var arrChk = $("input[name='ebay_varants_ids[]']:checked");
        if (arrChk.length == 0) {
            alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
            return false;
        }
        for (var i = 0; i < arrChk.length; i++) {
            ids += arrChk[i].value + ',';
        }
        ids = ids.substring(0, ids.lastIndexOf(','));
        url += '/ids/' + ids;
        $.pdialog.open(url, 'batchChangeStock', '批量更新库存', {width: 400, height: 200});
        return false;
    }

    //批量更新发货方式
    function batchChangeShip() {
        var url = '/ebay/ebayproduct/updateship';
        var ids = "";
        var arrChk = $("input[name='ebay_product_list_c0[]']:checked");
        var site_id = $("#site_id").val();
        if (!site_id) {
            alertMsg.error('请先选择站点');
            return false;
        }
        if (arrChk.length == 0) {
            alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
            return false;
        }
        for (var i = 0; i < arrChk.length; i++) {
            ids += arrChk[i].value + ',';
        }
        ids = ids.substring(0, ids.lastIndexOf(','));
        url += '/ids/' + ids + '/site_id/' + site_id;
        $.pdialog.open(url, 'batchChangeShip', '批量更新发货方式', {width: 660, height: 400});
        return false;
    }
    //复制刊登
    function copylisting() {
        var ids = "";
        var arrChk = $("input[name='ebay_product_list_c0[]']:checked");
        if (arrChk.length == 0) {
            alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
            return false;
        }
        for (var i = 0; i < arrChk.length; i++) {
            ids += arrChk[i].value + ',';
        }
        ids = ids.substring(0, ids.lastIndexOf(','));
        var url = '/ebay/ebayproduct/copylisting/ids/' + ids;
        $.pdialog.open(url, 'copylisting', '复制刊登', {width: 680, height: 400});
        return false;
    }

    //触发所在部门
    $("#department_id").change(function () {
        var departID = $(this).find("option:selected").val();
        var ajaxurl = '/ebay/ebaydepartmentaccountsite/getaccount/department_id/' + departID;
        htmlobj = $.ajax({url: ajaxurl, async: false});
        option = htmlobj.responseText;
        $('#account_id').empty();
        $('#account_id').append(option);

        //判断是否选择的是所有
        // if(departID == ''){
        var accountID = '';
        var ajaxurl = '/ebay/ebaydepartmentaccountsite/getsite/department_id/' + departID + '/account_id/' + accountID;
        htmlobj = $.ajax({url: ajaxurl, async: false});
        option = htmlobj.responseText;
        $('#site_id').empty();
        $('#site_id').append(option);
        // }
    });

    //触发所在账号
    $("#account_id").change(function () {
        var departID = $("#department_id").find("option:selected").val();
        var accountID = $(this).find("option:selected").val();
        var ajaxurl = '/ebay/ebaydepartmentaccountsite/getsite/department_id/' + departID + '/account_id/' + accountID;
        htmlobj = $.ajax({url: ajaxurl, async: false});
        option = htmlobj.responseText;
        $('#site_id').empty();
        $('#site_id').append(option);
    });

</script>