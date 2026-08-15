<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_dashboard')):

class acfe_location_dashboard extends acfe_pro_location{
    
    /**
     * initialize
     *
     * @return void
     */
    function initialize(){
        
        $this->name     = 'dashboard';
        $this->label    = __('WP Dashboard', 'acfe');
        $this->category = 'forms';
        $this->after    = 'acfe_template';
        
    }
    
    
    /**
     * rule_operators
     *
     * @param $operators
     * @param $rule
     *
     * @return array
     */
    function rule_operators($operators, $rule){
        return array('==' => __('is equal to', 'acf'));
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
            'widget' => sprintf(__('Widget %s'), '')
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
        
        // validate screen
        if(empty($screen['dashboard'])){
            return false;
        }
        
        // compare
        return $this->compare($screen['dashboard'], $rule);
        
    }
    
}

acf_register_location_rule('acfe_location_dashboard');

endif;