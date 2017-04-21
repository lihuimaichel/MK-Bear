<style>
<!--
/* #product_add .pageFormContent label{width:auto;} */
#product_add table td li{line-height:20px;}
#product_add table td font.bold{font-weight:bold;}
#product_add table.dataintable_inquire td td{border:none;}
#product_add .sortDragShow div{border:1px solid #B8D0D6;padding:5px;margin:5px;width:80px;height:80px;display:inline-block;cursor:move;}
.sortDragArea div{border:1px solid #B8D0D6;padding:5px;margin:5px;width:80px;height:80px;display:inline-block;cursor:move;}
table.productAddInfo td .tabsContent{background-color:#efefef;}
.chosen-single span{padding-top:6px;}
.pageFormContent #lazada_attributes label{width:200px;}
ul.multi_select li {float:left;width:150px;}
.pageFormContent #lazada_attributes ul.multi_select li label {width:auto;float:none;display:inline;}
/* #product_add table{display:inline-block;} */
#variationTbody tr td input.text_price{width:90px;}
#variationTbody tr td input.text_sku{width:120px;}
#variationTbody tr td select{width:230px;}
.descriptionContent label{
    width: 120px;
    line-height: 21px;
    float: left;
    padding: 0 5px;
}
-->
</style>
<div class="pageContent">
    <div class="pageFormContent" layoutH="56">
        <div class="bg14 pdtb2 dot">
             <strong>SKU：[<?php echo $listingProduct['sku'];?>]</strong>
        </div>
        <div class="dot7" style="padding:5px;">
           <div class="row productAddInfo" style="width:99%;float:left;">
           <?php
            $form = $this->beginWidget('ActiveForm', array(
                'id' => 'product_add',
                'enableAjaxValidation' => false,  
                'enableClientValidation' => true,
                'clientOptions' => array(
                    'validateOnSubmit' => true,
                    'validateOnChange' => true,
                    'validateOnType' => false,
                    'afterValidate'=>'js:afterValidate',
                    'additionValidate'=>'js:checkResult',
                ),
                'action' => '', 
                'htmlOptions' => array(        
                    'class' => 'pageForm',         
                )
            ));
            ?> 
                <table class="dataintable_inquire productAddInfo" width="100%" cellspacing="1" cellpadding="3" border="0">
                    <tbody>
                        <!-- 刊登参数显示START -->
                        <tr>
                            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('lazada', 'Product Add Params');?></td>
                            <td>
                                <ul>
                                    <li><font class="bold">SKU：</font><?php echo 
                                        CHtml::link($listingProduct['sku'], '/products/product/viewskuattribute/sku/'.$listingProduct['sku'], 
                                array('style'=>'color:blue;','target'=>'dialog','width'=>'1100','height'=>'600','mask'=>true, 'rel' => 'external', 'external' => 'true'))
                                ?></li>
                                    <li><font class="bold"><?php echo Yii::t('lazada', 'Listing Type')?>：</font>
                                        <?php
                                            if($listingProduct['listing_type'] == 2){
                                                echo '单品';
                                            }else{
                                                echo '多属性';
                                            }
                                        ?>
                                    </li>
                                    <li><font class="bold"><?php echo Yii::t('lazada', 'Listing Site')?>：</font><?php echo LazadaSite::getSiteList($listingProduct['site_id']); ?></li> 
                                </ul>
                                <input type="hidden" name="baseInfo[sku]" value="<?php echo $listingProduct['sku'];?>" />
                                <input type="hidden" name="baseInfo[seller_sku]" value="<?php echo $listingProduct['seller_sku'];?>" />
                                <input type="hidden" name="update_id" value="<?php echo $listingProduct['id'];?>" />
                                <input type="hidden" name="baseInfo[listing_type]" value="<?php echo $listingProduct['listing_type'];?>" />
                                <input type="hidden" name="baseInfo[listing_site]" value="<?php echo $listingProduct['site_id'];?>" />
                            </td>
                        </tr>
                        <!-- 刊登参数显示END -->
                        
                        <!-- 类别显示START -->
                        <tr>
                            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('lazada', 'Product Category');?></td>
                            <td>
                                <table class="category">
                                    <tr>
                                        <td>
                                            <div>
                                                <input type="radio" name="get_category" id="history_category" disabled="disabled" />
                                                <label for="history_category"><?php echo Yii::t('lazada', 'History Category');?></label>
                                                <a style="display:none;" href="<?php echo Yii::app()->createUrl('lazada/lazadacategory/historycategory', array(
                                                    'sku' => $listingProduct['sku']
                                                ));?>" lookupGroup="" lookupPk="category_id" width="400" height="300" ><?php echo Yii::t('lazada', 'History Category');?></a>
                                                <div style="clear:both;"></div>
                                            </div>
                                            <div>
                                                <input type="radio" name="get_category" id="choose_category" />
                                                <label for="choose_category"><?php echo Yii::t('lazada', 'Choose Category');?></label>
                                                <a style="display:none;" href="<?php echo Yii::app()->createUrl('lazada/lazadacategory/categorytree');?>" 
                                                lookupGroup="" lookupPk="category_id" width="400" height="300" ><?php echo Yii::t('lazada', 'Choose Category');?></a>
                                                <div style="clear:both;"></div>
                                            </div>
                                        </td>
                                        <td style="vertical-align:middle;">
                                            <div id="lazada_category_name" style="font-weight:bold;font-size:14px;"><?php echo $listingProduct['category_name']; ?></div>
                                            <input type="hidden" name="category_name" value="<?php echo $listingProduct['category_name']; ?>" />
                                            <input type="hidden" name="category_id" value="<?php echo $listingProduct['category_id']; ?>" />
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <!-- 类别显示END -->

                        <!-- 产品属性显示START -->
                        <tr>
                            <td width="15%" style="font-weight:bold;">产品属性</td>
                            <td><?php
                                foreach ($listingAttribute as $key2 => $val2) {     
                                    $attribute_value_name_cn = UebModel::model('ProductAttributeValueLang')->getAttributeNameByCode($val2,CN);
                                    if ( isset($selectAttrPairs[3]) && in_array($key2, (array)$selectAttrPairs[3]) ) {
                                        $flag = true;
                                        echo CHtml::checkBox("attr[3][]", $flag, array( 'value' => $key2));
                                        echo $attribute_value_name_cn;
                                    }     
                                 }
                                
                            ?></td>
                        </tr>
                        <!-- 产品属性显示END -->
                        
                        <!-- 品牌显示START -->
                        <tr>
                            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('common', 'Product Brand');?></td>
                            <td>
                                <input class="textInput" name="brand" type="text" size="30" value="<?php echo $listingProduct['brand']; ?>">
                                <a class="btnLook" width="400" height="300" href="lazada/lazadabrand/list/target/dialog" lookupGroup="" lookupPk="brand"><?php echo Yii::t('lazada', 'Choose Brand On Lazada')?></a>
                            </td>
                        </tr>
                        <!-- 品牌显示END -->
                        <!-- 多属性显示START -->
                        <tr id="mutilVariationRow">
                            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('lazada_product', 'Sale Variation');?></td>
                            <td>
                                <div id="skuAttributes">
                                <table width="100%" table-layout="fixed" id="skuAttributeTable" class="attributesTable">
                                                    <thead>
                                                        <tr>
                                                            <th id="variationName"><input type="hidden" name="variationName" value="Variation">Variation</th>
                                                            <th><?php echo Yii::t('lazada_product', 'Size');?></th> 
                                                            <th><?php echo Yii::t('lazada_product', 'Sku');?></th>
                                                            <th><?php echo Yii::t('lazada_product', 'Sale Price');?></th>
                                                            <th><?php echo Yii::t('lazada_product', 'Special Price');?></th>
                                                            <th><?php echo Yii::t('lazada_product', 'Sale Start Date');?></th>
                                                            <th><?php echo Yii::t('lazada_product', 'Sale End Date');?></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="variationTbody">
                                                        <?php echo $variationHtml; ?>
                                                    </tbody>
                                                </table>
                                </div>
                                <div id="productVariations">
                                </div>
                            </td>
                        </tr>
                        <!-- 多属性显示END -->
                        <!-- 基本信息显示START -->
                        <tr>
                            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('lazada', 'Base Info');?></td>
                            <td>
                                <div class="tabs"> 
                                    <div class="tabsHeader"> 
                                        <div class="tabsHeaderContent"> 
                                            <ul> 
                                                <li class="selected">
                                                    <a href="#"><span>&nbsp;&nbsp;<?php echo $listingProduct['account_name'];?>&nbsp;&nbsp;</span></a>
                                                </li>
                                            </ul> 
                                        </div> 
                                    </div>
                                    <div class="tabsContent"> 
                                        <div class="pageFormContent" style="border:1px solid #B8D0D6">
                                            <div class="row">
                                                <?php echo CHtml::label(Yii::t('lazada', 'Sale Price'), 'sale_price_'.$listingProduct['account_id']); ?>
                                                <?php echo CHtml::textField('baseInfo[sale_price]['.$listingProduct['account_id'].']',$listingProduct['sale_price'],array(
                                                    'id'            => 'sale_price_'.$listingProduct['account_id'],
                                                    'account_id'    => $listingProduct['account_id'],
                                                    'onBlur'        => 'loadPriceInfo(this)',
                                                )); ?>
                                                <span class="profitDetail"></span>
                                            </div>
                                            <div class="row">
                                                <?php echo CHtml::label(Yii::t('lazada', 'Title'), 'title_'.$listingProduct['account_id']); ?>
                                                <?php echo CHtml::textField('baseInfo[title]['.$listingProduct['account_id'].']',$listingProduct['title'],array('id' => 'title_'.$listingProduct['account_id'], 'size' => '100', 'onKeyDown' => 'checkStrLength(this,255)')) ?>
                                                &nbsp;&nbsp;<span class="warn" style="color:red;line-height:22px;"></span>
                                            </div>
                                            <div class="row">
                                                <?php echo CHtml::label(Yii::t('lazada', 'Product Description'), 'description_'.$listingProduct['account_id']);?>
                                                <textarea rows="42" cols="22" name="baseInfo[description][<?php echo $listingProduct['account_id'];?>]" class="productDescription"><?php echo $listingProduct['description'];?></textarea>
                                            </div>
                                            <div class="row">
                                                <?php echo CHtml::label(Yii::t('lazada', 'Product Highlight'), 'highlight_'.$listingProduct['account_id']);?>
                                                <textarea rows="42" cols="22" name="baseInfo[highlight][<?php echo $listingProduct['account_id'];?>]" class="productHightlight"><?php echo $listingProduct['highlight'];?></textarea>
                                            </div>
                                            <input type="hidden" class="accountid" name="baseInfo[account][<?php echo $listingProduct['account_id'];?>]" value="<?php echo $listingProduct['account_id'];?>" />
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <!-- 基本信息显示END -->
                        
                        <!-- 图片信息显示START -->
                        <tr>
                            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('lazada', 'Image Info');?></td>
                            <td>
                                <div class="page unitBox">
                                    <div class="sortDrag sortDragShow" style="width:99%;margin:5px;min-height:100px">
                                        <?php $count = 0;?>
                                        <?php foreach($skuImg as $k=>$image):?>
                                        <div style="position:relative;" class="lazada_image">
                                            <img alt="<?php echo $k;?>" src="<?php echo $image;?>" style="width:80px;height:80px;" />
                                            <input type="hidden" value="<?php echo $count;?>" name="imageInfo[sortImg][<?php echo $k;?>]" />
                                        </div>
                                        <?php $count++;?>
                                        <?php endforeach;?>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                            </td>
                        </tr>
                        <!-- 图片信息显示END -->
                        
                        <!-- 属性信息显示START -->
                        <tr>
                            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('lazada', 'Attribute Info');?></td>
                            <td>
                                <div id="lazada_attributes"><?php echo $categoryAttribute; ?></div>
                            </td>
                        </tr>
                        <!-- 属性信息显示END -->
                    </tbody>
                </table>
                <?php $this->endWidget(); ?>
            </div>
            <div style="clear:both;"></div>
        </div>
    </div>
</div>
<script type="text/javascript">
    KindEditor.create('textarea.productDescription',{
        allowFileManager: true,
        width: '65%',
        height: '400',
        afterCreate : function() {
            this.sync();
        },
        afterBlur:function(){
            this.sync();
        },
    });
    KindEditor.create('textarea.productHightlight',{
        allowFileManager: true,
        width: '65%',
        height: '400',
        afterCreate : function() {
            this.sync();
        },
        afterBlur:function(){
            this.sync();
        },
    });
</script>