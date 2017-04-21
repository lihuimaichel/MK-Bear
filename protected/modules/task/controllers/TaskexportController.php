<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/3/6
 * Time: 13:44
 */
class TaskexportController extends TaskBaseController
{
    public function accessRules()
    {
        return array(
            array(
                'allow',
                'users' => array(
                    '*'
                ),
                'actions' => array(

                )
            )
        );
    }


    public function actionExportWaitListing()
    {
        $model = $this->model('sync');
        $account_id = Yii::app()->request->getParam('account_id', 0);
        $account_name = Yii::app()->request->getParam('account_name', '');
        $site = Yii::app()->request->getParam('site', '');

        $userPlatform = $this->userPlatform();
        $platform = isset($userPlatform) ? $userPlatform : Platform::CODE_EBAY;
        $platform_class = new Platform();
        $platform_code = $platform_class->getPlatformCodesAndNames();
        $platform_name = strtolower($platform_code[$platform]);

        //如果是Lazada，则需要转换账号id
        if (in_array($platform, array(Platform::CODE_LAZADA))) {
            $account_arr = LazadaAccount::model()->getApiAccountInfoByID($account_id);
            $account_id = $account_arr['old_account_id'];
        }
        //取得有站点的平台
        $platformSite = $this->platformSite();
        $site_name = in_array($platform, $platformSite) ? $site : '';
        $params = array(
            'seller_user_id' => Yii::app()->user->id,
            'account_id' => $account_id,
            'site' => $site_name
        );
        $prefix = (Yii::app()->user->id % 10);
        $types = array(
            'sub',
            'single',
            // 'main',
        );
        $str  = '<table border="1">';
        $title = '<tr><td align="center">Accounts</td><td align="center">Sites</td><td align="center">SKU</td></tr>';
        $str .= iconv("utf-8", "gbk", $title);
        foreach($types as $key => $type) {
            $data = $model->exportWaitListing($params, $platform_name, $platform, $prefix, $type);
            if (!empty($data)) {
                foreach ($data as $k=>$v) {
                    $str .= '<tr><td align="center">'.$account_name.'</td><td align="center">'.$site_name.'</td><td align="center">'.$v['sku'].'</td></tr>';;
                }
            }
        }
        $str .='</table>';
        header('Content-Length: ' . strlen($str));
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:filename={$account_name}.xls");
        echo $str;
        die();
    }


}