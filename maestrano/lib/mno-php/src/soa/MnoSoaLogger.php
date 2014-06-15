<?php

/**
 * Mno DB Map Interface
 */
class MnoSoaLogger {
    protected static $_app_prefix = "";
    
    public static function initialize()
    {
        if (empty(self::$_app_prefix)) {
            $maestrano = MaestranoService::getInstance();
            self::$_app_prefix = $maestrano->getSettings()->app_id;
        }
    }
    
    public static function debug($msg) 
    {
        error_log(self::$_app_prefix . " [debug] " . self::get_context() . " " . $msg);
    }
    
    public static function warn($msg)
    {
        error_log(self::$_app_prefix . " [warn] " . self::get_context() . " " . $msg);
    }
    
    public static function error($msg)
    {
        error_log(self::$_app_prefix . " [error] " . self::get_context() . " " . $msg);
    }
    
    public static function info($msg)
    {
        error_log(self::$_app_prefix . " [info] " . self::get_context() . " " . $msg);
    }

    private static function get_context()
    {
            $e = new Exception();
            $trace = $e->getTrace();
            // position 0 would be the line that called this function so we ignore it
            $context_function = $trace[2]['function'];
            $context_class = $trace[2]['class'];
            $context_file = $trace[2]['file'];

            if (!empty($context_class)) {
                    return "[" . $context_class . "." . $context_function . "]";
            }
            return "[" . $context_file . "." . $context_function . "]";
    }
}

?>