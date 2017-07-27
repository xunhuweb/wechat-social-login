<?php 
/**
 * 【此类已作废】
 * @author rain
 */
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
        ?>
        <script type="text/javascript">
			(function($){
				window.view={
						loading:false,
						plugin:function(params){
							if(this.loading){return;}
							this.loading=true;
							jQuery.ajax({
					            url: '<?php echo XH_Social::instance()->ajax_url(array(
					                'action'=>'xh_social_plugin',
					                'tab'=>$action,
					                'plugin_id'=>$plugin_id
					            ),true,true)?>',
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

				window.view.plugin({});
			})(jQuery);
		</script>
        <?php 
    }
}
?>