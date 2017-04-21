<?php
/**
 * @desc 文件下载
 * @author lihy
 *
 */
class FiledownloadlistController extends UebController {
	
    public function actionIndex() {
       $model = new FileDownloadList();
       $this->render("index", array("model"=>$model));
    }
}