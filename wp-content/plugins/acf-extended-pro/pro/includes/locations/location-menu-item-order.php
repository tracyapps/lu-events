<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_menu_item_order')):

class acfe_location_menu_item_order extends acfe_pro_location{
    
    /**
     * initialize
     *
     * @return void
     */
    function initialize(){
        
        $this->name     = 'nav_menu_item_order';
        $this->label    = __('Menu Item Order', 'acfe');
        $this->category = 'forms';
        $this->after    = 'nav_menu_item_depth';
        
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
        
        return $this->get_rule_value_input($rule, array(
            'type' => 'number',
            'min'  => 1,
        ));
        
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
    
        $choices['<']  = __('is less than', 'acf');
        $choices['<='] = __('is less or equal to', 'acf');
        $choices['>']  = __('is greater than', 'acf');
        $choices['>='] = __('is greater or equal to', 'acf');
        
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
        
        // validate screen
        if(empty($screen['nav_menu_item']) || empty($screen['nav_menu_item_id']) || !is_nav_menu_item($screen['nav_menu_item_id'])){
            return false;
        }

        // get nav menu item order
        $order = $this->get_nav_menu_item_order($screen['nav_menu_item_id']);

        // validate order
        if($order === false){
            return false;
        }
        
        // compare
        return $this->compare_advanced($order, $rule);
        
    }
    
    
    /**
     * get_nav_menu_item_order
     *
     * Get the 1-based order of a menu item among its siblings (items with the same menu_item_parent)
     *
     * @param int $menu_item_id
     *
     * @return int|false 1-based index among siblings, or false on failure
     */
    function get_nav_menu_item_order($menu_item_id){
        
        // validate
        if(!is_nav_menu_item($menu_item_id)){
            return false;
        }

        // get the menu object/term
        $nav_menu = $this->get_nav_menu_from_menu_item_id($menu_item_id);
        if(!$nav_menu){
            return false;
        }

        // retrieve all items for this menu
        $items = wp_get_nav_menu_items($nav_menu->term_id);
        if(empty($items) || is_wp_error($items)){
            return false;
        }

        // find the target item and its parent id
        $parent = null;
        foreach($items as $item){
            if((int) $item->ID === (int) $menu_item_id){
                $parent = isset($item->menu_item_parent) ? (int) $item->menu_item_parent : 0;
                break;
            }
        }
        
        if($parent === null){
            return false;
        }

        // collect siblings (same parent) and sort them by global menu_order to preserve their display order
        $siblings = array();
        foreach($items as $item){
            if(((int) $item->menu_item_parent) === $parent){
                $siblings[] = $item;
            }
        }

        if(empty($siblings)){
            return false;
        }

        usort($siblings, function($a, $b){
            if($a->menu_order == $b->menu_order) return 0;
            return ($a->menu_order < $b->menu_order) ? -1 : 1;
        });

        $pos = 0;
        $i = 0;
        foreach($siblings as $s){
            $i++;
            if((int) $s->ID === (int) $menu_item_id){
                $pos = $i;
                break;
            }
        }

        return $pos ?: false;
    }
    
    
    /**
     * Return the nav menu (WP_Term or menu object) for a nav menu item ID.
     *
     * @param int $menu_item_id Post ID of the nav_menu_item
     *
     * @return WP_Term|object|false WP_Term (term object) or menu object from wp_get_nav_menu_object, or false on failure
     */
    function get_nav_menu_from_menu_item_id($menu_item_id){
        
        // validate
        if(!is_nav_menu_item($menu_item_id)){
            return false;
        }
        
        // get the nav_menu terms attached to the menu item
        $terms = wp_get_post_terms($menu_item_id, 'nav_menu');
        if(is_wp_error($terms) || empty($terms)){
            return false;
        }
        
        // take the first
        $menu_term = $terms[0]; // WP_Term
        
        // return the menu object
        return wp_get_nav_menu_object($menu_term->term_id);
    }
    
}

acf_register_location_rule('acfe_location_menu_item_order');

endif;