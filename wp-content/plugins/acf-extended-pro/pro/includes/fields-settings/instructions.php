<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_pro_instructions')):

class acfe_pro_instructions{
    
    /**
     * construct
     */
    function __construct(){
        
        add_action('acf/field_group/admin_head',    array($this, 'admin_head'));
        add_filter('acf/field_wrapper_attributes',  array($this, 'field_wrapper_attributes'), 10, 2);
        
    }
    
    
    /**
     * admin_head
     */
    function admin_head(){
        
        global $field_group;
        
        if(acf_is_filter_enabled('acfe/field_group/advanced')){
            add_action('acf/render_field_settings', array($this, 'render_field_instructions_settings'), 11);
        }
        
    }
    
    
    /**
     * render_field_instructions_settings
     *
     * @param $field
     */
    function render_field_instructions_settings($field){
        
        // hide Field
        acf_render_field_setting($field, array(
            'label'         => __('Instructions Placement', 'acfe'),
            'instructions'  => '',
            'name'          => 'instruction_placement',
            'type'          => 'select',
            'placeholder'   => 'Default',
            'allow_null'    => true,
            'choices'       => array(
                'label'         => __('Below labels', 'acf'),
                'field'         => __('Below fields', 'acf'),
                'above_field'   => __('Above fields', 'acfe'),
                'tooltip'       => __('Tooltip', 'acfe'),
            ),
            'wrapper' => array(
                'data-after' => 'instructions',
            )
        ), true);
        
    }
    
    
    /**
     * field_wrapper_attributes
     *
     * @param $wrapper
     * @param $field
     *
     * @return mixed
     */
    function field_wrapper_attributes($wrapper, $field){
        
        if(acfe_get($field, 'instructions')){
            
            if(acfe_get($field, 'instruction_placement')){
                $wrapper['data-instruction-placement'] = acfe_get($field, 'instruction_placement');
            }
    
            if(strpos($field['instructions'], '---') !== false){
                $wrapper['data-instruction-more'] = true;
            }
            
        }
        
        return $wrapper;
        
    }
    
}

// initialize
new acfe_pro_instructions();

endif;