<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget('UGridView', array(
    'id' => 'conditionsfield-grid',
    'dataProvider' => $model->search(null),
    'filter' => $model,

    'columns' => array(
    		array(
    				'class' => 'CCheckBoxColumn',
    				'selectableRows' => 2,
    				'value' => '$data->id',
    				'htmlOptions' => array('width' => '10'),
    		),
    		array(
    				'name' => 'id',
    				'value'=> '$row+1',
    				'htmlOptions' => array('style' => 'width:25px;'),
    		),
    		array(
    				'name' => 'platform_code',
    				'value' => '$data->platform_code',
    				'htmlOptions' => array('style' => 'width:90px;'),
    		),
    		array(
    				'name' => 'rule_class',
    				'value' => '$data->rule_class_cn',
    				'htmlOptions' => array('style' => 'width:100px;'),
    		),
    		array(
    				'name' => 'field_title',
    				'htmlOptions' => array('style' => 'width:150px;'),
    		),
    		array(
    				'name' => 'field_name',
    				'htmlOptions' => array('style' => 'width:240px;'),
    		),
    		array(
    				'name' => 'field_type',
    				'value'	=> 'UebModel::model("ConditionsField")->getFieldType($data->field_type)',
    				'htmlOptions' => array('style' => 'width:50px;'),
    		),
    		array(
    				'name' => 'is_enable',
    				'value'	=> 'VHelper::getStatusLable($data->is_enable)',
    				'htmlOptions' => array('style' => 'width:100px;'),
    		),
    		array(
    				'name' => 'unit_code',
    				'htmlOptions' => array('style' => 'width:100px;'),
    		),
    		array(
    				'name' => 'validate_type',
    				'htmlOptions' => array('style' => 'width:100px;'),
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
            'header' => Yii::t('system', 'Operation'),
            'class' => 'CButtonColumn',
            'headerHtmlOptions' => array('style' => 'width:100px;', 'align' => 'center'),
            'template' => '{edit}',
            'buttons' => array(
                'edit' => array(
                    'url'       => 'Yii::app()->createUrl("/common/conditionsfield/update", array("id"=>$data->id))',
                    'label'     => Yii::t('system', 'Edit'),
                    'options'   => array(
                        'target'    => 'dialog',
                        'class'     =>'btnEdit',
                        'rel' => 'conditionsfield-grid',
                    	'mask'  	=> true,
                        'width'     => '700',
                        'height'    => '400',
                    ),
                ),

            ),
        )
    ),
		
	'toolBar' => array(
        array(
            'text' => Yii::t('system', 'Add'),
            'url' => '/common/conditionsfield/create',
            'htmlOptions' => array(
                'class' => 'add',
                'target' => 'dialog',
                'rel' => 'conditionsfield-grid',
            	'mask'  	=> true,
                'width' => '700',
                'height' => '400',
            	'onclick' => false
            )
        ),
        array(
            'text' => Yii::t('system', 'Delete'),
            'url' => '/common/conditionsfield/delete',
            'htmlOptions' => array(
                'class' => 'delete',
                'title' => Yii::t('system', 'Really want to delete these records?'),
                'target' => 'selectedTodo',
                'rel' => 'conditionsfield-grid',
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

</script>

<style type="text/css">
    .grid .gridTbody td div{
        height:auto;
        padding-top:2px;
    }
</style>