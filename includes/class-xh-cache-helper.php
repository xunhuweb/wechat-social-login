<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * XH_Social_Cache_Helper class.
 *
 * @class 		XH_Social_Cache_Helper
 * @version		2.2.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class XH_Social_Cache_Helper {
	/**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 * @param  string $group
	 * @return string
	 */
	public static function get_cache_prefix( $group ) {
		// Get cache key - uses cache key xh_social_orders_cache_prefix to invalidate when needed
		$prefix = wp_cache_get( 'xh_social_' . $group . '_cache_prefix', $group );

		if ( false === $prefix ) {
			$prefix = 1;
			wp_cache_set( 'xh_social_' . $group . '_cache_prefix', $prefix, $group );
		}

		return 'xh_social_cache_' . $prefix . '_';
	}

	/**
	 * Increment group cache prefix (invalidates cache).
	 * @param  string $group
	 */
	public static function incr_cache_prefix( $group ) {
		wp_cache_incr( 'xh_social_' . $group . '_cache_prefix', 1, $group );
	}
}

/**
 * 临时缓存
 * @author rain
 * @since 1.0.0
 */
class XH_Social_Temp_Helper{
    private static $_data=array();
    
    public static function get($key,$group='common',$_default=null){
        return isset(self::$_data[$group][$key])
        ?self::$_data[$group][$key]
        :$_default;
    }
    public static function clear($key,$group='common',$_default=null){
        if( isset(self::$_data[$group][$key])){
            $data =self::$_data[$group][$key];
            self::$_data[$group][$key]=$_default;
            return $data;
        }
        return $_default;
    }
    public static function set($key,$val,$group='common'){
        self::$_data[$group][$key]=$val;
    }
}