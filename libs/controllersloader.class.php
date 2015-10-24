<?php
class ControllersLoader {
    protected static $cache = false;
    protected static $stack = array();

    /**
     * Calling a controller by url
     *
     * @param (url) url that will be matched with list of controllers 
     * @param (smart_args_enabled) search arguments values in GET and POST
     */
    public static function load($url, $smart_args_enabled = true) {
        if (!($info = self::getController($url))) {
            return false;
        }

        if (!class_exists($info['controller']['class'], false)) {
            include(ROOT_PATH . '/controllers/' . $info['controller']['file']);

            if (!class_exists($info['controller']['class'], false)) {
                trigger_error('Incorrect controller ' . $info['controller']['controller']);
                exit;
            }
        }

        array_push(
            self::$stack,
            array(
            'controller' => $info['controller']['controller'],
                'action' => $info['action']['action'],
            )
        );

        $class = new $info['controller']['class'];
        $class->__callAction(
            $info['action']['method'],
            $info['action']['argumets'],
            $info['args'],
            !!$smart_args_enabled
        );

        array_pop(self::$stack);
    }

    public static function exists($url) {
        return !!self::getController($url);
    }

    protected static function getController($url) {
        $controllers_list = self::getControllers();

        $is_valid = true;
        $counter = 0;
        $url_args = array();
        $url_controllers = array();
        $controller = '';

        // split url into args and valid controllers list

        foreach (explode('/', $url) as $part) {
            if (!$part) {
                continue;
            }

            ++$counter;
            $url_args[] = $part;

            if (!$is_valid) {
                continue;
            }

            if (!preg_match('#^[A-Za-z][A-Za-z0-9_]*$#', $part)) {
                $is_valid = false;
                continue;
            }

            if ($controller) {
                $controller .= '/';
            }

            $controller .= strtolower($part);

            if (!isset($controllers_list[$controller])) {
                continue;
            }

            $url_controllers[] = array(
                'controller' => $controller,
                'pos' => $counter,
            );
        }

        // reverse found controllers and take the one best matching
        $url_controllers = array_reverse($url_controllers);

        $controller = '';
        $controller_args = array();

        foreach ($url_controllers as $c) {
            if (!isset($controllers_list[$c['controller']])) {
                continue;
            }

            // found one
            $controller = $c['controller'];

            // and arguments for that controller
            for ($i = $c['pos'], $end = count($url_args); $i < $end; ++$i) {
                $controller_args[] = $url_args[$i];
            }

            break;
        }

        if (!$controller) {
            return false;
        }

        $info = $controllers_list[$controller];
        $action = 'index';

        // check if the first argument is an action
        if (count($controller_args) && isset($info['actions'][strtolower($controller_args[0])])) {
            $action = strtolower(array_shift($controller_args));
        }

        return array(
            'controller' => array(
                'file' => $info['file'],
                'class' => $info['class'],
                'controller' => $controller,
            ),
            'action' => $info['actions'][$action],
            'args' => $controller_args,
        );
    }

    protected static function getControllers() {
        if (is_array(self::$cache)) {
            return self::$cache;
        }

        if (is_array(self::$cache = self::loadFromFileCache())) {
            return self::$cache;
        }

        if (!is_array(self::$cache = ControllersParser::parse())) {
            trigger_error('Unable to load controllers list');
            exit;
        }

        self::saveToFileCache(self::$cache);

        return self::$cache;
    }

    protected static function loadFromFileCache() {
        if (!Config::get('app.cache_controllers')) {
            return false;
        }

        if (!file_exists($tmp_path = ROOT_PATH . '/tmp/controllers.php')) {
            return false;
        }

        include $tmp_path;

        return isset($cache) ? $cache : false;
    }

    protected static function saveToFileCache($cache) {
        if (!Config::get('app.cache_controllers')) {
            return false;
        }

        $code = self::variable2code($cache);

        if (!($file = fopen(ROOT_PATH . '/tmp/controllers.php.tmp', 'wb'))) {
            return false;
        }

        fwrite($file, '<?php $cache = ' . $code . '; ?>');
        fclose($file);

        rename(
            ROOT_PATH . '/tmp/controllers.php.tmp',
            ROOT_PATH . '/tmp/controllers.php'
        );

        return true;
    }

    /**
    * Translates variable into text variable declaration 
    */
    protected static function variable2code($var) {
        $type = gettype($var);

        switch ($type) {
            case 'boolean': {
                return $var ? 'true' : 'false';
            }

            case 'integer': {
                return $var;
            }

            case 'double': {
                return $var;
            }

            case 'string': {
                return "'" . str_replace(array("\\", "'"), array("\\\\", "\\'"), $var) . "'";
            }

            case 'array': {
                $array = array();

                foreach ($var as $key => $value) {
                    $array[] = self::variable2code($key) . '=>' . self::variable2code($value);
                }

                return 'array(' . implode(',', $array) . ')';
            }

            default: {
                return 'NULL';
            }
        }

        return 'NULL';
    }
}
?>