<?php

class ServiceProvider
{

    protected static $services = array();

    /**
     *
     * @return AbstractService
     */
    public static function get($service)
    {
        $name = strtolower($service);
        if (!isset(static::$services[$name])) {
            static::$services[$name] = new $service();
        }
        return static::$services[$name];
    }

}
