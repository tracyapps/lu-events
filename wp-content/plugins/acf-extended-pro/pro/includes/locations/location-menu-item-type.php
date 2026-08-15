<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_location_menu_item_type')):

class acfe_location_menu_item_type extends acfe_pro_location{
    
    /**
     * initialize
     *
     * @return void
     */
    function initialize(){
        
        $this->name     = 'nav_menu_item_type';
        $this->label    = __('Menu Item Type', 'acf');
        $this->category = 'forms';
        $this->after    = 'nav_menu_item_order';
        
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
        
        // types
        $choices[ __('Type', 'acfe') ] = array(
            'custom'            => __('Custom Link'),
            'post_type'         => __('Post Type'),
            'post_type_archive' => __('Post Type Archive'),
            'taxonomy'          => __('Taxonomy'),
            'front_page'        => __('Front Page', 'acf'),
            'posts_page'        => __('Posts Page', 'acf'),
        );
        
        // get post types
        $post_types = get_post_types(array('show_in_nav_menus' => true));
        $post_types = acf_get_pretty_post_types($post_types);
        
        // loop post types
        foreach($post_types as $post_type => $post_type_label){
            
            // add post type
            $choices[ __('Post Type', 'acfe') ][ "post_type:{$post_type}" ] = $post_type_label;
            
            // get post type object
            $post_type_object = get_post_type_object($post_type);
            
            // check post type has_archive prop
            if($post_type_object && $post_type_object->has_archive){
                
                // label
                $artchive_label = __('Archive <span class="count">(%s)</span>');
                $artchive_label = preg_replace('/ <span(.*?)<\/span>/', '', $artchive_label);
                
                $choices[ __('Post Type', 'acfe') ][ "post_type_archive:{$post_type}" ] = $post_type_object->labels->archives . ' ' . $artchive_label;
            }
            
        }
        
        // get taxonomies
        $taxonomies = get_taxonomies(array('show_in_nav_menus' => true));
        $taxonomies = acf_get_taxonomy_labels($taxonomies);
        
        // loop taxonomies
        foreach($taxonomies as $taxonomy => $taxonomy_label){
            
            // add taxonomy
            $choices[ __('Taxonomy', 'acfe') ][ "taxonomy:{$taxonomy}" ] = $taxonomy_label;
            
        }
        
        // levels
        $choices[ __('Level', 'acfe') ] = array(
            'top_level' => __('Top Level Item (no parent)', 'acfe'),
            'parent'    => __('Parent Item (has children)', 'acfe'),
            'child'     => __('Child Item (has parent)', 'acfe'),
        );
        
        return $choices;
        
    }
    
    
    /**
     * rule_match
     *
     * @param $result
     * @param $rule
     * @param $screen
     *
     * @return false
     */
    function rule_match($result, $rule, $screen){
        
        // validate screen
        if(empty($screen['nav_menu_item']) || empty($screen['nav_menu_item_id']) || !is_nav_menu_item($screen['nav_menu_item_id'])){
            return false;
        }
        
        /**
         * get menu item data
         *
         * $menu_item_data['menu_item_parent'] = 8533 (post_id of parent menu item) / 0 (top level menu item)
         * $menu_item_data['object_id'] = 8733
         * $menu_item_data['object'] = custom / page      / post      / my-post-type / my-post-type      / category
         * $menu_item_data['type'] =   custom / post_type / post_type / post_type    / post_type_archive / taxonomy
         */
        $menu_item_data = (array) wp_setup_nav_menu_item(get_post($screen['nav_menu_item_id']));
        
        // vars
        $object = '';
        $value = $rule['value'];
        
        /**
         * handle custom types
         *
         * post_type:my-post-type
         * post_type_archive:my-post-type
         * taxonomy:my-taxonomy
         */
        foreach(array('post_type', 'post_type_archive', 'taxonomy') as $type){
            
            if(acfe_starts_with($value, "{$type}:")){
                
                // retrieve the actual object. ie: my-post-type or my-taxonomy
                $object = str_replace("{$type}:", '', $value);
                
                // set value to type
                $value = $type;
                break;
                
            }
        }
        
        // loop cases
        switch($value){
            
            case 'custom': {
                $result = $menu_item_data['object'] === 'custom' ? 'custom' : false;
                break;
            }
            
            case 'post_type': {
                
                $result = $menu_item_data['type'] === 'post_type' ? 'post_type' : false;
                
                // custom object
                if($result && !empty($object)){
                    $result = $menu_item_data['object'] === $object ? "post_type:{$object}" : false;
                }
                
                break;
            }
            
            case 'post_type_archive': {
                
                $result = $menu_item_data['type'] === 'post_type_archive' ? 'post_type_archive' : false;
                
                // custom object
                if($result && !empty($object)){
                    $result = $menu_item_data['object'] === $object ? "post_type_archive:{$object}" : false;
                }
                
                break;
            }
            
            case 'taxonomy': {
                
                $result = $menu_item_data['type'] === 'taxonomy' ? 'taxonomy' : false;
                
                // custom object
                if($result && !empty($object)){
                    $result = $menu_item_data['object'] === $object ? "taxonomy:{$object}" : false;
                }
                
                break;
            }
            
            case 'front_page': {
                $front_page = (int) get_option('page_on_front');
                $result = $menu_item_data['type'] === 'post_type' && (int) $menu_item_data['object_id'] === $front_page ? 'front_page' : false;
                break;
            }
            
            case 'posts_page': {
                $posts_page = (int) get_option('page_for_posts');
                $result = $menu_item_data['type'] === 'post_type' && (int) $menu_item_data['object_id'] === $posts_page ? 'posts_page' : false;
                break;
            }
            
            case 'top_level': {
                $result = empty($menu_item_data['menu_item_parent']) ? 'top_level' : false;
                break;
            }
            
            case 'parent': {
                $result = $this->has_menu_item_children($menu_item_data['ID']) ? 'parent' : false;
                break;
            }
            
            case 'child': {
                $result = !empty($menu_item_data['menu_item_parent']) ? 'child' : false;
                break;
            }
            
            default: {
                return false;
            }
            
        }
        
        // compare
        return $this->compare($result, $rule);
        
    }
    
    
    /**
     * has_menu_item_children
     *
     * @param $menu_item_id
     *
     * @return bool
     */
    function has_menu_item_children($menu_item_id){
        
        // get all nav menus
        $menus = wp_get_nav_menus();
        if(empty($menus)){
            return false;
        }
        
        // loop menus
        foreach($menus as $menu){
            
            // get menu items
            $items = wp_get_nav_menu_items($menu->term_id);
            if(empty($items)){
                continue;
            }
            
            // loop menu items
            foreach((array) $items as $item){
                if((int) $item->menu_item_parent === (int) $menu_item_id){
                    return true;
                }
            }
        }
        
        return false;
        
    }
    
}

acf_register_location_rule('acfe_location_menu_item_type');

endif;