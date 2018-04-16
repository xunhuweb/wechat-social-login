<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly
/**
 * Error
 *
 * @since 1.0.0
 * @author 		ranj
 */
class XH_Social_Error{
    public $errcode,$errmsg,$data,$errors=array();

    /**
     * initialize
     * 
     * @since  1.0.0
     * @param int $errcode
     * @param string $errmsg
     */
	public function __construct($errcode=0, $errmsg='',$data=null) {
		$this->errcode = $errcode;
		$this->errmsg = $errmsg;
		$this->data = $data;
		$this->errors = array (
		    403 => __('Sorry!Your are offline.',XH_SOCIAL),
		    404 => __('The resource was not found!',XH_SOCIAL),
		    405 => __('Your account has been frozen!',XH_SOCIAL),
		    500 => __('Server internal error, please try again later!',XH_SOCIAL),
		    501 =>__('You are accessing unauthorized resources!',XH_SOCIAL),
		    600 =>__('Your request is invalid!',XH_SOCIAL),
		    700 => __('Frequent operation, please try again later!',XH_SOCIAL),
		    701 => __('Sorry,Your request is timeout!',XH_SOCIAL),
		    1000 => __('Sorry,Network error!',XH_SOCIAL)
		);
	}
	
	/**
	 * Success result.
	 * 
	 * @since  1.0.0
	 * @return XH_Social_Error
	 */
	public static function success($data=null) {
		return new XH_Social_Error ( 0, '' ,$data);
	}
	
	/**
	 * Unknow error result.
	 *
	 * @since  1.0.0
	 * @return XH_Social_Error
	 */
	public static function error_unknow() {
		return new XH_Social_Error ( - 1, __('Ops!Something is wrong.',XH_SOCIAL) );
	}
	
	public static function wp_error($error) {
	    if(is_wp_error($error))
	    return new XH_Social_Error ( - 1, $error->get_error_message() );
	    
	    return self::error_unknow();
	}
	/**
	 * Custom error result.
	 *
	 * @since  1.0.0
	 * @param string $errmsg
	 * @return XH_Social_Error
	 */
	public static function error_custom($errmsg='') {
	    if($errmsg instanceof Exception){
	        $errmsg ="errcode:{$errmsg->getCode()},errmsg:{$errmsg->getMessage()}";
	    }else if($errmsg instanceof WP_Error){
	        $errmsg ="errcode:{$errmsg->get_error_code()},errmsg:{$errmsg->get_error_message()}";
	    }
	    
		return new XH_Social_Error ( - 1, $errmsg );
	}
	
	/**
	 * Defined error result.
	 *
	 * @since  1.0.0
	 * @param int $error_code
	 * @return XH_Social_Error
	 */
	public static function err_code($err_code) {
	    $self = XH_Social_Error::error_unknow ();
	    
	    if(isset($self->errors[$err_code])){
	        $self->errcode=$err_code;
	        $self->errmsg=$self->errors[$err_code];
	    }
	    
	    return $self;
	}
	
	/**
	 * check error result is valid.
	 *
	 * @since  1.0.0
	 * @param XH_Social_Error $xh_social_error
	 * @return bool
	 */
	public static function is_valid(&$xh_social_error) {
	    if(!$xh_social_error){
	        $xh_social_error = XH_Social_Error::error_unknow ();
	        return false;
	    }
	    
	    if($xh_social_error instanceof XH_Social_Error){
	        return $xh_social_error->errcode == 0;
	    }

	    if(isset($xh_social_error->errcode)){
	        return $xh_social_error->errcode == 0;
	    }
	    return true;
	}
	
	/**
	 * serialize the error result.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function to_json() {
		return json_encode ( array(
				'errcode'=>$this->errcode,
				'errmsg'=>$this->errmsg,
		         'data'=>$this->data
		));
	}
	
	public function to_string(){
	    return "errcode:{$this->errcode},errmsg:{$this->errmsg}";
	}
	
	public function to_wp_error(){
	    return new WP_Error($this->errcode,$this->errmsg,$this->data);
	}
}