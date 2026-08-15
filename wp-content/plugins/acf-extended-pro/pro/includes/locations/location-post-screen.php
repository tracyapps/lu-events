<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_post_screen')):

class acfe_location_post_screen extends acfe_pro_location{
    
    /**
     * initialize
     *
     * @return void
     */
    function initialize(){
        
        $this->name     = 'post_screen';
        $this->label    = __('Post Screen', 'acf');
        $this->category = 'post';
        
    }
    
    
    /**
     * rule_values
     *
     * @param $choices
     * @param $rule
     *
     * @return array
     */
    function rule_values($choices, $rule){
        
        return array(
            'post-new.php' => __('Add New'),
            'post.php'     => __('Edit'),
        );
        
    }
    
    
    /**
     * rule_match
     *
     * @param $result
     * @param $rule
     * @param $screen
     *
     * @return false
     */
    function rule_match($result, $rule, $screen){
        
        // vars
        global $pagenow;
        
        // ajax request from acf/ajax/check_screen
        if(!empty($screen['ajax']) && !empty($screen['pagenow'])){
            $pagenow = $screen['pagenow'];
        }
        
        // get screen
        $post_id = acfe_get($screen, 'post_id');
        $post_type = acfe_get($screen, 'post_type');
        
        // bail early
        if(!$pagenow || !$post_id || !$post_type){
            return false;
        }
        
        // compare
        return $this->compare($pagenow, $rule);
        
    }
    
}

acf_register_location_rule('acfe_location_post_screen');

endif;