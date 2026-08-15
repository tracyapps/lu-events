<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_post_time')):

class acfe_location_post_time extends acfe_pro_location{
    
    /**
     * initialize
     *
     * @return void
     */
    function initialize(){
        
        $this->name     = 'post_time';
        $this->label    = __('Post Time', 'acf');
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
        return $this->get_rule_value_input($rule, 'time_picker');
    }
    
    
    /**
     * format_rule_value
     *
     * @param $rule
     *
     * @return mixed|string
     */
    function format_rule_value($rule){
        return acf_format_date($rule['value'], 'H:i:s');
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
    
        $choices['<']  = __('is less than', 'acf');
        $choices['<='] = __('is less or equal to', 'acf');
        $choices['>']  = __('is greater than', 'acf');
        $choices['>='] = __('is greater or equal to', 'acf');
    
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

        // get data
        $post_date = get_post_field('post_date', $post_id);
    
        if(!$post_date){
            return false;
        }

        // format
        $post_date = acf_format_date($post_date, 'His');
        $rule['value'] = acf_format_date($rule['value'], 'His');
    
        // compare
        return $this->compare_advanced($post_date, $rule);
        
    }
    
}

acf_register_location_rule('acfe_location_post_time');

endif;