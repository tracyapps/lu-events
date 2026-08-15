<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_attachment_list')):

class acfe_location_attachment_list{

    /**
     * construct
     */
    function __construct(){

        add_filter('acf/location/rule_values/attachment', array($this, 'rule_values'));
        add_filter('acf/location/rule_match/attachment',  array($this, 'rule_match'), 10, 3);
        
    }


    /**
     * rule_values
     *
     * @param $choices
     *
     * @return array
     */
    function rule_values($choices){
        
        $choices = acfe_after($choices, 'all', array('list' => __('List', 'acfe')));
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
        
        // validate screen
        if(empty($screen['attachment_list'])){
            return $match;
        }
        
        // validate rule
        if($rule['value'] !== 'list'){
            return $match;
        }
        
        // compare
        return acfe_compare_location_rule('list', $rule);

    }
    
}

acf_new_instance('acfe_location_attachment_list');

endif;