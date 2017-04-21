<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget('UGridView', array(
    'id' => 'conditionsrules-grid',
    'dataProvider' => $model->search(),
    'filter' => $model,

    'columns' => array(
    		array(
    				'class' => 'CCheckBoxColumn',
    				'selectableRows' => 2,
    				'value' => '$data->id',
    				'htmlOptions' => array('style' => 'width:20px;'),
    		),
    		array(
    				'name' => '',
    				'value'=> '$row+1',
    				'htmlOptions' => array('style' => 'width:30px;'),
    		),
    		array(
    				'name' => 'id',
    				'value' => '$data->id',
    				'htmlOptions' => array('style' => 'width:30px;'),
    		),
    		array(
    				'name' => 'rule_class',
    				'value' => '$data->rule_class_cn',
    				'htmlOptions' => array('style' => 'width:100px;'),
    		),
    		array(
    				'name' => 'platform_code',
    				'value' => 'empty($data->platform_code) ? "%" : $data->platform_code',
    				'htmlOptions' => array('style' => 'width:60px;'),
    		),
    		array(
    				'name' => 'rule_name',
    				'value' => '$data->rule_name',
    				'htmlOptions' => array('style' => 'width:240px;'),
    		),
    		array(
    				'name' => 'template_name',
    				'value' => '$data->template_name',
    				'htmlOptions' => array('style' => 'width:240px;'),
    		),
    		array(
    				'name' => 'create_time',
    				'htmlOptions' => array('style' => 'width:120px;'),
    		),
    		array(
    				'name' => 'create_user_id',
    				'value' => 'MHelper::getUsername($data->create_user_id)',
    				'htmlOptions' => array('style' => 'width:50px;'),
    		),
    		array(
    				'name' => 'modify_time',
    				'htmlOptions' => array('style' => 'width:120px;'),
    		),
    		array(
    				'name' => 'modify_user_id',
    				'value' => 'MHelper::getUsername($data->modify_user_id)',
    				'htmlOptions' => array('style' => 'width:50px;'),
    		),
    		array(
    				'name' => 'is_enable',
    				'value'	=> 'VHelper::getStatusLable($data->is_enable)',
    				'htmlOptions' => array('style' => 'width:60px;'),
    		),
    		array(
    				'name' => 'priority',
    				'value' => array($this, 'showSort'),
    				'htmlOptions' => array('style' => 'width:60px;'),
    		),
    		array(
    				'header' => Yii::t('system', 'Operation'),
    				'class' => 'CButtonColumn',
    				'headerHtmlOptions' => array('align' => 'center','style' => 'width:100px;'),
    				'template' => '{edit}',
    				'buttons' => array(
    						'edit' => array(
    								'url'       => 'Yii::app()->createUrl("/common/conditionsrules/update", array("id" => $data->id))',
    								'label'     => Yii::t('system', 'Edit'),
    								'options'   => array(
    										'target'    => 'dialog',
    										'class'     =>'btnEdit',
    										'rel' 		=> 'conditionsrules-grid',
    										'mask'  	=> true,
    										'width'     => '810',
    										'height'    => '680',
    								),
    						),
    		
    				),
    		)

    ),
    'toolBar' => array(
    		array(
    				'text' => Yii::t('system', 'Add'),
    				'url' => '/common/conditionsrules/create',
    				'htmlOptions' => array(
    						'class' => 'add',
    						'target' => 'dialog',
    						'rel' => 'conditionsrules-grid',
    						'mask'  	=> true,
    						'width' => '800',
    						'height' => '700',
    						'onclick' => false
    				)
    		),
    		array(
    				'text' => Yii::t('conditionsrules', 'Batch close the order rule'),
    				'url' => '/common/conditionsrules/batchchangestatus/type/0',
    				'htmlOptions' => array(
    						'class' => 'edit',
    						'title' => Yii::t('system', 'Really want to oprate these records?'),
    						'target' => 'selectedTodo',
    						'rel' => 'conditionsrules-grid',
    						'postType' => 'string',
    						'callback' => 'navTabAjaxDone',
    						'onclick' => false
    				)
    		),
    		array(
    				'text' => Yii::t('conditionsrules', 'Batch open the order rule'),
    				'url' => '/common/conditionsrules/batchchangestatus/type/1',
    				'htmlOptions' => array(
    						'class' => 'edit',
    						'title' => Yii::t('system', 'Really want to oprate these records?'),
    						'target' => 'selectedTodo',
    						'rel' => 'conditionsrules-grid',
    						'postType' => 'string',
    						'callback' => 'navTabAjaxDone',
    						'onclick' => false
    				)
    		),
    ),
    'tableOptions' 	=> array(
        'layoutH' 	=> 135,
    ),
    'pager' 		=> array(),
));

?>

<script type="text/javascript">

function updateSort(id,v) {
    $.getJSON("/common/conditionsrules/updatesort/id/"+id+"/sort/"+v,function(data) {
        navTab.reloadFlag(data.navTabId);
        navTab.reload(data.forwardUrl);
    })
}

$().ready(function(){
    $("input[name='rule_sort']").bind('keypress',function(event){
        if(event.which == 13)    
        {
           // alert('ts');
        }
    });
	
});

</script>

<style type="text/css">
    .grid .gridTbody td div{
        height:auto;
        padding-top:2px;
    }
</style>
