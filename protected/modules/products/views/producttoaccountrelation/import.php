<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent">   
<?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'import_file_product_seller',
         'clientOptions' => array(
        ),
        'action' => Yii::app()->createUrl($this->route),
        'htmlOptions' => array(
         	'enctype'		=>'multipart/form-data',
			'onsubmit'=>"return iframeCallback(this);",
        	'novalidate'		=>'novalidate',
        	'class'		=>'pageForm required-validate',
        	'is_dialog'		=>1,
        )
    ));
?>  
    <div class="pageFormContent" layoutH="80">  
          <div class="row">
			<div class="title"><?php echo Yii::t('product', 'Import Excel File');?></div>
			<?php echo CHtml::fileField('import_file_product_seller', '');?>
         </div> 
         <a target="_blank" href="./uploads/purchase/producttoaccount.xlsx" style="color: blue;font-size:20px;">SKU分配账号模板下载</a>
	</div>
 	<div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                    	<button type="submit"><?php echo Yii::t('system', 'Save') ?></button>
                    </div>
                </div>
            </li>
            <li>
                <div class="button"><div class="buttonContent"><button type="button" class="close"><?php echo Yii::t('system', 'Cancel') ?></button></div></div>
            </li>
        </ul>
    </div>
    <?php $this->endWidget(); ?>
</div>