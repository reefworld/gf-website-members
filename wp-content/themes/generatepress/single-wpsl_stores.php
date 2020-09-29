<?php
/**
 * Example of a single WPSL store template for the Twenty Fifteen theme.
 * 
 * @package GeneratePress
 */

get_header(); ?>

    <div id="primary" class="content-area">
        <main id="main" class="site-main" role="main">
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

                <div class="entry-content">
                <?php
                    global $post;

                    $queried_object = get_queried_object();
            
                    // Add the content
                    $post = get_post( $queried_object->ID );
                    setup_postdata( $post );
                    the_content();
                    wp_reset_postdata( $post );
                    
                    // Add the address shortcode
                    echo do_shortcode( '[wpsl_address]' );

                    // Add the map shortcode
                    echo do_shortcode( '[wpsl_map zoom="16"]' );                    
                ?>
                </div>
            </article>
        </main><!-- #main -->
    </div><!-- #primary -->

<?php get_footer(); ?>