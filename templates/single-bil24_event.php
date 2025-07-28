<?php
/**
 * Single Bil24 Event Template
 * 
 * This template displays a single Bil24 event.
 * 
 * @package Bil24Connector
 * @since 0.1.0
 */

get_header(); ?>

<div class="bil24-single-event-wrapper">
    <?php if ( have_posts() ) : ?>
        <?php while ( have_posts() ) : the_post(); ?>
            
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'bil24-single-event-article' ); ?>>
                
                <?php
                // Use EventDisplay shortcode to render the event
                if ( shortcode_exists( 'bil24_event' ) ) {
                    // Render single event with all components
                    echo do_shortcode( '[bil24_event id="' . get_the_ID() . '" show_sessions="true" show_booking="true" show_description="true"]' );
                } else {
                    // Fallback if EventDisplay is not available
                    ?>
                    <div class="bil24-event-fallback">
                        <header class="bil24-event-header">
                            <h1 class="bil24-event-title"><?php the_title(); ?></h1>
                            
                            <?php if ( has_post_thumbnail() ) : ?>
                                <div class="bil24-event-image">
                                    <?php the_post_thumbnail( 'large' ); ?>
                                </div>
                            <?php endif; ?>
                        </header>
                        
                        <div class="bil24-event-content">
                            <?php the_content(); ?>
                        </div>
                        
                        <div class="bil24-event-meta">
                            <?php
                            $start_date = get_post_meta( get_the_ID(), '_bil24_start_date', true );
                            $venue = get_post_meta( get_the_ID(), '_bil24_venue', true );
                            $price = get_post_meta( get_the_ID(), '_bil24_price', true );
                            
                            if ( $start_date ) :
                                ?>
                                <div class="bil24-meta-item">
                                    <span class="bil24-meta-label"><?php _e( 'Date:', 'bil24' ); ?></span>
                                    <span class="bil24-meta-value"><?php echo esc_html( date( 'F j, Y', strtotime( $start_date ) ) ); ?></span>
                                </div>
                                <?php
                            endif;
                            
                            if ( $venue ) :
                                ?>
                                <div class="bil24-meta-item">
                                    <span class="bil24-meta-label"><?php _e( 'Venue:', 'bil24' ); ?></span>
                                    <span class="bil24-meta-value"><?php echo esc_html( $venue ); ?></span>
                                </div>
                                <?php
                            endif;
                            
                            if ( $price ) :
                                ?>
                                <div class="bil24-meta-item">
                                    <span class="bil24-meta-label"><?php _e( 'Price:', 'bil24' ); ?></span>
                                    <span class="bil24-meta-value">
                                        <?php 
                                        if ( function_exists( 'wc_price' ) ) {
                                            echo wc_price( $price );
                                        } else {
                                            echo esc_html( $price ) . ' ₽';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <?php
                            endif;
                            ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
                
            </article><!-- #post-<?php the_ID(); ?> -->
            
        <?php endwhile; ?>
    <?php else : ?>
        
        <div class="bil24-no-event">
            <h1><?php _e( 'Event Not Found', 'bil24' ); ?></h1>
            <p><?php _e( 'Sorry, the event you are looking for could not be found.', 'bil24' ); ?></p>
            <a href="<?php echo home_url(); ?>" class="bil24-btn bil24-btn-primary">
                <?php _e( 'Back to Homepage', 'bil24' ); ?>
            </a>
        </div>
        
    <?php endif; ?>
</div>

<?php get_footer(); ?> 