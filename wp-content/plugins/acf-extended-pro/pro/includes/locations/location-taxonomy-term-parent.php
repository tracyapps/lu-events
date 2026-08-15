<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_taxonomy_term_parent')):

class acfe_location_taxonomy_term_parent extends acfe_pro_location{
    
    /**
     * initialize
     *
     * @return void
     */
    function initialize(){
        
        $this->name     = 'taxonomy_term_parent';
        $this->label    = __('Taxonomy Term Parent', 'acf');
        $this->category = 'forms';
        $this->after    = 'taxonomy_term_name';
        
    }
    
    
    /**
     * rule_values
     *
     * @param $choices
     * @param $rule
     *
     * @return mixed
     */
    function rule_values($choices, $rule){
        return acf_get_location_rule('taxonomy_term')->rule_values($choices, $rule);
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
        $taxonomy = acfe_get($screen, 'taxonomy');
        $term_id = acfe_get($screen, 'term_id');
        
        // bail early
        if(!$taxonomy || !$term_id){
            return false;
        }
        
        // get term parent
        $term_parent = get_term_field('parent', $term_id);
        
        // compare
        return $this->compare($term_parent, $rule);
        
    }
    
}

acf_register_location_rule('acfe_location_taxonomy_term_parent');

endif;