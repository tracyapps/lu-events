<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_post_author')):

class acfe_location_post_author extends acfe_pro_location{
    
    /**
     * initialize
     *
     * @return void
     */
    function initialize(){
        
        $this->name     = 'post_author';
        $this->label    = __('Post Author', 'acf');
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
        
        // vars
        $choices = array();
        $users = get_users();
    
        // check users
        if(!$users){
            return $choices;
        }
        
        // loop users
        foreach($users as $user){
            
            // get user info
            $user_info = acf_get_user_result($user);
            
            // append choice
            $choices[ $user->ID ] = $user_info['text'];
            
        }
        
        // return choices
        return $choices;
        
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
    
        if(!$post_author){
            return false;
        }
        
        // compare
        return $this->compare($post_author, $rule);
        
    }
    
}

acf_register_location_rule('acfe_location_post_author');

endif;