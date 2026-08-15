<?php

if(!defined('ABSPATH')){
    exit;
}

// check setting
if(!acfe_get_setting('modules/field_group_ui')){
    return;
}

if(!class_exists('acfe_field_group_ui')):

class acfe_field_group_ui{
    
    /**
     * construct
     */
    function __construct(){

        add_action('acf/field_group/admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('acf/render_field_group_settings',       array($this, 'render_field_group_settings'));

    }
    
    /**
     * admin_enqueue_scripts
     */
    function admin_enqueue_scripts(){
    
        acf_localize_text(array(
            'Enter a Field Type'                => __('Enter a Field Type', 'acfe'),
            'Enter the Field Label'             => __('Enter the Field Label', 'acfe'),
            'Hint: Shift to bypass this prompt' => __('Hint: Shift to bypass this prompt', 'acfe'),
            'Shift+click: Alternative mode'     => __('Shift+click: Alternative mode', 'acfe'),
        ));
        
    }


    /**
     * render_field_group_settings
     *
     * @param $field_group
     *
     * @return void
     */
    function render_field_group_settings($field_group){

        if(!acfe_is_acf('6.0')){

            // tab: general
            acf_render_field_wrap(array(
                'label'   => __('General', 'acfe'),
                'type'    => 'tab',
                'key'     => 'general',
                'wrapper' => array(
                    'data-no-preference' => true,
                    'data-before' => 'active'
                )
            ));

        }

        // advanced settings
        acfe_render_group_setting($field_group, array(
            'label'         => __('Advanced settings', 'acfe'),
            'name'          => 'acfe.advanced',
            'type'          => 'true_false',
            'ui'            => 1,
            'instructions'  => __('Enable advanced fields settings & validation', 'acfe'),
            'required'      => false,
            'wrapper'       => array(
                'data-after' => 'active'
            )
        ), 'div', 'label', true);


        if(!acfe_is_acf('6.6')){

            // display title
            $wrapper = array();

            if(!acfe_is_acf('6.0')){
                $wrapper = array('data-before' => 'menu_order');
            }elseif(acfe_is_acf('6.1')){
                $wrapper = array('data-after' => 'description');
            }

            acfe_render_group_setting($field_group, array(
                'label'         => __('Display title', 'acfe'),
                'instructions'  => __('Render this title on edit post screen', 'acfe'),
                'type'          => 'text',
                'name'          => 'acfe.display_title',
                'placeholder'   => '',
                'prepend'       => '',
                'append'        => '',
                'wrapper'       => $wrapper
            ), 'div', 'label', true);

        }

        if(acfe_is_acf('6.0', '6.1')){
            echo '</div>';
        }

        if(!acfe_is_acf('6.0')){

            // tab: screen
            acf_render_field_wrap(array(
                'label'   => __('Screen', 'acfe'),
                'type'    => 'tab',
                'key'     => 'screen',
                'wrapper' => array(
                    'data-before' => 'acfe.display_title'
                )
            ));

        }

        
        if(acfe_get($field_group, 'acfe.permissions') || acf_is_filter_enabled('acfe/field_group/advanced')){

            acf_render_field_wrap(array(
                'type'  => 'tab',
                'label' => __('Permissions', 'acf'),
                'key'   => 'acf_field_group_settings_tabs',
            ));

            if(acfe_is_acf('6.0')){
                echo '<div class="field-group-settings field-group-settings-tab">';
            }

            // permissions
            acfe_render_group_setting($field_group, array(
                'label'         => __('Permissions', 'acf'),
                'name'          => 'acfe.permissions',
                'type'          => 'checkbox',
                'instructions'  => __('Select user roles that are allowed to view and edit this field group in post edition', 'acfe'),
                'required'      => false,
                'default_value' => false,
                'choices'       => acfe_get_roles(),
                'layout'        => 'vertical'
            ), 'div', 'label', true);

            if(acfe_is_acf('6.0')){
                echo '</div>';
            }

        }


        // tab: data
        acf_render_field_wrap(array(
            'label' => __('Data', 'acfe'),
            'type'  => 'tab',
            'key'   => 'acf_field_group_settings_tabs'
        ));

        if(acfe_is_acf('6.0')){
            echo '<div class="field-group-settings field-group-settings-tab">';
        }

        // meta
        acfe_render_group_setting($field_group, array(
            'label'         => __('Custom meta data', 'acfe'),
            'name'          => 'acfe.meta',
            'instructions'  => __('Add custom meta data to the field group.', 'acfe'),
            'type'          => 'repeater',
            'button_label'  => __('+ Meta'),
            'required'      => false,
            'layout'        => 'table',
            'wrapper'       => array(
                'data-enable-switch' => true
            ),
            'sub_fields'    => array(
                array(
                    'ID'            => false,
                    'label'         => __('Key', 'acfe'),
                    'name'          => 'acfe.meta.key',
                    'key'           => 'key',
                    'prefix'        => '',
                    '_name'         => '',
                    '_prepare'      => '',
                    'type'          => 'text',
                    'instructions'  => false,
                    'required'      => false,
                    'wrapper'       => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                ),
                array(
                    'ID'            => false,
                    'label'         => __('Value', 'acfe'),
                    'name'          => 'acfe.meta.value',
                    'key'           => 'value',
                    'prefix'        => '',
                    '_name'         => '',
                    '_prepare'      => '',
                    'type'          => 'text',
                    'instructions'  => false,
                    'required'      => false,
                    'wrapper'       => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                ),
            )
        ), 'div', 'label', true);

        // data
        acfe_render_group_setting($field_group, array(
            'label'         => __('Field group data', 'acfe'),
            'instructions'  => __('View raw field group data, for development use', 'acfe'),
            'name'          => 'acfe.data',
            'type'          => 'acfe_dynamic_render',
            'value'         => $field_group['key'],
        ), 'div', 'label', true);

        if(acfe_is_acf('6.0')){
            echo '</div>';
        }

        // tab: note
        acf_render_field_wrap(array(
            'label' => __('Note', 'acfe'),
            'type'  => 'tab',
            'key'   => 'acf_field_group_settings_tabs',
        ));

        if(acfe_is_acf('6.0')){
            echo '<div class="field-group-settings field-group-settings-tab">';
        }

        // note
        acfe_render_group_setting($field_group, array(
            'label'         => __('Note', 'acfe'),
            'name'          => 'acfe.note',
            'type'          => 'textarea',
            'instructions'  => __('Add personal note. Only visible to administrators', 'acfe'),
            'required'      => false
        ), 'div', 'label', true);

        // close only in acf 6.1+
        if(acfe_is_acf('6.1')){
            echo '</div>';
        }

    }
    
}

// initialize
new acfe_field_group_ui();

endif;