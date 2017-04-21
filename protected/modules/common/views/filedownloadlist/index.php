<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'=>'file_download_listing_widget',
	'filter'=>$model,
	'dataProvider'=>$model->search(),
	'selectableRows' => 2,
	'columns'=>array(
			array(
					'name'=>'ID',
					'value'=>'$data->id',
					
					'htmlOptions'=>array(
						'style'=>'width:100px',
						'align'=>'center',
					),
					'headerHtmlOptions'=>array(
							'align'=>'center',
							'onclick'=>'',
					),
					
			),
            array(
					'name'=>'filename',
					'value'=>'$data->filename',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:200px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'create_time',
					'value'=>'$data->create_time',
					'type'=>'raw',
                    'htmlOptions'   => array('style'=>'width:200px'),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'local_path',
					'value'=>'$data->local_path',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			
			
			array(
					'name'=>'create_user_id',
					'value'=>'MHelper::getUsername($data->create_user_id)',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
	),
	'toolBar'=>array(
			
	),
	'pager'=>array(
		
	),
	'tableOptions'=>array(
		'layoutH'	=>	150,
		'tableFormOptions'	=>	true
	)
		
));
?>