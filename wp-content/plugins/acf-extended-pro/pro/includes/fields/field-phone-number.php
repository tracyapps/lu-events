<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_field_phone_number')):

class acfe_field_phone_number extends acf_field{
    
    /**
     * initialize
     */
    function initialize(){
    
        $category = acfe_is_acf('6.1') ? 'advanced' : 'jquery';
        
        $this->name = 'acfe_phone_number';
        $this->label = __('Phone Number', 'acfe');
        $this->category = $category;
        $this->defaults = array(
            'countries'             => array(),
            'preferred_countries'   => array(),
            'default_country'       => '',
            'geolocation'           => 0,
            'geolocation_token'     => '',
            'native'                => 0,
            'national'              => 0,
            'dropdown'              => 0,
            'dial_code'             => 0,
            'default_value'         => '',
            'placeholder'           => '',
            'return_format'         => 'number',
        );

        // ajax
        add_action('wp_ajax_acfe/fields/phone_number/geolocation',        array($this, 'ajax_geolocation'));
        add_action('wp_ajax_nopriv_acfe/fields/phone_number/geolocation', array($this, 'ajax_geolocation'));

        // filter variations
        acf_add_filter_variations('acfe/fields/phone_number/geolocation/custom_query', array('name', 'key'), 1);
        acf_add_filter_variations('acfe/fields/phone_number/geolocation/api_params',   array('name', 'key'), 1);

    }


    /**
     * ajax_geolocation
     *
     * @return void
     */
    function ajax_geolocation(){

        // parse args
        $args = acf_request_args(array(
            'nonce'       => '',
            'field_key'   => '',
        ));

        // verify nonce
        if(!acf_verify_ajax($args['nonce'], $args['field_key'], true)){
            die();
        }

        // get field
        $field = acf_get_field($args['field_key']);
        if(!$field){
            die();
        }

        // validate geolocation setting
        if(empty($field['geolocation'])){
            die();
        }

        // filter: allow third-party custom geolocation query
        // this allows bypassing the default IPinfo.io API request and response, and use a custom geolocation method instead
        $custom_api_response = apply_filters('acfe/fields/phone_number/geolocation/custom_query', null, $field);

        if($custom_api_response !== null){

            // allow 'false' to bypass
            if(empty($custom_api_response)){
                die();
            }

            // send response
            wp_send_json(array(
                'country' => $custom_api_response,
            ));

        }

        // vars
        $ip = acfe_get_ip();
        $api_token = $field['geolocation_token'];

        /**
         * IPInfo.io: Lite Plan API
         * https://support.ipinfo.io/hc/en-us/articles/34121895556242-Legacy-Free-API-vs-IPinfo-Lite
         *
         * Array(
         *     [ip] => 8.8.8.8
         *     [asn] => AS0000
         *     [as_name] => Internet
         *     [as_domain] => internet.com
         *     [country_code] => US
         *     [country] => United States
         *     [continent_code] => NA
         *     [continent] => North America
         * )
         */
        $params = array(
            'url'         => add_query_arg('token', $api_token, "https://api.ipinfo.io/lite/{$ip}"),
            'country_key' => 'country_code',
        );

        /**
         * IPInfo.io: Legacy Free API
         * Allows 1000 requests/day for free without token (data are different tho)
         *
         * Array(
         *     [ip] => 8.8.8.8
         *     [hostname] => hostname.internet.com
         *     [city] => City
         *     [region] => Region
         *     [country] => US
         *     [loc] => 0.0.0
         *     [org] => AS0000 Internet
         *     [postal] => 00000
         *     [timezone] => America/Los_Angeles
         *     [readme] => https://ipinfo.io/missingauth
         * )
         */
        if(empty($api_token)){
            $params = array(
                'url'         => "https://ipinfo.io/{$ip}/json",
                'country_key' => 'country',
            );
        }

        // filters
        $params = apply_filters('acfe/fields/phone_number/geolocation/api_params', $params, $field);

        // make request
        $response = wp_remote_get($params['url']);
        if(is_wp_error($response)){
            die();
        }

        // decode response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // validate
        if(!is_array($data)){
            die();
        }

        // send response
        wp_send_json(array(
            'country' => acfe_get($data, $params['country_key'], ''), // get country key based on API used (allow multi-dimentional array access. Ie: geo.country_code)
        ));

    }
    
    
    /**
     * render_field_settings
     *
     * @param $field
     */
    function render_field_settings($field){
        
        // Countries
        acf_render_field_setting($field, array(
            'label'         => __('Allow Countries', 'acf'),
            'instructions'  => 'Allow only the defined countries',
            'name'          => 'countries',
            'type'          => 'acfe_countries',
            'field_type'    => 'select',
            'flags'         => true,
            'placeholder'   => __('All countries', 'acf'),
            'ui'            => true,
            'multiple'      => true,
        ));
        
        // Preferred Countries
        acf_render_field_setting($field, array(
            'label'         => __('Preferred Countries','acf'),
            'instructions'  => 'Define the countries to appear at the top of the list',
            'name'          => 'preferred_countries',
            'type'          => 'acfe_countries',
            'field_type'    => 'select',
            'flags'         => true,
            'placeholder'   => __('Select', 'acf'),
            'ui'            => true,
            'multiple'      => true,
        ));
    
        // Default Country
        acf_render_field_setting($field, array(
            'label'         => __('Default Country', 'acf'),
            'instructions'  => 'Set the initial country selection',
            'name'          => 'default_country',
            'type'          => 'acfe_countries',
            'field_type'    => 'select',
            'flags'         => true,
            'ui'            => true,
            'allow_null'    => true,
            'conditions'    => array(
                array(
                    array(
                        'field'     => 'geolocation',
                        'operator'  => '!=',
                        'value'     => '1',
                    ),
                ),
            )
        ));
    
        // Geolocation
        acf_render_field_setting($field, array(
            'label'         => __('Geolocation', 'acfe'),
            'instructions'  => __("Lookup the user's country based on their IP address using <a href='https://ipinfo.io/' target='_blank'>IPinfo.io</a>", 'acfe'),
            'name'          => 'geolocation',
            'type'          => 'true_false',
            'ui'            => 1,
        ));
    
        acf_render_field_setting($field, array(
            'label'         => __('Geolocation API token', 'acfe'),
            'instructions'  => __('<a href="https://ipinfo.io/" target="_blank">IPinfo.io</a> API token', 'acfe'),
            'name'          => 'geolocation_token',
            'type'          => 'text',
            'conditional_logic'    => array(
                array(
                    array(
                        'field'     => 'geolocation',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                ),
            )
        ));
    
        // Native Names
        acf_render_field_setting($field, array(
            'label'         => __('Native Names','acf'),
            'instructions'  => 'Show native country names',
            'name'          => 'native',
            'type'          => 'true_false',
            'ui'            => 1,
        ));
    
        // National Mode
        acf_render_field_setting($field, array(
            'label'         => __('National Mode','acf'),
            'instructions'  => 'Allow users to enter national numbers',
            'name'          => 'national',
            'type'          => 'true_false',
            'ui'            => 1,
            'conditions'    => array(
                array(
                    array(
                        'field'     => 'dial_code',
                        'operator'  => '!=',
                        'value'     => '1',
                    ),
                ),
            )
        ));
    
        // Dropdown
        acf_render_field_setting($field, array(
            'label'         => __('Allow Dropdown','acf'),
            'instructions'  => 'Whether or not to allow the dropdown',
            'name'          => 'dropdown',
            'type'          => 'true_false',
            'ui'            => 1,
        ));
    
        // Separate Dial Code
        acf_render_field_setting($field, array(
            'label'         => __('Separate Dial Code','acf'),
            'instructions'  => 'Display the country dial code next to the selected flag',
            'name'          => 'dial_code',
            'type'          => 'true_false',
            'ui'            => 1,
        ));
        
        // Default Value
        acf_render_field_setting($field, array(
            'label'         => __('Default Value','acf'),
            'instructions'  => 'Must be international number with prefix. ie: +1201-555-0123',
            'name'          => 'default_value',
            'type'          => 'text',
        ));
        
        // Placeholder
        acf_render_field_setting($field, array(
            'label'         => __('Placeholder','acf'),
            'instructions'  => 'You may use <code>{placeholder}</code> to print the country phone number placeholder',
            'name'          => 'placeholder',
            'type'          => 'text',
        ));
        
        $format = array(
            'array'  => __('Phone Array', 'acfe'),
            'number' => __('Phone Number', 'acfe'),
        );
        
        if(class_exists('libphonenumber\PhoneNumberUtil')){
        
            $format['national'] = __('National Number', 'acfe');
            $format['international'] = __('International Number', 'acfe');
        
        }
        
        // return format
        acf_render_field_setting($field, array(
            'label'         => __('Return Value', 'acf'),
            'instructions'  => '',
            'type'          => 'radio',
            'name'          => 'return_format',
            'layout'        => 'horizontal',
            'choices'       => $format
        ));
        
        // server Validation
        if(!class_exists('libphonenumber\PhoneNumberUtil')){
        
            acf_render_field_setting($field, array(
                'label'         => __('Additional Settings', 'acfe'),
                'instructions'  => '',
                'type'          => 'message',
                'new_lines'     => 'br',
                'message'       => __('Additional settings such as "National Number", "International Number" return formats and phone number server validation are available when using the <a href="https://github.com/giggsey/libphonenumber-for-php" target="_blank">Libphonenumber for PHP</a> library.<br /><br />You can install this library manually or with the <a href="https://www.acf-extended.com/addons/acf-extended-pro-libphonenumber.zip" target="_blank">ACF Extended: Phone Number Library Addon</a> plugin.', 'acfe'),
            ));
        
        }
        
    }
    
    
    /**
     * input_admin_enqueue_scripts
     */
    function input_admin_enqueue_scripts(){
        
        // suffix
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        
        // localize
        acf_localize_data(array(
            'int_tel_input_api'   => acfe_get_url('pro/assets/inc/intl-tel-input/intl-tel-input' . $suffix . '.js'),
            'int_tel_input_utils' => acfe_get_url('pro/assets/inc/intl-tel-utils/intl-tel-utils' . $suffix . '.js'),
            'phoneNumberL10n' => array(
                'invalidPhoneNumber'    => _x('Invalid Phone Number',       'Phone Number JS invalidPhoneNumber',   'acfe'),
                'invalidCountry'        => _x('Invalid Country',            'Phone Number JS invalidCountry',       'acfe'),
                'phoneNumberTooShort'   => _x('Phone Number is too short',  'Phone Number JS phoneNumberTooShort',  'acfe'),
                'phoneNumberTooLong'    => _x('Phone Number is too long',   'Phone Number JS phoneNumberTooLong',   'acfe'),
            )
        ));
        
    }
    
    
    /**
     * render_field
     *
     * @param $field
     */
    function render_field($field){
        
        // div
        $div = array(
            'class'                     => "acfe-phone-number {$field['class']}",
            'data-countries'            => $field['countries'],
            'data-preferred_countries'  => $field['preferred_countries'],
            'data-default_country'      => $field['default_country'],
            'data-geolocation'          => $field['geolocation'],
            'data-native'               => $field['native'],
            'data-dropdown'             => $field['dropdown'],
            'data-dial_code'            => $field['dial_code'],
            'data-national'             => $field['national'],
            'data-placeholder'          => $field['placeholder'],
        );

        // geolocation nonce
        if($field['geolocation']){
            $div['data-nonce'] = wp_create_nonce('acf_field_' . $this->name . '_' . $field['key']);
        }
        
        // value
        $value = $field['value'];
        
        // decode old array format
        if(is_array($value)){
            $value = acfe_get($value, 'number');
        }
        
        // Hidden
        $hidden_input = array(
            'name'  => $field['name'],
            'value' => $value,
        );
        
        // Text
        $text_input = array(
            'type'  => 'tel',
            'class' => 'input',
            'value' => $value,
        );
        
        // Render
        ?>
        <div <?php echo acf_esc_atts($div); ?>>
            <?php acf_hidden_input($hidden_input); ?>
            <?php acf_text_input($text_input); ?>
        </div>
        <?php
        
    }
    
    
    /**
     * validate_value
     *
     * @param $valid
     * @param $value
     * @param $field
     * @param $input
     *
     * @return mixed|string|null
     */
    function validate_value($valid, $value, $field, $input){
        
        // bail early
        if(!$value){
            return $valid;
        }
        
        // check library
        if(!class_exists('libphonenumber\PhoneNumberUtil')){
            return $valid;
        }
        
        // get libphonenumber instance
        $libphonenumber = libphonenumber\PhoneNumberUtil::getInstance();
        
        // validate
        try{
        
            $number_data = $libphonenumber->parse($value);
            
            // check number validity
            if(!$libphonenumber->isValidNumber($number_data)){
                $valid = __('Invalid Phone Number', 'acfe');
            }
            
            // check allowed countries
            if($field['countries']){
                
                // get phone country
                $country = strtolower($libphonenumber->getRegionCodeForNumber($number_data));
                
                // check allowed
                if(!in_array($country, $field['countries'])){
                    $valid = __('Invalid Country', 'acfe');
                }
                
            }
        
        }catch(libphonenumber\NumberParseException $e){
            $valid = __('Invalid Phone Number', 'acfe');
        }
        
        return $valid;
        
    }
    
    
    /**
     * update_value
     *
     * @param $value
     * @param $post_id
     * @param $field
     *
     * @return array|false
     */
    function update_value($value, $post_id, $field){
        
        // parse old array format
        if(is_array($value)){
            $value = acfe_get($value, 'number');
        }
        
        // return
        return $value;
        
    }
    
    
    /**
     * load_value
     *
     * @param $value
     * @param $post_id
     * @param $field
     *
     * @return mixed
     */
    function load_value($value, $post_id, $field){
        
        // parse old array format
        if(is_array($value)){
            $value = acfe_get($value, 'number');
        }
        
        // return
        return $value;
        
    }
    
    
    /**
     * format_value
     *
     * @param $value
     * @param $post_id
     * @param $field
     *
     * @return array|mixed|string|null
     */
    function format_value($value, $post_id, $field){
        
        // bail early
        if(empty($value)){
            return $value;
        }
        
        // array
        if($field['return_format'] === 'array'){
            
            $array = array(
                'number'        => $value,
                'country'       => $this->get_phone_country($value),
                'national'      => $this->get_phone_format($value, 'national'),
                'international' => $this->get_phone_format($value, 'international'),
            );
            
            // set value
            $value = $array;
        
        // number
        }elseif($field['return_format'] === 'number'){
            
            // do nothing
    
        // national + international
        }elseif($field['return_format'] === 'national' || $field['return_format'] === 'international'){
            
            $value = $this->get_phone_format($value, $field['return_format']);
            
        }
    
        // return
        return $value;
        
    }
    
    
    /**
     * get_phone_country
     *
     * @param $value
     *
     * @return mixed|string
     */
    function get_phone_country($value){
    
        // libphonenumber
        if(class_exists('libphonenumber\PhoneNumberUtil')){
    
            // retrieve library
            $libphonenumber = libphonenumber\PhoneNumberUtil::getInstance();
    
            try{
        
                // parse number
                $number_data = $libphonenumber->parse($value);
        
                return strtolower($libphonenumber->getRegionCodeForNumber($number_data));
        
            }catch(libphonenumber\NumberParseException $e){}
            
            return '';
            
        // simple phones
        }else{
    
            $phones = acfe_include('pro/includes/data/phones.php', false);
            $number = str_replace('+', '', $value);
            $storage = array();
    
            foreach($phones as $phone){
        
                if(acfe_starts_with($number, $phone['code'])){
                    $storage[] = $phone;
                }
        
            }
    
            if(!$storage){
                return '';
            }
            
            $found = array();
            
            // sort storage by priority
            usort($storage, function($a, $b){
                return $a['priority'] - $b['priority'];
            });
            
            // loop storage
            foreach($storage as $store){
                
                // parse full code + area
                foreach($store['full'] as $full){
                    
                    if(acfe_starts_with($number, $full)){
                        $found = $store;
                        break;
                    }
                    
                }
                
            }
            
            // found full code + area
            if($found){
                return $found['country'];
            }
            
            // use top priority code
            $data = current($storage);
            
            return $data['country'];
            
        }
        
    }
    
    
    /**
     * get_phone_format
     *
     * @param $value
     * @param $format
     *
     * @return mixed|string
     */
    function get_phone_format($value, $format = 'national'){
        
        // bail early
        if(!class_exists('libphonenumber\PhoneNumberUtil')){
            return $value;
        }
    
        // retrieve library
        $libphonenumber = libphonenumber\PhoneNumberUtil::getInstance();
    
        try{
        
            // parse number
            $number_data = $libphonenumber->parse($value);
        
            // 044 668 18 00
            if($format === 'national'){
                $value = $libphonenumber->format($number_data, libphonenumber\PhoneNumberFormat::NATIONAL);
            
                // +41 44 668 18 00
            }elseif($format === 'international'){
                $value = $libphonenumber->format($number_data, libphonenumber\PhoneNumberFormat::INTERNATIONAL);
            }
        
        }catch(libphonenumber\NumberParseException $e){}
        
        // return
        return $value;
        
    }
    
}

// initialize
acf_register_field_type('acfe_field_phone_number');

endif;