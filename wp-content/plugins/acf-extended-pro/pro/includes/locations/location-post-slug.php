<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_post_slug')):

class acfe_location_post_slug extends acfe_pro_location{
    
    /**
     * initialize
     *
     * @return void
     */
    function initialize(){
        
        $this->name     = 'post_slug';
        $this->label    = __('Post Slug', 'acf');
        $this->category = 'post';
        
    }
    
    
    /**
     * rule_values
     *
     * @param $choices
     * @param $rule
     *
     * @return array|string
     */
    function rule_values($choices, $rule){
        return $this->get_rule_value_input($rule);
    }
    
    
    /**
     * rule_operators
     *
     * @param $choices
     * @param $rule
     *
     * @return mixed
     */
    function rule_operators($choices, $rule){
        
        $choices['contains']  = __('contains', 'acf');
        $choices['!contains'] = __('doesn\'t contains', 'acf');
        $choices['starts']    = __('starts with', 'acf');
        $choices['!starts']   = __('doesn\'t starts with', 'acf');
        $choices['ends']      = __('ends with', 'acf');
        $choices['!ends']     = __('doesn\'t ends with', 'acf');
        $choices['regex']     = __('matches regex', 'acf');
        $choices['!regex']    = __('doesn\'t matches regex', 'acf');
        
        return $choices;
        
    }
    
    
    /**
     * rule_match
     *
     * @param $result
     * @param $rule
     * @param $screen
     *
     * @return bool|int
     */
    function rule_match($result, $rule, $screen){
    
        // vars
        $post_id = acfe_get($screen, 'post_id');
        $post_type = acfe_get($screen, 'post_type');
    
        // bail early
        if(!$post_id || !$post_type){
            return false;
        }
        
        // get post name
        $post_name = get_post_field('post_name', $post_id);
        
        if(!$post_name){
            return false;
        }
        
        // compare
        return $this->compare_advanced($post_name, $rule);
        
    }
    
}

acf_register_location_rule('acfe_location_post_slug');

endif;