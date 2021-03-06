<?php
/**
 * SlimExt
 *
 * @author Vasiliy Horbachenko <shadow.prince@ya.ru>
 * @copyright 2013 Vasiliy Horbachenko
 * @version 1.0
 * @package shadowprince/slimext
 *
 */
namespace SlimExt;

class SlimExt extends \Slim\Slim {
    protected $router_prefix = array();
    protected $router_middlewares = array();

    /**
     * Monkeypatched route registration methods
     */
    public function get() {
        return call_user_func_array("Parent::get", 
            call_user_func_array(array($this, "applyPrefix"), func_get_args()));
    }

    public function post() {
        return call_user_func_array("Parent::post", 
            call_user_func_array(array($this, "applyPrefix"), func_get_args()));
    }

    public function map() {
        return call_user_func_array("Parent::map", 
            call_user_func_array(array($this, "applyPrefix"), func_get_args()))
            ->via(\Slim\Http\Request::METHOD_GET, \Slim\Http\Request::METHOD_POST);
    }

    /**
     * Apply prefix and middlewares to all routes registered in callable $setups.
     * Syntax similar to route-registration method
     * @param string
     * @param mixed
     */
    public function prefix($prefix, $setups) {
        $args = func_get_args();
        $this->router_prefix[] = array_shift($args);
        $setups = array_pop($args);
        $this->router_middlewares = array_merge($this->router_middlewares, $args);
        call_user_func($setups);
        array_pop($this->router_prefix);
        array_pop($this->router_middlewares);
    }

    /**
     * Get component resource located on $path.
     * For example model classes: Component.ModelClass
     * @param string
     * @return mixed
     */
    public function getCC($path) {
        if (strpos($path, "\\") === false) {
            $path = explode(".", $path); // @TODO
            if (count($path) == 2) {
                $path = array(
                    $path[0],
                    "Model",
                    $path[1],
                ); 
            }

            return implode("\\", $path);
        } else {
            return $path;
        }
    }

    /**
     * Load component located at $path (namespace directory)
     * Loading component includes (so executes too) $path/urls.php,
     * where routes registered at (there is variable $app).
     *
     * @param string
     */
    public function loadComponent($path) {
        global $app;
        $urls = $path . DIRECTORY_SEPARATOR . "urls.php";
        if (is_file($urls)) {
            include_once $urls;
        }
    }

    /**
     * Load components from $app->config("comps")
     */
    public function loadComponents() {
        if (is_array($this->config("comps"))) 
            foreach ($this->config("comps") as $comp) {
                $this->loadComponent(realpath($comp));
            }
    }

    /**
     * Merge all current prefixes and middlewares to args
     * @return array
     */
    protected function applyPrefix() {
        $args = func_get_args();
        $pattern = array_shift($args);
        $callback = array_pop($args);
        $mwares = $args;

        if (count($this->router_prefix)) {
            $pattern = implode("", $this->router_prefix) . $pattern;
        }
        if (count($this->router_middlewares)) {
            $mwares = array_merge($this->router_middlewares, $mwares);
        }

        return array_merge(array($pattern), $mwares, array($callback));
    }

}
