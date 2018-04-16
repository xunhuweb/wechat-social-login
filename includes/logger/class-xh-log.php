<?php
if (! defined('ABSPATH'))
    exit(); // Exit if accessed directly

require_once 'class-xh-log-file-handler.php';

/**
 * Logger
 *
 * Record some abnormal or errors.
 *
 * @since 1.0.0
 * @author ranj
 */
class XH_Social_Log
{
    private $handler = null;
    private $level = 0;
    private static $instance = null;

    private function __construct(){}
    /**
     * Get instance.
     *
     * @since  1.0.0
     * @return XH_Social_Log
     */
    public static function instance($handler = null, $level = 0)
    {
        if (! self::$instance instanceof self) {
            self::$instance = new self();
            self::$instance->__setHandle($handler);
            self::$instance->__setLevel($level);
        }
        return self::$instance;
    }
    
    /**
     * Set log handler.
     *
     * @since  1.0.0
     * @param XH_Social_Log_File_Handler $handler
     */
    private function __setHandle($handler)
    {
        $this->handler = $handler;
    }

    /**
     * Set log level.
     *
     * @since  1.0.0
     * @param int $level
     */
    private function __setLevel($level)
    {
        $this->level = $level;
    }
    
    /**
     * Rebuild error msg.
     *
     * @since  1.0.0
     * @param string|Exception $msg
     */
    private static function rebuild_err_msg($msg){
        if(!$msg){
            return '';
        }
        
        if($msg instanceof Exception){
            return "errcode:{$msg->getCode()},errmsg:{$msg->getMessage()}";
        }
        
        if($msg instanceof WP_Error){
            return "errcode:{$msg->get_error_code()},errmsg:{$msg->get_error_message()}";
        }
        
        if($msg instanceof XH_Social_Error){
            return "errcode:{$msg->errcode},errmsg:{$msg->errmsg}";
        }
        
        if(is_string($msg)){
            return $msg;
        }
        
        return json_encode($msg);
    }
    
    /**
     * Get level source.
     *
     * @since  1.0.0
     * @param string|Exception $msg
     */
    private function get_leval_source($level)
    {
        switch ($level) {
            case 1:
                return 'debug';
                break;
            case 2:
                return 'info';
                break;
            case 4:
                return 'warn';
                break;
            case 8:
                return 'error';
                break;
            default:
        }
    }
    
    /**
     * Debug.
     *
     * @since  1.0.0
     * @param string|Exception|XH_Social_Error $msg
     */
    public static function debug($msg)
    {
        if (! self::$instance) {
            return;
        }
        
        self::$instance->write(1, self::rebuild_err_msg($msg));
    }
    
    /**
     * Warn.
     *
     * @since  1.0.0
     * @param string|Exception $msg
     */
    public static function warn($msg)
    {
        if (! self::$instance) {
            return;
        }
        self::$instance->write(4, self::rebuild_err_msg($msg));
    }

    /**
     * Error.
     *
     * @since  1.0.0
     * @param string|Exception $msg
     */
    public static function error($msg)
    {
        if (! self::$instance) {
            return;
        }
        
        $debugInfo = debug_backtrace();
        $stack = "[";
        foreach ($debugInfo as $key => $val) {
            if (array_key_exists("file", $val)) {
                $stack .= ",file:" . $val["file"];
            }
            if (array_key_exists("line", $val)) {
                $stack .= ",line:" . $val["line"];
            }
            if (array_key_exists("function", $val)) {
                $stack .= ",function:" . $val["function"];
            }
        }
        $stack .= "]";
        self::$instance->write(8, $stack ."\n". self::rebuild_err_msg($msg));
    }

    /**
     * Info.
     *
     * @since  1.0.0
     * @param string|Exception $msg
     */
    public static function info($msg)
    {
        if (! self::$instance) {
            return;
        }
        self::$instance->write(2, self::rebuild_err_msg($msg));
    }

    /**
     * Write logs by handler.
     *
     * @since  1.0.0
     * @param string|Exception $msg
     */
    protected function write($level, $msg)
    {
        if($level>$this->level){
             $msg = '[' . date_i18n('Y-m-d H:i:s') . '][' . $this->get_leval_source($level) . '] ' . $msg . "\n";
             $this->handler->write($msg);
        }
    }
}
