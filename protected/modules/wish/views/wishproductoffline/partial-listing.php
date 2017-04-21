<div class="pageContent">
<table class="list" width="90%" layoutH="138">
    <thead>
    <tr>
        <th width="30"></th>
        <th width="80"><?php echo Yii::t('wish', 'Seller'); ?></th>
        <th width="80"><?php echo Yii::t('wish', 'Account') ?></th>
        <th width="100"><?php echo Yii::t('wish', 'SKU') ?></th>
        <th width="100"><input type="checkbox" class="checkbox select-sub"></th>
        <th width="100"><?php echo Yii::t('wish', 'Sub SKU') ?></th>
        <th> <?php echo Yii::t('wish', 'Listing ID') ?></th>
        <th><?php echo Yii::t('wish', 'Warehouse') ?></th>
        <th><?php echo Yii::t('wish', 'Listing Status') ?></th>

    </tr>
    </thead>
    <tbody>
    <?php foreach ($listings as $listing): ?>
        <?php if ($listing['variations']): ?>
            <tr>
                <td><input type="checkbox" name="parent[]" class="row-checkbox" onclick="checkSelect(this)" value="<?php echo $listing['pid'] ?>"></td>
                <td><?php echo isset($sellerList[$listing['variation_product_id']])?$sellerList[$listing['variation_product_id']]:'-'; ?></td>
                <td><?php echo  $accountList[$listing['account_id']] ?></td>
                <td><?php echo $listing['psku']; ?></td>
                <td><?php echo generateCell($listing['variations'], 'id', 'checkbox', 'listing[]'); ?></td>
                <td><?php echo generateCell($listing['variations'], 'sku'); ?> </td>
                <td><?php echo generateCell($listing['variations'], 'variation_product_id'); ?></td>
                <td><?php echo generateCell($listing['variations'], 'warehouse'); ?></td>
                <td><?php echo generateCell($listing['variations'], 'enabled'); ?></td>
            </tr>
        <?php else: ?>
            <tr>
                <td><input type="checkbox" name="parent[]" class="row-checkbox" onclick="checkSelect(this)" value="<?php echo $listing['pid'] ?>"></td>
                <td><?php echo isset($sellerList[$listing['variation_product_id']])?$sellerList[$listing['variation_product_id']]:'-';?></td>
                <td><?php echo $accountList[$listing['account_id']] ?></td>
                <td><?php echo $listing['sku']; ?></td>
                <td><input type="checkbox" name="listing[]" class="row-checkbox sub-checkbox" value="<?php echo $listing['id'] ?>"></td>
                <td><?php echo $listing['sku']; ?></td>
                <td><?php echo $listing['product_id']; ?></td>
                <td><?php echo $listing['warehouse']; ?></td>
                <td><?php echo $listing['enabled']; ?></td>
            </tr>
        <?php endif; ?>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php
function generateCell($data, $key, $htmlTag = '', $htmlTagName = '')
{
    $html = "<table>";
    foreach ($data as $value) {
        $html .= '<tr><td>';
        if (!$htmlTag) {
            $html .= $value[$key];
        } elseif ($htmlTag == 'checkbox') {
            $html .= '<input type="checkbox" name="'.$htmlTagName.'" class="row-checkbox sub-checkbox" value="' . $value[$key] . '">';
        }

        $html .= "</td></tr>";
    }

    $html .= "</table>";
    return $html;
}
?>
<style>
    table.list td font{
        font-size: 14px;
        line-height: 20px;
    }
    table.list tr:nth-child(2n){
        border:1px solid #ccc;
    }
    table.list table td {
        border:0;
        padding:1px 0;
    }

 </style>
<script>
    $('.select-sub').click(function (){
        var checked = this.checked;
        console.log(checked);
        $('.sub-checkbox').each(function (i, e){
            e.checked = checked;
            changeSubCheckbox(e);
        });
    });
    
    function changeSubCheckbox(obj)
    {
        var parentObj = $(obj).closest("table").closest("tr");
        var l = parentObj.find("tr input:checked").length;
        if (!parentObj.length) {
            parentObj = $(obj).closest('tr');
            l = parentObj.find('input:last:checked').length;
        }

        var parentCheckbox =parentObj.find("input:first");
        if (l > 0) {
            parentCheckbox.attr("checked", true);
        } else {
            parentCheckbox.attr("checked", false);
        }
    }

    $('.sub-checkbox').change(function (){
        changeSubCheckbox(this);
    });
    function checkSelect(obj) {
        $(obj).parents('tr').find('input[name="listing[]"]').each(function () {
            this.checked = obj.checked;
        });
    }

    function batchOffline() {

        var url = '<?php echo Yii::app()->createUrl('wish/wishproductoffline/batchoffline');?>';
        var data= [];
        $('input[name="listing\[\]"]:checked').each(function () {
            data.push(this.value);
        });
        if (data.length == 0) {
            alertMsg.warn('<?php echo Yii::t('wish', 'Please select')?>');
            return false;
        }
        alertMsg.confirm("<?php echo Yii::t('wish', 'Confirm to batch offline selected listing?')?>", {
            okCall: function(){
                $.post(url, {'listing': data}, DWZ.ajaxDone, "json");
            }
        });
    }

</script>
