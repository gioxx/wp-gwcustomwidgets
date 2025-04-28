<?php
/**
 * Plugin Name:         Custom Widgets per Gioxx's Wall
 * Plugin URI:          https://gioxx.org/
 * Description:         Widget personalizzati per la Sidebar di Gioxx's Wall, ora aggiornabili tramite Git Updater.
 * Version:             0.26
 * Author:              Gioxx
 * Author URI:          https://gioxx.org
 * License:             GPL3
 * Text Domain:         wp-gwcustomwidgets
 *
 * GitHub Plugin URI:   gioxx/wp-gwcustomwidgets
 * Primary Branch:      main
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'GWCustomWidgets' ) ) {
    final class GWCustomWidgets {
        public function __construct() {
            add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
            add_action( 'widgets_init', array( $this, 'register_widgets' ) );
        }

        public function load_textdomain() {
            load_plugin_textdomain(
                'wp-gwcustomwidgets',
                false,
                dirname( plugin_basename( __FILE__ ) ) . '/languages/'
            );
        }

        public function enqueue_styles() {
            wp_enqueue_style(
                'gwcustomwidgets-styles',
                plugin_dir_url( __FILE__ ) . 'css/plg_customwidgets.css',
                array(),
                '0.26'
            );
        }

        public function register_widgets() {
            register_widget( 'GWCustomWidgets_Widget_Covid19' );
            register_widget( 'GWCustomWidgets_Widget_Donazioni' );
            register_widget( 'GWCustomWidgets_Widget_BancoProva' );
            register_widget( 'GWCustomWidgets_Widget_Evidenza' );
            register_widget( 'GWCustomWidgets_Widget_Eventi' );
            register_widget( 'GWCustomWidgets_Widget_BancoProvaConsole' );
        }
    }

    new GWCustomWidgets();
}

/**
 * Covid-19 Widget
 */
class GWCustomWidgets_Widget_Covid19 extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'gwcustomwidgets_sidebar_covid19',
            __( '(GX) Covid-19', 'wp-gwcustomwidgets' ),
            array( 'description' => __( 'Mostra il box per la copertura Covid-19', 'wp-gwcustomwidgets' ) )
        );
    }

    public function widget( $args, $instance ) {
        $title         = ! empty( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
        $posts_to_show = ! empty( $instance['cv19posts_load'] ) ? intval( $instance['cv19posts_load'] ) : 5;

        echo $args['before_widget'];
        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }
        ?>
        <div class="header_widget red">
            <h2>
                <i class="fas fa-shield-virus fa-lg"></i>
                <?php esc_html_e( 'Covid-19', 'wp-gwcustomwidgets' ); ?>
            </h2>
        </div>
        <ul class="fa-ul extlinks" style="margin-bottom: 1em;">
            <?php
            $query = new WP_Query( array(
                'tag'            => 'covid-19',
                'meta_key'       => 'covid19',
                'meta_value'     => 'sidebar',
                'posts_per_page' => $posts_to_show,
            ) );
            if ( $query->have_posts() ) :
                while ( $query->have_posts() ) :
                    $query->the_post();
                    ?>
                    <li>
                        <span class="fa-li"><i class="far fa-file"></i></span>
                        <a href="<?php echo esc_url( get_permalink() ); ?>"
                           title="<?php echo esc_attr( get_the_title() ); ?>">
                            <?php echo esc_html( get_the_title() ); ?>
                        </a>
                    </li>
                    <?php
                endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </ul>
        <?php echo do_shortcode( '[sc name="covid19"]' ); ?>

        <?php
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title         = isset( $instance['title'] ) ? $instance['title'] : __( 'Covid-19', 'wp-gwcustomwidgets' );
        $posts_to_show = isset( $instance['cv19posts_load'] ) ? intval( $instance['cv19posts_load'] ) : 5;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'wp-gwcustomwidgets' ); ?>
            </label>
            <input class="widefat"
                   id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'cv19posts_load' ) ); ?>">
                <?php esc_html_e( 'Articles to load:', 'wp-gwcustomwidgets' ); ?>
            </label>
            <select class="widefat"
                    id="<?php echo esc_attr( $this->get_field_id( 'cv19posts_load' ) ); ?>"
                    name="<?php echo esc_attr( $this->get_field_name( 'cv19posts_load' ) ); ?>">
                <?php for ( $i = 1; $i <= 10; $i++ ) : ?>
                    <option
                        value="<?php echo esc_attr( $i ); ?>"
                        <?php selected( $posts_to_show, $i ); ?>>
                        <?php echo esc_html( $i ); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title']           = sanitize_text_field( $new_instance['title'] );
        $instance['cv19posts_load']  = intval( $new_instance['cv19posts_load'] );
        return $instance;
    }
}

/**
 * Donazioni Widget
 */
class GWCustomWidgets_Widget_Donazioni extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'gwcustomwidgets_sidebar_donazioni',
            __( '(GX) Donazioni', 'wp-gwcustomwidgets' ),
            array( 'description' => __( 'Mostra il box per le donazioni', 'wp-gwcustomwidgets' ) )
        );
    }

    public function widget( $args, $instance ) {
        $title = ! empty( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';

        echo $args['before_widget'];
        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }
        ?>
        <div class="header_widget">
            <h2>
                <i class="fas fa-hands-helping fa-lg"></i>
                <?php esc_html_e( 'Sostieni il blog', 'wp-gwcustomwidgets' ); ?>
            </h2>
        </div>
        <p><?php esc_html_e( 'Articoli sempre nuovi e approfondimenti, soluzioni, anteprime. Gioxx\'s Wall ti permette di risparmiare tempo e leggere cose per te interessanti.', 'wp-gwcustomwidgets' ); ?></p>
        <ul class="fa-ul gwalldonate">
            <li>
                <span class="fa-li"><i class="fab fa-amazon"></i></span>
                <a href="https://go.gioxx.org/acquista" target="_blank">
                    <?php esc_html_e( 'Amazon (fai acquisti)', 'wp-gwcustomwidgets' ); ?>
                </a>
            </li>
            <li>
                <span class="fa-li"><i class="fas fa-coffee"></i></span>
                <a href="https://ko-fi.com/gioxx" target="_blank">
                    <?php esc_html_e( 'Ko-fi (offrimi un caffè)', 'wp-gwcustomwidgets' ); ?>
                </a>
            </li>
            <li>
                <span class="fa-li"><i class="fas fa-hand-holding-usd"></i></span>
                <a href="https://tag.satispay.com/gioxx" target="_blank">
                    <?php esc_html_e( 'Satispay (dona)', 'wp-gwcustomwidgets' ); ?>
                </a>
            </li>
            <li>
                <span class="fa-li"><i class="fab fa-paypal"></i></span>
                <a href="https://paypal.me/gioxx" target="_blank">
                    <?php esc_html_e( 'PayPal (dona)', 'wp-gwcustomwidgets' ); ?>
                </a>
            </li>
        </ul>
        <p><?php esc_html_e( 'Oppure:', 'wp-gwcustomwidgets' ); ?></p>
        <ul class="fa-ul gwalldonate">
            <li>
                <span class="fa-li"><i class="fas fa-coffee"></i></span>
                <a href="https://www.buymeacoffee.com/gioxx" target="_blank">
                    <?php esc_html_e( 'Buy Me A Coffee (offrimi un caffè)', 'wp-gwcustomwidgets' ); ?>
                </a>
            </li>
            <li>
                <span class="fa-li"><i class="fab fa-patreon"></i></span>
                <a href="https://www.patreon.com/gioxx" target="_blank">
                    <?php esc_html_e( 'Patreon (Sostienimi)', 'wp-gwcustomwidgets' ); ?>
                </a>
            </li>
        </ul>
        <p style="font-size:0.7em;">
            <?php esc_html_e( 'In qualità di Affiliato Amazon ricevo un guadagno dagli acquisti idonei senza alcun costo aggiuntivo per te.', 'wp-gwcustomwidgets' ); ?>
        </p>
        <?php
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = isset( $instance['title'] ) ? $instance['title'] : __( 'Sostieni il blog', 'wp-gwcustomwidgets' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'wp-gwcustomwidgets' ); ?>
            </label>
            <input class="widefat"
                   id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance           = array();
        $instance['title']  = sanitize_text_field( $new_instance['title'] );
        return $instance;
    }
}

/**
 * Banco Prova Widget
 */
class GWCustomWidgets_Widget_BancoProva extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'gwcustomwidgets_sidebar_bancoprova',
            __( '(GX) Banco Prova', 'wp-gwcustomwidgets' ),
            array( 'description' => __( 'Mostra gli articoli Banco Prova', 'wp-gwcustomwidgets' ) )
        );
    }

    public function widget( $args, $instance ) {
        $title = ! empty( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';

        echo $args['before_widget'];
        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }
        ?>
        <div class="header_widget">
            <h2><i class="fas fa-shipping-fast fa-lg"></i> <?php esc_html_e( 'Banco prova', 'wp-gwcustomwidgets' ); ?></h2>
        </div>
        <?php
        $recent_args = array(
            'tag'            => 'banco-prova,banco-prova-baby',
            'posts_per_page' => 1,
        );
        $recent_query = new WP_Query( $recent_args );
        if ( $recent_query->have_posts() ) :
            while ( $recent_query->have_posts() ) :
                $recent_query->the_post(); ?>
                <h3><a href="<?php echo esc_url( get_permalink() ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>"><?php echo esc_html( get_the_title() ); ?></a></h3>
                <?php the_excerpt(); ?>
            <?php endwhile;
            wp_reset_postdata();
        endif;
        ?>
        <span class="smallcaps"><?php esc_html_e( 'Le tre prove più recenti', 'wp-gwcustomwidgets' ); ?></span><br />
        <ul class="fa-ul gwallselection">
            <?php
            $list_args = array(
                'tag'            => 'banco-prova',
                'posts_per_page' => 3,
            );
            $list_query = new WP_Query( $list_args );
            if ( $list_query->have_posts() ) :
                while ( $list_query->have_posts() ) :
                    $list_query->the_post(); ?>
                    <li><span class="fa-li"><i class="fas fa-shipping-fast"></i></span> <a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a></li>
                <?php endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </ul>
        <span class="smallcaps"><?php esc_html_e( 'Dal Banco Prova "Baby"', 'wp-gwcustomwidgets' ); ?></span><br />
        <ul class="fa-ul gwallselection">
            <?php
            $baby_args = array(
                'tag'            => 'banco-prova-baby',
                'posts_per_page' => 3,
            );
            $baby_query = new WP_Query( $baby_args );
            if ( $baby_query->have_posts() ) :
                while ( $baby_query->have_posts() ) :
                    $baby_query->the_post(); ?>
                    <li><span class="fa-li"><i class="fa-solid fa-child"></i></span> <a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a></li>
                <?php endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </ul>
        <p class="othernews"><?php esc_html_e( 'Qui di seguito invece ti elenco gli ultimi prodotti del "Banco Prova Lampo" d\'uso quotidiano, riassunti in poche righe.', 'wp-gwcustomwidgets' ); ?></p>
        <ul class="fa-ul gwallselection">
            <?php
            $lampo_args = array(
                'tag'            => 'banco-prova-lampo',
                'posts_per_page' => 3,
            );
            $lampo_query = new WP_Query( $lampo_args );
            if ( $lampo_query->have_posts() ) :
                while ( $lampo_query->have_posts() ) :
                    $lampo_query->the_post(); ?>
                    <li><span class="fa-li"><i class="fas fa-box-open"></i></span> <a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a></li>
                <?php endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </ul>
        <?php
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = isset( $instance['title'] ) ? $instance['title'] : __( 'Banco Prova', 'wp-gwcustomwidgets' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'wp-gwcustomwidgets' ); ?>
            </label>
            <input class="widefat"
                   id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        return $instance;
    }
}

/**
 * In Evidenza Widget
 */
class GWCustomWidgets_Widget_Evidenza extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'gwcustomwidgets_sidebar_evidenza',
            __( '(GX) In Evidenza', 'wp-gwcustomwidgets' ),
            array( 'description' => __( 'Mostra il box per l\'articolo in evidenza', 'wp-gwcustomwidgets' ) )
        );
    }

    public function widget( $args, $instance ) {
        $title = ! empty( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';

        echo $args['before_widget'];
        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }
        ?>
        <div class="header_widget">
            <h2><i class="fas fa-crosshairs fa-lg"></i> <?php esc_html_e( 'In primo piano', 'wp-gwcustomwidgets' ); ?></h2>
        </div>
        <?php
        $query = new WP_Query( array( 'tag' => 'in-evidenza', 'posts_per_page' => 1 ) );
        if ( $query->have_posts() ) :
            while ( $query->have_posts() ) :
                $query->the_post(); ?>
                <h3><a href="<?php echo esc_url( get_permalink() ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>"><?php echo esc_html( get_the_title() ); ?></a></h3>
                <?php the_excerpt(); ?>
            <?php endwhile;
            wp_reset_postdata();
        endif;
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = isset( $instance['title'] ) ? $instance['title'] : __( 'In evidenza', 'wp-gwcustomwidgets' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'wp-gwcustomwidgets' ); ?>
            </label>
            <input class="widefat"
                   id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        return $instance;
    }
}

/**
 * Eventi Widget
 */
class GWCustomWidgets_Widget_Eventi extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'gwcustomwidgets_sidebar_eventi',
            __( '(GX) Eventi', 'wp-gwcustomwidgets' ),
            array( 'description' => __( 'Mostra il box per l\'articolo da tenere d\'occhio', 'wp-gwcustomwidgets' ) )
        );
    }

    public function widget( $args, $instance ) {
        $title = ! empty( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';

        echo $args['before_widget'];
        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }
        ?>
        <div class="header_widget">
            <h2><i class="fas fa-history fa-lg"></i> <?php esc_html_e( 'Da tenere d\'occhio', 'wp-gwcustomwidgets' ); ?></h2>
        </div>
        <?php
        $query = new WP_Query( array( 'tag' => 'eventi', 'posts_per_page' => 1 ) );
        if ( $query->have_posts() ) :
            while ( $query->have_posts() ) :
                $query->the_post(); ?>
                <h3><a href="<?php echo esc_url( get_permalink() ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>"><?php echo esc_html( get_the_title() ); ?></a></h3>
                <?php the_excerpt(); ?>
            <?php endwhile;
            wp_reset_postdata();
        endif;
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = isset( $instance['title'] ) ? $instance['title'] : __( 'Eventi', 'wp-gwcustomwidgets' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'wp-gwcustomwidgets' ); ?>">
            </label>
            <input class="widefat"
                   id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        return $instance;
    }
}

/**
 * Banco Prova Console Widget
 */
class GWCustomWidgets_Widget_BancoProvaConsole extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'gwcustomwidgets_sidebar_bancoprovaconsole',
            __( '(GX) Banco Prova Console', 'wp-gwcustomwidgets' ),
            array( 'description' => __( 'Mostra gli articoli Banco Prova Console', 'wp-gwcustomwidgets' ) )
        );
    }

    public function widget( $args, $instance ) {
        $title = ! empty( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';

        echo $args['before_widget'];
        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }
        ?>
        <div class="header_widget">
            <h2><i class="fab fa-xbox fa-lg"></i> <?php esc_html_e( 'Banco prova: Console', 'wp-gwcustomwidgets' ); ?></h2>
        </div>
        <?php
        $query = new WP_Query( array( 'tag' => 'banco-prova-console', 'posts_per_page' => 1 ) );
        if ( $query->have_posts() ) :
            while ( $query->have_posts() ) :
                $query->the_post();
                if ( has_post_thumbnail() ) {
                    the_post_thumbnail( array( 375, 175 ) );
                }
                ?>
                <h3><a href="<?php echo esc_url( get_permalink() ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>"><?php echo esc_html( get_the_title() ); ?></a></h3>
                <?php the_excerpt(); ?>
            <?php endwhile;
            wp_reset_postdata();
        endif;
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = isset( $instance['title'] ) ? $instance['title'] : __( 'Banco Prova Console', 'wp-gwcustomwidgets' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'wp-gwcustomwidgets' ); ?>
            </label>
            <input class="widefat"
                   id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        return $instance;
    }
}
