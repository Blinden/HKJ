<?php

class AbstractService
{

    public function getConfig($name = null)
    {
        $config = FrontController::getConfig('services');
        $class = get_class($this);
        if (isset($config[$class])) {
            if ($name === null) {
                return $config[$class];
            }
            $name = strtolower($name);
            if (isset($config[$class][$name])) {
                return $config[$class][$name];
            }
        }
        return array();
    }

    public function setSession($name, $value)
    {
        $_SESSION[$name] = $value;
        return $this;
    }

    public function hasSession($name)
    {
        return isset($_SESSION[$name]);
    }

    public function getSession($name, $default = null)
    {
        return $this->hasSession($name) ? $_SESSION[$name] : $default;
    }

}
