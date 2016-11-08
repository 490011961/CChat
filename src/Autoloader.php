<?php

/**
 * 自动装载
 * 
 * @author zengqingyun
 * @package JWebIM
 * @namespace JWebIM
 * @version 1.0.0
 * @copyright 2007-2016 iam2C
 */
class Autoloader
{

    /**
     *
     * @param string $class            
     */
    public static function load($class)
    {
        if (!(strpos($class, 'JWebIM') !== false || strpos($class, 'Swoole') !== false)) {
            return false;
        }
        
        $filePath = __DIR__ . '/' . $class . '.php';
        $filePath = strtr($filePath, 
            array(
                '\\' => '/',
                '_' => '/'
            ));
        
        if (file_exists($filePath)) {
            require $filePath;
            return true;
        }
        
        return false;
    }
}
