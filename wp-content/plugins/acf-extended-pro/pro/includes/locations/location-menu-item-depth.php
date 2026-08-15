<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_menu_item_depth')):

class acfe_location_menu_item_depth extends acfe_pro_location{
    
    /**
     * initialize
     *
     * @return void
     */
    function initialize(){
        
        $this->name     = 'nav_menu_item_depth';
        $this->label    = __('Menu Item Depth', 'acfe');
        $this->category = 'forms';
        $this->after    = 'nav_menu_item';
        
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
        
        return $this->get_rule_value_input($rule, array(
            'type' => 'number',
            'min'  => 0,
        ));
        
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
        
        // validate screen
        if(!isset($screen['nav_menu_item_depth'])){
            return false;
        }
        
        // sanitize value
        $depth = absint($screen['nav_menu_item_depth']);
        
        // compare
        return $this->compare_advanced($depth, $rule);
        
    }
    
}

acf_register_location_rule('acfe_location_menu_item_depth');

endif;