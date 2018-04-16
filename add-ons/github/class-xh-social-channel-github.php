<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * 
 * @since 1.0.0
 * @author ranj
 */
class XH_Social_Channel_Github extends Abstract_XH_Social_Settings_Channel{    
    /**
     * Instance
     * @var XH_Social_Channel_Github
     * @since  1.0.0
     */
    private static $_instance;

    /**
     * @return XH_Social_Channel_Github
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
        $this->id='social_github';
        
        $this->icon =XH_SOCIAL_URL.'/assets/image/github.png';
        $this->title =__('Github', XH_SOCIAL);
        $this->description='GitHub brings together the world’s largest community of developers to discover, share, and build better software. From open source projects to private team repositories, we’re your all-in-one platform for collaborative development.';
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
                'label' => __ ( 'Enable github authorization.', XH_SOCIAL ),
                'default' => 'no'
            ),
            'appid'=>array(
                'title' => __ ( 'Client ID', XH_SOCIAL ),
                'type' => 'textbox',
                'description'=>__('The client ID you received from GitHub when you <a href="https://github.com/settings/applications/new">registered</a>.',XH_SOCIAL)
            ),
            'appsecret'=>array(
                'title' => __ ( 'Client Secret', XH_SOCIAL ),
                'type' => 'textbox'
            ),
            'callback_url'=>array(
                'title'=>__('Authorization callback URL',XH_SOCIAL),
                'type'=>'text',
                'default'=>admin_url('admin-ajax.php'),
                'custom_attributes'=>array(
                    'disabled'=>'disabled'
                )
            )
        );
        
        $this->form_fields=apply_filters('xh_social_channel_github_form_fields', $fields,$this);
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
       
     //如果系统已存在用户
        $user = get_user_by('email', $ext_user_info['email']);
        if($user){
            if($wp_user_id&&$wp_user_id>0&&$user->ID!=$wp_user_id){
                return XH_Social_Error::error_custom("对不起，Github用户绑定失败！错误：当前账户已与用户(email:{$ext_user_info['email']})绑定！");
            }
            $wp_user_id = $user->ID;
        }
        
        global $wpdb;
        if(!$wp_user_id){
            $userdata=apply_filters('wsocial_insert_user_Info',array(
                'user_login'=>$ext_user_info['email'],
                'user_nicename'=>$ext_user_info['nicename'],
                'first_name'=>method_exists($this, 'filter_display_name')?$this->filter_display_name($ext_user_info['nickname']):$ext_user_info['nickname'],
                'user_email'=>$ext_user_info['email'],
                'display_name'=>method_exists($this, 'filter_display_name')?$this->filter_display_name($ext_user_info['nickname']):$ext_user_info['nickname'],
                'nickname'=>method_exists($this, 'filter_display_name')?$this->filter_display_name($ext_user_info['nickname']):$ext_user_info['nickname'],
                'user_pass'=>str_shuffle(time())
            ),$this);
            
            $wp_user_id = $this->wp_insert_user_Info($ext_user_id, $userdata);
            if($wp_user_id instanceof XH_Social_Error){
                return $wp_user_id;
            }
        }
        
        if($wp_user_id!=$ext_user_info['wp_user_id']){
            //若当前用户已绑定过其他号？那么解绑
            $wpdb->query(
            "delete from  {$wpdb->prefix}xh_social_channel_github
             where user_id=$wp_user_id and id<>$ext_user_id; ");
            if(!empty($wpdb->last_error)){
                XH_Social_Log::error($wpdb->last_error);
                return XH_Social_Error::err_code(500);
            }
            
            $result =$wpdb->query(
                         "update {$wpdb->prefix}xh_social_channel_github
                          set user_id=$wp_user_id
                          where id=$ext_user_id;");
            if(!$result||!empty($wpdb->last_error)){
                XH_Social_Log::error("update xh_social_channel_github failed.detail error:".$wpdb->last_error);
                return XH_Social_Error::err_code(500);
            }
        }
        
        $ext_user_info['wp_user_id']=$wp_user_id;
        update_user_meta($wp_user_id, '_social_img', $ext_user_info['avatar_url']);
        do_action('xh_social_channel_update_wp_user_info',$ext_user_info);
        do_action('xh_social_channel_github_update_wp_user_info',$ext_user_info);
        
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
            from {$wpdb->prefix}xh_social_channel_github w
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
            from {$wpdb->prefix}xh_social_channel_github w
            where w.user_id=$wp_user_id
            limit 1;");
        
        if(!$user) {
            return null;
        }
        
        $guid = XH_Social_Helper_String::guid();
        return array(
                'wp_user_id'=>$user->user_id,
                'ext_user_id'=>$user->id,
            
                'avatar_url'=>$user->avatar_url,
                'html_url'=>$user->html_url,
                'company'=>$user->company,
                'blog'=>$user->blog,
                'location'=>$user->location,
                'email'=>$user->email,
                'bio'=>$user->bio,
                'login'=>$user->login,
            
                'nicename'=>$guid,
                'uid'=>$user->_id
        );
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::remove_ext_user_info_by_wp($wp_user_id)
     */
    public function remove_ext_user_info_by_wp($wp_user_id){
        global $wpdb;
        $wpdb->query("delete from {$wpdb->prefix}xh_social_channel_github where user_id={$wp_user_id};");
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
                     from {$wpdb->prefix}xh_social_channel_github w
                     where w.id=$ext_user_id
                     limit 1;");
        if(!$user) {
            return null;
        }     
     
        $guid = XH_Social_Helper_String::guid();
        return  array(
                'wp_user_id'=>$user->user_id,
                'ext_user_id'=>$user->id,
                'avatar_url'=>$user->avatar_url,
                'html_url'=>$user->html_url,
                'company'=>$user->company,
                'blog'=>$user->blog,
                'location'=>$user->location,
                'email'=>$user->email,
                'bio'=>$user->bio,
                'login'=>$user->login,
                'nicename'=>$guid,
                'uid'=>$user->_id
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
        $state =stripslashes($_GET['state']);
        try {
            //获取accesstoken
            $appid = $this->get_option("appid");
            $appsecret = $this->get_option("appsecret");
            
            $ch = curl_init();
            $header = array(
                'User-Agent: Awesome-Octocat-App',
                'Accept:application/json'
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $content = XH_Social_Helper_Http::http_post('https://github.com/login/oauth/access_token',array(
                'client_id'=>$appid,
                'client_secret'=>$appsecret,
                'code'=>$code,
                'state'=>$state
            ),false,$ch);
            
            //{"access_token":"29fdd6bd6de3cb49ecd941786c7c28828243b2bc","token_type":"bearer","scope":""}
            $response = json_decode($content,true);
            if(!$response){
                throw new Exception("获取ACCESS_TOKEN时发生异常:".$content,500);
            }
        
            if(isset($response['error'])){
                throw new Exception("获取ACCESS_TOKEN时发生异常:".$response['error_description']);
            }
            
            $ch = curl_init();
            $header = array(
                'User-Agent: Awesome-Octocat-App',
                'Accept:application/json',
                "Authorization:token {$response['access_token']}"
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $content = XH_Social_Helper_Http::http_get('https://api.github.com/user',false,$ch);
       
            $response = json_decode($content,true);
            if(!$response){
                throw new Exception("获取用户授权的持久授权码时发生异常:".$content,500);
            }
            if(isset($response['error'])){
                throw new Exception("获取用户授权的持久授权码时发生异常:".$response['error_description']);
            }
           
            $userdata=array(
                '_id'=>$response['id'],
                'nickname'=>XH_Social_Helper_String::remove_emoji($response['name']),
                'avatar_url'=>$response['avatar_url'],
                'html_url'=>$response['html_url'],
                'company'=>$response['company'],
                'blog'=>$response['blog'],
                'location'=>$response['location'],
                'email'=>$response['email'],
                'bio'=>$response['bio'],
                'login'=>$response['login'],
                
                'last_update'=>date_i18n('Y-m-d H:i')
            );
            
            $user = get_user_by('email', $userdata['email']);
            //如果系统已存在用户
            if($user){
                if($wp_user_id&&$wp_user_id>0&&$user->ID!=$wp_user_id){
                    throw new Exception("对不起，Github用户绑定失败！错误：当前账户已与用户(email:{$userdata['email']})绑定！");
                }
                $wp_user_id = $user->ID;
            }
            
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
                from {$wpdb->prefix}xh_social_channel_github
                where _id=%s
                limit 1;", $userdata['_id']));
        
            if($wp_user_id
                &&$wp_user_id>0
                &&$ext_user_info
                &&$ext_user_info->user_id
                &&$ext_user_info->user_id!=$wp_user_id){
                    $wp_user = get_userdata($ext_user_info->user_id);
                    if($wp_user){
                        throw new Exception(sprintf(__("对不起，您的Github已与账户(%s)绑定，请解绑后重试！",XH_SOCIAL),$wp_user->nickname));
                    }
            }
            
            if($wp_user_id>0
                &&(!$ext_user_info||$ext_user_info->user_id<>$wp_user_id)){
                
                $wpdb->query("delete from {$wpdb->prefix}xh_social_channel_github where user_id=$wp_user_id ;");
                if(!empty($wpdb->last_error)){
                    XH_Social_Log::error($wpdb->last_error);
                    throw new Exception(__($wpdb->last_error,XH_SOCIAL));
                }
            }
            
            if(!$ext_user_info){
                if($wp_user_id>0){
                    $userdata['user_id']=$wp_user_id;
                }
                $wpdb->insert("{$wpdb->prefix}xh_social_channel_github", $userdata);
                if(!empty($wpdb->last_error)){
                    throw new Exception($wpdb->last_error);
                }
        
                if($wpdb->insert_id<=0){
                    XH_Social_Log::error(__('insert github user info failed'.print_r($userdata,true),XH_SOCIAL));
                    throw new Exception(__('insert github user info failed',XH_SOCIAL));
                }
        
                $ext_user_id=$wpdb->insert_id;
            } else{
                //user_id
                if($wp_user_id>0){
                    $userdata['user_id'] =$wp_user_id;
                }
                
                $wpdb->update("{$wpdb->prefix}xh_social_channel_github", $userdata,
                array(
                    'id'=>$ext_user_info->id
                ));
        
                if(!empty($wpdb->last_error)){
                    XH_Social_Log::error($wpdb->last_error);
                    throw new Exception($wpdb->last_error);
                }
        
                $ext_user_id=$ext_user_info->id;
            }
        
             return $this->process_login($ext_user_id,$wp_user_id>0);
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
            from {$wpdb->prefix}xh_social_channel_github
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
            from {$wpdb->prefix}XH_Social_Channel_Github
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
        $api = XH_Social_Add_On_Social_Github::instance();
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
        
         $url ='https://github.com/login/oauth/authorize';
        
        return $url.'?'.http_build_query(array(
            'client_id'=>$this->get_option('appid'),
            'state'=>str_shuffle(time()),
            'redirect_uri'=>$redirect_uri
        ));
    }
}
require_once XH_SOCIAL_DIR.'/includes/abstracts/abstract-xh-schema.php';
/**
* 微信接口
*
* @since 1.0.0
* @author ranj
*/
class XH_Social_Channel_Github_Model extends Abstract_XH_Social_Schema{

    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Schema::init()
     */
    public function init(){
        $collate=$this->get_collate();
        global $wpdb;
     
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}xh_social_channel_github` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` BIGINT(20) NULL,
            `_id` VARCHAR(64) NULL DEFAULT NULL,
            `nickname` TEXT NULL DEFAULT NULL,
            `avatar_url` TEXT NULL DEFAULT NULL,
            `html_url` TEXT NULL DEFAULT NULL,
            `company` TEXT NULL DEFAULT NULL,
            `blog` TEXT NULL DEFAULT NULL,
            `location` TEXT NULL DEFAULT NULL,
            `email` TEXT NULL DEFAULT NULL,
            `bio` TEXT NULL DEFAULT NULL,
            `login` TEXT NULL DEFAULT NULL,
            `last_update` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `_id_unique` (`_id`),
            UNIQUE INDEX `user_id_unique` (`user_id`)
        )
        $collate;");
    
        if(!empty($wpdb->last_error)){
            XH_Social_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
    }
}