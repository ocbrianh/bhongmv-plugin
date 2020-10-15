<?php
/*
    Plugin Name: Brian Hong Mediavine Code Challenge Plugin
    Plugin URI: https://github.com/ocbrianh/bhongmv-plugin
    Description: Mediavine Code Challenge Plugin
    Version: 0.1.0
    Author: Brian Hong
    Author URI: https://www.linkedin.com/in/brian-hong-07974a29/
    Text Domain: textdomain
    Domain Path: /languages
*/

class BHongMV {
    
    protected $pluginPath;
    protected $pluginUrl;
    protected $fileName;

    public function __construct() {

        $this->pluginUrl = plugin_dir_url( __FILE__ );
        $this->pluginPath = dirname(__FILE__);
        $this->fileName = basename( __FILE__ );

        add_shortcode('album_review', array($this, 'shortcode'));
        add_action('init', array($this, 'taxonomies'));
        add_action('init', array($this, 'reviewPostType'));
        add_action('save_post', array($this, 'saveMeta'), 1, 2);
        add_action('manage_album_review_posts_custom_column' , array($this, 'changeColumnValues') , 10, 2);
        add_filter('enter_title_here', array($this, 'changeTitlePlaceholder'), 20 , 2 );
        add_filter( 'admin_post_thumbnail_html', array($this, 'changeFeaturedImageText'));
        add_filter('manage_album_review_posts_columns', array($this, 'changeColumns'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
        
    }

    public function enqueueScripts() {

        wp_register_style('bhongmv-plugin-style', $this->pluginUrl . 'style.css');
        wp_enqueue_style('bhongmv-plugin-style');

    }

    public function taxonomies() {

        $labels = array(
            'name'                       => _x('Album Review Genres', 'taxonomy general name', 'textdomain'),
            'singular_name'              => _x('Album Review Genre', 'taxonomy singular name', 'textdomain'),
            'search_items'               => __('Search Album Review Genres', 'textdomain'),
            'popular_items'              => __('Popular Album Reviews Genres', 'textdomain'),
            'all_items'                  => __('All Album Reviews Genres', 'textdomain'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Edit Album Review Genre', 'textdomain'),
            'update_item'                => __('Update Album Review Genre', 'textdomain'),
            'add_new_item'               => __('Add New Album Review Genre', 'textdomain'),
            'new_item_name'              => __('New Album Review Genre Name', 'textdomain'),
            'separate_items_with_commas' => __('Separate Album Review Genre With Commas', 'textdomain'),
            'add_or_remove_items'        => __('Add Or Remove Album Review Genres', 'textdomain'),
            'choose_from_most_used'      => __('Choose From The Most Used Album Review Genres', 'textdomain'),
            'not_found'                  => __('No Album Review Genres Found.', 'textdomain'),
            'menu_name'                  => __('Album Review Genres', 'textdomain'),
        );
    
        $args = array(
            'hierarchical'          => false,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'rewrite'               => array('slug' => 'album_review_genres'),
        );

        register_taxonomy(
            'album_review_genre', 
            array('album_review'), 
            $args
        );

    }

    public function reviewPostType() {

        $labels = array(
            'name' => __('Album Reviews', 'textdomain'),
            'singular_name' => __('Album Review', 'textdomain'),
            'menu_name' => __('Album Reviews', 'textdomain'),
            'add_new' => __('Add New Review', 'textdomain'),
            'add_new_item'  => __('Album Reviews', 'textdomain'),
            'new_item' => __('New Review', 'textdomain'),
            'edit_item' => __('Edit Review', 'textdomain'),
            'view_item' => __('View Review', 'textdomain'),
            'all_items' => __('All Reviews', 'textdomain'),
        );
        
        $supports = array(
            'title',
            'thumbnail',
        );
        
        $args = array(
            'labels'               => $labels,
            'supports'             => $supports,
            'public'               => true,
            'capability_type'      => 'page',
            'rewrite'              => array( 'slug' => 'album_review'),
            'has_archive'          => false,
            'menu_position'        => 20,
            'taxonomies' => array('album_review_genre'),
            'register_meta_box_cb' => array($this, 'addMetaboxes'),
        );
    
        register_post_type('album_review', $args);

    }

    public function addMetaboxes() {

        add_meta_box(
            'bhongmv_review_artist',
            'Artist',
            array($this, 'createArtist'),
            'album_review',
            'normal',
            'default'
        );
        
        add_meta_box(
            'bhongmv_review_rating',
            'Review Rating (1 - 5)',
            array($this, 'createRating'),
            'album_review',
            'normal',
            'default'
        );

    }

    public function createArtist() {

        global $post;
    
        wp_nonce_field($this->fileName, 'bhongmv_review_fields');
    
        $artist = get_post_meta($post->ID, 'bhongmv_review_artist_name', true );
        echo '<input type="text" name="bhongmv_review_artist_name" value="' . esc_textarea( $artist )  . '" class="widefat">';
    
    }

    public function createRating() {

        global $post;
    
        wp_nonce_field($this->fileName, 'bhongmv_review_fields');
    
        $review_rating = get_post_meta($post->ID, 'bhongmv_review_rating', true );
        
        echo '<select name="bhongmv_review_rating">';
        echo '<option value="">Select A Rating</option>';
    
        for($x = 1; $x <= 5; $x++) {
    
            if($x == $review_rating) {
    
                $selected = 'selected="selected"';
    
            } else {
    
                $selected = '';
    
            }
    
            echo '<option '.$selected.' value='.$x.'>'.$x.'</option>';
    
        }
    
        echo '</select>';

    }


    function saveMeta($post_id, $post) {

        if ( !isset( $_POST['bhongmv_review_artist_name']) || !isset( $_POST['bhongmv_review_rating']) || !wp_verify_nonce( $_POST['bhongmv_review_fields'], $this->fileName)) {
            
            return $post_id;
    
        }
        
        $review_meta['bhongmv_review_artist_name'] = esc_textarea($_POST['bhongmv_review_artist_name']);
        $review_meta['bhongmv_review_rating'] = esc_textarea($_POST['bhongmv_review_rating']);
    
        foreach ($review_meta as $key => $value) {

            if (get_post_meta($post_id, $key, false )) {
    
                update_post_meta($post_id, $key, $value);
                
            } else {
    
                add_post_meta($post_id, $key, $value);
                
            }
            
            if (!$value) {
                delete_post_meta($post_id, $key);
            }

        }
    
    }


    public function changeTitlePlaceholder($title , $post){

        if( $post->post_type == 'album_review' ) {

            $new_title = "Add New Album Name";
            return $new_title;

        }
    
        return $title;

    }


    public function changeFeaturedImageText($content) {

        return $content = str_replace( __( 'Set featured image' ), __( 'Set thumbnail of album art' ), $content);

    }


    public function changeColumns($columns) {

        $columns['bhongmv_review_artist_name'] = __('Album Artist', 'textdomain');
        $columns['bhongmv_review_rating'] = __('Ratings', 'textdomain');
        $columns['bhongmv_review_thumbnail'] = __('Album Cover', 'textdomain');
        $columns['bhongmv_review_shortcode'] = __('Short Code', 'textdomain');
    
        return $columns;

    }


    public function changeColumnValues($column, $post_id) {
    
        switch ($column) {
    
            case 'bhongmv_review_artist_name' :
                $meta_data = get_post_meta($post_id , 'bhongmv_review_artist_name' , true); 
                echo $meta_data;
                break;
    
            case 'bhongmv_review_rating' :
                $meta_data = get_post_meta($post_id , 'bhongmv_review_rating' , true); 
                echo $meta_data;
                break;
    
            case 'bhongmv_review_thumbnail' :
                echo the_post_thumbnail('thumbnail');
                break;
    
            case 'bhongmv_review_shortcode' :
                $meta_data = '[album_review id="'.$post_id.'"]';
                echo $meta_data;
                break;

        }
    
    }


    public function shortcode($atts = []) {

        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        $post_id = $atts['id'];
    
        $album_name = get_the_title($post_id);
        $artist = get_post_meta($post_id, 'bhongmv_review_artist_name', true );
        $rating = get_post_meta($post_id , 'bhongmv_review_rating' , true); 
        $thumbnail = get_the_post_thumbnail($post_id, 'thumbnail');
        $generes = get_the_terms($post_id, 'album_review_genre');
    
        $i = 1;
        $genre_list = "";
    
        foreach ($generes as $genere) {

            $term_link = get_term_link($genere, array('album_review_genre'));
    
            if( is_wp_error( $term_link ) )
            continue;
            $genre_list .= '<a href="' . $term_link . '">' . $genere->name . '</a>';
            $genre_list .= ($i < count($generes)) ? ", " : "";
            $i++;

        }
    
        $returnHTML = '<div class="albumWrapper">';
        $returnHTML .= $thumbnail;
        $returnHTML .= '<h3>'.$album_name.'</h3>';
        $returnHTML .= '<ul>';
        $returnHTML .= '<li><span class="album_label">Artist:</span> '.$artist.'</li>';
        $returnHTML .= '<li><span class="album_label">Generes:</span> '.$genre_list.'</li>';
        $returnHTML .= '<li><span class="album_label">Rating:</span> '.$rating.'</li>';
        $returnHTML .= '</ul>';
        $returnHTML .= '</div>';
    
        echo $returnHTML;
        
    }
}

$BHongMV = new BHongMV();