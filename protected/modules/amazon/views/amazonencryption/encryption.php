<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;?>
<div class="pageContent" layoutH="70">

    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'orderForm-grid',
        'clientOptions' => array(
        ),

        'htmlOptions' => array(
            'enctype'	=>'multipart/form-data',
            'novalidate'	=>'novalidate',
            'class'		=>'pageForm required-validate',
            'is_dialog'	=>1,
        )
    ));
    ?>
    <div class="pageFormContent" >
        <table class="dataintable_inquire" width="800px" cellspacing="1" cellpadding="3" border="0">
            <tbody>
            <tr>
                <a href="<?php echo Yii::app()->createUrl($this->route, array('type' => 'downloadtp')); ?>"> 下载模板</a>
            </tr>
            <tr>
                <td><b>加密SKU：</b>请选择文件（xlsx格式）（注：两种文件只能上传一种，两个都上传默认第一个）</td>
                <td>
                    <input type="file" name="csvfilename1" />
                    <input type="hidden" name="type" value="export" />
                </td>
            </tr>
            <tr>
                <td><b>解密SKU：</b>请选择文件（xlsx格式）</td>
                <td>
                    <input type="file" name="csvfilename2" />
                    <input type="hidden" name="type" value="export" />
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="formBar" style="width: 800px;">
        <ul>
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">
                        <button type="submit" ><?php echo Yii::t('system', '确定')?></button>
                    </div>
                </div>
            </li>
        </ul>
    </div>
    <?php $this->endWidget(); ?>
</div>
