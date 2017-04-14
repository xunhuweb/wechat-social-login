<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * Abstract Add-on Class
 *
 * add-on api
 *
 * @since 1.0.0
 * @author ranj
 */
abstract class Abstract_XH_Social_Add_Ons extends Abstract_XH_Social_Settings {
    /**
     * 插件版本
     * @var string
     * @since 1.0.0
     */
    public $version='1.0.0';
    
    /**
     * 作者
     * @var string
     * @since 1.0.0
     */
    public $author;
    
    /**
     * 插件介绍地址
     * @var string
     * @since 1.0.0
     */
    public $plugin_uri;
    
    /**
     * 作者地址
     * @var string
     * @since 1.0.0
     */
    public $author_uri;
    
    /**
     * 子插件设置地址
     * @var string
     * @since 1.0.0
     */
    public $setting_uri;
    
    /**
     * 第三方插件依赖
     * @var array
     * @since 1.0.0
     *  array(
     *      'id1'=>array(
     *          title1
     *      ), 
     *      'id2'=>array(
     *          title2
     *      )
     *  )
     */
    public $depends=array();
    
    /**
     * 要求核心插件最低版本
     * @var string
     * @since 1.0.0
     */
    public $min_core_version='1.0.0';
    
    /**
     * 插件是否已启用
     * @var bool
     * @since 1.0.0
     */
    public $is_active;
    
    /**
     * 插件启用时
     * @since 1.0.0
     */
    public function on_install(){}
    
    /**
     * 插件卸载时
     * @since 1.0.0
     */
    public function on_uninstall(){}
    /**
     * 插件加载时
     * @since 1.0.0
     */
	public function on_load(){}
	
	/**
	 * 插件
	 * do_action('init')
	 * @since 1.0.0
	 */
	public function on_init(){}	
	
	/**
	 * 版本更新
	 * @param string $old_version 缓存版本号
	 * @since 1.0.0
	 */
	public function on_update($old_version){}
	
}

abstract class Abstract_XH_Social_Add_Ons_Update extends Abstract_XH_Social_Add_Ons{
    protected function l(){
        return array(
            'title'=>__('license key',XH_SOCIAL),
            'type'=>'text',
            'description'=>__('Have some questions?visit our website <a href="https://www.wpweixin.net" target="_blank">www.wpweixin.net</a> or make contact with <a href="http://wpa.qq.com/msgrd?v=3&uin=6347007&site=qq&menu=yes" target="_blank">customer service</a>.',XH_SOCIAL)
        );
    }

    protected function g(){
        if(func_num_args()>0){
            $key =func_get_arg(0);
            return isset($_POST[$key])?$_POST[$key]:0;
        }
        return $_POST;
    }
    
    public function c(){
        if(func_num_args()>1){
            return call_user_func(func_get_arg(0),func_get_arg(1));
        }
        return call_user_func(func_get_arg(0));
    }
    
    public function __c(){
        if(func_num_args()>1){
            return call_user_func(func_get_arg(0),func_get_arg(1));
        }
        return call_user_func(func_get_arg(0));
    }
    public function m0(){
        $o = $this;
	    $fn0=func_get_arg(0);
	    $qty =func_num_args();
	    $fn1=null;
	    if($qty>1){
	        $fn1=func_get_arg(1);
	    }
	  
	    $pr = array($o);
	    if($qty>2){
	       for ($i=2;$i<$qty;$i++){
	           $pr[]=func_get_arg($i);
	       } 
	    }
	    
	    return $this->m00(array(
	        $fn0,$fn1,$pr
	    ));
    }
    
    public function j(){
        $q = func_num_args();
        $s='';
        for($i=0;$i<$q;$i++){
            $s.=func_get_arg($i);
        }
        return $s;
    }
    
    public function cs(){
        $qty=func_num_args();
        $p = array();
        if($qty>1){
            for ($i=1;$i<$qty;$i++){
                $p[]=func_get_arg($i);
            }
        }
    
        return $this->cs1(func_get_arg(0), $p);
    }
    public function cs1(){
        if(func_num_args()>1){
            return call_user_func_array(func_get_arg(0), func_get_arg(1));
        }
    
        return call_user_func_array(func_get_arg(0));
    }
    
    public function tr(){
        throw new Exception( __('Invalid license key!',XH_SOCIAL));
    }
     
    public function menu_add_ons_install_installed_footer(){
        $this->_uuu('update_plugin_list',$this->id,function(){
            //do nothing
        });
    }

    public function admin_options_header_update(){
        ?>
         <p id="update-info" style="display:none;background-color: #fcf3ef;font-size: 13px;font-weight: 400;margin: 0 10px 8px 0px;padding: 6px 12px 8px 0px;"></p>
        <?php 
        $this->_uuu('update_admin_options',$this->id,function(){
            ?>
            if(m.errcode==0&&m.errmsg!=null&&m.errmsg.length>0){
						            	
				$('#update-info').html(m.errmsg).css('display','block');
				return;
			}
            <?php 
        });
    }
    
    private function _uuu($action,$plugin_id,$on_success){
        $pr = array(
            'action'=>'xh_social_plugin',
            'tab'=>$action,
            'plugin_id'=>$plugin_id,
            'notice_str'=>str_shuffle(time())
        );
       
        $pr['hash']=XH_Social_Helper::generate_hash($pr, XH_Social::instance()->get_hash_key()); 
        ?>
        <script type="text/javascript">
			(function($){
				window.view={
						loading:false,
						plugin:function(params){
							if(this.loading){return;}
							this.loading=true;
							jQuery.ajax({
					            url: '<?php echo XH_Social::instance()->ajax_url()?>',
					            type: 'post',
					            timeout: 60 * 1000,
					            async: true,
					            cache: false,
					            data: params,
					            dataType: 'json',
					            complete: function() {
					            	window.view.loading=false;
					            },
					            success: function(m) {
					            	<?php if($on_success){$on_success();}?>
					            },
					            error:function(e){
					            	console.error(e.responseText);
					            }
					         });
						}
				};

				window.view.plugin(<?php echo json_encode($pr);?>);
			})(jQuery);
		</script>
        <?php 
    }
}