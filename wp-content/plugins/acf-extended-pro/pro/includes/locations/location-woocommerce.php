<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_woocommerce')):

class acfe_location_woocommerce extends acfe_pro_location{
    
    /**
     * initialize
     *
     * @return void
     */
    function initialize(){
        
        $this->name     = 'woocommerce';
        $this->label    = __('Woocommerce', 'acfe');
        $this->category = 'forms';
        
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
            'cart'      => __('Cart page', 'acfe'),
            'checkout'  => __('Checkout page', 'acfe'),
            'myaccount' => __('My account page', 'acfe'),
            'shop'      => __('Shop page', 'acfe'),
            'terms'     => __('Terms and conditions', 'acfe'),
        );
        
    }
    
    
    /**
     * rule_match
     *
     * @param $result
     * @param $rule
     * @param $screen
     *
     * @return bool
     */
    function rule_match($result, $rule, $screen){
        
        // check woocommerce exists
        if(!function_exists('wc_get_page_id')){
            return false;
        }
        
        // vars
        $post_id = (int) acfe_get($screen, 'post_id');
        $wc_page_id = (int) wc_get_page_id($rule['value']);
        
        // validate
        // $wc_page_id returns -1 if the page is not set
        if(!$post_id || !$wc_page_id || $wc_page_id === -1){
            return false;
        }
        
        $match = $post_id === $wc_page_id;
        
        if($rule['operator'] === '!='){
            $match = !$match;
        }
        
        return $match;
        
    }
    
}

acf_register_location_rule('acfe_location_woocommerce');

endif;