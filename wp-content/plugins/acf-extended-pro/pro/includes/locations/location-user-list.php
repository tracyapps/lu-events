<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_user_list')):

class acfe_location_user_list{

    /**
     * construct
     */
    function __construct(){
        
        // locations
        add_filter('acf/location/rule_values/user_form', array($this, 'rule_values'));
        add_filter('acf/location/rule_match/user_form',  array($this, 'rule_match'), 10, 3);
        
    }


    /**
     * rule_values
     *
     * @param $choices
     *
     * @return array
     */
    function rule_values($choices){
        
        $choices = acfe_after($choices, 'all', array('list' => __('List')));
        return $choices;
        
    }


    /**
     * rule_match
     *
     * @param $match
     * @param $rule
     * @param $screen
     *
     * @return bool|mixed
     */
    function rule_match($match, $rule, $screen){
        
        if(!acfe_get($screen, 'user_list') || acfe_get($rule, 'value') !== 'list'){
            return $match;
        }
    
        $match = true;
        
        if($rule['operator'] === '!='){
            $match = !$match;
        }
        
        return $match;

    }
    
}

acf_new_instance('acfe_location_user_list');

endif;