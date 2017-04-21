<?php
Yii::app()->clientScript->scriptMap['jquery.js'] = false;
$this->widget('UGridView', array(
	'id' => 'task_sales_target',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
		array(
			'class' => 'CCheckBoxColumn',
			'value' => '$data->id',
			'selectableRows' => 2,
			'headerHtmlOptions' => array(
				'style' => 'width:25px',
				'onclick'=>'allSalesSelectSetting(this)',
			),
			'checkBoxHtmlOptions'=>array( 
				'onchange'=>'checkSalesSelect(this)',
				'oninput'=>'checkSalesSelect(this)',
				'onpropertychange'=>'checkSalesSelect(this)'
			)
		),
		array(
			'name' => Yii::t('task','Id'),
			'value' => '$row+1',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:40px',
			),
		),
		array(
			'name' => Yii::t('task','Seller'),
			'value' => '$data->seller_name',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'color' => 'blue','width' => '40', 'align' => 'center',
			),
		),
        /*
		array(
			'name' => Yii::t('task','Department'),
			'value' => '$data->department_name',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'color' => 'blue','width' => '60', 'align' => 'center',
			),
		),*/
		array(
			'name' => Yii::t('task','Platform'),
			'value' => '$data->platform_name',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'color' => 'blue','width' => '50', 'align' => 'center',
			),
		),
		array(
			'name' => Yii::t('task','Accounts'),
			'value' => '$data->account_name',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'color' => 'blue','width' => '30', 'align' => 'center',
			),
		),
		array(
			'name'=>'variants_id',
			'value'=> array($this, 'renderGridCell'),
			'type'=>'raw',
			'htmlOptions'=>array(
				'name'=>'sales_varants_ids',
				'style'=>'width:20px',
				'type'=>'checkbox',
			),
		),
		array(
			'name'=> 'site_name',
			'value'=>array($this, 'renderGridCell'),
			'type'=>'raw',
			'htmlOptions'=>array(
				'color' => 'blue','width' => '50', 'align' => 'center',
			),
			'headerHtmlOptions'=>array(
				'align'=>'center'
			),
		),
		array(
			'name' => 'sales_target',
			'value'=>array($this, 'renderGridCell'),
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center','align' => 'center'
			),
			'htmlOptions' => array(
				'color' => 'blue','width' => '300', 'align' => 'center',
			),
		),
		array(
			'name' => 'profit_target',
			'value'=>array($this, 'renderGridCell'),
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center','align' => 'center'
			),
			'htmlOptions' => array(
				'color' => 'blue','width' => '300', 'align' => 'center',
			),
		),
       /*
		array(
			'name' => Yii::t('task', 'create_name'),
			'value'=>array($this, 'renderGridCell'),
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center','align' => 'center'
			),
			'htmlOptions' => array(
				'color' => 'blue','width' => '40', 'align' => 'center',
			),
		),*/
		array(
			'name' => 'created_at',
			'value'=>array($this, 'renderGridCell'),
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'style' => 'width:50px', 'align'=>'center'
			),
			'htmlOptions' => array(
				'color' => 'blue', 'style' => 'width:140px','align' => 'center',
			),
		),/*
		array(
			'name' => 'update_name',
			'value'=>array($this, 'renderGridCell'),
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center','align' => 'center'
			),
			'htmlOptions' => array(
				'color' => 'blue','width' => '50', 'align' => 'center',
			),
		),
		array(
			'name' => 'updated_at',
			'value'=>array($this, 'renderGridCell'),
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'style' => 'width:50px', 'align'=>'center'
			),
			'htmlOptions' => array(
				'color' => 'blue', 'style' => 'width:140px','align' => 'center',
			),
		),*/
		array(
			'name' => 'operate',
			'value' => array($this, 'renderGridCell'),
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center','align' => 'center'
			),
			'htmlOptions' => array(
				'color' => 'blue','width' => '40', 'align' => 'center',
			),
		),
	),
	'toolBar' => array(
		array(
			'text' => Yii::t('task', 'Edit More'),
			'url' => "javascript:void(0)",
			'htmlOptions' => array (
				'class' => 'add',
				'rel' => 'edit_sales_task',
				'mask' => true,
				'onclick' => 'checkSalesSelectId()',
			),
		),
        /*
		array(
			'text' => Yii::t('task', 'Add Sales Target'),
			'url'  => Yii::app()->createUrl('/task/tasksalesetting/edit/mode/1'),
			'htmlOptions' => array (
				'class' => 'add',
				'target' => 'dialog',
				'rel' => 'task_sales',
				'postType' => '',
				'title' => Yii::t('task', 'Add Sales Targe'),
				'mask' => true,
				'width' => 1100,
				'height' => 600,
				'callback' => '',
			)
		),*/
	),
	'pager' => array(

    ),
	'tableOptions' => array(
		'layoutH' => 150,
		'tableFormOptions' => true
	),
));
?>
<script>
    function allSalesSelectSetting(obj){
        var chcked = !!$(obj).find("input").attr("checked");
        $("input[name='sales_varants_ids[]']").not(":disabled").each(function(){
            this.checked = chcked;
        });
    }

    function checkSalesSelectId() {
        var ids = "";
        var arrChk= $("input[name='sales_varants_ids[]']:checked");
        if(arrChk.length==0) {
            alertMsg.error('请至少选择一项');
            return false;
        }

        for (var i=0;i<arrChk.length;i++) {
            ids += arrChk[i].value+',';
        }
        ids = ids.substring(0,ids.lastIndexOf(','));
        var url ='/task/tasksalesetting/edit/mode/0/ids/'+ids;
        $.pdialog.open(url, 'edit_sale', "<?php echo Yii::t('task', 'Edit More'); ?>", {width:1100, height:700, mask: true});
        return false;
    }

    function checkSalesSelect(obj) {
        if (!!$(obj).attr('checked')) {
            $(obj).parents('tr').find("input[name='sales_varants_ids[]']").not(":disabled").each(function(){
                this.checked = true;
            });
        } else {
            $(obj).parents('tr').find("input[name='sales_varants_ids[]']").not(":disabled").each(function(){
                this.checked = false;
            });
        }
    }
</script>
