<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;?>
<?php Yii::app()->clientscript->registerScriptFile('/js/uploadify/jquery.uploadify.min.js');?>
<?php Yii::app()->clientscript->registerCssFile('/js/uploadify/uploadify.css');?>
<script type="text/javascript">
</script>
<div class="pageContent">
	<?php
		$form = $this->beginWidget('CActiveForm', array(
			'id' => 'image_setting_form',
			'enableAjaxValidation' => false,
			'enableClientValidation' => true,
			'action' => Yii::app()->createUrl($this->route, array('id' => $model->id)),
			'clientOptions' => array(
				'validateOnSubmit' => true,
				'validateOnChange' => true,
				'validateOnType' => false,
				'afterValidate' => 'js:afterValidate',
			),
			'htmlOptions' => array(
				'class' => 'pageForm',
				'enctype' => 'multipart/form-data',
			),
		));
	?>
	<div class="pageFormContent">
		<div class="row">
			<?php echo $form->labelEx($model, 'platform_code');?>
			<?php if ($action == 'update') { ?>
				<strong><?php echo CHtml::encode($model->platform_code);?></strong>
				<?php echo $form->hiddenField($model, 'platform_code');?>
			<?php } else { ?>
				<?php echo $form->dropDownList($model, 'platform_code', CHtml::listData(UebModel::model('platform')->findAll(), 'platform_code', 'platform_name'), array(
					 'empty' => Yii::t('system', 'Please Select'),	 
					 'id' => 'platform_code',
					 'ajax' => array(
							'type' => 'POST',
							'url' => Yii::app()->createUrl('common/productimagesetting/getaccountlist'),
							'data' => array('platform_code' => 'js:this.value'),
							'update' => '#Productimagesetting_account_id',
							'cache' => false,
						),
				));?>
			<?php } ?>
			<?php echo $form->error($model, 'platform_code');?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model, 'account_id');?>
			<?php if ($action == 'update') { ?>			
				<strong><?php echo $model->account_name;?></strong>
				<?php echo $form->hiddenField($model, 'account_id');?>
			<?php } else { ?>
				<?php echo $form->dropDownList($model, 'account_id', array());?>
				<?php echo $form->error($model, 'account_id');?>
			<?php } ?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model, 'zt_watermark');?>
			<?php echo $form->radioButtonList($model, 'zt_watermark', $model->getOpenStateList(), array(
				'template' => '{input}{label}',
				'separator' => '&nbsp;&nbsp;',
				'labelOptions' => array('style' => 'display:inline;float:none;'),
			));
			?>
			<?php echo $form->error($model, 'zt_watermark');?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model, 'ft_watermark');?>
			<?php echo $form->radioButtonList($model, 'ft_watermark', $model->getOpenStateList(), array(
				'template' => '{input}{label}',
				'separator' => '&nbsp;&nbsp;',
				'labelOptions' => array('style' => 'display:inline;float:none;'),
			));?>
			<?php echo $form->error($model, 'ft_watermark');?>
		</div>
		<div class="row">
			<?php echo $form->label($model, 'watermark_position');?>
			<span><strong>X: </strong><?php echo $form->textField($model, 'watermark_position_x', array('size' => 4, 'style' => 'float:none;'));?></span>&nbsp;&nbsp;
			<span><strong>Y: </strong><?php echo $form->textField($model, 'watermark_position_y', array('size' => 4, 'style' => 'float:none;'));?></span>
			<?php echo $form->error($model, 'watermark_position_x');?>
			<?php echo $form->error($model, 'watermark_position_y');?>
		</div>
		<div class="row">
			<?php echo $form->label($model, 'watermark_alpha');?>
			<?php echo $form->textField($model, 'watermark_alpha', array('size' => 4));?>
			<?php echo $form->error($model, 'watermark_alpha');?>
		</div>
<!--		<div class="row">
			<?php echo $form->label($model, 'filename_prefix');?>
			<?php echo $form->textField($model, 'filename_prefix', array('size' => 12));?>
			<?php echo $form->error($model, 'filename_prefix');?>
		</div>
		<div class="row">
			<?php echo $form->label($model, 'filename_suffix');?>
			<?php echo $form->textField($model, 'filename_suffix', array('size' => 12));?>
			<?php echo $form->error($model, 'filename_suffix');?>
		</div>-->
		<div class="row">
			<div style="overflow: <?php echo $action == 'update' ? 'block' : 'hidden';?>;">
				<?php echo $form->labelEx($model, 'watermark_path', array('id' => 'watermark_path_label'));?>
				<a onmouseover="showPreview(event);" onmouseout="hidePreview(event);" href="javascript:;" style="margin-right:10px;">
					<?php if ($action == 'update') { ?>
					<img src="/uploads/<?php echo $model->watermark_path;?>" width="50" id="preview" large-src="/uploads/<?php echo $model->watermark_path;?>" pic-link="/uploads/<?php echo $model->watermark_path;?>" />
					<?php } else { ?>
					<img src="" width="50" id="preview" style="display: none" large-src="" pic-link="" />					
					<?php } ?>
				</a>
			</div>
			<?php echo $form->fileField($model, 'watermark_path');?>
			<?php echo $form->error($model, 'watermark_path');?>
		</div>
                <div id="preview_div" class="row" <?php if($model->watermark_path){ ?>  <?php } else { ?>   style="display:none"   <?php }  ?> >
			<div style="overflow: <?php echo $action == 'update' ? 'block' : 'hidden';?>;">
				<?php echo $form->labelEx($model, 'image_path', array('id' => 'image_path_label'));?>
				<a onmouseover="showPreview(event);" onmouseout="hidePreview(event);" href="javascript:;" style="margin-right:10px;">
					<img src="" width="50" id="preview_test"  large-src="" pic-link="" />
				</a>
			</div>
			<?php echo $form->fileField($model, 'image_path');?>
			<?php echo $form->error($model, 'image_path');?>
		</div>
                
	</div>
	<div class="formBar">
		<ul>
			<li>
				<div class="buttonActive">
					<?php echo CHtml::submitButton(Yii::t('system', 'Save'));?>
				</div>
			</li>
		</ul>
	</div>
	<?php $this->endWidget();?>
</div>
<div style="position: absolute;left:-285px;top:130px">
<?php echo $this->renderPartial('application.components.views._pic');?>	
</div>
<script type="text/javascript">
$(function(){
	var uploadify = $('#Productimagesetting_watermark_path').uploadify({
		//初始化常用设置
		'swf': 'js/uploadify/uploadify.swf',	//上传的flash文件
		'uploader': '<?php echo Yii::app()->createUrl("common/productimagesetting/uploadimage");?>',
		'method': 'post',						//请求的方式
		'folder': '',							//设置上传到那个文件夹
		'debug': true,							//调试模式
		'cancelImg': 'images/uploadify-cancel.png',		//设置取消图片
		'displayData': 'speed',									//进度条显示方式
		'auto': true,									//是否自动上传
		'multi': true,									//是否为多文件上传
		//'buttonImage': '',								//上传按钮
		'fileObjName': 'Productimagesetting[watermark_path]',
		'buttonText': '<?php echo Yii::t('system', 'Upload Files');?>',									//上传提示文本
		'fileSizeLimit': '100000KB',						//单个文件上传最大值
		'queueSizeLimit': 1,								//一次可以选定多少个文件
		'successTimeout': 60,								//上次超时时间
		'fileDesc': '支持格式:(*.jpg, *.gif, *.png, *.xls, *.xlsx, *.csv, *.doc, *.docx, *.rar, *.zip)',	//支持上传文件类型的说明
		'fileExt': '*.jpg, *.gif, *.png, *.xls, *.xlsx, *.csv, *.doc, *.docx, *.rar, *.zip',	//支持的文件
		'simUploadLimit': 1,			//多文件上传时，一次可以传几个文件
		'width': 110,					//按钮长度
		'height':27,						//按钮高度
		'onUploadSuccess': function(file, data, response) {
			if (response == true) {
				var json = eval('(' + data + ')');
				if (json.statusCode == '200') {
					//上传成功
					alertMsg.correct(json.file + json.message);
					var previewUrl = '/uploads/' + json.file;
					$('#preview').attr('src', previewUrl).attr('large-src', previewUrl).attr('pic-link', previewUrl).css('display', 'block');
					$('input[name=Productimagesetting\\[watermark_path\\]]').val(json.file);
                                        $('#preview_div').css('display', 'block');
				} else {
					alertMsg.error(json.message);
				}
			}
		}
	});
});			


$(function(){
	var uploadify2 = $('#Productimagesetting_image_path').uploadify({
		//初始化常用设置
		'swf': 'js/uploadify/uploadify.swf',	//上传的flash文件
		'uploader': '<?php echo Yii::app()->createUrl('common/productimagesetting/uploadtestimage');?>',
		'method': 'post',						//请求的方式
		'folder': '',							//设置上传到那个文件夹
		'debug': true,							//调试模式
		'cancelImg': 'images/uploadify-cancel.png',		//设置取消图片
		'displayData': 'speed',									//进度条显示方式
		'auto': true,									//是否自动上传
		'multi': true,									//是否为多文件上传
		//'buttonImage': '',								//上传按钮
		'fileObjName': 'Productimagesetting[image_path]',
		'buttonText': '<?php echo Yii::t('system', 'Upload Files');?>',									//上传提示文本
		'fileSizeLimit': '100000KB',						//单个文件上传最大值
		'queueSizeLimit': 1,								//一次可以选定多少个文件
		'successTimeout': 60,								//上次超时时间
		'fileDesc': '支持格式:(*.jpg, *.gif, *.png, *.xls, *.xlsx, *.csv, *.doc, *.docx, *.rar, *.zip)',	//支持上传文件类型的说明
		'fileExt': '*.jpg, *.gif, *.png, *.xls, *.xlsx, *.csv, *.doc, *.docx, *.rar, *.zip',	//支持的文件
		'simUploadLimit': 1,			//多文件上传时，一次可以传几个文件
		'width': 110,					//按钮长度
		'height':27,						//按钮高度
		'onUploadSuccess': function(file, data, response) {
			if (response == true) {
				var json = eval('(' + data + ')');
				if (json.statusCode == '200') {
					//上传成功
					alertMsg.correct(json.file + json.message);
					var testUrl = '/uploads/' + json.file;
					//$('#preview_test').attr('src', testUrl).attr('large-src', testUrl).attr('pic-link', testUrl).css('display', 'block');
                                        $('input[name=Productimagesetting\\[image_path\\]]').val(json.file);
                                        preview_image();
				} else {
					alertMsg.error(json.message);
				}
			}
		}
	});
});

function preview_image(){

    $.ajax({
            type: 'post',
            url: '<?php echo Yii::app()->createUrl('common/productimagesetting/PreviewImage');?>',
            data: {
                'position_x'    :$('#Productimagesetting_watermark_position_x').val(),
                'position_y'    :$('#Productimagesetting_watermark_position_y').val(),
                'alpha'         :$('#Productimagesetting_watermark_alpha').val(),
                'water_file'    :$('#preview')[0].src,
                'test_file'     :$('input[name=Productimagesetting\\[image_path\\]]').val()
            },
            success:function(result){
                    if(result.status==0){
                            alertMsg.error(result.message);
                    }else{
                        var previewUrl = '/uploads/' + result.file;
                        $('#preview_test').attr('src', previewUrl).attr('large-src', previewUrl).attr('pic-link', previewUrl).css('display', 'block');   
                    }
            },
            dataType:'json'
    });

    
}

</script>