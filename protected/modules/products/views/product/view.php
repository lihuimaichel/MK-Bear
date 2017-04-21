<?php 
	Yii::app()->clientscript->scriptMap['jquery.js'] = false; 
	$urlParms = isset($model->id) ? array( 'id' => $model->id) : array();
	$form = $this->beginWidget('ActiveForm', array(
	    'id' => 'productForm',
	    'enableAjaxValidation' => false,  
	    'enableClientValidation' => true,
	    'focus' => array($model, 'sku'),
	    'clientOptions' => array(
	        'validateOnSubmit' => true,
	        'validateOnChange' => true,
	        'validateOnType' => false,
	        //'additionValidate' => 'js:check',
	        'afterValidate'=>'js:afterValidate',
	    ),
	    'action' => Yii::app()->createUrl($this->route, $urlParms), 
	    'htmlOptions' => array(        
	        'class' => 'pageForm',
	    	)
	));
?> 
<style>
	.row label{
		padding:4px;
	}
	.selectType{
		margin-left:8px;
		font-size:14px;
		color:red;
	}
	.product_information{
		float:left;
		width:900px;
	}
	.photo{
		float:left;
		width:80px;
		margin-left:10px;
		margin-top:10px;
	}
	.clear:after{
		content:',';
		visibility:hidden;
		display:block;
		height:0px;
		clear:both;
	}
</style>  
<div class="pageFormContent" layoutH="100">      
    <div class="bg14 pdtb2 dot">
        <strong>基本资料</strong>           
    </div>
    <div class="dot7 pd20 clear" style="padding-bottom: 40px;">
        <div class="product_information">
        	<table class="dataintable" width="900" border="0" cellspacing="1" cellpadding="3">
		     	<tr>
		         	<td width="100"><?php  echo Yii::t('product', 'Sku');?></td>
		         	<td width="120" style="background-color:#fff;">
		         		<span style='color:green;font-size:13px;'><?php echo $model->sku;?></span>
		         	</td>
		         	<td width="80">自定义码</td>
		         	<td width="120" style="background-color:#fff;">
		         		<span style='color:green;font-size:13px;'>
		         			<?php echo $encryptSku->getEncryptSku($model->sku);?>
		         		</span>
		         	</td>
		     	</tr>
		      	<tr>
		     		<td width = "100"><?php  echo Yii::t('product', 'company category');?></td>
		     		<td width = "120" style="background-color:#fff;">
						<?php echo UebModel::model('ProductClass')->getClassNameByOnlineId($model->online_category_id); ?>
					</td>
		     		<td>产品二级品类</td>
		         	<td style="background-color:#fff;">
				        <?php
				          $online =UebModel::model('ProductCategoryOnline')->getCat($model->online_category_id); echo $online[$model->online_category_id];
				        ?>
		         	</td>
		     	</tr>	
		     	<tr>
		     		<td>产品一级品类</td>
		         	<td colspan='3'>
		         		<?php
		          			$online =UebModel::model('ProductCategoryOnline')->find("cate_id2 ='{$model->online_category_id}'"); echo $online->cate_name1;
		         		?>
					</td>
			    </tr>
			    <tr>
			     	<td width = "100"><?php  echo Yii::t('product', 'Product brand');?></td>
			     	<td width = "120" style="background-color:#fff;"><?php echo $productBrand[$model->product_brand_id];?></td>
			     	<td ><?php  echo Yii::t('product', 'Product Cost');?></td>
			        <td style="background-color:#fff;">
			        	<span style='color:green;font-size:13px;'><?php echo $model->product_cost; ?></span>
			        </td>
			    </tr>
		     	<tr>
			        <td><?php  echo Yii::t('product', 'Product Status');?></td>
			        <?php if($model->product_status=='7'){?>
			        <td style="background-color:#fff;">
			        	<span style='color:red;font-size:13px;'>
			        		<?php echo UebModel::model('Product')->getProductStatusConfig($model->product_status);?>
			        	</span>
			        </td>
			        <?php }else{?>
			        <td style="background-color:#fff;">
			        	<span style='color:green;font-size:13px;'>
			        		<?php echo UebModel::model('Product')->getProductStatusConfig($model->product_status);?>
			        	</span>
			        </td>
			        <?php }?>
			        <td><?php  echo Yii::t('system', 'Type');?></td>
			        <td style="background-color:#fff;"><?php echo UebModel::model('Product')->getProductType($model->product_type);?></td>
		     	</tr>	
		     	<tr>
			     	<td width="100"><?php  echo Yii::t('product', 'Is a multiple attribute');?></td>
			        <td width="120" style="background-color:#fff;"><?php echo Product::getProductMultiList($model->product_is_multi);?></td>
			        <td><?php  echo Yii::t('product', 'Whether the new product');?></td>
			        <td style="background-color:#fff;">
			        	<?php echo $model->product_is_new == 1 ? Yii::t('system','Yes') : Yii::t('system','No');?>
			        </td>
		     	</tr>
			    <tr>
			        <td>英文标题</td>
			        <td colspan='3'><?php echo $mds->title;?></td>
			    </tr>
			    <tr>
			        <td>多个一卖</td>
			        <td colspan='3' style="background-color:#fff;" ><?php echo $model->product_combine_code;?></td>
			    </tr>
			    <tr>
			        <td><?php  echo Yii::t('product', 'Product Binding');?></td>
			        <td colspan='3' style="background-color:#fff;" ><?php echo $model->product_bind_code;?></td>
			    </tr>
			    <tr>
			        <td><?php  echo Yii::t('product', 'Product CN Link');?></td>
			        <td colspan='3' style="background-color:#fff;word-break:break-all;word-wrap:break-word;">
			        	<a href="<?php echo $model->product_cn_link;?>" target ="blank" ><?php echo $model->product_cn_link;?></a>
			        </td>
			    </tr>
			    <tr>
			        <td><?php  echo Yii::t('product', 'Product EN Link');?></td>
			        <td colspan='3' style="background-color:#fff;word-break:break-all;word-wrap:break-word;">
			        	<a href="<?php echo $model->product_en_link;?>" target ="blank" ><?php echo $model->product_en_link;?></a>
			        </td>
			    </tr>
			    <tr>
			        <td><?php  echo Yii::t('product', 'Security Level');?></td>
			        <td style="background-color:#fff;"><?php echo $model->security_level;?></td>
			        <td><?php  echo Yii::t('product', 'Infringement Species');?></td>
			        <td style="background-color:#fff;"><?php  echo $model->infringement;?></td>
			    </tr>
			    <tr>
			     	<td><?php  echo Yii::t('product', 'Infringement Reason');?></td>
			     	<td style="background-color:#fff;"><?php echo isset($mops) ? $mops->infringement_reason : '';?></td>
			     	<td>侵权平台</td>
			     	<td style="background-color:#fff;font-weight:bold;"><?php echo isset($mops) ? $mops->infringe_platform : '';?></td>
			    </tr>
			    <tr>
					<td><?php  echo Yii::t('product', 'Developer');?></td>
				    <td style="background-color:#fff;"><?php echo UebModel::model('ProductRole')->getProductAssignBySku($model->sku,AuthAssignment::PRODUCT_DEVELOPERS);?></td>
				    <td><?php  echo Yii::t('product', 'Create User');?></td>
			        <td style="background-color:#fff;"><?php echo MHelper::getUsername($model->create_user_id);?></td>
				</tr>
			    <tr>
			        <td><?php  echo Yii::t('system', 'Modify User');?></td>
			        <td style="background-color:#fff;"><?php echo MHelper::getUsername($model->modify_user_id);?></td>
			        <td><?php  echo Yii::t('product', 'EBAY USER');?></td>
			        <td style="background-color:#fff;"><?php echo UebModel::model('ProductRole')->getProductAssignBySku($model->sku,AuthAssignment::EBAY_USER);?></td>
			    </tr>
			    <tr>
			        <td>美工</td>
			        <td style="background-color:#fff;"><?php echo UebModel::model('ProductRole')->getProductAssignBySku($model->sku,AuthAssignment::ROLE_AD_CODE);?></td>
			        <td>摄影</td>
			        <td style="background-color:#fff;"><?php echo UebModel::model('ProductRole')->getProductAssignBySku($model->sku,AuthAssignment::ROLE_PH_CODE);?></td>
			    </tr>
		      	<tr>
			        <td><?php  echo Yii::t('system', 'Create Time');?></td>
			        <td style="background-color:#fff;"><?php echo $model->create_time?></td>
			        <td><?php  echo Yii::t('system', 'Modify Time');?></td>
			        <td style="background-color:#fff;"><?php echo $model->modify_time;?></td>
		     	</tr> 
			</table> 
        </div>
        <div class="photo"><img style="border:1px solid #ccc;padding:2px;width:60px;height:60px;" src="/images/nopic.gif"></div>        
    </div> 
</div>
<div class="formBar">
    <ul>
   		<li>
            <div class="button"><div class="buttonContent"><button type="button" class="close" onclick="$.pdialog.closeCurrent();">
            <?php echo Yii::t('system', 'Closed')?></button></div></div>
        </li>
    </ul>
</div>
<?php $this->endWidget(); ?>