<?php 
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
        $this->dir= rtrim ( plugin_dir_path ( __FILE__ ), '/' );

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
            'page_login_id'=>array(
                'title'=>__('Login Page',XH_SOCIAL),
                'type'=>'select',
                'func'=>true,
                'options'=>array($this,'get_page_options')
            ),
            'page_register_id'=>array(
                'title'=>__('Register Page',XH_SOCIAL),
                'type'=>'select',
                'func'=>true,
                'options'=>array($this,'get_page_options')
            ),
            'enabled_mobile_login'=>array(
                'title'=>__('Mobile',XH_SOCIAL),
                'type'=>'checkbox',
                'label'=>__('When register a new account,mobile is required.',XH_SOCIAL),
                'description'=>__('Before enable,"<a href="https://www.wpweixin.net/product/1090.html" target="_blank">Mobile(add-on)</a>" must be activated.',XH_SOCIAL),
                'default'=>'no'
            ),
            'register_terms_of_service_link'=>array(
                'title'=>__('Register Terms Of Service(link)',XH_SOCIAL),
                'type'=>'text',
                'placeholder'=>__('http://www.xxx.com/...(Leave blank,terms of service will be hidden).',XH_SOCIAL),
                'description'=>__('Terms Of Service under the register form(before submit button).',XH_SOCIAL),
            ),
            'login_with_captcha'=>array(
                'title'=>__('Login With Iamge Captcha',XH_SOCIAL),
                'type'=>'checkbox',
                'description'=>__('Iamge captcha in the login form(before submit button).',XH_SOCIAL),
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
        return $shortcodes;
    }
    
    public function on_install(){
        $this->init_page_login();
        $this->init_page_register();
    }
    
    public function on_load(){
        //插件模板
        add_filter('xh_social_page_templetes', array($this,'page_templetes'),10,1);
        if($this->enabled){
            add_filter('xh_social_shortcodes', array($this,'shortcodes'),10,1);
            add_filter('xh_social_ajax', array($this,'ajax'),10,1);
        }
    }
    
    public function on_init(){
        add_filter('xh_social_admin_menu_menu_default_account', array($this,'admin_menu_account'),10,1);
       
        if($this->enabled){  
            //禁用wordpress默认登录页面
            if('yes'==$this->get_option('disable_wp')){
                add_action('login_init', array($this,'disable_wp_login'));
            }
        }
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
        $datas = array(
            'notice_str'=>isset($_REQUEST['notice_str'])?XH_Social_Helper_String::sanitize_key_ignorecase($_REQUEST['notice_str']):'',
            'action'=>isset($_REQUEST['action'])?XH_Social_Helper_String::sanitize_key_ignorecase($_REQUEST['action']):'',
            'tab'=>isset($_REQUEST['tab'])?XH_Social_Helper_String::sanitize_key_ignorecase($_REQUEST['tab']):'',
        );
         
        $hash=XH_Social_Helper::generate_hash($datas, XH_Social::instance()->get_hash_key());
        if(!isset($_REQUEST['hash'])||$hash!=XH_Social_Helper_String::sanitize_key_ignorecase($_REQUEST['hash'])){
            echo (XH_Social_Error::err_code(701)->to_json());
            exit;
        }
    
        switch($datas['tab']){
            case 'register':
                $this->register($datas);
                break;
            case 'login':
                $this->login($datas);
                break;
            case 'reset_password':
                break;
        }
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
                if(isset($settings['validate'])){
                    $userdata = call_user_func_array($settings['validate'],array($name,$userdata,$settings));
                    if(!XH_Social_Error::is_valid($userdata)){
                        echo $userdata->to_json();
                        exit;
                    }
                }
            }
        }
        
        $userdata =apply_filters('xh_social_page_login_login_validate', $userdata);
        if(!XH_Social_Error::is_valid($userdata)){
            echo $userdata->to_json();
            exit;
        }
         
        $wp_user = wp_authenticate($userdata['user_login'],  $userdata['user_pass']);
        if ( is_wp_error($wp_user) ) {
            echo XH_Social_Error::error_custom(__('login name or password is invalid!',XH_SOCIAL))->to_json();
            exit;
        }
       
        do_action('xh_social_page_login_login_after',$wp_user,$userdata);
       
        XH_Social::instance()->WP->do_wp_login($wp_user);
        //刷新当前用户信息
        wp_set_current_user($wp_user->ID);
       
        echo XH_Social_Error::success()->to_json();
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
                    if(!XH_Social_Error::is_valid($userdata)){
                        echo $userdata->to_json();
                        exit;
                    }
                }
            }
        }
        
        $userdata =apply_filters('xh_social_page_login_register_validate', $userdata);
        if(!XH_Social_Error::is_valid($userdata)){
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
        
        //手机注册
        if('yes'==$this->get_option('enabled_mobile_login')){
            $api =XH_Social::instance()->get_available_addon('wechat_social_add_ons_social_mobile');
            if($api&&$api->enabled){
                $enable_mobile = true;
                $ext_user_id =XH_Social_Channel_Mobile::instance()->create_ext_user($userdata['mobile'],$wp_user_id);
                if($ext_user_id instanceof XH_Social_Error){
                    echo new XH_Social_Error(1001,__('注册成功,但手机绑定失败(你可以在用户中心重新绑定)！',XH_SOCIAL));
                    exit;
                }
        
                XH_Social_Add_On_Social_Mobile::instance()->clear_mobile_validate_code();
            }
        }
        
        XH_Social::instance()->WP->do_wp_login($wp_user);
        //刷新当前用户信息
        wp_set_current_user($wp_user_id);
         
        
        echo XH_Social_Error::success()->to_json();
        exit;
    }
  
    /**
     * 禁用wordpress默认登录页面
     * 跳转到新的注册页面
     * @since 1.0.0
     */
    public function disable_wp_login(){
        if(!$this->enabled){
            return;
        }
        
        if(!empty($_POST)){
            return;
        }
        
        $redirect_to=isset($_GET['redirect_to'])?esc_url_raw(urldecode($_GET['redirect_to'])):'';
        $action = isset($_GET['action'])?XH_Social_Helper_String::sanitize_key_ignorecase($_GET['action']):'';
       
        $redirect ='';
        switch ($action){
            case 'postpass':
            case 'logout':
            case 'lostpassword':
            case 'retrievepassword':
            case 'resetpass':
            case 'rp':
            return;
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
        
        if(!empty($redirect_to)){
            $params = array();
            $redirect = XH_Social_Helper_Uri::get_uri_without_params($redirect,$params);
            $params['redirect_to']=$redirect_to;
            $redirect.="?".http_build_query($params);
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
        $templetes[$this->dir]['social/account/login.php']=__('Social - Login',XH_SOCIAL);
        $templetes[$this->dir]['social/account/register.php']=__('Social - Register',XH_SOCIAL);
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
    
        $this->update_option('page_login_id', $page_id,false);
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
                '_wp_page_template'=>'social/account/register.php'
            )
        ),true);
    
        if(is_wp_error($page_id)){
            XH_Social_Log::error($page_id);
            throw new Exception($page_id->get_error_message());
        }
    
        $this->update_option('page_register_id', $page_id,false);
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

    private function page_login_login_fields(){
        $fields=apply_filters('xh_social_page_login_login_fields',array(),0);
        
        $fields['login_name']=array(
                'title'=>__('user login',XH_SOCIAL),
                'type'=>'text',
                'required'=>true,
                'placeholder'=>__('Please enter userlogin/email/mobile',XH_SOCIAL),
                'validate'=>function($name,$datas,$settings){
                    $user_login =isset($_POST[$name])?sanitize_user(trim($_POST[$name])):'';
                    if(isset($settings['required'])&&$settings['required']){
                        if(empty($user_login)){
                            return XH_Social_Error::error_custom(__('user login is required!',XH_SOCIAL));
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
    
    private function page_login_register_fields(){
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
        
        $fields['register_password']=array(
            'title'=>__('password',XH_SOCIAL),
            'type'=>'text',
            'required'=>true,
            'validate'=>function($name,$datas,$settings){
                $password = isset($_POST[$name])?trim($_POST[$name]):'';
                if(isset($settings['required'])&&$settings['required']){
                     if(empty($password)){
                        return XH_Social_Error::error_custom(__('password is required!',XH_SOCIAL));
                     }
                }
               
                $datas['user_pass']=$password;
                return $datas;
            }
        );
        
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
    
    public function page_login($attrs=array(), $innerhtml=''){
        $log_on_callback_uri=esc_url_raw(XH_Social_Shortcodes::get_attr($attrs, 'redirect_to'));
        if(empty($log_on_callback_uri)){
            if(isset($_GET['redirect_to'])){  
                $log_on_callback_uri =esc_url_raw(urldecode($_GET['redirect_to']));
            }
        }
        
        if(empty($log_on_callback_uri)){
            $log_on_callback_uri =home_url('/');
        }
        
        if(strcasecmp(XH_Social_Helper_Uri::get_location_uri(), $log_on_callback_uri)===0){
            $log_on_callback_uri =home_url('/');
        }
        
        XH_Social::instance()->session->set('social_login_location_uri',$log_on_callback_uri);
       
        do_action('xh_social_page_login_before');
        
        $action = apply_filters('xh_social_page_login_before', null);
        if(!empty($action)){
            return $action;
        }
        
        if(is_user_logged_in()){
           return XH_Social::instance()->WP->wp_loggout_html($log_on_callback_uri);
        }
                
        ob_start();
        ?>
            <div class="xh-regbox">
    			<div class="xh-title" id="form-title"><?php echo __('Login',XH_SOCIAL)?></div>
    			<form class="xh-form">
        			<div id="fields-error"></div>
                        <?php 
                           $fields = $this->page_login_login_fields(); 
                           echo XH_Social_Helper_Html_Form::generate_html('login',$fields);
                           
                           do_action('xh_social_page_login_login_form');
                        ?>
                        <div class="xh-form-group mt10">
                            <button type="button" id="btn-login" onclick="window.xh_social_view.login();" class="xh-btn xh-btn-primary xh-btn-block xh-btn-lg"><?php echo __('Log On',XH_SOCIAL)?></button>
                        </div>
                    	<?php 
                    	$channels = XH_Social::instance()->channel->get_social_channels(array('login'));
                    	if(count($channels)>0){
                    	    ?>
                    	    <div class="xh-form-group xh-mT20">
                                <label><?php echo __('Quick Login',XH_SOCIAL)?></label>
                               <div class="xh-social">
                                   <?php foreach ($channels as $channel){
                                       ?><a href="<?php echo XH_Social::instance()->channel->get_authorization_redirect_uri($channel->id,$log_on_callback_uri);?>" class="xh-social-item" style="background:url(<?php echo $channel->icon?>) no-repeat transparent;"></a><?php 
                                   }?>
                               </div>
                            </div>
                    	    <?php 
                    	}
                    	?>
    			</form>
    		</div>
    		
    		<script type="text/javascript">
    			(function($){
    				window.xh_social_view={
    					loading:false,
    					reset:function(){
    						$('.xh-alert').empty().css('display','none');
    					},
    					error:function(msg){
    						$('#fields-error').html('<div class="xh-alert xh-alert-danger" role="alert">'+msg+' </div>').css('display','block');
    					},
    					success:function(msg){
    						$('#fields-error').html('<div class="xh-alert xh-alert-success" role="alert">'+msg+' </div>').css('display','block');
    					},
    					login:function(){
    						this.reset();
    						<?php 
    						$data = array(
    						    'notice_str'=>str_shuffle(time()),
    						    'action'=>"xh_social_{$this->id}",
    						    'tab'=>'login'
    						);
    						
    						$data['hash']= XH_Social_Helper::generate_hash($data,XH_Social::instance()->get_hash_key());
    						?>
    						var data=<?php echo json_encode($data);?>;
    						<?php XH_Social_Helper_Html_Form::generate_submit_data('login', 'data');?>
    						if(this.loading){
    							return;
    						}
    						
    						$('#btn-login').attr('disabled','disabled').text('<?php print __('loading...',XH_SOCIAL)?>')
    						this.loading=true;
    
    						jQuery.ajax({
    				            url: '<?php echo XH_Social::instance()->ajax_url()?>',
    				            type: 'post',
    				            timeout: 60 * 1000,
    				            async: true,
    				            cache: false,
    				            data: data,
    				            dataType: 'json',
    				            complete: function() {
    				            	$('#btn-login').removeAttr('disabled').text('<?php print __('Log On',XH_SOCIAL)?>')
    				            	window.xh_social_view.loading=false;
    				            },
    				            success: function(m) {
    				            	if(m.errcode==405||m.errcode==0){
    				            		window.xh_social_view.success('<?php print __('Congratulations, log on successfully!',XH_SOCIAL);?>');   				           
    				            		location.href='<?php echo $log_on_callback_uri?>';
    									return;
    								}
    				            	
    				            	window.xh_social_view.error(m.errmsg);
    				            },
    				            error:function(e){
    				            	window.xh_social_view.error('<?php print __('Internal Server Error!',XH_SOCIAL);?>');
    				            	console.error(e.responseText);
    				            }
    				         });
    					}
    				};
    			})(jQuery);
    		</script>
            <?php 
            return ob_get_clean();
       }
       
       public function page_register($attrs=array(), $innerhtml=''){
            $log_on_callback_uri=esc_url_raw(XH_Social_Shortcodes::get_attr($attrs, 'redirect_to'));
            if(empty($log_on_callback_uri)){
                if(isset($_GET['redirect_to'])){  
                    $log_on_callback_uri =esc_url_raw(urldecode($_GET['redirect_to']));
                }
            }
            
            if(empty($log_on_callback_uri)){
                $log_on_callback_uri =home_url('/');
            }
            
            if(strcasecmp(XH_Social_Helper_Uri::get_location_uri(), $log_on_callback_uri)===0){
                $log_on_callback_uri =home_url('/');
            }
            
            XH_Social::instance()->session->set('social_login_location_uri',$log_on_callback_uri);
            do_action('xh_social_page_register_before');
            
            $action = apply_filters('xh_social_page_register_before', null);
            if(!empty($action)){
                return $action;
            }
            
            if(is_user_logged_in()){
                 return XH_Social::instance()->WP->wp_loggout_html($log_on_callback_uri);
            }
            
           ob_start();
           ?>
           <div class="xh-regbox">
   			<div class="xh-title" id="form-title"><?php echo __('Register',XH_SOCIAL)?></div>
   			<form class="xh-form">
       			<div id="fields-error"></div>
               <?php 
                   $fields = $this->page_login_register_fields();
                   echo XH_Social_Helper_Html_Form::generate_html('register',$fields);
                   do_action('xh_social_page_login_register_form');
               ?>
               <div class="xh-form-group mt10">
                   <button type="button" id="btn-register" onclick="window.xh_social_view.register();" class="xh-btn xh-btn-primary xh-btn-block xh-btn-lg"><?php echo __('Log In',XH_SOCIAL)?></button>
               </div>
   			</form>
   		</div>
   		
   		<script type="text/javascript">
   			(function($){
   				window.xh_social_view={
   					loading:false,
   					reset:function(){
   						$('.xh-alert').empty().css('display','none');
   					},
   					error:function(msg){
   						$('#fields-error').html('<div class="xh-alert xh-alert-danger" role="alert">'+msg+' </div>').css('display','block');
   					},
   					warning:function(msg){
   						$('#fields-error').html('<div class="xh-alert xh-alert-warning" role="alert">'+msg+' </div>').css('display','block');
   					},
   					success:function(msg){
   						$('#fields-error').html('<div class="xh-alert xh-alert-success" role="alert">'+msg+' </div>').css('display','block');
   					},
   					register:function(){
   						this.reset();
   						<?php 
   						$data = array(
   						    'notice_str'=>str_shuffle(time()),
   						    'action'=>"xh_social_{$this->id}",
   						    'tab'=>'register'
   						);
   						
   						$data['hash']= XH_Social_Helper::generate_hash($data,XH_Social::instance()->get_hash_key());
   						?>
   						var data=<?php echo json_encode($data);?>;
   						<?php XH_Social_Helper_Html_Form::generate_submit_data('register', 'data');?>
   						
   						if(this.loading){
   							return;
   						}
   						
   						$('#btn-register').attr('disabled','disabled').text('<?php print __('loading...',XH_SOCIAL)?>')
   						this.loading=true;
   
   						jQuery.ajax({
   				            url: '<?php echo XH_Social::instance()->ajax_url()?>',
   				            type: 'post',
   				            timeout: 60 * 1000,
   				            async: true,
   				            cache: false,
   				            data: data,
   				            dataType: 'json',
   				            complete: function() {
   				            	$('#btn-register').removeAttr('disabled').text('<?php print __('Log In',XH_SOCIAL)?>')
   				            	window.xh_social_view.loading=false;
   				            },
   				            success: function(m) {
   				            	if(m.errcode==0){
   				            		window.xh_social_view.success('<?php print __('Congratulations, registered successfully!',XH_SOCIAL);?>');
   									location.href='<?php echo $log_on_callback_uri?>';
   									return;
   								}

   								if(m.errcode==1001){
   									window.xh_social_view.warning(m.errmsg);
   									location.href='<?php echo admin_url('/')?>';
   									return;
   	   							}
   				            	
   				            	window.xh_social_view.error(m.errmsg);
   				            },
   				            error:function(e){
   				            	window.xh_social_view.error('<?php print __('Internal Server Error!',XH_SOCIAL);?>');
   				            	console.error(e.responseText);
   				            }
   				         });
   					}
   				};
   			})(jQuery);
   		</script>
           <?php 
           return ob_get_clean();
      }
          
}

return XH_Social_Add_On_Login::instance();
?>