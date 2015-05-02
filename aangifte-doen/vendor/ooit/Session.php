<?php

class Session
{
    protected $namespace;

    public function __construct($namespace)
    {
        $this->namespace = $namespace;
    }

    public function set($name, $value)
    {
        $_SESSION[$this->namespace][$name] = $value;
        return $this;
    }

    public function delete($name)
    {
        unset($_SESSION[$this->namespace][$name]);
        return $this;
    }

    public function has($name)
    {
        return isset($_SESSION[$this->namespace][$name]);
    }

    public function get($name, $default = null)
    {
        return $this->has($name) ? $_SESSION[$this->namespace][$name] : $default;
    }

}
