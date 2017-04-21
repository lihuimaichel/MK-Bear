<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;


$this->widget("UGridView", array(
    'id' => 'shopee_listing_widget',
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
                'onclick' => 'allSelect(this)',
            ),
            'checkBoxHtmlOptions' => array(
                'onchange' => 'checkSelect(this)',
                //'onclick'=>'checkSelect(this)',
                'oninput' => 'checkSelect(this)',
                'onpropertychange' => 'checkSelect(this)'
            )
        ),
        array(

            'name' => 'variation_id',
            'type' => 'raw',
            'value' => array($this, 'renderGridCell'),
            'headerHtmlOptions' => array(
                'class' => 'CCheckBoxColumn',
                'align' => 'center',
                'onclick' => 'allSelect(this)',
            ),
            'htmlOptions' => array(
                'style' => 'width:25px;height:auto',
                'type' => 'checkbox',
                'click_event' => 'checkSubSelect(this)'
            ),
        ),
        array(
            'name' => 'main_image',
            'value' => '$data->mainImageUrl',
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
                'style' => 'width:55px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
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
            'name' => 'listing_id',
            'value' => '$data->listing_id',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'seller_sku',
            'value' => '$data->seller_sku',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px;word-wrap:break-word;word-break:break-all;'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'main_status',
            'value' => '$data->status',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px;word-wrap:break-word;word-break:break-all;'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),

        array(
            'name' => 'variation_id',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px',
                'align' => 'center'
            )
        ),

        array(
            'name' => 'variation_sku',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px',
                'align' => 'center'
            )
        ),


        array(
            'name' => 'variation_option_name',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',

            'htmlOptions' => array(
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),


        array(
            'name' => 'stock',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:60px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'system_stock',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:60px',
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
                'style' => 'width:50px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        
        array(
            'name' => 'status',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:55px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'created_at',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',

            'htmlOptions' => array(
                'style' => 'width:100px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        /*  array(
              'name' => 'seller_name',
              'value' => array($this, 'renderGridCell'),
              'type' => 'raw',
              'htmlOptions' => array(
                  'style' => 'width:55px',
                  'align' => 'center'
              ),
              'headerHtmlOptions' => array(
                  'align' => 'center'
              ),
          ),*/
        array(
            'name' => 'account_name',
            'value' => '$data->accountName',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:60px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'action',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        )
    ),
    'orderBar'=> array(
            'orderOptions'=> array(
                    'created_at'=> Yii::t('shopee', 'Created At'),
                    'price'=> Yii::t('shopee', 'Price'),
                    'sub_stock'=> Yii::t('shopee', 'Stock')

            )
    ),
    'toolBar' => array(
        array(
            'text' => Yii::t('shopee', 'Batch Update Stock'),
            'url' => 'javascript:void(0)',
            'htmlOptions' => array(
                'class' => 'edit',
                'onclick' => 'batchUpdateStock()',
            ),
        ),
        array(
            'text' => Yii::t('shopee', 'Batch Update Price'),
            'url' => 'javascript:void(0)',
            'htmlOptions' => array(
                'class' => 'edit',
                'onclick' => 'batchUpdatePrice()',
            ),
        ),
        array(
            'text' => Yii::t('shopee', 'Export Listing'),
            'url' => 'javascript:void(0)',
            'htmlOptions' => array(
                'class' => 'edit',
                'onclick' => 'exportListing()',
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

<script>
    
    function exportListing() {
        var url ='<?php echo Yii::app()->request->baseUrl;?>/shopee/shopeeproduct/exportListingForm/';
        $.pdialog.open(url, 'Export SKU', '<?php echo Yii::t("shopee", "Export SKU")?>', {width:800,
            height:300, mask:true});
        return true;
    }
    
    function batchUpdateStock(){
        var selectedIds = [];
        var selectedListing= $("input[name='variation_id[]']:checked");

        if(selectedListing.length==0){
            alertMsg.error('<?php echo Yii::t('shopee', 'Please Select Listing'); ?>');
            return false;
        }
        $.each(selectedListing, function (k, e) {
            selectedIds.push(e.value);
        });
        var url ='<?php echo Yii::app()->request->baseUrl;?>/shopee/shopeeproduct/batchUpdateForm/action/updateStock/id/'+selectedIds.join(",");
        $.pdialog.open(url, 'Batch Update Form', '<?php echo Yii::t("shopee", "Batch Update")?>', {width:980, height:800, mask:true});
        return true;
    }
    function batchUpdatePrice(){
        var selectedIds = [];
        var selectedListing= $("input[name='variation_id[]']:checked");

        if(selectedListing.length==0){
            alertMsg.error('<?php echo Yii::t('shopee', 'Please Select Listing'); ?>');
            return false;
        }
        $.each(selectedListing, function (k, e) {
            selectedIds.push(e.value);
        });
        var url ='<?php echo Yii::app()->request->baseUrl;?>/shopee/shopeeproduct/batchUpdateForm/action/updatePrice/id/'+selectedIds.join(",");
        $.pdialog.open(url, 'Batch Update Form', '<?php echo Yii::t("shopee", "Batch Update")?>', {width:980, height:800, mask:true});
        return true;
    }
    function allSelect(obj) {
        var headerCheckBox = $(obj).find("input[type='checkbox']")[0];
        $('input[name="variation_id[]"').not(":disabled").each(function () {
            this.checked = headerCheckBox.checked;
        });
    }
    function checkSelect(obj) {
        $(obj).parents('tr').find('input[name="variation_id[]"]').each(function () {
            this.checked = obj.checked;
        });
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

    $(function (){
        var attributeOptions = '<?php echo \json_encode($attributeOptions)?>';

        if (attributeOptions.length > 0) {
            attributeOptions = $.parseJSON(attributeOptions);
            var filterDiv = $('<div>').addClass('attribute-filter').css('clear', 'both').html('<?php echo Yii::t
            ('shopee', 'Product Attribute:')?>');
            $.each(attributeOptions, function (k, v){
                $('<label>').css('float', 'none').html(v.name).append(
                $('<input>').attr('type', 'checkbox').attr('name', 'product_attribute_filter[]').val(k).attr
                ('checked', v.checked))
                .appendTo
                (filterDiv);
            });
            $('#shopee_listing_widget #pagerForm .searchBar').append(filterDiv);
        }
    });

</script>
