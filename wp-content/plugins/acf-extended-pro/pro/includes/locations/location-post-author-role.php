<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_post_author_role')):

class acfe_location_post_author_role extends acfe_pro_location{
    
    /**
     * initialize
     *
     * @return void
     */
    function initialize(){
        
        $this->name     = 'post_author_role';
        $this->label    = __('Post Author Role', 'acf');
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
        
        // get roles
        global $wp_roles;
        
        // return
        return wp_parse_args($wp_roles->get_names(), array(
            'all' => __('All', 'acf')
        ));
        
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
        $post_id = acfe_get($screen, 'post_id');
        $post_type = acfe_get($screen, 'post_type');
        
        // bail early
        if(!$post_id || !$post_type){
            return false;
        }
        
        // get post author
        $post_author = get_post_field('post_author', $post_id);
        
        // no post author
        if(!$post_author){
            return false;
        }
        
        $user_role = false;
        
        // check permission
        if(user_can($post_author, $rule['value'])){
            $user_role = $rule['value'];
        }
        
        // compare
        return $this->compare($user_role, $rule);
        
    }
    
}

acf_register_location_rule('acfe_location_post_author_role');

endif;