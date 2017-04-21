<?php

Yii::import('ext.eui.widgets.EuiContainer');
Yii::import('ext.eui.widgets.EuiMenuitem');

class EuiMenu extends EuiContainer 
{	
	/**	 
	 * @var number	Menu z-index style,increase from it.
	 */
	public $zIndex;	
	
	/**	 
	 * @var number	Menu left position.
	 */
	public $left;	
	
	
	/**
	 * @var number Menu top position
	 */
	public $top;	
	
	/**	 
	 * @var number The minimum width of menu.
	 */
	public $minWidth;	
	
	
	protected function getCssClass()
	{
		return 'easyui-menu';
	}
	
	public function getDefaultItemClass() 
	{
		return 'EuiMenuitem';		
	}

	public function init()
	{
		parent::init();		
		echo CHtml::openTag('div', $this->toOptions())."\n";
	}
	
	public function run()
	{
		parent::run();
		echo CHtml::closeTag('div')."\n";
	}
}