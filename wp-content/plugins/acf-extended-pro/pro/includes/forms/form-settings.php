<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_form_settings')):

class acfe_form_settings{
    
    /**
     * construct
     */
    function __construct(){
        
        add_action('current_screen',             array($this, 'current_screen'));
        add_action('load-options.php',           array($this, 'load_options'));
        add_action('load-options-permalink.php', array($this, 'load_options'));
        
    }
    
    
    /**
     * current_screen
     *
     * @return void
     */
    function current_screen(){
        
        if(!acf_is_screen(array('options-general', 'options-writing', 'options-reading', 'options-discussion', 'options-media', 'options-permalink'))){
            return;
        }
        
        $screen = get_current_screen()->id;
        
        $field_groups = acf_get_field_groups(array(
            'wp_settings' => $screen
        ));
        
        if(empty($field_groups)){
            return;
        }
        
        // acf enqueue scripts
        acf_enqueue_scripts();
        
        // add uploader
        add_action('in_admin_header', array($this, 'in_admin_header'));
        
    }
    
    
    /**
     * in_admin_header
     *
     * @return void
     */
    function in_admin_header(){
        
        acf_enqueue_uploader();
        
    }
    
    
    /**
     * load_options
     *
     * @return void
     */
    function load_options(){
        
        // verify nonce
        if(!acf_verify_nonce('wp_settings')){
            return;
        }
        
        // get post id
        $post_id = acf_maybe_get_POST('_acf_post_id');
        if(!$post_id){
            return;
        }
        
        $post_id = acf_get_valid_post_id($post_id);
        
        // validate
        if(!acf_validate_save_post(true)){
            return;
        }
            
        // autoload setting
        acf_update_setting('autoload', false);
        
        // save
        acf_save_post($post_id);
        
    }
    
}

acf_new_instance('acfe_form_settings');

endif;