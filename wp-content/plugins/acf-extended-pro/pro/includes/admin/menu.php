<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_pro_admin_menu')):

class acfe_pro_admin_menu{
    
    /**
     * construct
     */
    function __construct(){
        
        add_action('admin_menu', array($this, 'admin_menu'), 1000);
        
    }
    
    
    /**
     * admin_menu
     */
    function admin_menu(){
        
        // vars
        global $submenu;
        $parent = 'edit.php?post_type=acf-field-group';
        
        // check menu exists
        if(!isset($submenu[ $parent ])){
            return;
        }
        
        // new key to insert after
        $new_key = acfe_is_acf('6.1') ? 6 : 7;
        
        // get key of "Templates" menu item
        $key = acfe_array_key_first($submenu[ $parent ], function($item, $key, $offset){
            return $item[2] === 'edit.php?post_type=acfe-template';
        });
        
        // item key not found
        if($key === null){
            return;
        }
        
        // extract item
        $extract = acfe_extract($submenu[ $parent ], $key);
        
        // insert after "new_key" menu item
        $submenu[ $parent ] = acfe_after($submenu[ $parent ], $new_key, $extract, false);
        
    }
    
}

new acfe_pro_admin_menu();

endif;