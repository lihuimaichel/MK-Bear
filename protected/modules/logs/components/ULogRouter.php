<?php

/**
 * ULogRouter class file
 *
 * @author Bob <zunfengke@gmail.com>
 * 
 */
class ULogRouter extends CApplicationComponent {

    private $_routes = array();

    /**
     * Initializes this application component.
     * This method is required by the IApplicationComponent interface.
     */
    public function init() {
        parent::init();
        foreach ($this->_routes as $name => $route) {
            $route = Yii::createComponent($route);
            $route->init();
            $this->_routes[$name] = $route;
        }
        Yii::getULogger()->attachEventHandler('onFlush', array($this, 'collectLogs'));
        Yii::app()->attachEventHandler('onEndRequest', array($this, 'processLogs'));
    }

    /**
     * @return array the currently initialized routes
     */
    public function getRoutes() {
        return new CMap($this->_routes);
    }

    /**
     * @param array $config list of route configurations. Each array element represents
     * the configuration for a single route and has the following array structure:
     * <ul>
     * <li>class: specifies the class name or alias for the route class.</li>
     * <li>name-value pairs: configure the initial property values of the route.</li>
     * </ul>
     */
    public function setRoutes($config) {
        foreach ($config as $name => $route)
            $this->_routes[$name] = $route;
    }

    /**
     * Collects log messages from a logger.
     * This method is an event handler to the {@link CLogger::onFlush} event.
     * @param CEvent $event event parameter
     */
    public function collectLogs($event) {
        $logger = Yii::getULogger();
        $dumpLogs = isset($event->params['dumpLogs']) && $event->params['dumpLogs'];
        foreach ($this->_routes as $route) {
            if ($route->enabled)
                $route->collectLogs($logger, $dumpLogs);
        }
    }

    /**
     * Collects and processes log messages from a logger.
     * This method is an event handler to the {@link CApplication::onEndRequest} event.
     * @param CEvent $event event parameter
     * @since 1.1.0
     */
    public function processLogs($event) {
        $logger = Yii::getULogger();
        foreach ($this->_routes as $route) {
            if ($route->enabled)
                $route->collectLogs($logger, true);
        }
    }

}
