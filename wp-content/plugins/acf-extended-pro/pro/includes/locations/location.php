<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_pro_location')):

class acfe_pro_location extends acfe_location{


    /**
     * format_rule_value
     *
     * @param $rule
     *
     * @return mixed
     */
    function format_rule_value($rule){
        return $rule['value'];
    }


    /**
     * get_rule_value_input
     *
     * Returns the HTML for the rule value input, based on the field type defined in the location class
     *
     * @param $rule
     * @param $field
     *
     * @return array|string
     */
    function get_rule_value_input($rule, $field = 'text'){

        // bypass ACF Field Group screen + AJAX render location rule
        if(!acf_is_screen('acf-field-group') && !acf_is_ajax('acf/field_group/render_location_rule') && !acf_is_ajax('acfe/layout/render_location_rule')){
            return array($rule['value'] => $this->format_rule_value($rule));
        }
        
        // default prefix
        // might be passed by flexible content layouts locations
        if(empty($rule['prefix'])){
            $rule['prefix'] = "acf_field_group[location][{$rule['group']}][{$rule['id']}]";
        }

        // start buffer
        ob_start();

        // field is a string, assign it as field type
        if(is_string($field)){
            $field = array(
                'type' => $field
            );
        }

        // cast as array
        $field = acfe_as_array($field);

        // parse defaults
        $field = wp_parse_args($field, array(
            'name'      => 'value',
            'prefix'    => $rule['prefix'],
            'value'     => (isset($rule['value']) ? $rule['value'] : '')
        ));

        // render field input
        acf_render_field_wrap($field);
        
        ?>
        <script>
        (function($){

            if(typeof acf !== 'undefined'){
                acf.doAction('acfe/field_group/rule_refresh');
            }

        })(jQuery);
        </script>
        <?php

        // return buffer
        return ob_get_clean();
        
    }
    
    
    /**
     * compare_advanced
     *
     * @param $value
     * @param $rule
     * @param $all_as_wildcard
     *
     * @return bool|int
     */
    function compare_advanced($value, $rule, $all_as_wildcard = true){
        return acfe_compare_location_rule($value, $rule, $all_as_wildcard);
    }
    
}

endif;


if(!class_exists('acfe_pro_location_kses')):

class acfe_pro_location_kses{

    /**
     * construct
     */
    function __construct(){

        add_filter('acf/location/rule_values', array($this, 'location_rules_values'), 9, 2);

    }


    /**
     * location_rules_values
     *
     * Fixed kses on Field Group location rules values ajax
     * This fix an issue where custom input render were escaped starting ACF > 6.2.7
     *
     * @param $choices
     * @param $rule
     *
     * @return mixed
     *
     * @since 0.9.0.2 (28/04/2024)
     */
    function location_rules_values($choices, $rule){

        if(acf_is_screen('acf-field-group') || acf_is_ajax('acf/field_group/render_location_rule')){
            add_filter('wp_kses_allowed_html', array($this, 'location_rules_values_kses'), 10, 2);
        }

        return $choices;

    }


    /**
     * location_rules_values_kses
     *
     * @param $allowed_tags
     * @param $context
     *
     * @return array
     */
    function location_rules_values_kses($allowed_tags, $context){

        if($context === 'acf'){

            $allowed_tags['script'] = true;
            $allowed_tags['input']['type'] = true;
            $allowed_tags['input']['id'] = true;
            $allowed_tags['input']['name'] = true;
            $allowed_tags['input']['value'] = true;
            $allowed_tags['input']['min'] = true;
        }

        return $allowed_tags;

    }

}

new acfe_pro_location_kses();

endif;