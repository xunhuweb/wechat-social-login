<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

require_once  'interface-xh-log-handler.php';

/**
 * Log Handler
 *
 * Write logs into files
 *
 * @since 1.0.0
 * @author ranj
 */
class XH_Social_Log_File_Handler implements XH_Social_Log_Handler {
	private $handle = null;
	private $file = null;

	/**
	 * init log file
	 * 
	 * @since  1.0.0
	 * @param string $file
	 */
	public function __construct($file = '') {
		$this->file=$file;
	}

	/**
	 * Write msg into file
	 * 
	 * @since  1.0.0
	 * @param string $msg
	 */
	public function write($msg) {
	   if(is_null($this->handle)){
	        if($this->file)	{
	            try {
	               $dir = dirname($this->file);
	               //不是目录，则创建目录
	                if(is_dir($dir)||@mkdir($dir,0777,true)){
	                   
	                   $this->handle = @fopen ( $this->file, 'a' );
	                }
	            } catch (Exception $e) {
	                //ignore
	                $this->handle = false;
	            }
	        }
	    }
		
		if($this->handle){
			try {
			    @fwrite ($this->handle, $msg, 4096 );
			} catch (Exception $e) {
			    //ignore
			}
		}
	}

	/**
	 * destruct file handle
	 * 
	 * @since  1.0.0
	 */
	public function __destruct() {
		if ($this->handle){
			try {
			    @fclose ( $this->handle );
			} catch (Exception $e) {
			    //ignore
			}
		}
	}
}