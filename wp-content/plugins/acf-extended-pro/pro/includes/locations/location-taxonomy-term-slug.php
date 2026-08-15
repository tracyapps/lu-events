<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_taxonomy_term_slug')):

class acfe_location_taxonomy_term_slug extends acfe_pro_location{
    
    /**
     * initialize
     *
     * @return void
     */
    function initialize(){
        
        $this->name     = 'taxonomy_term_slug';
        $this->label    = __('Taxonomy Term Slug', 'acf');
        $this->category = 'forms';
        $this->after    = 'taxonomy_term_parent';
        
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
        $taxonomy = acfe_get($screen, 'taxonomy');
        $term_id = acfe_get($screen, 'term_id');
    
        // bail early
        if(!$taxonomy || !$term_id){
            return false;
        }
        
        // get term slug
        $term_slug = get_term_field('slug', $term_id);
        
        if(!$term_slug){
            return false;
        }
        
        // compare
        return $this->compare_advanced($term_slug, $rule);
        
    }
    
}

acf_register_location_rule('acfe_location_taxonomy_term_slug');

endif;