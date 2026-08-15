<?php

if(!defined('ABSPATH')){
    exit;
}

if(!function_exists('wp_supports_ai') || !wp_supports_ai()){
    return;
}

if(!class_exists('acfe_field_ai_chat')):

class acfe_field_ai_chat extends acf_field{
    
    /**
     * initialize
     */
    function initialize(){
        
        $this->name = 'acfe_ai_chat';
        $this->label = __('AI Chat', 'acfe');
        $this->category = 'basic';
        $this->defaults = array(
            'placeholder'        => '',
            'default_value'      => '',
            'default_message'    => '',
            'system_instruction' => '',           // custom system instruction ...
            'allowed_mode'       => 'text',       // text | image | text_image
            'display'            => 'inline',     // inline | modal
            'modal'              => array(),
            'history'            => 'persistent', // persistent | clear
            'provider'           => '',           // '' (auto) | anthropic | openai | ...
            'text_models'        => array(),      // custom1, custom2 ...
            'image_models'       => array(),      // custom1, custom2 ...
            'acfe_permissions'   => array('administrator'),  // administrator, editor ...
            'request'            => array(
                'timeout' => '30',
                'token'   => '',
            ),
            'height'             => array(
                'min'    => '',
                'max'    => '300',
                'prompt' => '',
            ),
        );
        
        
        $this->add_action('wp_ajax_acfe/fields/ai_chat/query',         array($this, 'ajax_query'));
        $this->add_action('wp_ajax_nopriv_acfe/fields/ai_chat/query',  array($this, 'ajax_query'));

        // admin
        $this->add_action('wp_ajax_acfe/fields/ai_chat/query_models', array($this, 'ajax_query_models'));

    }


    /**
     * ajax_query_models
     *
     * @return void
     */
    function ajax_query_models(){

        // args
        $nonce = acf_request_arg('nonce', '');
        $action = acf_request_arg('action', '');

        // verify nonce
        if(!acf_verify_ajax($nonce, $action)){
            die();
        }

        // verify permissions
        if(!acf_current_user_can_admin()){
            die();
        }

        // send response
        acf_send_ajax_results($this->ajax_query_models_results($_POST));

    }


    /**
     * ajax_query_models_results
     *
     * @param $options
     *
     * @return array|array[]
     */
    function ajax_query_models_results($options = array()){

        // default
        $results = array();

        // get options
        $options = acf_parse_args($options, array(
            'field_key' => '', // text_models | image_models
            'post_id'   => 0,
            'paged'     => 0,
            's'         => '',
        ));

        if($options['field_key'] === 'text_models'){
            $capability = 'text';
        }elseif($options['field_key'] === 'image_models'){
            $capability = 'image';
        }else{
            return $results;
        }

        // get models for capability
        $all_models = $this->get_models_for_capability($capability);

        // populate choices
        if(empty($all_models)){
            return $results;
        }

        // search.
        $s = null;

        if($options['s'] !== ''){
            
            // strip slashes (search may be integer)
            $s = strval($options['s']);
            $s = wp_unslash( $s );
            
        }

        // loop providers models
        foreach($all_models as $provider => $models){

            // validate provider
            $provider_data = $this->get_provider($provider);
            if(!$provider_data){
                continue;
            }

            // prepare children
            $children = array();

            // loop provider models
            foreach($models as $model){

                // ensure $v is a string.
                $value = strval($model);

                // if searching, but doesn't exist.
                if(is_string($s) && stripos($value, $s) === false){
                    continue;
                }

                // append children.
                $children[] = array(
                    'id'   => "{$provider}:{$value}",
                    'text' => $value,
                );
            }

            // append results
            if(!empty($children)){
                $results[] = array(
                    'text'     => $provider_data['name'],
                    'children' => $children
                );
            }


        }
        
        // return
        return array(
            'results' => $results,
        );
    
    }


    /**
     * input_admin_enqueue_scripts
     */
    function input_admin_enqueue_scripts(){

        // suffix
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

        // localize
        acf_localize_data(array(
            'marked_api'   => acfe_get_url('pro/assets/inc/marked/marked' . $suffix . '.js'),
            'purify_api'   => acfe_get_url('pro/assets/inc/purify/purify' . $suffix . '.js'),
        ));

    }
    
    
    /**
     * ajax_query
     */
    function ajax_query(){
        
        // vars
        $nonce        = acf_request_arg('nonce', '');
        $key          = acf_request_arg('field_key', '');
        $is_field_key = acf_is_field_key($key);
        
        // validate
        if(!acf_verify_ajax($nonce, $key, $is_field_key)){
            die();
        }
        
        // return
        wp_send_json($this->get_ajax_query($_POST));
        
    }
    
    
    /**
     * get_ajax_query
     *
     * Based on the post_object get_ajax_query() function
     *
     * @param $options
     *
     * @return array
     */
    function get_ajax_query($options = array()){
        
        // defaults
        $options = acf_parse_args($options, array(
            'post_id'   => 0,
            'value'     => '',
            'mode'      => '',
            'field_key' => '',
            'history'   => array(),
        ));
        
        // load field
        $field = acf_get_field($options['field_key']);
        if(!$field || !$this->supports_ai() || !$this->can_use($field)){
            return array(
                'success' => false,
                'message' => __('Something went wrong.', 'acfe'),
            );
        }
        
        // force allowed mode
        if($field['allowed_mode'] === 'text'){
            $options['mode'] = 'text';
        }elseif($field['allowed_mode'] === 'image'){
            $options['mode'] = 'image';
        }

        // timeout
        $timeout = floatval($field['request']['timeout']);

        // allow more timeout for image generation
        add_filter('wp_ai_client_default_request_timeout', function() use($timeout){
            return $timeout;
        });
        
        // text command
        if($options['mode'] === 'text'){
            return $this->get_ajax_query_for_text($field, $options);
            
        // image command
        }elseif($options['mode'] === 'image'){
            return $this->get_ajax_query_for_image($field, $options);
            
        }
        
        return array(
            'success' => false,
            'message' => __('Invalid command', 'acfe'),
        );
    
    }
    
    
    /**
     * get_ajax_query_for_text
     *
     * @param $field
     * @param $options
     *
     * @return array
     */
    function get_ajax_query_for_text($field, $options){
        
        // setup builder
        $builder = wp_ai_client_prompt($options['value']);
        
        // field: provider
        if(!empty($field['provider']) && $this->has_provider($field['provider'])){
            $builder->using_provider($field['provider']);
        }
        
        // field: model preference
        if(!empty($field['text_models'])){
            
            // get models
            $models = $this->prepare_models_for_builder($field['text_models']);

            // no valid model found, return error
            if(empty($models)){
                return array(
                    'success' => false,
                    'message' => __('No provider/model available for text generation.', 'acfe'),
                );

            // using multiple model preference
            }elseif(count($models) > 1){

                // use names only
                // 'claude-sonnet-4-5',
                // 'gemini-3-pro-preview',
                // 'gpt-5.1'
                $models = wp_list_pluck($models, 'name');

                $builder->using_model_preference(...$models);

            // using single model
            }elseif(count($models) === 1){

                // get the model
                // Anthropic::model('claude-sonnet-4-5')
                $model = $models[0];
                $model = $model['class'];

                $builder->using_model($model);

            }

        }
        
        // field: system instruction
        if(!empty($field['system_instruction'])){
            $builder->using_system_instruction($field['system_instruction']);
        }

        // field: max token
        if(!empty($field['request']['token'])){
            $token = intval($field['request']['token']);
            $builder->using_max_tokens($token);
        }

        // history
        if(!empty($options['history'])){

            $messages = array();
            $options['history'] = acfe_as_array($options['history']);

            $user_role = WordPress\AiClient\Messages\Enums\MessageRoleEnum::user();
            $model_role = WordPress\AiClient\Messages\Enums\MessageRoleEnum::user();

            foreach($options['history'] as $message){

                if($message['role'] === 'user'){
                    $messages[] = new WordPress\AiClient\Messages\DTO\Message($user_role, [new WordPress\AiClient\Messages\DTO\MessagePart($message['text'])]);
                }elseif($message['role'] === 'model'){
                    $messages[] =  new WordPress\AiClient\Messages\DTO\Message($model_role, [new WordPress\AiClient\Messages\DTO\MessagePart($message['text'])]);
                }

            }

            if(!empty($messages)){
                $builder->with_history(...$messages);
            }

        }
        
        // check support
        if(!$builder->is_supported_for_text_generation()){
            return array(
                'success' => false,
                'message' => __('No provider/model available for text generation.', 'acfe'),
            );
        }
        
        // generate text
        $response = $builder->generate_text_result();
        
        // handle error
        if(is_wp_error($response)){
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }

        // vars
        $provider = $response->getProviderMetadata()->getName();
        $model = $response->getModelMetadata()->getName();
        $text = $response->toText();
        
        // success
        return array(
            'success'  => true,
            'provider' => $provider,
            'model'    => $model,
            'message'  => $text,
        );
        
    }
    
    
    /**
     * get_ajax_query_for_image
     *
     * @param $field
     * @param $options
     *
     * @return array
     */
    function get_ajax_query_for_image($field, $options){


        // setup builder
        $builder = wp_ai_client_prompt($options['value']);

        // field: provider
        if(!empty($field['provider']) && $this->has_provider($field['provider'])){
            $builder->using_provider($field['provider']);
        }

        // field: model preference
        if(!empty($field['image_models'])){

            // get models
            $models = $this->prepare_models_for_builder($field['image_models']);

            // no valid model found, return error
            if(empty($models)){
                return array(
                    'success' => false,
                    'message' => __('No provider/model available for image generation.', 'acfe'),
                );

            // using multiple model preference
            }elseif(count($models) > 1){

                // use names only
                // 'claude-sonnet-4-5',
                // 'gemini-3-pro-preview',
                // 'gpt-5.1'
                $models = wp_list_pluck($models, 'name');

                $builder->using_model_preference(...$models);

            // using single model
            }elseif(count($models) === 1){

                // get the model
                // Anthropic::model('claude-sonnet-4-5')
                $model = $models[0];
                $model = $model['class'];

                $builder->using_model($model);

            }

        }

        // field: max token
        if(!empty($field['request']['token'])){
            $token = intval($field['request']['token']);
            $builder->using_max_tokens($token);
        }

        // check support
        if(!$builder->is_supported_for_image_generation()){
            return array(
                'success' => false,
                'message' => __('No provider/model available for image generation.', 'acfe'),
            );
        }

        // generate image
        $response = $builder->generate_image_result();

        // handle error
        if(is_wp_error($response)){
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }

        // vars
        $provider = $response->getProviderMetadata()->getName();
        $model = $response->getModelMetadata()->getName();
        $image = $response->toImageFile();

        // success
        return array(
            'success'  => true,
            'provider' => $provider,
            'model'    => $model,
            'message'  => '<img src="' . $image->getDataUri() . '" alt="">',
        );
        
    }
    
    
    /**
     * render_field_settings
     *
     * @param $field
     */
    function render_field_settings($field){

        // placeholder
        acf_render_field_setting($field, array(
            'label'         => __('Placeholder Prompt','acf'),
            'instructions'  => __('Appears within the prompt input','acfe'),
            'name'          => 'placeholder',
            'type'          => 'text',
        ));
        
        // default_value
        acf_render_field_setting($field, array(
            'label'         => __('Default Prompt','acfe'),
            'instructions'  => __('Appears as the default prompt','acfe'),
            'name'          => 'default_value',
            'type'          => 'textarea',
        ));

        // default_message
        acf_render_field_setting($field, array(
            'label'         => __('Default Agent Message','acfe'),
            'instructions'  => __('The default message displayed in the field','acfe'),
            'name'          => 'default_message',
            'type'          => 'textarea',
            'placeholder'   => '',
        ));

        // system_instruction
        acf_render_field_setting($field, array(
            'label'         => __('Agent Instruction','acfe'),
            'instructions'  => __('Gives the agent instructions and context','acfe'),
            'name'          => 'system_instruction',
            'type'          => 'textarea',
            'placeholder'   => __('I am a WordPress expert and I want to assist you.','acfe'),
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
        ));
        
        // allowed_mode
        acf_render_field_setting($field, array(
            'label'         => __('Allowed Mode','acfe'),
            'instructions'  => '',
            'name'          => 'allowed_mode',
            'type'          => 'radio',
            'layout'        => 'horizontal',
            'choices'       => array(
                'text'       => __('Text', 'acfe'),
                'image'      => __('Image', 'acfe'),
                'text_image' => __('Text & Image', 'acfe'),
            ),
        ));
        
        // display
        acf_render_field_setting($field, array(
            'label'         => __('Display','acfe'),
            'instructions'  => __('How the chat should be displayed','acfe'),
            'name'          => 'display',
            'type'          => 'radio',
            'layout'        => 'horizontal',
            'choices'       => array(
                'inline' => __('Inline', 'acfe'),
                'modal'  => __('Modal', 'acfe'),
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
                    'placeholder'   => __('Open Chat', 'acfe'),
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
                    'default_value' => 'large',
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
                        'field'     => 'display',
                        'operator'  => '==',
                        'value'     => 'modal',
                    ),
                )
            ),
            'wrapper' => array(
                'class' => 'acfe-field-setting-flex-width'
            )
        ));

        
        $providers = $this->get_providers();
        $provider_choices = array(
            '' => __('Auto', 'acfe')
        );
        foreach($providers as $provider){
            $provider_choices[ $provider['id'] ] = $provider['name'];
        }

        $connectors_url = admin_url('options-connectors.php');
        
        // provider
        acf_render_field_setting($field, array(
            'label'         => __('Provider','acf'),
            'instructions'  => sprintf(__('Choose the provider to use. You can set up your providers in the <a href="%s">WP Connectors</a> menu.','acfe'), $connectors_url),
            'name'          => 'provider',
            'type'          => 'radio',
            'layout'        => 'horizontal',
            'choices'       => $provider_choices,
        ));
        
        // text: models
        acf_render_field_setting($field, array(
            'label'         => __('Text: Model Preference','acfe'),
            'name'          => 'text_models',
            'key'           => 'text_models',
            'type'          => 'select',
            'choices'       => $this->get_models_choices($field['text_models']),
            'ui'            => 1,
            'multiple'      => 1,
            'ajax'          => 1,
            'ajax_action'   => 'acfe/fields/ai_chat/query_models',
            'nonce'         => wp_create_nonce('acfe/fields/ai_chat/query_models'),
            'conditional_logic' => array(
                array(
                    array(
                        'field'     => 'allowed_mode',
                        'operator'  => '==',
                        'value'     => 'text_image',
                    ),
                ),
                array(
                    array(
                        'field'     => 'allowed_mode',
                        'operator'  => '==',
                        'value'     => 'text',
                    ),
                ),
            )
        ));
        
        // image: models
        acf_render_field_setting($field, array(
            'label'         => __('Image: Model Preference','acfe'),
            'name'          => 'image_models',
            'key'           => 'image_models',
            'type'          => 'select',
            'choices'       => $this->get_models_choices($field['image_models']),
            'ui'            => 1,
            'multiple'      => 1,
            'ajax'          => 1,
            'ajax_action'   => 'acfe/fields/ai_chat/query_models',
            'nonce'         => wp_create_nonce('acfe/fields/ai_chat/query_models'),
            'conditional_logic' => array(
                array(
                    array(
                        'field'     => 'allowed_mode',
                        'operator'  => '==',
                        'value'     => 'text_image',
                    ),
                ),
                array(
                    array(
                        'field'     => 'allowed_mode',
                        'operator'  => '==',
                        'value'     => 'image',
                    ),
                ),
            )
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
            'conditional_logic' => array(),
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
                    'instructions'  => false,
                    'required'      => false,
                    'wrapper'       => array(
                        'width' => '',
                        'class' => '',
                        'id'    => '',
                    ),
                ),
            ),
            'conditional_logic' => array(),
            'wrapper' => array(
                'class' => 'acfe-field-setting-flex-width'
            )
        ));
        
    }


    /**
     * get_models_choices
     *
     * @param $value
     *
     * @return array
     */
    function get_models_choices($value){

        // vars
        $choices = array();

        // bail early if no value
        if(empty($value)){
            return $choices;
        }

        // force value to array
        $value = acfe_as_array($value);

        // loop
        foreach($value as $v){

            // try to explode
            $explode = explode(':', $v);
            $model = isset($explode[1]) ? $explode[1] : $v;

            // assign
            $choices[ $v ] = $model;

        }

        // return
        return $choices;

    }


    /**
     * prepare_field
     *
     * @param $field
     *
     * @return false
     */
    function prepare_field($field){

        // wp doesn't support AI
        if(!$this->supports_ai()){
            return false;
        }

        // display normally
        return $field;

    }
    
    
    /**
     * render_field
     *
     * @param $field
     */
    function render_field($field){
        
        // field type
        $field['type']  = 'textarea';
        $field['rows']  = 1;
        $field['value'] = $field['default_value']; // also use default value
        $field['nonce'] = wp_create_nonce('acf_field_' . $this->name . '_' . $field['key']);
    
        // wrapper
        $wrapper = array(
            'class'        => 'acfe-ai-prompt',
            'data-display' => $field['display'],
            'data-history' => $field['history'],
            'data-nonce'   => $field['nonce'],
        );

        if(!empty($field['height']['min'])){
            $wrapper['data-min-height'] = intval($field['height']['min']);
        }

        if(!empty($field['height']['max'])){
            $wrapper['data-max-height'] = intval($field['height']['max']);
        }
        
        ?>
        <div <?php echo acf_esc_atts($wrapper); ?>>

            <?php if($field['display'] === 'modal'): ?>
                <?php $this->render_modal($field); ?>
            <?php else: ?>
                <?php $this->render_inline($field); ?>
            <?php endif; ?>
            
        </div>
        <?php
    }


    /**
     * render_inline
     *
     * @param $field
     *
     * @return void
     */
    function render_inline($field){

        ?>
        <div <?php echo acf_esc_atts($this->get_logs_wrapper($field)); ?>><?php if($field['default_message']): ?>
            <div class="message response -default">
                <div class="meta">
                    <span class="author">Agent</span>
                    <span class="separator"> - </span>
                    <span class="date" title="<?php echo wp_date('H:i:s'); ?>"><?php echo wp_date('H:i:s'); ?></span>
                </div>
                <div class="content"><?php echo $field['default_message']; ?></div>
                <div class="actions"></div>
            </div>
        <?php endif; ?></div>
        <?php $this->render_input($field); ?>
        <div class="acfe-ai-prompt-clear"><a href="#"><?php echo __('Clear history', 'acfe'); ?></a></div>
        <?php
    }


    /**
     * render_modal
     *
     * @param $field
     *
     * @return void
     */
    function render_modal($field){

        // modal attributes
        $modal_title  = !empty($field['modal']['title']) ? $field['modal']['title'] : $field['label'];
        $modal_size   = !empty($field['modal']['size']) ? $field['modal']['size'] : 'medium';
        $model_button = !empty($field['modal']['button']) ? $field['modal']['button'] : __('Open Chat', 'acfe');

        ?>
        <div class="acfe-modal" data-title="<?php echo $modal_title; ?>" data-size="<?php echo $modal_size; ?>">
            <div class="acfe-modal-wrapper">

                <div class="acfe-modal-content">
                    <div <?php echo acf_esc_atts($this->get_logs_wrapper($field)); ?>><?php if($field['default_message']): ?>
                            <div class="message response -default">
                                <div class="meta">
                                    <span class="author">Agent</span>
                                    <span class="separator"> - </span>
                                    <span class="date" title="<?php echo wp_date('H:i:s'); ?>"><?php echo wp_date('H:i:s'); ?></span>
                                </div>
                                <div class="content"><?php echo $field['default_message']; ?></div>
                                <div class="actions"></div>
                            </div>
                    <?php endif; ?></div>
                </div>

                <div class="acfe-modal-footer">
                    <?php $this->render_input($field); ?>
                    <div class="acfe-ai-prompt-clear"><a href="#"><?php echo __('Clear history', 'acfe'); ?></a></div>
                </div>

            </div>
        </div>
        <a href="#" class="acf-button button" data-modal><?php echo $model_button; ?></a>
        <?php
    }


    /**
     * render_input
     *
     * @param $field
     *
     * @return void
     */
    function render_input($field){

        if(!empty($field['height']['prompt'])){
            $height = intval($field['height']['prompt']);
            $style = "min-height: {$height}px;";
            $field['style'] = isset($field['style']) ? $field['style'] . ';' . $style : $style;
        }

        ?>
        <div class="acfe-ai-prompt-input-wrap">
            <div class="acfe-ai-prompt-input">
                <?php $this->render_textarea($field); ?>
            </div>
            <div class="acfe-ai-prompt-actions">
                <?php if($field['allowed_mode'] === 'text' || $field['allowed_mode'] === 'text_image'): ?>
                    <button type="button" class="button acfe-ai-prompt-button" data-mode="text"><span class="dashicons dashicons-redo"></span></button>
                <?php endif; ?>
                <?php if($field['allowed_mode'] === 'image' || $field['allowed_mode'] === 'text_image'): ?>
                    <button type="button" class="button acfe-ai-prompt-button" data-mode="image"><span class="dashicons dashicons-admin-appearance"></span></button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }


    function render_textarea($field){

        // vars
        $atts  = array();
        $keys  = array('id', 'class', 'name', 'value', 'placeholder', 'rows', 'maxlength', 'style'); // allow style
        $keys2 = array('readonly', 'disabled', 'required');

        // atts (value="123")
        foreach($keys as $k){
            if (isset($field[ $k ])){
                $atts[ $k ] = $field[ $k ];
            }
        }

        // atts2 (disabled="disabled")
        foreach($keys2 as $k){
            if(!empty($field[ $k ])){
                $atts[ $k ] = $k;
            }
        }

        // remove empty atts
        $atts = acf_clean_atts($atts);

        // return
        acf_textarea_input($atts);

    }


    /**
     * get_logs_wrapper
     *
     * @param $field
     *
     * @return string[]
     */
    function get_logs_wrapper($field){

        $wrapper = array(
            'class' => 'acfe-ai-prompt-chat',
            'style' => '',
        );

        if(!empty($field['height']['min'])){
            $wrapper['style'] .= 'min-height:' . intval($field['height']['min']) . 'px;';
        }

        if(!empty($field['height']['max'])){
            $wrapper['style'] .= 'max-height:' . intval($field['height']['max']) . 'px;';
        }

        return $wrapper;

    }


    /**
     * update_value
     *
     * @param $value
     * @param $post_id
     * @param $field
     *
     * @return string
     */
    function update_value($value, $post_id, $field){
        return ''; // save as empty (to allow repeater with only one ai chat sub field to be saved)
    }
    
    
    /**
     * translate_field
     *
     * @param $field
     *
     * @return mixed
     */
    function translate_field($field){

        if(isset($field['modal']['button'])){
            $field['modal']['button'] = acf_translate($field['modal']['button']);
        }

        if(isset($field['modal']['title'])){
            $field['modal']['title'] = acf_translate($field['modal']['title']);
        }

        $field['placeholder']        = acf_translate($field['placeholder']);
        $field['default_value']      = acf_translate($field['default_value']);
        $field['default_message']    = acf_translate($field['default_message']);
        $field['system_instruction'] = acf_translate($field['system_instruction']);

        // return
        return $field;
        
    }
    
    
    /**
     * get_registry
     *
     * @return \WordPress\AiClient\Providers\ProviderRegistry
     */
    function get_registry(){
        return WordPress\AiClient\AiClient::defaultRegistry();
    }
    
    
    /**
     * get_providers
     *
     * @return array
     */
    function get_providers(){
        
        // vars
        $result = array();
        $providers = $this->get_registry()->getRegisteredProviderIds();
        
        // loop providers
        foreach($providers as $provider_id){
            
            // get provider data
            $provider_data = $this->get_provider($provider_id);
            if($provider_data){
                $result[ $provider_id ] = $provider_data;
            }
            
        }
        
        // return
        return $result;
        
    }
    
    
    /**
     * get_provider
     *
     * @param $id
     *
     * @return array|false
     */
    function get_provider($id){
        
        try{
            $class_name = $this->get_registry()->getProviderClassName($id);
        }catch(WordPress\AiClient\Common\Exception\InvalidArgumentException $e){
            return false;
        }
        
        // return
        return array(
            'id'          => $class_name::metadata()->getId(),
            'name'        => $class_name::metadata()->getName(),
            'description' => $class_name::metadata()->getDescription(),
            'class_name'  => $class_name,
        );
        
    }
    
    
    /**
     * has_provider
     *
     * @param $provider_id
     *
     * @return bool
     */
    function has_provider($provider_id){
        return $this->get_registry()->hasProvider($provider_id);
    }
    
    
    /**
     * get_provider_models
     *
     * @param $provider_id
     *
     * @return array
     */
    function get_provider_models($provider_id){
        
        // vars
        $return = array();
        
        // validate provider
        if(!$this->has_provider($provider_id)){
            return $return;
        }
        
        $class_name = $this->get_registry()->getProviderClassName($provider_id);
        $modelMetadataDirectory = $class_name::modelMetadataDirectory();
        
        // loop models
        foreach($modelMetadataDirectory->listModelMetadata() as $modelMetadata){
            $return[] = $modelMetadata->getName();
        }
        
        return $return;
        
    }
    
    
    /**
     * has_provider_model
     *
     * @param $provider_id
     * @param $model
     *
     * @return bool
     */
    function has_provider_model($provider_id, $model){
        return in_array($model, $this->get_provider_models($provider_id), true);
    }
    
    
    /**
     * get_providers_models
     *
     * @return array
     */
    function get_providers_models(){
        
        // vars
        $providers_models = array();
        
        // loop providers
        foreach($this->get_providers() as $provider){
            $providers_models[ $provider['id'] ] = $this->get_provider_models($provider['id']);
        }
        
        // return
        return $providers_models;
        
    }
    
    
    /**
     * get_provider_models_for_capability
     *
     * @param $provider_id
     * @param $capability
     *
     * @return array
     */
    function get_provider_models_for_capability($provider_id, $capability){
        
        // vars
        $result = array();
        
        // check provider
        if(!$this->has_provider($provider_id)){
            return $result;
        }
        
        // prepare requirements
        $capability_instance = $this->get_capability_instance($capability);
        $model_config = new WordPress\AiClient\Providers\Models\DTO\ModelConfig();
        $requirements = WordPress\AiClient\Providers\Models\DTO\ModelRequirements::fromPromptData($capability_instance, [], $model_config);
        $models = array();
        
        try {
            $models = $this->get_registry()->findProviderModelsMetadataForSupport($provider_id, $requirements);
        } catch (WordPress\AiClient\Common\Exception\InvalidArgumentException $e) {}
        
        // loop providers
        foreach($models as $model){
            $result[] = $model->getName();
        }
        
        // return
        return $result;
        
    }
    
    
    /**
     * get_models_for_capability
     *
     * @param $capability
     *
     * @return array
     */
    function get_models_for_capability($capability){
        
        // prepare requirements
        $capability_instance = $this->get_capability_instance($capability);
        $model_config = new WordPress\AiClient\Providers\Models\DTO\ModelConfig();
        $requirements = WordPress\AiClient\Providers\Models\DTO\ModelRequirements::fromPromptData($capability_instance, [], $model_config);
        $providers = array();
        
        try {
            $providers = $this->get_registry()->findModelsMetadataForSupport($requirements);
        } catch (WordPress\AiClient\Common\Exception\InvalidArgumentException $e) {}
        
        // prepare results
        $result = array();
        
        // loop providers
        foreach($providers as $provider){
            
            // vars
            $provider_id = $provider->getProvider()->getId();
            $models = $provider->getModels();
            
            // append results
            foreach($models as $model){
                $result[ $provider_id ][] = $model->getName();
            }
        
        }
        
        // return
        return $result;
        
    }
    
    
    /**
     * get_capability_instance
     *
     * @param $capability
     *
     * @return false|\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum
     */
    function get_capability_instance($capability){
        
        if($capability === 'text'){
            return WordPress\AiClient\Providers\Models\Enums\CapabilityEnum::textGeneration();
        }elseif($capability === 'image'){
            return WordPress\AiClient\Providers\Models\Enums\CapabilityEnum::imageGeneration();
        }
        
        return false;
        
    }


    /**
     * prepare_models_for_builder
     *
     * @param $choices
     *
     * @return array
     */
    function prepare_models_for_builder($choices){

        // vars
        $models = array();
        $choices = acfe_as_array($choices);

        // explode choice "openai:gpt-3.5-turbo" into provider/model
        foreach($choices as $model){

            // validate format "openai:gpt-3.5-turbo"
            if(strpos($model, ':') === false){
                continue;
            }

            // extract provider/model
            list($provider_id, $model_name) = explode(':', $model);

            // get provider data and validate model
            $provider_data = $this->get_provider($provider_id);
            if($provider_data && $this->has_provider_model($provider_id, $model_name)){

                $models[] = array(
                    'name' => $model_name,
                    'class' => $provider_data['class_name']::model($model_name)
                );

            }
        }

        return $models;

    }
    
    
    /**
     * supports_ai
     *
     * @return bool
     */
    function supports_ai(){
        return function_exists('wp_supports_ai') && wp_supports_ai();
    }


    /**
     * can_use
     *
     * @return bool
     */
    function can_use($field){

        // validate field type
        if(acfe_get($field, 'type') !== $this->name){
            return false;
        }

        // get permissions
        $permissions = acfe_get($field, 'acfe_permissions');

        // permissions was never set, the field is probably misconfigured
        if($permissions === null){
            $permissions = 'administrator'; // default to administrator only
        }

        // cast as array
        $permissions = acfe_as_array($permissions);

        // no permissions set, allow by default
        if(empty($permissions)){
            return true;
        }

        // check current user role
        return acfe_has_current_user_role($permissions);

    }
    
}

// initialize
acf_register_field_type('acfe_field_ai_chat');

endif;