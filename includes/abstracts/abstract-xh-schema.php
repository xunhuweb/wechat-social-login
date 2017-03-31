<?php
/**
 * 数据表模型
 *
 * @class       Abstract_XH_Social_Model_Api
 * @since       1.0.0
 * @author      ranj
 */
abstract class Abstract_XH_Social_Schema {
    /**
     * 获取排序字符集
     * 
     * @return string
     * @since 1.0.0
     */
    protected function get_collate(){
        global $wpdb;
        $collate = '';
        
        if ( $wpdb->has_cap( 'collation' ) ) {
            $collate = $wpdb->get_charset_collate();
        }
        
        return $collate;
    }
    
    public function init(){
        
    }
}