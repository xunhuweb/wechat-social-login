<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 登录apis
 * 
 * @author rain
 * @since 1.0.0
 */
class XH_Social_Channel_Api{
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var Social
     */
    private static $_instance = null;
    
    /**
    * Main Social Instance.
    *
    * Ensures only one instance of Social is loaded or can be loaded.
    *
    * @since 1.0.0
    * @static
    * @return XH_Social_Channel_Api - Main instance.
    */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    
    private function __construct(){
        
    }

    /**
     * 获取解绑链接
     * @param string $redirect_to
     * @return string
     */
    public function get_do_unbind_uri($channel_id,$redirect_to =''){
        if(empty($redirect_to)){
            $redirect_to=XH_Social_Helper_Uri::get_location_uri();
        }
    
        $params=array();
        $ajax_url =XH_Social_Helper_Uri::get_uri_without_params(XH_Social::instance()->ajax_url(),$params);
    
        $params['channel_id'] =$channel_id;
        $params['action']='xh_social_channel';
        $params['tab']='do_unbind';
        $params['notice_str']=str_shuffle(time());
        $params['hash']=XH_Social_Helper::generate_hash($params,  XH_Social::instance()->get_hash_key());
        $params['redirect_to']=$redirect_to;
        return $ajax_url."?".http_build_query($params);
    }
    
    /**
     * 获取登录回调地址
     * @param string $redirect_to
     * @return string
     */
    public function get_authorization_redirect_uri($channel_id,$redirect_to =''){
        if(empty($redirect_to)){
            if(isset($_GET['redirect_to'])){
                $redirect_to=esc_url_raw(urldecode($_GET['redirect_to']));
            }
    
            if(empty($redirect_to)){
                $redirect_to =XH_Social_Helper_Uri::get_location_uri(); 
            }
        }
        
        if(strpos($redirect_to, 'wp-login.php')!==false){
            $redirect_to= admin_url('/');
        }
        
        $redirect_to= apply_filters('xh_social_log_on_callback_uri', $redirect_to,$channel_id);
    
        $params1=array();
        $ajax_url =XH_Social_Helper_Uri::get_uri_without_params(XH_Social::instance()->ajax_url(),$params1);
    
        $params=array();
        $params['channel_id'] = $channel_id;
        $params['action']='xh_social_channel';
        $params['tab']='login_redirect_to_authorization_uri';
        $params['notice_str']=str_shuffle(time());
        $params['hash']=XH_Social_Helper::generate_hash($params,  XH_Social::instance()->get_hash_key());
        $params['redirect_to']=$redirect_to;
    
        return $ajax_url."?".http_build_query(array_merge($params,$params1));
    } 
    
    /**
     * 获取绑定地址
     * @param unknown $channel_id
     * @param string $redirect_to
     * @return string
     */
    public function get_do_bind_redirect_uri($channel_id,$redirect_to =''){
        if(empty($redirect_to)){
            if(isset($_GET['redirect_to'])){
                $redirect_to=esc_url_raw(urldecode($_GET['redirect_to']));
            }
    
            if(empty($redirect_to)){
                $redirect_to =XH_Social_Helper_Uri::get_location_uri();
            }
        }
    
        if(strpos($redirect_to, 'wp-login.php')!==false){
            $redirect_to= admin_url('/');
        }
    
        $redirect_to= apply_filters('xh_social_log_on_callback_uri', $redirect_to,$channel_id);
    
        $params1=array();
        $ajax_url =XH_Social_Helper_Uri::get_uri_without_params(XH_Social::instance()->ajax_url(),$params1);
    
        $params=array();
        $params['channel_id'] = $channel_id;
        $params['action']='xh_social_channel';
        $params['tab']='bind_redirect_to_authorization_uri';
        $params['notice_str']=str_shuffle(time());
        $params['hash']=XH_Social_Helper::generate_hash($params,  XH_Social::instance()->get_hash_key());
        $params['redirect_to']=$redirect_to;
    
        return $ajax_url."?".http_build_query(array_merge($params,$params1));
    }
        
    /**
     * 获取所有登录接口(已开启的)
     * @param array $action_includes 接口约束
     * @return Abstract_XH_Social_Settings_Channel[]
     * @since 1.0.0
     */
    public function get_social_channels($action_includes = array()){
        $channels = apply_filters('xh_social_channels', array());
        
        $results = array();
        foreach ($channels as $channel){ 
            if(!$channel
                ||!$channel instanceof Abstract_XH_Social_Settings_Channel
                ||!$channel->is_available($action_includes)){
                    continue;
            }
            
            $results[]=$channel;
        }
      
        return $results;
    }
    
    /**
     * 获取登录接口(已开启的)
     * @param string $id
     * @param array $action_includes 接口约束 
     * @return Abstract_XH_Social_Settings_Channel
     */
    public function get_social_channel($id,$action_includes = array()){   
        return XH_Social_Helper_Array::first_or_default($this->get_social_channels($action_includes),function($m,$id){
            return $m->id===$id;
        },$id);
    }
}