<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * Admin setting page templete
 *
 * @since    1.0.0
 * @author   ranj
 */
abstract class XH_Social_Abstract_View extends Abstract_XH_Social_Settings{
    /**
     * 输出html
     * 
     * @since 1.0.0
     */
	public function render(){
		?>
		<div class="wrap">
			<?php 
				$menus = $this->menus();
				if($menus){
				    ksort($menus);
				    reset($menus);
					?><h2 class="nav-tab-wrapper woo-nav-tab-wrapper"><?php 
					foreach ($menus as $menu){
						?><a href="<?php print esc_attr($menu['url'])?>" class="nav-tab <?php print (isset($menu['selected'])&&$menu['selected']?'nav-tab-active':'')?>"><?php print esc_html($menu['name'])?></a><?php 
					}
					?></h2><?php
				}
				unset($menus);
				$sub_menus = $this->sub_menus();
				$qty =count($sub_menus);
				if($sub_menus&&$qty>1){
				    ksort($sub_menus);
				    reset($sub_menus);
					$index =0;
					?><ul class="subsubsub"><?php
					foreach ($sub_menus as $menu){
						?>
						<li>
							<a href="<?php print esc_attr($menu['url'])?>" class="<?php print (isset($menu['selected'])&&$menu['selected']?'current':'')?>"><?php print esc_html($menu['name'])?></a> <?php print ($index++<($qty-1)?'|':'')?>
						</li>
						<?php 
					}
					unset($qty);
					unset($index);
					?></ul>
					<br class="clear">
					<?php
				}
				unset($sub_menus);
				$this->before_content();
    			$this->content();
    			$this->after_content();
			?>
		</div>
		<?php		
	}
	
	/**
	 * 一级菜单
	 * 
	 * @since 1.0.0
	 */
    public function menus() {
        return array();
    }
    
	/**
	 * 二级菜单
	 *
	 * @since 1.0.0
	 */
	public function sub_menus(){return array();}
	
	/**
	 * 表单前内容
	 *
	 * @since 1.0.0
	 */
	public function before_content(){}
	
	/**
	 * 主要的表单内容
	 *
	 * @since 1.0.0
	 */
	public function content(){}
	
	/**
	 * 表单后内容
	 *
	 * @since 1.0.0
	 */
	public function after_content(){}
}
?>