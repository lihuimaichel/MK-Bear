<?php
/** 
 * @desc 主控制器
 * @author Gordon
 * @since 2015-05-22
 */
Yii::import('ext.eui.base.EuiController');
class ErpController extends EuiController {
    
    /** @var string 主视图文件 */
    public $layout = 'main';
    
    /** @var string 主视图文件 */
    public $layoutPath = 'eui_layouts';
    
    /** @var string 排序字段 */
    public $orderField = null;
    
    /** @var string 排序类型 */
    public $orderDirection = 'Desc';
    
    public $breadcrumbs=array();
    
    /**
     * @desc 修改view目录
     * @see EuiController::init()
     */
    public function init(){
        Yii::app()->setLayoutPath(Yii::app()->getViewPath().DIRECTORY_SEPARATOR.$this->layoutPath);
        parent::init();
    }
    
    /**
     * @desc 成功提示信息
     * @param array $data
     */
    public function successJson($data) {        
        $data['statusCode'] = 200;
        header("Content-Type:text/html; charset=utf-8");
        return json_encode($data);
    }
    
    /**
     * @desc 失败提示信息
     * @param array $data
     */
    public function failureJson($data) {
        $data['statusCode'] = 300;
        header("Content-Type:text/html; charset=utf-8");
        return json_encode($data);
    }     
    
    /**
     * rewirte render 
     * @param type $view
     * @param type $data
     * @param type $return
     */
    public function render($view, $data=null, $return = false){
        if (! empty($_REQUEST['orderField']) ) {
            $data['orderField'] = $_REQUEST['orderField'];
            $orderDirection = isset($_REQUEST['orderDirection']) ? $_REQUEST['orderDirection'] : 'DESC';
            $data['orderDirection'] = $orderDirection;
        }       
        parent::render($view, $data, $return);
    }
   
    /**
     * 请求执行前验证权限
     * @param string $action
     * @return boolean
     * @throws CHttpException
     */
    protected function beforeAction($action = null) {
        return true;
    }
    
    /**
     * 请求执行后
     * @param type $action
     */
    protected function afterAction($action) {
        parent::afterAction($action);
        if ( stripos(Yii::app()->request->getRequestUri(), "?_=") !== false ) {
            $uri = explode("?_=", Yii::app()->request->getUrl()); 
            $uri[1] = substr($uri[1], 0, 13);
            $uniqueKey = 'submit_'.session_id().$uri[1];
            
            if ( Yii::app()->session->get($uniqueKey) ) {         
                Yii::app()->session->remove($uniqueKey);
            }
            unset($_REQUEST['orderField']);
            CHelper::profilingTimeLog();
        }               
    }

    /**
     * @desc 过滤请求action
     */
    protected function filterAccessAction() {
        if (in_array($this->id, array('msg','auto'))) {
            return true;
        }
        if (in_array($this->getAction()->getId(), array('sider'))) {
            return true;
        }
        return false;
    }   
}