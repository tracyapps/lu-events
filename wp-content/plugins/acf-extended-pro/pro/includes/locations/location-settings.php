<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_settings')):

class acfe_location_settings extends acfe_pro_location{
    
    /**
     * initialize
     *
     * @return void
     */
    function initialize(){
        
        $this->name     = 'wp_settings';
        $this->label    = __('WP Settings', 'acfe');
        $this->category = 'forms';
        $this->after    = 'dashboard';
        
    }
    
    
    /**
     * rule_values
     *
     * @param $choices
     *
     * @return array
     */
    function rule_values($choices){
        
        return array(
            'all'                => __('All', 'acf'),
            'options-general'    => _x('General', 'settings screen'),
            'options-writing'    => __('Writing'),
            'options-reading'    => __('Reading'),
            'options-discussion' => __('Discussion'),
            'options-media'      => __('Media'),
            'options-permalink'  => __('Permalinks')
        );
        
    }
    
    
    /**
     * rule_match
     *
     * @param $match
     * @param $rule
     * @param $screen
     *
     * @return bool
     */
    function rule_match($match, $rule, $screen){
        
        if(!acfe_get($screen, 'wp_settings') || !acfe_get($rule, 'value')){
            return $match;
        }
        
        return $this->compare_advanced($screen['wp_settings'], $rule);

    }
    
}

acf_register_location_rule('acfe_location_settings');

endif;