<?php
/**
 * @desc User Import
 * @author Gordon
 */
class UsersModule extends CWebModule {

    public function init() {
        // import the module-level models and components
        $this->setImport(array(
            'users.models.*',
            'users.components.*',
            'application.components.*',
        ));
    }

}
