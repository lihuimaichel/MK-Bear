<?php
/**
 * 
 * @author liuj
 *
 */
class JdlogController extends UebController{
	public function actionList(){
		$this->render("list", array(
			"model"	=>	new JdLog()
		));
	}
}