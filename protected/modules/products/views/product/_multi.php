<table class="dataintable" width="100%" cellspacing="1" cellpadding="3" border="0">
    <thead>
        <tr>
            <th>SKU</th>                 
            <?php foreach ($attributePairs as $key => $attributeName):?>
            <th><?php echo $attributeName;?><input type="hidden" name="multi_sku" id="multi_sku" value="<?php echo $product_multi_sku;?>" ></th>
            <?php endforeach;?>
            <th>毛重</th> 
            <th>净重</th>
            <th>价格</th>
        </tr>
    </thead>
    <tbody>   
            <?php if (isset($selectMultiPairs)):?>        
                <?php foreach ($selectMultiPairs as $index => $combineVal):?>
                <tr>
                    <td class="multi-attr-td" >
                        <?php echo CHtml::textField("multi[$index][sku]", $combineVal['sku'], array('style' =>'width:100px;','readonly'=>"readonly"))?>                             
                    </td>
                    <?php $i = 1;?>
                    <?php foreach ($attributePairs as $attributeId => $attributeName):?>
                    <td class="multi-attr-td" >
                        <?php echo CHtml::textField("multi[$index][attr][$attributeId]", isset($combineVal['multi'][$attributeId]) ? $combineVal['multi'][$attributeId] :'',array('style' =>'width:100px;'));?>         
                    </td>
                    <?php $i++;?>
                    <?php endforeach;?>     
                    <td class="multi-attr-td" >
                        <?php echo CHtml::textField("multi[$index][other][weight]", $sonSkuInfo[$combineVal['sku']]['product_weight'],array('style' =>'width:45px;'));?>         
                    </td>
                    <td class="multi-attr-td" >
                        <?php echo CHtml::textField("multi[$index][other][gross_product_weight]", $sonSkuInfo[$combineVal['sku']]['gross_product_weight'],array('style' =>'width:45px;'));?>         
                    </td>
                    <td class="multi-attr-td" >
                        <?php echo CHtml::textField("multi[$index][other][price]",  $sonSkuInfo[$combineVal['sku']]['product_cost'],array('style' =>'width:45px;'));?>         
                    </td>                   
                </tr>             
                <?php endforeach;?>
            <?php endif;?>
    </tbody>
</table>