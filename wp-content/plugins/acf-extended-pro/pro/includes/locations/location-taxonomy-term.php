<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_taxonomy_term')):

class acfe_location_taxonomy_term extends acfe_pro_location{
    
    /**
     * initialize
     *
     * @return void
     */
    function initialize(){
        
        $this->name     = 'taxonomy_term';
        $this->label    = __('Taxonomy Term', 'acf');
        $this->category = 'forms';
        $this->after    = 'taxonomy_list';
        
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
        return acfe_get_taxonomy_terms_ids();
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
        
        // compare
        return $this->compare($term_id, $rule);
        
    }
    
}

acf_register_location_rule('acfe_location_taxonomy_term');

endif;