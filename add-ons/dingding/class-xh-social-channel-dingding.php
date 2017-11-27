<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * 微信接口
 *
 * @since 1.0.0
 * @author ranj
 */
class XH_Social_Channel_Dingding extends Abstract_XH_Social_Settings_Channel{    
    /**
     * Instance
     * @var XH_Social_Channel_Dingding
     * @since  1.0.0
     */
    private static $_instance;

    /**
     * @return XH_Social_Channel_Dingding
     * @since  1.0.0
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
            return self::$_instance;
    }
    
    /**
     * 初始化接口ID，标题等信息
     * 
     * @since 1.0.0
     */
    protected function __construct(){
        $this->id='social_dingding';
        
        $this->icon =XH_SOCIAL_URL.'/assets/image/dingding.png';
        $this->title =__('钉钉', XH_SOCIAL);
        $this->description='钉钉，阿里巴巴出品，专为中国企业打造的免费智能移动办公平台';
        $this->init_form_fields(); 
        $this->supports=array('login');
        $this->enabled ='yes'== $this->get_option('enabled');
    }
  
    
    /**
     * 初始化设置项
     *
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings::init_form_fields()
     * @since 1.0.0
     */
    public function init_form_fields(){
        $fields =array(
            'enabled' => array (
                'title' => __ ( 'Enable/Disable', XH_SOCIAL ),
                'type' => 'checkbox',
                'label' => __ ( 'Enable Dingding connect', XH_SOCIAL ),
                'default' => 'no'
            ),
            'appid'=>array(
                'title' => __ ( 'App ID', XH_SOCIAL ),
                'type' => 'textbox',
                'description'=>sprintf(__('请设置网站回调域为:%s',XH_SOCIAL),home_url('/'))
            ),
            'appsecret'=>array(
                'title' => __ ( 'APP Secret', XH_SOCIAL ),
                'type' => 'textbox',
                'description'=>'点击进入“钉钉开发者平台” 的页面，然后点击左侧菜单的“自助申请”然后再右侧自助创建用于完整免登过程中验证身份的appId及appSecret即可'
            )
        );
        
        $this->form_fields=apply_filters('xh_social_channel_dingding_form_fields', $fields,$this);
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::update_wp_user_info($ext_user_id,$wp_user_id=null)
     */
    public function update_wp_user_info($ext_user_id,$wp_user_id=null){
        $ext_user_info = $this->get_ext_user_info($ext_user_id);
        if(!$ext_user_info){
            return XH_Social_Error::error_unknow();
        }
        
        global $wpdb;
        if(!$wp_user_id){
            $user_login = XH_Social::instance()->WP->generate_user_login($ext_user_info['nickname']);
           
            $userdata=array(
                'user_login'=>$user_login,
                'user_nicename'=>$ext_user_info['nicename'],
                'first_name'=>$ext_user_info['nickname'],
                'user_email'=>null,
                'display_name'=>$ext_user_info['nickname'],
                'nickname'=>$ext_user_info['nickname'],
                'user_pass'=>str_shuffle(time())
            );
            
            $wp_user_id = $this->wp_insert_user_Info($ext_user_id, $userdata);
            if($wp_user_id instanceof XH_Social_Error){
                return $wp_user_id;
            }
        }
        
        if($wp_user_id!=$ext_user_info['wp_user_id']){
            //若当前用户已绑定过其他微信号？那么解绑
            $wpdb->query(
            "delete from  {$wpdb->prefix}xh_social_channel_dingding
             where user_id=$wp_user_id and id<>$ext_user_id; ");
            if(!empty($wpdb->last_error)){
                XH_Social_Log::error($wpdb->last_error);
                return XH_Social_Error::err_code(500);
            }
            
            $result =$wpdb->query(
                         "update {$wpdb->prefix}xh_social_channel_dingding
                          set user_id=$wp_user_id
                          where id=$ext_user_id;");
            if(!$result||!empty($wpdb->last_error)){
                XH_Social_Log::error("update xh_social_channel_dingding failed.detail error:".$wpdb->last_error);
                return XH_Social_Error::err_code(500);
            }
        }
        
        $ext_user_info['wp_user_id']=$wp_user_id;
        
        do_action('xh_social_channel_update_wp_user_info',$ext_user_info);
        do_action('xh_social_channel_dingding_update_wp_user_info',$ext_user_info);
        
        return $this->get_wp_user_info($ext_user_id);
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::get_wp_user_info($ext_user_id)
     */
    public function get_wp_user_info($ext_user_id){
        $ext_user_id = intval($ext_user_id);
        global $wpdb;
        $user = $wpdb->get_row(
           "select w.user_id
            from {$wpdb->prefix}xh_social_channel_dingding w
            where w.id=$ext_user_id
            limit 1;");
        if(!$user||!$user->user_id) {
            return null;
        }
        
        return get_userdata($user->user_id);
    }
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::get_ext_user_info_by_wp($wp_user_id)
     */
    public function get_ext_user_info_by_wp($wp_user_id){
        $wp_user_id = intval($wp_user_id);
        
        global $wpdb;
        $user = $wpdb->get_row(
            "select w.*
            from {$wpdb->prefix}xh_social_channel_dingding w
            where w.user_id=$wp_user_id
            limit 1;");
        
        if(!$user) {
            return null;
        }
        $guid = XH_Social_Helper_String::guid();
        return array(
                'wp_user_id'=>$user->user_id,
                'ext_user_id'=>$user->id,
                'nickname'=>$user->nickname,
                'openid'=>$user->openid,
                'unionid'=>$user->unionid,
                'dingId'=>$user->dingId,
                'nicename'=>$guid,
                'uid'=>$user->unionid
        );
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::remove_ext_user_info_by_wp($wp_user_id)
     */
    public function remove_ext_user_info_by_wp($wp_user_id){
        global $wpdb;
        $wpdb->query("delete from {$wpdb->prefix}xh_social_channel_dingding where user_id={$wp_user_id};");
        if(!empty($wpdb->last_error)){
            return XH_Social_Error::error_custom($wpdb->last_error);
        }
        
        return XH_Social_Error::success();
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::get_ext_user_info($ext_user_id)
     */
    public function get_ext_user_info($ext_user_id){
        $ext_user_id = intval($ext_user_id);
        global $wpdb;
        $user = $wpdb->get_row(
                    "select w.*
                     from {$wpdb->prefix}xh_social_channel_dingding w
                     where w.id=$ext_user_id
                     limit 1;");
        if(!$user) {
            return null;
        }               
        $guid = XH_Social_Helper_String::guid();
        return  array(
                'wp_user_id'=>$user->user_id,
                'ext_user_id'=>$user->id,
                'nickname'=>$user->nickname,
                'openid'=>$user->openid,
                'unionid'=>$user->unionid,
                'dingId'=>$user->dingId,
                'nicename'=>$guid,
                'uid'=>$user->unionid
        );
    }
    
    public function get($id){
        $token = get_option('wechat_token',array());
        if(!$token||!is_array($token)){
            $token=array();
        }
    
        $token = isset($token[$id])?$token[$id]:null;
        if(!$token
            ||!is_array($token)
            ||!isset($token['expire'])
            ||$token['expire']<time()){
                return null;
        }
    
        return isset($token['data'])?$token['data']:null;
    }
    
    public function set($id,$data){
        $token = get_option('wechat_token',array());
        if(!$token||!is_array($token)){
            $token=array();
        }
    
        $token[$id]=array(
            'expire'=>time()+60*60,
            'data'=>$data
        );
    
        return update_option('wechat_token', $token,true);
    }
   
    public function process_authorization_callback($wp_user_id){
        $login_location_uri=XH_Social::instance()->session->get('social_login_location_uri');  
        if(empty($login_location_uri)){
            $login_location_uri = home_url('/');
        }
        
        $userdata=array();
        
        if(!isset($_GET['code'])){
           return $login_location_uri;
        }
        $code =stripslashes($_GET['code']);
         
        try {
            //获取accesstoken
            $appid = $this->get_option("appid");
            $appsecret = $this->get_option("appsecret");
            
            $content = XH_Social_Helper_Http::http_get('https://oapi.dingtalk.com/sns/gettoken?'.http_build_query(array(
                'appid'=>$appid,
                'appsecret'=>$appsecret
            )));
            $response = json_decode($content,true);
            if(!$response){
                throw new Exception("获取ACCESS_TOKEN时发生异常:".$content,500);
            }
        
            if("{$response['errcode']}"!=="0"){
                throw new Exception("获取ACCESS_TOKEN时发生异常:".$response['errmsg']);
            }
            $access_token =$response['access_token'];
            $ch = curl_init();
            $post_data = json_encode(array(
                'tmp_auth_code'=>$code
            ));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($post_data))
            );
            
            $content = XH_Social_Helper_Http::http_post('https://oapi.dingtalk.com/sns/get_persistent_code?'.http_build_query(array(
                'access_token'=>$access_token
            )),$post_data,false,$ch);
            
            $response = json_decode($content,true);
            if(!$response){
                throw new Exception("获取用户授权的持久授权码时发生异常:".$content,500);
            }
            if("{$response['errcode']}"!=="0"){
                throw new Exception("获取用户授权的持久授权码时发生异常:".$response['errmsg']);
            }
            $ch = curl_init();
            $post_data = json_encode(array(
                'openid'=>$response['openid'],
                'persistent_code'=>$response['persistent_code']
            ));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($post_data))
            );
            
            $content = XH_Social_Helper_Http::http_post('https://oapi.dingtalk.com/sns/get_sns_token?'.http_build_query(array(
                'access_token'=>$access_token
            )),$post_data,false,$ch);
            
            $response = json_decode($content,true);
            if(!$response){
                throw new Exception("获取用户授权的SNS_TOKEN时发生异常:".$content,500);
            }
            if("{$response['errcode']}"!=="0"){
                throw new Exception("获取用户授权的SNS_TOKEN时发生异常:".$response['errmsg']);
            }
            
            $content = XH_Social_Helper_Http::http_get('https://oapi.dingtalk.com/sns/getuserinfo?'.http_build_query(array(
                'sns_token'=>$response['sns_token']
            )));
            
            $response = json_decode($content,true);
            if(!$response){
                throw new Exception("获取用户授权的个人信息时发生异常:".$content,500);
            }
            if("{$response['errcode']}"!=="0"){
                throw new Exception("获取用户授权的个人信息时发生异常:".$response['errmsg']);
            }
            $obj = $response['user_info'];
            $userdata=array(
                'openid'=>$obj['openid'],
                'nickname'=>XH_Social_Helper_String::remove_emoji($obj['nick']),
                'dingId'=>$obj['dingId'],
                'unionid'=>$obj['unionid'],
                'last_update'=>date_i18n('Y-m-d H:i')
            );
        } catch (Exception $e) {
            XH_Social_Log::error($e);
            $err_times = isset($_GET['err_times'])?intval($_GET['err_times']):3;
             
            if($err_times>0){
                $err_times--;
                return $this->_login_get_authorization_uri($wp_user_id, $err_times);
            }
            
            XH_Social::instance()->WP->set_wp_error($login_location_uri, $e->getMessage());
            return $login_location_uri;
        }
        
        if(!$userdata||empty($userdata)){
           return $login_location_uri;
        }
        
        //获取到用户信息，存储且跳转
        global $wpdb;
        $ext_user_id = 0;
        $wpdb->last_error='';
        
        try {
            $ext_user_info = $wpdb->get_row(
            $wpdb->prepare(
               "select id,
                       user_id
                from {$wpdb->prefix}xh_social_channel_dingding
                where unionid=%s
                limit 1;", $userdata['unionid']));
        
            if($wp_user_id
                &&$wp_user_id>0
                &&$ext_user_info
                &&$ext_user_info->user_id
                &&$ext_user_info->user_id!=$wp_user_id){
                    $wp_user = get_userdata($ext_user_info->user_id);
                    if($wp_user){
                        throw new Exception(sprintf(__("对不起，您的钉钉已与账户(%s)绑定，请解绑后重试！",XH_SOCIAL),$wp_user->nickname));
                    }
            }
            
            if($wp_user_id>0
                &&(!$ext_user_info||$ext_user_info->user_id<>$wp_user_id)){
                
                $wpdb->query("delete from {$wpdb->prefix}xh_social_channel_dingding where user_id=$wp_user_id ;");
                if(!empty($wpdb->last_error)){
                    XH_Social_Log::error($wpdb->last_error);
                    throw new Exception(__($wpdb->last_error,XH_SOCIAL));
                }
            }
            
            if(!$ext_user_info){
                if($wp_user_id>0){
                    $userdata['user_id']=$wp_user_id;
                }
                $wpdb->insert("{$wpdb->prefix}xh_social_channel_dingding", $userdata);
                if(!empty($wpdb->last_error)){
                    throw new Exception($wpdb->last_error);
                }
        
                if($wpdb->insert_id<=0){
                    XH_Social_Log::error(__('insert dingding user info failed',XH_SOCIAL));
                    throw new Exception(__('insert dingding user info failed',XH_SOCIAL));
                }
        
                $ext_user_id=$wpdb->insert_id;
            } else{
                //user_id
                if($wp_user_id>0){
                    $userdata['user_id'] =$wp_user_id;
                }
                
                $wpdb->update("{$wpdb->prefix}xh_social_channel_dingding", $userdata,
                array(
                    'id'=>$ext_user_info->id
                ));
        
                if(!empty($wpdb->last_error)){
                    XH_Social_Log::error($wpdb->last_error);
                    throw new Exception($wpdb->last_error);
                }
        
                $ext_user_id=$ext_user_info->id;
            }
        
             return $this->process_login($ext_user_id);
        } catch (Exception $e) {
            XH_Social_Log::error($e);
            XH_Social::instance()->WP->set_wp_error($login_location_uri, $e->getMessage());
            return $login_location_uri;
        }
    }
    
    public function get_wp_user($field,$field_val){
        if(!in_array($field, array(
            'openid'
        ))){
            return null;
        }
    
        global $wpdb;
        $ext_user_info =$wpdb->get_row($wpdb->prepare(
            "select user_id
            from {$wpdb->prefix}xh_social_channel_dingding
            where $field=%s
            limit 1;", $field_val));
        if($ext_user_info&&$ext_user_info->user_id){
            return get_userdata($ext_user_info->user_id);
        }
    
        return null;
    }
    
    public function get_ext_user($field,$field_val){
        if(!in_array($field, array(
            'openid'
        ))){
            return null;
        }
    
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "select *
            from {$wpdb->prefix}XH_Social_Channel_Dingding
            where $field=%s
            limit 1;", $field_val));
    }

    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::generate_authorization_uri()
     */
    public function generate_authorization_uri($user_ID=0,$login_location_uri=null){
       return $this->_login_get_authorization_uri(is_null($user_ID)?0:$user_ID,0);
    }
    
    /**
     * 获取登录授权链接
     * @param string $login_location_uri
     * @param int $error_times
     * @return string
     * @since 1.0.0
     */
    private function _login_get_authorization_uri($user_ID=0,$error_times=null){       
        $params=array();
        $api = XH_Social_Add_On_Social_Dingding::instance();
        $url=XH_Social_Helper_Uri::get_uri_without_params(XH_Social::instance()->ajax_url(
            array(
                'tab'=>'authorization',
                'action'=>"xh_social_{$api->id}",
                'uid'=>is_null($user_ID)?0:$user_ID
            ),true,true
            ),$params);
         
        if(!is_null($error_times)){
            $params['err_times']=$error_times;
        }
         
        $redirect_uri= $url."?".http_build_query($params);
        
        $params=array(
            'appid'=>$this->get_option('appid'),
            'response_type'=>'code',
            'scope'=>'snsapi_login',
            'redirect_uri'=>$redirect_uri,
            'state'=>str_shuffle(time())
        );
        
        if(XH_Social_Helper_Uri::is_app_client()){
            return 'https://oapi.dingtalk.com/connect/oauth2/sns_authorize?'.http_build_query($params);
        }else{
            return 'https://oapi.dingtalk.com/connect/qrconnect?'.http_build_query($params);
        }
        
    }
}
require_once XH_SOCIAL_DIR.'/includes/abstracts/abstract-xh-schema.php';
/**
* 微信接口
*
* @since 1.0.0
* @author ranj
*/
class XH_Social_Channel_Dingding_Model extends Abstract_XH_Social_Schema{

    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Schema::init()
     */
    public function init(){
        $collate=$this->get_collate();
        global $wpdb;
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}xh_social_channel_dingding` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` BIGINT(20) NULL,
            `openid` VARCHAR(64) NULL DEFAULT NULL,
            `dingId` VARCHAR(64) NULL DEFAULT NULL,
            `unionid` VARCHAR(64) NULL DEFAULT NULL,
            
            `last_update` DATETIME NOT NULL,
            `nickname` VARCHAR(512) NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `unionid_unique` (`unionid`),
            UNIQUE INDEX `user_id_unique` (`user_id`)
        )
        $collate;");
    
        if(!empty($wpdb->last_error)){
            XH_Social_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
    }
}