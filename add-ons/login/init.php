<?php 

if ( ! defined( 'ABSPATH' ) ) {
    exit;
} 
/**
 * 登录注册
 * 实现自定义登录注册，找回密码页面
 * 
 * @author ranj
 * @since 1.0.0
 */
class XH_Social_Add_On_Login extends Abstract_XH_Social_Add_Ons{   
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var XH_Social_Add_On_Login
     */
    private static $_instance = null;
    
    /**
     * 当前插件目录
     * @var string
     * @since 1.0.0
     */
    private $dir;
    
    /**
     * Main Social Instance.
     *
     * @since 1.0.0
     * @static
     * @return XH_Social_Add_On_Login
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    private function __construct(){
        $this->id='add_ons_login';
        $this->title=__('Login Page(NEW)',XH_SOCIAL);
        $this->description=__('新增一个新的登录注册页面，替换wordpress默认的登录页面。',XH_SOCIAL);
        $this->version='1.0.0';
        $this->min_core_version = '1.0.0';
        $this->author=__('xunhuweb',XH_SOCIAL);
        $this->author_uri='https://www.wpweixin.net';
        $this->setting_uri = admin_url('admin.php?page=social_page_default&section=menu_default_account&sub=add_ons_login');
        $this->dir= rtrim ( trailingslashit( dirname( __FILE__ ) ), '/' );

        $this->init_form_fields();
        
        $this->enabled ='yes'== $this->get_option('enabled');
    }

    public function init_form_fields(){
        $this->form_fields=array(
            'enabled'=>array(
                'title'=>__('Enable/Disable',XH_SOCIAL),
                'type'=>'checkbox',
                'default'=>'no'
            ),
            'disable_wp'=>array(
                'title'=>__('Disable WP Login',XH_SOCIAL),
                'type'=>'checkbox',
                'label'=>__('Disable the wordpress default login page and use the new login/register page.',XH_SOCIAL),
                'default'=>'no'
            ),
            'password_mode'=>array(
                'title'=>__('Password input mode',XH_SOCIAL),
                'type'=>'select',
                'options'=>array(
                    'plaintext'=>__('Plaintext',XH_SOCIAL),
                    'password'=>__('Password',XH_SOCIAL)
                ),
            ),
            'page'=>array(
                'title'=>__('Page settings',XH_SOCIAL),
                'type'=>'tabs',
                'options'=>array(
                    'login'=>__('Login page',XH_SOCIAL),
                    'register'=>__('Register page',XH_SOCIAL),
                    'findpassword'=>__('Find password page',XH_SOCIAL),
                ),
            ),
            //==========================================
            'tab_login'=>array(
                'title' => __ ( 'Login page settings', XH_SOCIAL ),
                'type' => 'subtitle',
                'dividing'=>false,
                'tr_css'=>'tab-page tab-login'
            )
            ,
            'page_login_id'=>array(
                'title'=>__('Login Page',XH_SOCIAL),
                'type'=>'select',
                'func'=>true,
                'tr_css'=>'tab-page tab-login',
                'options'=>array($this,'get_page_options')
            ),
            'login_with_captcha'=>array(
                'title'=>__('Enable image captcha',XH_SOCIAL),
                'type'=>'checkbox',
                'tr_css'=>'tab-page tab-login',
                'label'=>__('Enable image captcha when login.',XH_SOCIAL),
            ),
            //==========================================
            'tab_register'=>array(
                'title' => __ ( 'Register page settings', XH_SOCIAL ),
                'type' => 'subtitle',
                'dividing'=>false,
                'tr_css'=>'tab-page tab-register'
            ),
            'page_register_id'=>array(
                'title'=>__('Register Page',XH_SOCIAL),
                'type'=>'select',
                'func'=>true,
                'tr_css'=>'tab-page tab-register',
                'options'=>array($this,'get_page_options')
            ),
            'enabled_mobile_login'=>array(
                'title'=>__('Bind mobile',XH_SOCIAL),
                'type'=>'checkbox',
                'tr_css'=>'tab-page tab-register',
                'label'=>__('Bind mobile when register.',XH_SOCIAL),
                'description'=>__('Before enable,"<a href="https://www.wpweixin.net/product/1090.html" target="_blank">Mobile(add-on)</a>" must be activated.',XH_SOCIAL),
                'default'=>'no'
            ),

            'register_terms_of_service_link'=>array(
                'title'=>__('Register Terms Of Service(link)',XH_SOCIAL),
                'type'=>'text',
                'tr_css'=>'tab-page tab-register',
                'placeholder'=>__('http://www.xxx.com/...(Leave blank,terms of service will be hidden).',XH_SOCIAL),
                'description'=>__('Terms Of Service under the register form.',XH_SOCIAL),
            ),
            'email_required'=>array(
                'title'=>__('Email Required',XH_SOCIAL),
                'type'=>'checkbox',
                'tr_css'=>'tab-page tab-register',
                'label'=>__('Email is required when register.',XH_SOCIAL),
            ),
            
            //==========================================
            'tab_findpassword'=>array(
                'title' => __ ( 'Find password page settings', XH_SOCIAL ),
                'type' => 'subtitle',
                'dividing'=>false,
                'tr_css'=>'tab-page tab-findpassword'
            ),
            'page_findpassword_id'=>array(
                'title'=>__('Find password Page',XH_SOCIAL),
                'type'=>'select',
                'func'=>true,
                'tr_css'=>'tab-page tab-findpassword',
                'options'=>array($this,'get_page_options')
            ),
            'findpassword_email_mode'=>array(
                'title'=>__('Via email mode',XH_SOCIAL),
                'type'=>'select',
                'tr_css'=>'tab-page tab-findpassword',
                'options'=>array(
                    'link'=>__('Reset password via email (new password)link.',XH_SOCIAL),
                    'code'=>__('Reset password via email verification code.',XH_SOCIAL)
                ),
            )
        );
    }
    
    /**
     * 短代码
     * @param array $shortcodes
     * @return array
     * @since 1.0.0
     */
    public function shortcodes($shortcodes){
        $shortcodes['xh_social_page_login']=array($this,'page_login');
        $shortcodes['xh_social_page_register']=array($this,'page_register');
        $shortcodes['xh_social_page_findpassword']=array($this,'page_findpassword');
        return $shortcodes;
    }
    
    public function on_install(){
        $this->init_page_login();
        $this->init_page_register();
        $this->init_page_findpassword();
    }
    
    public function on_load(){
        //插件模板
        add_filter('xh_social_page_templetes', array($this,'page_templetes'),10,1);
        if($this->enabled){
            add_filter('xh_social_shortcodes', array($this,'shortcodes'),10,1);
            add_filter('xh_social_ajax', array($this,'ajax'),10,1);
        }
        
        add_filter('retrieve_password_message', array($this,'retrieve_password_message'),10,4);
    }
    /**
     * 把邮件内多余的< >符号去掉
     * @param string $message
     * @param string $key
     * @param string $user_login
     * @param string $user_data
     */
    public function retrieve_password_message($message, $key, $user_login, $user_data){
        $url =network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');
        return str_replace('<' . $url . ">", $url, $message);
    }
    
    public function on_init(){
        add_filter('xh_social_admin_menu_menu_default_account', array($this,'admin_menu_account'),10,1);
       
        if($this->enabled){  
            //禁用wordpress默认登录页面
            if('yes'==$this->get_option('disable_wp')){
                add_action('login_init', array($this,'disable_wp_login'));
                add_filter('login_url', array($this,'login_url'),99,3);
                add_filter('register_url', array($this,'register_url'),99,1);
                add_filter('lostpassword_url', array($this,'lostpassword_url'),99,2);
            }
        }
        
        /**
         * 兼容低4.5低版本无法邮箱登录的bug
         */
        if(!has_filter('authenticate','wp_authenticate_email_password')){
            add_filter('authenticate', array($this,'wp_authenticate_email_password'),20, 3);
        }
    }
    public function wp_authenticate_email_password( $user, $email, $password ) {
        if ( $user instanceof WP_User ) {
            return $user;
        }
    
        if ( empty( $email ) || empty( $password ) ) {
            if ( is_wp_error( $user ) ) {
                return $user;
            }
    
            $error = new WP_Error();
    
            if ( empty( $email ) ) {
                $error->add( 'empty_username', __( '<strong>ERROR</strong>: The email field is empty.' ) ); // Uses 'empty_username' for back-compat with wp_signon()
            }
    
            if ( empty( $password ) ) {
                $error->add( 'empty_password', __( '<strong>ERROR</strong>: The password field is empty.' ) );
            }
    
            return $error;
        }
    
        if ( ! is_email( $email ) ) {
            return $user;
        }
    
        $user = get_user_by( 'email', $email );
    
        if ( ! $user ) {
            return new WP_Error( 'invalid_email',
                __( '<strong>ERROR</strong>: Invalid email address.' ) .
                ' <a href="' . wp_lostpassword_url() . '">' .
                __( 'Lost your password?' ) .
                '</a>'
                );
        }
    
        /** This filter is documented in wp-includes/user.php */
        $user = apply_filters( 'wp_authenticate_user', $user, $password );
    
        if ( is_wp_error( $user ) ) {
            return $user;
        }
    
        if ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
            return new WP_Error( 'incorrect_password',
                sprintf(
                    /* translators: %s: email address */
                    __( '<strong>ERROR</strong>: The password you entered for the email address %s is incorrect.' ),
                    '<strong>' . $email . '</strong>'
                    ) .
                ' <a href="' . wp_lostpassword_url() . '">' .
                __( 'Lost your password?' ) .
                '</a>'
                );
        }
    
        return $user;
    }
    public function lostpassword_url($url,$redirect){
        $page = $this->get_page_findpassword();
        if($page){
           return  get_page_link($page);
        }
        
        return $url;
    }
    
    public function login_url($login_url, $redirect, $force_reauth ){
        $page = $this->get_page_login();
        if($page){
            $url =get_page_link($page);
            if(empty($redirect)){
                return $url;
            }
            
            $params = array();
            $login_url = XH_Social_Helper_Uri::get_uri_without_params($url,$params);
            $params['redirect_to']=$redirect;
            $login_url.= "?".http_build_query($params);
            return $login_url;
        }
        
        return $login_url;
    }
    
    public function register_url($register_url){
        $page = $this->get_page_register();
        if($page){
            return get_page_link($page);
        }
        
        return $register_url;
    }
    
    /**
     * ajax
     * @param array $shortcodes
     * @return array
     * @since 1.0.0
     */
    public function ajax($shortcodes){
        $shortcodes["xh_social_{$this->id}"]=array($this,'do_ajax');
        return $shortcodes;
    }
    
    public function do_ajax(){
        $action ="xh_social_{$this->id}";
        
        $datas=shortcode_atts(array(
            'notice_str'=>null,
            'action'=>$action,
            $action=>null,
            'tab'=>null
        ), stripslashes_deep($_REQUEST));
        
        if(!XH_Social::instance()->WP->ajax_validate($datas,isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
           if($_SERVER['REQUEST_METHOD']=='GET'){
               XH_Social::instance()->WP->wp_die(XH_Social_Error::err_code(701));
               exit;
           }else{
               echo (XH_Social_Error::err_code(701)->to_json());
               exit;
           }
        }
        
        switch($datas['tab']){
            case 'register':
                $this->register($datas);
                break;
            case 'login':
                $this->login($datas);
                break;
            case 'resetpassword':
                $this->resetpassword($datas);
                break;
            case 'email_login_vcode':
                $userdata = array();
                $fields=null;
                try {
                    $fields = $this->email_valication_fields();
                } catch (Exception $e) {
                    echo XH_Social_Error::error_custom($e->getMessage())->to_json();
                    exit;
                }
                 
                if($fields){
                    foreach ($fields as $name=>$settings){
                        if(!isset($settings['section'])||!is_array($settings['section'])){
                            continue;
                        }
                         
                        if(!in_array('code', $settings['section'])){
                            continue;
                        }
                         
                        if(!isset($settings['validate'])||!is_callable($settings['validate'])){
                            continue;
                        }
                         
                        $userdata = call_user_func_array($settings['validate'],array($name,$userdata,$settings));
                         
                        if($userdata instanceof XH_Social_Error&& !XH_Social_Error::is_valid($userdata)){
                            echo $userdata->to_json();
                            exit;
                        }
                    }
                }
                 
                $userdata =apply_filters('xh_social_email_login_vcode_validate', $userdata);
                if(!XH_Social_Error::is_valid($userdata)){
                    echo $userdata->to_json();
                    exit;
                }
               
                if(!isset($userdata['email'])||!is_email($userdata['email'])){
                    echo XH_Social_Error::error_custom(__('email field is invalid!',XH_SOCIAL))->to_json();
                    exit;
                }
                $user = get_user_by('email', $userdata['email']);
                if(!$user){
                    echo XH_Social_Error::error_custom(__('There is no user registered with that email address.',XH_SOCIAL))->to_json();
                    exit;
                }
                
                $time = intval(XH_Social::instance()->session->get('social_login_email_last_send_time',0));
                $now = time();
                 
                if($time>$now){
                    echo XH_Social_Error::error_custom(sprintf(__('Please wait for %s seconds!',XH_SOCIAL),$time-$now))->to_json();
                    exit;
                }
                
                XH_Social::instance()->session->set('social_login_email_last_send_time',$now+60);
                 
                $code = substr(str_shuffle(time()), 0,6);
                XH_Social::instance()->session->set('social_login_email_code',$code);
                 
                $subject = apply_filters('wsocial_email_validate_subject',sprintf( __("[%s]identity verification",XH_SOCIAL),get_option('blogname')));
                $message = apply_filters('wsocial_email_validate_subject', __("Hello!Your verification code is:",XH_SOCIAL)."\r\n\r\n".$code."\r\n\r\n".__("If this was a mistake, just ignore this email and nothing will happen.",XH_SOCIAL));
                if(defined('XH_SOCIAL_MOBILE_TEST')){
                    echo XH_Social_Error::error_custom(print_r(array(
                        'subject'=>$subject,
                        'content'=>$message
                    ),true))->to_json();
                }
                 
                add_action('wp_mail_failed', function($error){
                    if(!$error instanceof WP_Error){
                        return;
                    }
                    
                    throw new Exception($error->get_error_message());
                },10,1);
                
                try {
                    if(!@wp_mail($userdata['email'], $subject, $message)){
                        echo XH_Social_Error::error_custom(__('Something is wrong when send email!',XH_SOCIAL))->to_json();
                        exit;
                    }
                } catch (Exception $e) {
                    echo XH_Social_Error::error_custom(__('Something is wrong when send email!',XH_SOCIAL).$e->getMessage())->to_json();
                    exit;
                }
                 
                echo XH_Social_Error::success()->to_json();
                exit;
        }
    }
    
    private function resetpassword($datas){
        $methods = $this->page_login_findpassword_methods();
        $method = isset($_REQUEST['method'])?stripslashes($_REQUEST['method']):null;
        if(!isset($methods[$method])){
            echo XH_Social_Error::err_code(600)->to_json();
            exit;
        }
        
        $userdata=array();
        foreach ($methods[$method]['fields'] as $name=>$settings){
            if(isset($settings['section'])&&is_array($settings['section'])){
                if(!in_array('login', $settings['section'])){
                    continue;
                }
            }
            
            if(isset($settings['validate'])){
                $userdata = call_user_func_array($settings['validate'],array($name,$userdata,$settings));
                if($userdata instanceof XH_Social_Error){
                    echo $userdata->to_json();
                    exit;
                }
            }
        }
        
        if(isset($methods[$method]['on_submit'])){
            $error = call_user_func($methods[$method]['on_submit'],$userdata);
            echo $error->to_json();
        }
        exit;
    }
    
    /**
     * 实现登录功能
     * @since 1.0.0
     */
    private function login($datas){      
        $userdata = array();
        $fields = $this->page_login_login_fields();
        if($fields){
            foreach ($fields as $name=>$settings){
                if(isset($settings['section'])&&is_array($settings['section'])){
                    if(!in_array('login', $settings['section'])){
                        continue;
                    }
                }
                
                if(isset($settings['validate'])){
                    $userdata = call_user_func_array($settings['validate'],array($name,$userdata,$settings));
                    if($userdata instanceof XH_Social_Error){
                        echo $userdata->to_json();
                        exit;
                    }
                }
            }
        }
        
        $userdata =apply_filters('xh_social_page_login_login_validate', stripslashes_deep($userdata));
        if($userdata instanceof XH_Social_Error){
            echo $userdata->to_json();
            exit;
        }
     
        $wp_user = wp_authenticate($userdata['user_login'],  $userdata['user_pass']);
        if ( is_wp_error($wp_user) ) {
            echo XH_Social_Error::error_custom(__('login name or password is invalid!',XH_SOCIAL))->to_json();
            exit;
        }
       
        do_action('xh_social_page_login_login_after',$wp_user,$userdata);
       
        $error = XH_Social::instance()->WP->do_wp_login($wp_user);
        if(!$error instanceof XH_Social_Error){
            $error=XH_Social_Error::success();
        }
        
        echo $error->to_json();
        exit;
    }
    
    /**
     * 实现注册功能
     */
    private function register($datas){
        $userdata = array();
        $fields = $this->page_login_register_fields();
        if($fields){
            foreach ($fields as $name=>$settings){
                if(isset($settings['section'])&&is_array($settings['section'])){
                    if(!in_array('login', $settings['section'])){
                        continue;
                    }
                }
                
                if(isset($settings['validate'])){
                    $userdata = call_user_func_array($settings['validate'],array($name,$userdata,$settings));
                    if($userdata instanceof XH_Social_Error){
                        echo $userdata->to_json();
                        exit;
                    }
                }
            }
        }
        
        $userdata =apply_filters('xh_social_page_login_register_validate', stripslashes_deep($userdata));
        if($userdata instanceof XH_Social_Error){
            echo $userdata->to_json();
            exit;
        }
        
        if(!isset($userdata['user_nicename'])){
            $userdata['user_nicename'] =XH_Social_Helper_String::guid();
        }
        
        if(!isset($userdata['user_login'])||empty($userdata['user_login'])){
            if(!isset($userdata['user_email'])||empty($userdata['user_email'])){
                echo XH_Social_Error::error_custom(__('User email is required!',XH_SOCIAL))->to_json();
                exit;
            }
            
           $userdata['user_login']=XH_Social::instance()->WP->generate_user_login($userdata['user_email']);
        }
        
        $wp_user_id =wp_insert_user($userdata);
        if(is_wp_error($wp_user_id)){
            echo XH_Social_Error::wp_error($wp_user_id)->to_json();
            exit;
        }
        
        $wp_user= get_userdata($wp_user_id);
        if(!$wp_user){
            echo XH_Social_Error::error_unknow()->to_json();
            exit;
        }
        
        do_action('xh_social_page_login_register_after',$wp_user,$userdata);
        
        $error =apply_filters('xh_social_page_login_register_new_user', XH_Social_Error::success(), $wp_user,$userdata);
        if(!XH_Social_Error::is_valid($error)){
            echo $error->to_json();
            exit;
        }
      
        do_action( 'register_new_user', $wp_user_id );
        $error = XH_Social::instance()->WP->do_wp_login($wp_user);
        if(!$error instanceof XH_Social_Error){
            $error=XH_Social_Error::success();
        }
        
        echo $error->to_json();
        exit;
    }
  
    /**
     * 禁用wordpress默认登录页面
     * 跳转到新的注册页面
     * @since 1.0.0
     */
    public function disable_wp_login(){
        if(!apply_filters('xh_social_disable_wp_login', $this->enabled&&empty($_POST)&&'yes'===$this->get_option('disable_wp'))){
            return;
        }
        
        $redirect_to=isset($_GET['redirect_to'])?esc_url_raw(urldecode($_GET['redirect_to'])):'';
        $action = isset($_GET['action'])?XH_Social_Helper_String::sanitize_key_ignorecase($_GET['action']):'';
       
        $redirect ='';
        switch ($action){
            case 'postpass':
            case 'logout':
            case 'rp':
            case 'resetpass':
                return;
            case 'lostpassword':
            case 'retrievepassword':
                $page = $this->get_page_findpassword();
                if($page){
                    $redirect = get_page_link($page);
                }
                break;
            case 'register':
                $page = $this->get_page_register();
                if($page){
                    $redirect = get_page_link($page);
                }
                break;
            default:
            case 'login':
                $page = $this->get_page_login();
                if($page){
                    $redirect = get_page_link($page);
                }
                break;
        }
        
        if(empty($redirect)){
            return;
        }
        
        $request = $_GET;
        unset($request['action']);
        if(!empty($redirect_to)){
            $request['redirect_to']=$redirect_to;
        }
        
        if(count($request)>0){
            $params = array();
            $redirect = XH_Social_Helper_Uri::get_uri_without_params($redirect,$params);
            $redirect.="?".http_build_query(array_merge($params,$request));
        }
        
        wp_redirect($redirect);
        exit;
    }
    
    /**
     * 页面模板
     * @param array $templetes
     * @return array
     * @since 1.0.0
     */
    public function page_templetes($templetes){
        $templetes[$this->dir]['account/login.php']=__('Social - Login',XH_SOCIAL);
        $templetes[$this->dir]['account/register.php']=__('Social - Register',XH_SOCIAL);
        $templetes[$this->dir]['account/findpassword.php']=__('Social - Find password',XH_SOCIAL);
        return $templetes;
    }
    
    /**
     * 初始化 account page
     * @return bool
     *  @since 1.0.0
     */
    private function init_page_login(){
        $page_id =intval($this->get_option('page_login_id',0));
        $page=null;
        if($page_id>0){
            return true;
        }
    
        $page_id =wp_insert_post(array(
            'post_type'=>'page',
            'post_name'=>'login',
            'post_title'=>__('Social - Login',XH_SOCIAL),
            'post_content'=>'[xh_social_page_login]',
            'post_status'=>'publish',
            'meta_input'=>array(
                '_wp_page_template'=>'social/account/login.php'
            )
        ),true);
    
        if(is_wp_error($page_id)){
            XH_Social_Log::error($page_id);
            throw new Exception($page_id->get_error_message());
        }
    
        $this->update_option('page_login_id', $page_id,true);
        return true;
    }
    
    private function init_page_findpassword(){
        $page_id =intval($this->get_option('page_findpassword_id',0));
    
        $page=null;
        if($page_id>0){
            return true;
        }
    
        $page_id =wp_insert_post(array(
            'post_type'=>'page',
            'post_name'=>'findpassword',
            'post_title'=>__('Social - Find password',XH_SOCIAL),
            'post_content'=>'[xh_social_page_findpassword]',
            'post_status'=>'publish',
            'meta_input'=>array(
                '_wp_page_template'=>'account/findpassword.php'
            )
        ),true);
    
        if(is_wp_error($page_id)){
            XH_Social_Log::error($page_id);
            throw new Exception($page_id->get_error_message());
        }
    
        $this->update_option('page_findpassword_id', $page_id,true);
        return true;
    }
    
    private function init_page_register(){
        $page_id =intval($this->get_option('page_register_id',0));
    
        $page=null;
        if($page_id>0){
            return true;
        }
    
        $page_id =wp_insert_post(array(
            'post_type'=>'page',
            'post_name'=>'register',
            'post_title'=>__('Social - Register',XH_SOCIAL),
            'post_content'=>'[xh_social_page_register]',
            'post_status'=>'publish',
            'meta_input'=>array(
                '_wp_page_template'=>'account/register.php'
            )
        ),true);
    
        if(is_wp_error($page_id)){
            XH_Social_Log::error($page_id);
            throw new Exception($page_id->get_error_message());
        }
    
        $this->update_option('page_register_id', $page_id,true);
        return true;
    }
    
    /**
     * 获取account page
     * @param string $not_exists_and_create
     * @return WP_Post|NULL
     * @since 1.0.0
     */
    public function get_page_login(){
        $page_id =intval($this->get_option('page_login_id',0));
    
        if($page_id<=0){
            return null;
        }
    
        return get_post($page_id);
    }
    
    public function get_page_register(){
        $page_id =intval($this->get_option('page_register_id',0));
    
        if($page_id<=0){
            return null;
        }
    
        return get_post($page_id);
    }
    public function get_page_findpassword(){
        $page_id =intval($this->get_option('page_findpassword_id',0));
    
        if($page_id<=0){
            return null;
        }
    
        return get_post($page_id);
    }
    /**
     * 账户设置 加入菜单
     * @param array $menus
     * @return array
     * @since 1.0.0
     */
    public function admin_menu_account($menus){
        $menus[]=$this;
        return $menus;
    }

    /**
     * @since 1.0.0
     * @return array
     */
    public function page_login_login_fields(){
        $fields=apply_filters('xh_social_page_login_login_fields',array(),0);
        
        $fields['login_name']=array(
                'title'=>__('Username,email or mobile',XH_SOCIAL),
                'type'=>'text',
                'required'=>true,
                'placeholder'=>__('Please enter username,email or mobile',XH_SOCIAL),
                'validate'=>function($name,$datas,$settings){
                    $user_login =isset($_POST[$name])?sanitize_user(trim($_POST[$name])):'';
                    if(isset($settings['required'])&&$settings['required']){
                        if(empty($user_login)){
                            return XH_Social_Error::error_custom(__('Username,email or mobile is required!',XH_SOCIAL));
                        }
                    }
                    
                    $datas['user_login']=$user_login;
                    return $datas;
                }
                
        );
        
        $fields=apply_filters('xh_social_page_login_login_fields',$fields,1);
        
        $fields['login_password']=array(
            'title'=>__('password',XH_SOCIAL),
            'type'=>'password',
            'required'=>true,
            'validate'=>function($name,$datas,$settings){
                $password =isset($_POST[$name])?trim($_POST[$name]):'';
                if(isset($settings['required'])&&$settings['required']){
                    if(empty($password)){
                        return XH_Social_Error::error_custom(__('password is required!',XH_SOCIAL));
                    }
                }
                
                $datas['user_pass']=$password;
                return $datas;
            }
        );
        
        $fields=apply_filters('xh_social_page_login_login_fields',$fields,2);
        
        //显示验证码
        if('yes'==$this->get_option('login_with_captcha')){
           $captcha_fields = XH_Social::instance()->WP->get_captcha_fields();
           $fields = apply_filters('xh_social_page_login_login_fields',array_merge($fields,$captcha_fields),3);
        }
        
        return apply_filters('xh_social_page_login_login_fields',$fields,4);
    }
    
    public function page_login_register_fields(){
        $fields=apply_filters('xh_social_page_login_register_fields',array(),0);
        
        $fields['register_user_login']=array(
            'title'=>__('user login',XH_SOCIAL),
            'type'=>'text',
            'required'=>true,
            'validate'=>function($name,$datas,$settings){
                $user_login = isset($_POST[$name])?sanitize_user(trim($_POST[$name])):'';   
                if(isset($settings['required'])&&$settings['required']){
                     if(empty($user_login)){
                        return XH_Social_Error::error_custom(__('user login is required!',XH_SOCIAL));
                     }
                }
                
                $datas['user_login']=$user_login;
                $datas['user_nicename']=XH_Social_Helper_String::guid();
                return $datas;
            }
        );
        
        $fields=apply_filters('xh_social_page_login_register_fields',$fields,1);
        
        $fields['register_user_email']=array(
            'title'=>__('user email',XH_SOCIAL),
            'type'=>'email',
            'required'=>'yes'==$this->get_option('email_required'),
            'validate'=>function($name,$datas,$settings){
                $email = isset($_POST[$name])?sanitize_email(trim($_POST[$name])):'';
                
                if(isset($settings['required'])&&$settings['required'])
                if(empty($email)){
                    return XH_Social_Error::error_custom(__('user email is required!',XH_SOCIAL));
                }
                
                if(!empty($email)&&!is_email($email)){
                    return XH_Social_Error::error_custom(__('user email is invalid!',XH_SOCIAL));
                }
                
                $datas['user_email']=$email;
                return $datas;
            }
        );
     
        $fields=apply_filters('xh_social_page_login_register_fields',$fields,2);

        if('yes'==$this->get_option('enabled_mobile_login')){
            $api =XH_Social::instance()->get_available_addon('wechat_social_add_ons_social_mobile');
            if($api&&$api->enabled){
                $fields = array_merge($fields,$api->page_mobile_login_fields(true));
                $fields= apply_filters('xh_social_page_login_register_fields',$fields,3);
            }
        }
        
        $password_mode = $this->get_option('password_mode','');
        switch ($password_mode){
            default:
            case 'plaintext':
                $fields['register_password']=array(
                    'title'=>__('Password',XH_SOCIAL),
                    'type'=>'text',
                    'required'=>true,
                    'validate'=>function($name,$datas,$settings){
                        $password = isset($_POST[$name])?trim($_POST[$name]):'';
                        if(isset($settings['required'])&&$settings['required']){
                            if(empty($password)){
                                return XH_Social_Error::error_custom(__('Password is required!',XH_SOCIAL));
                            }
                        }
                         
                        $datas['user_pass']=$password;
                        return $datas;
                    }
                );
                break;
            case 'password':
                $fields['register_password']=array(
                    'title'=>__('password',XH_SOCIAL),
                    'type'=>'password',
                    'required'=>true,
                    'validate'=>function($name,$datas,$settings){
                        $password = isset($_POST[$name])?trim($_POST[$name]):'';
                        if(isset($settings['required'])&&$settings['required']){
                            if(empty($password)){
                                return XH_Social_Error::error_custom(__('Password is required!',XH_SOCIAL));
                            }
                        }
                         
                        $datas['user_pass']=$password;
                        return $datas;
                    }
                );
                
                $fields['register_repassword']=array(
                    'title'=>__('confirm password',XH_SOCIAL),
                    'type'=>'password',
                    'required'=>true,
                    'validate'=>function($name,$datas,$settings){
                        $repassword = isset($_POST[$name])?trim($_POST[$name]):'';
                        $password = isset($_POST['register_password'])?trim($_POST['register_password']):'';
                        if($password!=$repassword){
                            return XH_Social_Error::error_custom(__('Password is not match twice input!',XH_SOCIAL));
                        }
                        return $datas;
                    }
                );
                break;
        }
        
        $fields= apply_filters('xh_social_page_login_register_fields',$fields,4);
     
        $fields['register_terms_of_service']=array(
            'type'=>function($fome_id,$name,$settings){
                $api = XH_Social_Add_On_Login::instance();
                $register_terms_of_service_link = $api->get_option('register_terms_of_service_link','');
                if(empty($register_terms_of_service_link)){
                    return '';
                }
                ob_start();
                ?>
                    <div class="form-group policy" style="margin-bottom:10px;">
                          <span class="left"><?php echo __('Agree and accept',XH_SOCIAL)?><a target="_blank" href="<?php echo $register_terms_of_service_link;?>"><?php echo __('《Terms Of Service》',XH_SOCIAL)?></a></span>
                    </div>
                <?php 
                return ob_get_clean();
            }
        );
        
        return apply_filters('xh_social_page_login_register_fields',$fields,5); 
    }
    
    public function page_login_findpassword_methods(){
        $findpassword_email_mode = $this->get_option('findpassword_email_mode','');
        $submit = null;
        switch ($findpassword_email_mode){
            default:
            case 'link':
                $email_fields =  array();
                $email_fields['email']=array(
                    'title'=>__('Username or email',XH_SOCIAL),
                    'type'=>'text',
                    'required'=>true,
                    'description'=>__('Enter your username or email address and we will send you a link to reset your password.',XH_SOCIAL),
                    'validate'=>function($name,$datas,$settings){
                        $email =isset($_POST[$name])?trim($_POST[$name]):'';
                        if(isset($settings['required'])&&$settings['required']){
                            if(empty($email)){
                                return XH_Social_Error::error_custom(__('Username or email is required!',XH_SOCIAL));
                            }
                        }
                    
                        $datas['email']=$email;
                        return $datas;
                    }
                );
                if('yes'!=XH_Social_Email_Api::instance()->get_option('disabled_captcha')){
                    $captcha_fields =XH_Social::instance()->WP->get_captcha_fields('captcha_email');
                    
                    $_fields['captcha_email'] = $captcha_fields['captcha'];
                    $_fields['captcha_email']['section']=array('login');
                    
                    $email_fields=array_merge($email_fields,$_fields);
                }
                $submit=__('Get new password',XH_SOCIAL);
                break;
            case 'code':
                $email_fields=$this->email_valication_fields(false);
               
                $password_mode = $this->get_option('password_mode','');
                switch ($password_mode){
                    default:
                    case 'plaintext':
                        $email_fields['email_reset_password']=array(
                            'title'=>__('new password',XH_SOCIAL),
                            'type'=>'text',
                            'required'=>true,
                            'validate'=>function($name,$datas,$settings){
                                $password = isset($_POST[$name])?trim($_POST[$name]):'';
                                if(isset($settings['required'])&&$settings['required']){
                                    if(empty($password)){
                                        return XH_Social_Error::error_custom(__('Password is required!',XH_SOCIAL));
                                    }
                                }
                                 
                                $datas['user_pass']=$password;
                                return $datas;
                            }
                        );
                        break;
                    case 'password':
                        $email_fields['email_reset_password']=array(
                            'title'=>__('password',XH_SOCIAL),
                            'type'=>'password',
                            'required'=>true,
                            'validate'=>function($name,$datas,$settings){
                                $password = isset($_POST[$name])?trim($_POST[$name]):'';
                                if(isset($settings['required'])&&$settings['required']){
                                    if(empty($password)){
                                        return XH_Social_Error::error_custom(__('Password is required!',XH_SOCIAL));
                                    }
                                }
                                 
                                $datas['user_pass']=$password;
                                return $datas;
                            }
                        );
                
                        $email_fields['email_reset_repassword']=array(
                            'title'=>__('confirm password',XH_SOCIAL),
                            'type'=>'password',
                            'required'=>true,
                            'validate'=>function($name,$datas,$settings){
                                $repassword = isset($_POST[$name])?trim($_POST[$name]):'';
                                $password = isset($_POST['email_reset_password'])?trim($_POST['email_reset_password']):'';
                                if($password!=$repassword){
                                    return XH_Social_Error::error_custom(__('Password is not match twice input!',XH_SOCIAL));
                                }
                                return $datas;
                            }
                        );
                        break;
                }
               
                $submit=__('Reset password',XH_SOCIAL);
                break;
        }
        
        return apply_filters('wsocial_findpassword_methods', array(
            'email'=>array(
                'title'=>__('via Username or email',XH_SOCIAL),
                'submit'=>$submit,
                'fields'=>$email_fields,
                'on_submit'=>function($datas){
                    $user_name_or_email =$datas['email'];
                    $api =XH_Social_Add_On_Login::instance();
                    $findpassword_email_mode =$api ->get_option('findpassword_email_mode','');
                    switch ($findpassword_email_mode){
                        default:
                        case 'link':
                            $error = $api->retrieve_password($user_name_or_email);
                            if(is_wp_error($error)){
                                return XH_Social_Error::wp_error($error);
                            }
                            wp_logout();
                            $api->clear_email_validate_code();
                            return XH_Social_Error::success();
                        case 'code':
                            $wp_user = null;
                            if(is_email($user_name_or_email)){
                                $wp_user = get_user_by('email', $user_name_or_email);
                            }else{
                                $wp_user = get_user_by('login', $user_name_or_email);
                            }
                            
                            if(!$wp_user){
                                return XH_Social_Error::error_custom(__('Invalid username or email!',XH_SOCIAL));
                            }
                          
                            reset_password($wp_user,$datas['user_pass']);
                            clean_user_cache($wp_user);
                            $api->clear_email_validate_code();
                            wp_logout();
                            return XH_Social_Error::success();
                    }
                }
            )
        ));
    }
    function retrieve_password($user_name_or_email) {
        $errors = new WP_Error();
    
        if ( empty( $user_name_or_email ) ) {
            $errors->add('empty_username', __('<strong>ERROR</strong>: Enter a username or email address.'));
        } elseif ( strpos( $user_name_or_email, '@' ) ) {
            $user_data = get_user_by( 'email', trim( wp_unslash( $user_name_or_email ) ) );
            if ( empty( $user_data ) )
                $errors->add('invalid_email', __('<strong>ERROR</strong>: There is no user registered with that email address.'));
        } else {
            $login = trim($user_name_or_email);
            $user_data = get_user_by('login', $login);
        }
    
        /**
         * Fires before errors are returned from a password reset request.
         *
         * @since 2.1.0
         * @since 4.4.0 Added the `$errors` parameter.
         *
         * @param WP_Error $errors A WP_Error object containing any errors generated
         *                         by using invalid credentials.
         */
        do_action( 'lostpassword_post', $errors );
    
        if ( $errors->get_error_code() )
            return $errors;
    
            if ( !$user_data ) {
                $errors->add('invalidcombo', __('<strong>ERROR</strong>: Invalid username or email.'));
                return $errors;
            }
    
            // Redefining user_login ensures we return the right case in the email.
            $user_login = $user_data->user_login;
            $user_email = $user_data->user_email;
            $key = get_password_reset_key( $user_data );
    
            if ( is_wp_error( $key ) ) {
                return $key;
            }
    
            $message = __('Someone has requested a password reset for the following account:') . "\r\n\r\n";
            $message .= network_home_url( '/' ) . "\r\n\r\n";
            $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
            $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
            $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
            $message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . ">\r\n";
    
            if ( is_multisite() ) {
                $blogname = get_network()->site_name;
            } else {
                /*
                 * The blogname option is escaped with esc_html on the way into the database
                 * in sanitize_option we want to reverse this for the plain text arena of emails.
                 */
                $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
            }
    
            /* translators: Password reset email subject. 1: Site name */
            $title = sprintf( __('[%s] Password Reset'), $blogname );
    
            /**
             * Filters the subject of the password reset email.
             *
             * @since 2.8.0
             * @since 4.4.0 Added the `$user_login` and `$user_data` parameters.
             *
             * @param string  $title      Default email title.
             * @param string  $user_login The username for the user.
             * @param WP_User $user_data  WP_User object.
             */
            $title = apply_filters( 'retrieve_password_title', $title, $user_login, $user_data );
    
            /**
             * Filters the message body of the password reset mail.
             *
             * @since 2.8.0
             * @since 4.1.0 Added `$user_login` and `$user_data` parameters.
             *
             * @param string  $message    Default mail message.
             * @param string  $key        The activation key.
             * @param string  $user_login The username for the user.
             * @param WP_User $user_data  WP_User object.
             */
            $message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user_data );
    
            if ( $message && !wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) )
                return new WP_Error('email_send_failed', __('The email could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function.') );
    
                return true;
    }
    public function page_login($atts=array(), $content=null){
        XH_Social_Temp_Helper::set('atts', array(
            'atts'=>$atts,
            'content'=>$content
        ),'templete');
        
        ob_start();
        require XH_Social::instance()->WP->get_template($this->dir, 'account/login-content.php');
        return ob_get_clean();
    }
       
    public function page_register($atts=array(), $content=null){
       XH_Social_Temp_Helper::set('atts', array(
           'atts'=>$atts,
           'content'=>$content
       ),'templete');
       
       ob_start();
       require XH_Social::instance()->WP->get_template($this->dir, 'account/register-content.php');
       return ob_get_clean();
    }
    
    public function page_findpassword($atts=array(), $content=null){
        XH_Social_Temp_Helper::set('atts', array(
            'atts'=>$atts,
            'content'=>$content
        ),'templete');
         
        ob_start();
        require XH_Social::instance()->WP->get_template($this->dir, 'account/findpassword-content.php');
        return ob_get_clean();
    }
    
    public function email_valication_fields(){
        $fields = apply_filters('xh_social_email_valication_fields',array(),0);
    
        $fields['email'] =  array(
            'title'=>__('Username or email',XH_SOCIAL),
            'type'=>'text',
            //'description'=>__('Enter your username or email address  and we will send you a code to verify your identity.',XH_SOCIAL),
            'required'=>true,
            'section'=>array('login','code'),
            'validate'=>function ($name,$datas,$settings){
                    $email =isset($_REQUEST[$name])?trim($_REQUEST[$name]):'';
                    if(empty($email)){
                        return XH_Social_Error::error_custom(__('Username or email is required!',XH_SOCIAL));
                    }
                
                    if(!is_email($email)){
                        $wp_user = get_user_by('login', $email);
                        if(!$wp_user){
                            return XH_Social_Error::error_custom(__('Invalid username or email.',XH_SOCIAL));
                        }
                        
                        if(empty($wp_user->user_email)||!is_email($wp_user->user_email)){
                            return XH_Social_Error::error_custom(__('There is no email address registered with that username.',XH_SOCIAL));
                        }
                        
                        $email=$wp_user->user_email;
                    }else{
                        $user = get_user_by('email', $email);
                        if(!$user){
                            return XH_Social_Error::error_custom(__('There is no user registered with that email address.',XH_SOCIAL));
                        }
                    }
                
                    $last_send_email = XH_Social::instance()->session->get('social_login_email_last_send','');
                     
                    $datas['email']=$email;
                
                    if(!empty($last_send_email)&&$last_send_email!= $datas['email']){
                        XH_Social::instance()->WP->clear_captcha();
                        $api =XH_Social_Add_On_Login::instance();
                        $api->clear_email_validate_code();
                    }
                
                    XH_Social::instance()->session->set('social_login_email_last_send',$datas['email']);
                    return $datas;
                }
        );
    
        $fields=apply_filters('xh_social_email_valication_fields',$fields,1);
    
        if('yes'!=$this->get_option('disabled_captcha')){
            $captcha_fields =XH_Social::instance()->WP->get_captcha_fields('captcha_email');
                    
            $_fields['captcha_email'] = $captcha_fields['captcha'];
            $_fields['captcha_email']['section']=array('code');
                    
            $fields=apply_filters('xh_social_email_valication_fields',array_merge($fields,$_fields),2);
        }
    
        $fields['email_vcode']= array(
            'validate'=>function($name,$datas,$settings){
                $code_post =isset($_REQUEST[$name])?trim($_REQUEST[$name]):'';
                if(empty($code_post)){
                    return XH_Social_Error::error_custom(__('email captcha is required!',XH_SOCIAL));
                }
        
                $code = XH_Social::instance()->session->get('social_login_email_code');
                if(empty($code)){
                    return XH_Social_Error::error_custom(__('please get the email captcha again!',XH_SOCIAL));
                }
        
                if(strcasecmp($code, $code_post) !==0){
                    return XH_Social_Error::error_custom(__('email captcha is invalid!',XH_SOCIAL));
                }
        
                return $datas;
            },
            'section'=>array('login'),
            'type'=>function ($form_id,$data_name,$settings){
                $form_name = $data_name;
                $name = $form_id."_".$data_name;
                $api =XH_Social_Add_On_Login::instance();
                ob_start();
                $action ="xh_social_{$api->id}";
                $params = array(
                    'action'=>$action ,
                    'tab'=>'email_login_vcode',
                    $action=>wp_create_nonce($action),
                    'notice_str'=>str_shuffle(time())
                );
                $params['hash']=XH_Social_Helper::generate_hash($params, XH_Social::instance()->get_hash_key());
                ?>
               <div class="xh-input-group">
                    <input name="<?php echo esc_attr($name);?>" type="text" id="<?php echo esc_attr($name);?>" maxlength="6" class="form-control" placeholder="<?php echo __('email captcha',XH_SOCIAL)?>">
                    <span class="xh-input-group-btn"><button type="button" style="min-width:96px;" class="xh-btn xh-btn-default" id="btn-code-<?php echo esc_attr($name);?>"><?php echo __('Send Code',XH_SOCIAL)?></button></span>
                </div>
                
                <script type="text/javascript">
        			(function($){
        				if(!$){return;}
        
        				$('#btn-code-<?php echo esc_attr($name);?>').click(function(){
            				var $this = $(this);
        					var data =<?php echo json_encode($params);?>;
        					<?php XH_Social_Helper_Html_Form::generate_submit_data($form_id, 'data');?>
        					window.xh_social_view.reset();
        					if(window.xh_social_view._email_v_loading){
        						return;
        					}
        					
        					$this.attr('disabled','disabled').text('<?php echo __('Processing...',XH_SOCIAL)?>');
        				
        					$.ajax({
        			            url: '<?php echo XH_Social::instance()->ajax_url()?>',
        			            type: 'post',
        			            timeout: 60 * 1000,
        			            async: true,
        			            cache: false,
        			            data: data,
        			            dataType: 'json',
        			            success: function(m) {
        			            	if(m.errcode!=0){
        				            	window.xh_social_view.error(m.errmsg);
        				            	$this.removeAttr('disabled').text('<?php echo __('Send Code',XH_SOCIAL)?>');
        				            	return;
        							}
        			            
        							var time = 60;
        							if(window.xh_social_view._interval){
        								window.xh_social_view._email_v_loading=false;
        								clearInterval(window.xh_social_view._interval);
        							}
        							
        							window.xh_social_view._email_v_loading=true;
        							window.xh_social_view._interval = setInterval(function(){
        								if(time<=0){
        									window.xh_social_view._email_v_loading=false;
        									$this.removeAttr('disabled').text('<?php echo __('Send Code',XH_SOCIAL)?>');
        									if(window.xh_social_view._interval){
            									clearInterval(window.xh_social_view._interval);
                							}
        									return;
        								}
        								time--;
        								$this.text('<?php echo __('Resend',XH_SOCIAL)?>('+time+')');
        							},1000);
        			            },error:function(e){
        			            	$this.removeAttr('disabled').text('<?php echo __('Send Code',XH_SOCIAL)?>');
        							console.error(e.responseText);
        				         }
        			         });
        				});
        
        			})(jQuery);
                </script>
                <?php 
                 XH_Social_Helper_Html_Form::generate_field_scripts($form_id, $data_name);
                return ob_get_clean();
            });
    
        return apply_filters('xh_social_email_valication_fields',$fields,3);
    }
    
  
    public function clear_email_validate_code(){
        XH_Social::instance()->session->__unset('social_login_email_code');
        XH_Social::instance()->session->__unset('social_login_email_last_send');
    }
}



return XH_Social_Add_On_Login::instance();
?>