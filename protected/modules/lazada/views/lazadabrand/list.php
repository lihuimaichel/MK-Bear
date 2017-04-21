<?php
Yii::app ()->clientscript->scriptMap ['jquery.js'] = false;
$this->widget ( 'UGridView', array (
		'id' => 'lazadabrand-grid',
		'dataProvider' => $model->search(null),
		'filter' => $model,
		'toolBar' => array(),
		'columns'=>array(
				array(
						'class' => 'CCheckBoxColumn',
						'selectableRows' =>2,
						'value'=> '$data->id',
				),
				array(
						'name' => 'name',
						'value' => '$data->name',
				),
				array(
						'name' => 'operation',
						'value' => '$data->operation',
				        'type'  => 'raw',
				),
			
		),
		'tableOptions' => array (
				'layoutH' => 90 
		),
	    'pager' => array () 
));
?>