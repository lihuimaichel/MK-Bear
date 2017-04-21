<?php

Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'amazonaccount-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
		'toolBar' => array(
				array(
						'text'          => Yii::t('system', 'Lock'),
						'url'           => 'amazon/amazonaccount/lockaccount/',
						'htmlOptions'   => array(
								'class'     => 'delete',
								'title'     => Yii::t('system', 'Really want to lock the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'amazonaccount-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
				array(
						'text'          => Yii::t('system', 'Unlock'),
						'url'           => 'amazon/amazonaccount/unlockaccount/',
						'htmlOptions'   => array(
								'class'     => 'edit',
								'title'     => Yii::t('system', 'Really want to unlock the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'amazonaccount-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
				// array(
				// 		'text'          => Yii::t('system', 'ShutDown'),
				// 		'url'           => 'amazon/amazonaccount/shutdownaccount/',
				// 		'htmlOptions'   => array(
				// 				'class'     => 'delete',
				// 				'title'     => Yii::t('system', 'Really want to shutdown the account'),
				// 				'target'    => 'selectedTodo',
				// 				'rel'       => 'amazonaccount-grid',
				// 				'postType'  => 'string',
				// 				'warn'      => Yii::t('system', 'Please Select'),
				// 				'callback'  => 'navTabAjaxDone',
				// 		)
				// ),
				// array(
				// 		'text'          => Yii::t('system', 'Open'),
				// 		'url'           => 'amazon/amazonaccount/openaccount/',
				// 		'htmlOptions'   => array(
				// 				'class'     => 'edit',
				// 				'title'     => Yii::t('system', 'Really want to open the account'),
				// 				'target'    => 'selectedTodo',
				// 				'rel'       => 'amazonaccount-grid',
				// 				'postType'  => 'string',
				// 				'warn'      => Yii::t('system', 'Please Select'),
				// 				'callback'  => 'navTabAjaxDone',
				// 		)
				// ),
				// array(
				// 		'text'          => '设置部门',
				// 		'url' => 'javascript:void(0)',
				// 		'htmlOptions' => array (
				// 				'class' => 'add',
				// 				'rel' => 'amazonaccount-grid',
				// 				'onclick' => 'setDepartment()'
				// 		)
				// ),
		),
	'columns' => array(
			array(
					'class' => 'CCheckBoxColumn',
					'selectableRows' =>2,
					'value'=> '$data->id',
					'htmlOptions' => array('style' => 'width:30px;'),
			),
			array(
					'name' => 'account_name',
					'value' => '$data->account_name',
					'htmlOptions' => array('style' => 'width:150px;'),
			),

			array(
					'name' => 'is_lock',
					'value' => 'amazonaccount::getLockLable($data->is_lock)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'status',
					'value' => 'amazonaccount::getStatusLable($data->status)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'department_id',
					'value' => 'amazonaccount::getDepartmentLable($data->department_id)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			// array(
			// 		'header' => Yii::t('system', 'Operation'),
			// 		'class' => 'CButtonColumn',
			// 		'headerHtmlOptions' => array('width' => '40', 'align' => 'center',),
			// 		'template' => '{updates}',
			// 		'buttons' => array(
			// 			'updates' => array(
			// 					//'url'       => 'Yii::app()->createUrl("/amazon/amazonaccount/update", array("id" => $data->id))',
			// 					'label'     => '-',
			// 					/*'headerHtmlOptions' => array('height' => '20', 'align' => 'center'),
			// 					'options'   => array(
			// 							'target'    => 'dialog',
			// 							'mask'		=>true,
			// 							'rel' 		=> 'amazonaccount-grid',
			// 							'width'     => '300',
			// 							'height'    => '300',
			// 					),*/
			// 			)
			// 	),
			// ),
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
));
?>
<script type="text/javascript">
//设置部门
function setDepartment(){
	var ids = "";
    var arrChk= $("input[name='amazonaccount-grid_c0[]']:checked");
    if(arrChk.length==0){
        alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
        return false;
    }
    for (var i=0;i<arrChk.length;i++)
    {
        ids += arrChk[i].value+',';
    }
    ids = ids.substring(0,ids.lastIndexOf(','));
    var url ='/amazon/amazonaccount/setdepartment/ids/'+ids;
	$.pdialog.open(url, 'department', '设置部门', {width:380, height:200});
    return false;
}
</script>