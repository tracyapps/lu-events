<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_taxonomy_term_type')):

class acfe_location_taxonomy_term_type extends acfe_pro_location{
    
    /**
     * initialize
     *
     * @return void
     */
    function initialize(){
        
        $this->name     = 'taxonomy_term_type';
        $this->label    = __('Taxonomy Term Type', 'acf');
        $this->category = 'forms';
        $this->after    = 'taxonomy_term_slug';
        
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
            'top_level' => __('Top Level Term (no parent)', 'acfe'),
            'parent'    => __('Parent Term (has children)', 'acfe'),
            'child'     => __('Child Term (has parent)', 'acfe'),
        );
        
    }
    
    
    /**
     * rule_match
     *
     * @param $result
     * @param $rule
     * @param $screen
     *
     * @return bool|mixed
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
        
        // top Level
        if($rule['value'] === 'top_level'){
            $result = ($term_parent == 0);
        
        // parent
        }elseif($rule['value'] === 'parent'){
    
            $term_childs = acf_get_terms(array(
                'parent'    => $term_id,
                'taxonomy'  => $taxonomy,
                'fields'    => 'ids',
            ));
    
            $result = !empty($term_childs);
        
        // child
        }elseif($rule['value'] === 'child'){
    
            $result = ($term_parent > 0);
        
        }
        
        // reverse
        if($rule['operator'] === '!='){
            $result = !$result;
        }
        
        // return
        return $result;
        
    }
    
}

acf_register_location_rule('acfe_location_taxonomy_term_type');

endif;