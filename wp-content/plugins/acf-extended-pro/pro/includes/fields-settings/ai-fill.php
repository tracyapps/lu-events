<?php

if(!defined('ABSPATH')){
    exit;
}

if(!function_exists('wp_supports_ai') || !wp_supports_ai()){
    return;
}

if(!class_exists('acfe_pro_ai_fill')):

class acfe_pro_ai_fill{
    
    /**
     * construct
     */
    function __construct(){
        
        // hook name
        $render_field_settings = acfe_is_acf('6.1') ? 'render_field_acfe-ai_settings' : 'render_field_settings';
        $field_types = apply_filters('acfe/fields/ai_fill/field_types', array(
            'text',
            'textarea',
            'wysiwyg',
            'acfe_code_editor',
        ));

        // loop field types
        foreach($field_types as $field_type){
            add_action("acf/{$render_field_settings}/type={$field_type}", array($this, 'render_field_settings'));
        }

        add_action('acf/load_field',                                     array($this, 'load_field'), 11);
        add_action('acf/render_field',                                   array($this, 'render_field'), 11);

        add_filter('acf/field_group/additional_field_settings_tabs', array($this, 'additional_field_settings_tabs'));
        
    }


    /**
     * additional_field_settings_tabs
     *
     * @param $tabs
     *
     * @return mixed
     */
    function additional_field_settings_tabs($tabs){
        
        $tabs['acfe-ai'] = __('AI Fill', 'acfe');
        return $tabs;
        
    }
    
    
    /**
     * render_field_settings
     *
     * @param $field
     */
    function render_field_settings($field){
        
        // configure fake field
        $field['prefix'] .= '[acfe_ai]';
        
        // get ai chat field settings
        $acfe_ai = acfe_get($field, 'acfe_ai');
        $acfe_ai = acfe_as_array($acfe_ai);
        
        // assign ai chat field settings
        foreach($acfe_ai as $k => $v){
            $field[ $k ] = $v;
        }
        
        // enable
        acf_render_field_setting($field, array(
            'label'         => __('Fill with AI', 'acfe'),
            'name'          => 'enable',
            'instructions'  => '',
            'type'          => 'true_false',
            'ui'            => true,
        ));

        // render permissions setting
        acf_render_field_setting($field, array(
            'label'         => __('Permissions', 'acfe'),
            'name'          => 'acfe_permissions',
            'key'           => 'acfe_permissions',
            'instructions'  => __('Restrict user roles that are allowed to view and use this feature', 'acfe'),
            'type'          => 'checkbox',
            'required'      => false,
            'default_value' => array('administrator'),
            'choices'       => acfe_get_roles(),
            'layout'        => 'horizontal',
            'conditional_logic' => array(
                array(
                    array(
                        'field'     => 'enable',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                ),
            ),
        ));

        // placeholder
        acf_render_field_setting($field, array(
            'label'         => __('Placeholder Prompt','acf'),
            'instructions'  => __('Appears within the prompt input','acfe'),
            'name'          => 'placeholder',
            'type'          => 'text',
            'conditional_logic' => array(
                array(
                    array(
                        'field'     => 'enable',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                ),
            ),
        ));

        // default_value
        acf_render_field_setting($field, array(
            'label'         => __('Default Prompt','acfe'),
            'instructions'  => __('Appears as the default prompt','acfe'),
            'name'          => 'default_value',
            'type'          => 'textarea',
            'conditional_logic' => array(
                array(
                    array(
                        'field'     => 'enable',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                ),
            ),
        ));

        // default_message
        acf_render_field_setting($field, array(
            'label'         => __('Default Agent Message','acfe'),
            'instructions'  => __('The default message displayed in the field','acfe'),
            'name'          => 'default_message',
            'type'          => 'textarea',
            'placeholder'   => '',
            'conditional_logic' => array(
                array(
                    array(
                        'field'     => 'enable',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                ),
            ),
        ));

        // system_instruction
        acf_render_field_setting($field, array(
            'label'         => __('Agent Instruction','acfe'),
            'instructions'  => __('Gives the agent instructions and context','acfe'),
            'name'          => 'system_instruction',
            'type'          => 'textarea',
            'placeholder'   => __('I am a WordPress expert and I want to assist you.','acfe'),
            'conditional_logic' => array(
                array(
                    array(
                        'field'     => 'enable',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                ),
            ),
        ));

        // chat history
        acf_render_field_setting($field, array(
            'label'         => __('Chat History','acfe'),
            'instructions'  => '',
            'name'          => 'history',
            'type'          => 'radio',
            'layout'        => 'horizontal',
            'choices'       => array(
                'persistent' => __('Persistent', 'acfe'),
                'clear'      => __('Clear on exit', 'acfe'),
            ),
            'conditional_logic' => array(
                array(
                    array(
                        'field'     => 'enable',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                ),
            ),
        ));

        // modal
        acf_render_field_setting($field, array(
            'label'         => __('Modal', 'acfe'),
            'name'          => 'modal',
            'key'           => 'modal',
            'instructions'  => '',
            'type'          => 'group',
            'layout'        => 'block',
            'sub_fields'    => array(
                array(
                    'label'         => '',
                    'name'          => 'button',
                    'key'           => 'button',
                    'type'          => 'text',
                    'prepend'       => __('Button', 'acfe'),
                    'placeholder'   => __('Fill with AI', 'acfe'),
                    'instructions'  => false,
                    'required'      => false,
                    'wrapper'       => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                ),
                array(
                    'label'         => '',
                    'name'          => 'size',
                    'key'           => 'size',
                    'type'          => 'select',
                    'prepend'       => '',
                    'instructions'  => false,
                    'required'      => false,
                    'choices'       => array(
                        'small'     => __('Small', 'acfe'),
                        'medium'    => __('Medium', 'acfe'),
                        'large'     => __('Large', 'acfe'),
                        'xlarge'    => __('Extra Large', 'acfe'),
                        'full'      => __('Full', 'acfe'),
                    ),
                    'default_value' => 'medium',
                    'wrapper'       => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                        'data-acfe-prepend' => __('Size', 'acfe'),
                    ),
                ),
                array(
                    'label'         => '',
                    'name'          => 'title',
                    'key'           => 'title',
                    'type'          => 'text',
                    'prepend'       => __('Title', 'acfe'),
                    'placeholder'   => '',
                    'instructions'  => false,
                    'required'      => false,
                    'wrapper'       => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                ),
            ),
            'conditional_logic' => array(
                array(
                    array(
                        'field'     => 'enable',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                ),
            ),
            'wrapper' => array(
                'class' => 'acfe-field-setting-flex-width'
            )
        ));


        $field_type = acf_get_field_type('acfe_ai_chat');
        $providers = $field_type->get_providers();
        $provider_choices = array(
                '' => __('Auto', 'acfe')
        );
        foreach($providers as $provider){
            $provider_choices[ $provider['id'] ] = $provider['name'];
        }

        // provider
        $connectors_url = admin_url('options-connectors.php');

        acf_render_field_setting($field, array(
            'label'         => __('Provider','acf'),
            'instructions'  => sprintf(__('Choose the provider to use. You can set up your providers in the <a href="%s">WP Connectors</a> menu.','acfe'), $connectors_url),
            'name'          => 'provider',
            'type'          => 'radio',
            'layout'        => 'horizontal',
            'choices'       => $provider_choices,
            'conditional_logic' => array(
                array(
                    array(
                        'field'     => 'enable',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                ),
            ),
        ));

        // text: models
        acf_render_field_setting($field, array(
            'label'         => __('Text: Model Preference','acfe'),
            'name'          => 'text_models',
            'key'           => 'text_models',
            'type'          => 'select',
            'choices'       => !empty($field['text_models']) ? $field_type->get_models_choices($field['text_models']) : array(),
            'ui'            => 1,
            'multiple'      => 1,
            'ajax'          => 1,
            'ajax_action'   => 'acfe/fields/ai_chat/query_models',
            'nonce'         => wp_create_nonce('acfe/fields/ai_chat/query_models'),
            'conditional_logic' => array(
                array(
                    array(
                        'field'     => 'enable',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                ),
            ),
        ));

        // request
        acf_render_field_setting($field, array(
            'label'         => __('Request', 'acfe'),
            'name'          => 'request',
            'key'           => 'request',
            'instructions'  => '',
            'type'          => 'group',
            'layout'        => 'block',
            'sub_fields'    => array(
                array(
                    'label'         => '',
                    'name'          => 'timeout',
                    'key'           => 'timeout',
                    'type'          => 'number',
                    'min'           => 0,
                    'prepend'       => __('Timeout', 'acfe'),
                    'placeholder'   => '',
                    'default_value' => '30',
                    'instructions'  => false,
                    'required'      => false,
                    'wrapper'       => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                ),
                array(
                    'label'         => '',
                    'name'          => 'token',
                    'key'           => 'token',
                    'type'          => 'number',
                    'min'           => 0,
                    'prepend'       => __('Max Tokens', 'acfe'),
                    'placeholder'   => '',
                    'instructions'  => false,
                    'required'      => false,
                    'wrapper'       => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                ),
            ),
            'conditional_logic' => array(
                array(
                    array(
                        'field'     => 'enable',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                ),
            ),
            'wrapper' => array(
                'class' => 'acfe-field-setting-flex-width'
            )
        ));

        // height
        acf_render_field_setting($field, array(
            'label'         => __('Height', 'acfe'),
            'name'          => 'height',
            'key'           => 'height',
            'instructions'  => '',
            'type'          => 'group',
            'layout'        => 'block',
            'sub_fields'    => array(
                array(
                    'label'         => '',
                    'name'          => 'min',
                    'key'           => 'min',
                    'type'          => 'number',
                    'min'           => 0,
                    'prepend'       => __('Minimum', 'acfe'),
                    'placeholder'   => '',
                    'instructions'  => false,
                    'required'      => false,
                    'wrapper'       => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                ),
                array(
                    'label'         => '',
                    'name'          => 'max',
                    'key'           => 'max',
                    'type'          => 'number',
                    'min'           => 0,
                    'prepend'       => __('Maximum', 'acfe'),
                    'placeholder'   => '',
                    'instructions'  => false,
                    'required'      => false,
                    'wrapper'       => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                ),
                array(
                    'label'         => '',
                    'name'          => 'prompt',
                    'key'           => 'prompt',
                    'type'          => 'number',
                    'min'           => 0,
                    'prepend'       => __('Prompt', 'acfe'),
                    'placeholder'   => '',
                    'default_value' => '',
                    'instructions'  => false,
                    'required'      => false,
                    'wrapper'       => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                ),
            ),
            'conditional_logic' => array(
                array(
                    array(
                        'field'     => 'enable',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                ),
            ),
            'wrapper' => array(
                'class' => 'acfe-field-setting-flex-width'
            )
        ));

    }


    /**
     * load_field
     *
     * @param $field
     *
     * @return mixed
     */
    function load_field($field){

        if(!wp_doing_ajax()){
            return $field;
        }

        if(empty($field['acfe_ai']['enable'])){
            return $field;
        }

        if(acf_maybe_get_POST('field_key') !== $field['key'] || acf_maybe_get_POST('action') !== 'acfe/fields/ai_chat/query'){
            return $field;
        }

        // prepare field setings
        $field = $this->prepare_field_for_ai($field);

        // return
        return $field;

    }
    
    
    /**
     * render_field
     *
     * @param $field
     *
     * @return void
     */
    function render_field($field){

        // validate
        if(empty($field['acfe_ai']['enable'])){
            return;
        }

        // prepare field setings
        $field = $this->prepare_field_for_ai($field);

        // get field type instance
        $field_type = acf_get_field_type('acfe_ai_chat');

        // permissions
        if($field_type && (!$field_type->can_use($field) || !$field_type->supports_ai())){
            return;
        }

        // trigger validate_field again
        acf_extract_var($field, '_valid');
        
        ?>
        <div class="acf-field acf-field-acfe-ai-chat" data-type="acfe_ai_chat" data-key="<?php echo esc_attr($field['key']); ?>" data-fill-with-ai="1">
            <div class="acf-input">
                <?php acf_render_field($field); ?>
            </div>
        </div>
        <?php
    }


    /**
     * prepare_field_for_ai
     *
     * @param $field
     *
     * @return mixed
     */
    function prepare_field_for_ai($field){

        if(empty($field['acfe_ai']['enable'])){
            return $field;
        }

        // extract acfe_ai settings
        $acfe_ai = acf_extract_var($field, 'acfe_ai');

        // loop settings
        foreach($acfe_ai as $k => $v){
            $field[ $k ] = $v;
        }

        // other vars
        $field['type'] = 'acfe_ai_chat';
        $field['name'] = '';
        $field['display'] = 'modal';
        $field['allowed_mode'] = 'text';

        if(empty($field['modal']['button'])){
            $field['modal']['button'] = __('Fill with AI', 'acfe');
        }

        // return
        return $field;

    }
    
}

// initialize
new acfe_pro_ai_fill();

endif;