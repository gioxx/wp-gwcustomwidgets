<?php
/*
	Plugin Name: Custom Widgets per Gioxx's Wall
	Plugin URI: https://gioxx.org/
	Description: Widget personalizzati per la Sidebar di Gioxx's Wall.
	Author: Gioxx
	Version: 0.23
	Author URI: https://gioxx.org
	License: GPL3
*/

/*  Credits:
    https://wordpress.stackexchange.com/questions/47492/wordpress-multiple-widget-in-single-plugin
    https://www.hostinger.com/tutorials/how-to-create-custom-widget-in-wordpress
*/

defined( 'ABSPATH' ) || exit;

/*	Registro sorgente aggiornamento plugin e collegamento a pagina di dettaglio (nell'area installazione plugin di WordPress)
	  Credits: https://rudrastyh.com/wordpress/self-hosted-plugin-update.html
*/
if ( !class_exists('gwplgUpdateChecker_wdg') ) {
	class gwplgUpdateChecker_wdg{
		public $plugin_slug;
		public $version;
		public $cache_key;
		public $cache_allowed;

		public function __construct() {
			$this->plugin_slug = plugin_basename( __DIR__ );
			$this->version = '0.23';
			$this->cache_key = 'customwidgets_updater';
			$this->cache_allowed = true;

			add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
			add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
			add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );
		}

		public function request() {
			$remote = get_transient( $this->cache_key );
			if( false === $remote || ! $this->cache_allowed ) {
				$remote = wp_remote_get(
					'https://gioxx.github.io/wp-gwcustomwidgets/plg-customwidgets.json',
					array(
						'timeout' => 10,
						'headers' => array(
							'Accept' => 'application/json'
						)
					)
				);

				if(
					is_wp_error( $remote )
					|| 200 !== wp_remote_retrieve_response_code( $remote )
					|| empty( wp_remote_retrieve_body( $remote ) )
				) {
					return false;
				}

				set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );

			}
			$remote = json_decode( wp_remote_retrieve_body( $remote ) );
			return $remote;
		}


		function info( $res, $action, $args ) {
			// do nothing if you're not getting plugin information right now
			if( 'plugin_information' !== $action ) {
				return $res;
			}

			// do nothing if it is not our plugin
			if( $this->plugin_slug !== $args->slug ) {
				return $res;
			}

			// get updates
			$remote = $this->request();

			if( ! $remote ) {
				return $res;
			}

			$res = new stdClass();
			$res->name = $remote->name;
			$res->slug = $remote->slug;
			$res->version = $remote->version;
			$res->tested = $remote->tested;
			$res->requires = $remote->requires;
			$res->author = $remote->author;
			$res->author_profile = $remote->author_profile;
			$res->download_link = $remote->download_url;
			$res->trunk = $remote->download_url;
			$res->requires_php = $remote->requires_php;
			$res->last_updated = $remote->last_updated;
			$res->sections = array(
				'description' => $remote->sections->description,
				'installation' => $remote->sections->installation,
				'changelog' => $remote->sections->changelog
			);

			if( ! empty( $remote->banners ) ) {
				$res->banners = array(
					'low' => $remote->banners->low,
					'high' => $remote->banners->high
				);
			}

			return $res;
		}

		public function update( $transient ) {
			if ( empty($transient->checked ) ) {
				return $transient;
			}
			$remote = $this->request();

			if(
				$remote
				&& version_compare( $this->version, $remote->version, '<' )
				&& version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' )
				&& version_compare( $remote->requires_php, PHP_VERSION, '<' )
			) {
				$res = new stdClass();
				$res->slug = $this->plugin_slug;
				$res->plugin = plugin_basename( __FILE__ ); // example: misha-update-plugin/misha-update-plugin.php
				$res->new_version = $remote->version;
				$res->tested = $remote->tested;
				$res->package = $remote->download_url;

				$transient->response[ $res->plugin ] = $res;
	    	}
			return $transient;
		}

		public function purge( $upgrader, $options ) {
			if (
				$this->cache_allowed
				&& 'update' === $options['action']
				&& 'plugin' === $options[ 'type' ]
			) {
				// just clean the cache when new plugin version is installed
				delete_transient( $this->cache_key );
			}
		}

	}
	new gwplgUpdateChecker_wdg();
}

add_filter( 'plugin_row_meta', function( $links_array, $plugin_file_name, $plugin_data, $status ) {
	if( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
		$links_array[] = sprintf(
			'<a href="%s" class="thickbox open-plugin-details-modal">%s</a>',
			add_query_arg(
				array(
					'tab' => 'plugin-information',
					'plugin' => plugin_basename( __DIR__ ),
					'TB_iframe' => true,
					'width' => 772,
					'height' => 788
				),
				admin_url( 'plugin-install.php' )
			),
			__( 'View details' )
		);
	}
	return $links_array;
}, 25, 4 );

/*  Registro foglio di stile dei box
		Credits: https://stackoverflow.com/questions/21759642/wordpress-load-a-stylesheet-through-plugin
*/
function CustomWdgCSSLoad() {
    $plugin_url = plugin_dir_url( __FILE__ );
    wp_enqueue_style( 'CustomWdg', $plugin_url . 'css/plg_customwidgets.css' );
}
add_action( 'wp_enqueue_scripts', 'CustomWdgCSSLoad' );

/*	Registro icona personalizzata del plugin (credits: ChatGPT!)
*/
function customwdg_plugin_icon() {
    $plugin_dir = plugin_dir_url(__FILE__);
    $icon_url   = $plugin_dir . 'assets/icon-128x128.png';

    $plugin_data = get_plugin_data(__FILE__);
    $plugin_slug = sanitize_title($plugin_data['Name']);

    ?>
    <style>
        #<?php echo $plugin_slug; ?> .dashicons-admin-generic:before {
            content: "\f108";
            background-image: url(<?php echo $icon_url; ?>);
            background-repeat: no-repeat;
            background-position: center center;
            background-size: 16px;
            display: inline-block;
            vertical-align: top;
            height: 16px;
            width: 16px;
        }
    </style>
    <?php
}
add_action('admin_head-update-core.php', 'customwdg_plugin_icon');

/* Widget Events Sidebar Gioxx's Wall ========================================================================================================= */

class wdg_Covid19 extends WP_Widget {
  function __construct() {
    parent::__construct(
      // widget ID
      'gwallcustom_sidebar_covid19',
      // widget name
      __('(GX) Covid-19', ' gwallcustom_widget_covid19'),
      // widget description
      array( 'description' => __( 'Mostra il box per la copertura Covid-19', 'gwallcustom_widget_covid19' ), )
    );
  }

  public function widget( $args, $instance ) {
    if ( !empty( $instance['title'] ) ) {
      $title = apply_filters( 'widget_title', $instance['title'] );
    } else {
      $title = '';
    }
    echo $args['before_widget'];
    $cv19posts_load = $instance[ 'cv19posts_load' ];
    //if title is present
    if ( ! empty( $title ) )
    echo $args['before_title'] . $title . $args['after_title'];
    //output
    ?>

		<!-- Eventi -->
		<div class="header_widget red">
				<h2><i class="fas fa-shield-virus fa-lg" style="color: #000000;"></i> Covid-19</h2>
		</div>

		<ul class="fa-ul extlinks" style="margin-bottom: 1em;">
			<?php
			$args = array(
				'showposts'   => $cv19posts_load,
				'tag'         => 'covid-19',
				'meta_key'    => 'covid19',
				'meta_value'  => 'sidebar'
			);
				$my_query = new WP_Query($args);
				while ($my_query->have_posts()) : $my_query->the_post(); ?>
				<li><span class="fa-li"><i class="far fa-file"></i></span> <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></li>
			<?php endwhile; ?>
		</ul>
		<?php echo do_shortcode( ' [sc name="covid19"] ' ); ?>

		<?php
    echo $args['after_widget'];
  }

  public function form( $instance ) {
    if ( isset( $instance[ 'title' ] ) )
    $title = $instance[ 'title' ];
    else
    $title = __( 'Covid-19', 'gwallcustom_widget_covid19' );

    if ( isset( $instance[ 'cv19posts_load' ] ) )
    $cmnts_load = $instance[ 'cv19posts_load' ];
		else
    $cmnts_load = __( '', 'gwallcustom_widget_covid19' );
    ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <p>
      <!-- Articoli da mostrare -->
      <label for="<?php echo $this->get_field_id('cv19posts_load'); ?>"><?php _e( 'Articles to load:' ); ?></label>
      <select class='widefat' id="<?php echo $this->get_field_id('cv19posts_load'); ?>" name="<?php echo $this->get_field_name('cv19posts_load'); ?>">
        <?php
          for ($cv19posts_max=1; $cv19posts_max<=10; $cv19posts_max++) { ?>
            <option <?php selected( $instance['cv19posts_load'], $cv19posts_max); ?> value="<?php echo $cv19posts_max; ?>"><?php echo $cv19posts_max; ?></option>
        <?php } ?>
      </select>
    </p>
    <?php
  }

  public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    $instance['cv19posts_load'] = ( ! empty( $new_instance['cv19posts_load'] ) ) ? strip_tags( $new_instance['cv19posts_load'] ) : '';
    //$instance['cv19posts_load'] = $new_instance['cv19posts_load'];
    return $instance;
  }
} // Covid-19 terminato

/* Widget Prod Sidebar Gioxx's Wall ========================================================================================================= */

class wdg_Donazioni extends WP_Widget {
  function __construct() {
    parent::__construct(
      // widget ID
      'gwallcustom_sidebar_donazioni',
      // widget name
      __('(GX) Donazioni', ' gwallcustom_widget_donazioni'),
      // widget description
      array( 'description' => __( 'Mostra il box per le donazioni', 'gwallcustom_widget_donazioni' ), )
    );
  }

  public function widget( $args, $instance ) {
    if ( !empty( $instance['title'] ) ) {
      $title = apply_filters( 'widget_title', $instance['title'] );
    } else {
      $title = '';
    }
    echo $args['before_widget'];
    //if title is present
    if ( ! empty( $title ) )
    echo $args['before_title'] . $title . $args['after_title'];
    //output
    ?>

			<!-- Blocco donazioni -->
			<div class="header_widget">
				<h2><i class="fas fa-hands-helping fa-lg"></i> Sostieni il blog</h2>
			</div>

			<p>Articoli sempre nuovi e <strong>approfondimenti</strong>, <strong>soluzioni</strong>, <strong>anteprime</strong>. Gioxx's Wall ti permette di risparmiare tempo e leggere cose per te interessanti (o almeno ci prova). Hai mai pensato di dare una mano alla sopravvivenza di questo progetto? :-)
				<ul class="fa-ul gwalldonate">
          <li><span class="fa-li"><i class="fab fa-amazon"></i></span><a href="https://go.gioxx.org/acquista" target="_blank" />Amazon (fai acquisti)</a></li>
          <li><span class="fa-li"><i class="fas fa-coffee"></i></span><a href="https://ko-fi.com/gioxx" target="_blank" />Ko-fi (offrimi un caff&eacute;)</a></li>
					<li><span class="fa-li"><i class="fas fa-hand-holding-usd"></i></span><a href="https://tag.satispay.com/gioxx" target="_blank" />Satispay (dona)</a></li>
          <li><span class="fa-li"><i class="fab fa-paypal"></i></span><a href="https://paypal.me/gioxx" target="_blank" />PayPal (dona)</a></li>
				</ul>
        oppure
        <ul class="fa-ul gwalldonate">
					<li><span class="fa-li"><i class="fas fa-coffee"></i></span><a href="https://www.buymeacoffee.com/gioxx" target="_blank" />Buy Me A Coffee (offrimi un caff&eacute;)</a></li>
					<li><span class="fa-li"><i class="fab fa-patreon"></i></span><a href="https://www.patreon.com/gioxx" target="_blank" />Patreon (Sostienimi)</a></li>
				</ul>
			</p>

		<?php
    echo $args['after_widget'];
  }

  public function form( $instance ) {
    if ( isset( $instance[ 'title' ] ) )
    $title = $instance[ 'title' ];
    else
    $title = __( 'Sostieni il blog', 'gwallcustom_widget_donazioni' );
    ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <?php
  }

  public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    return $instance;
  }
} // Donazioni terminato

class wdg_BancoProva extends WP_Widget {
  function __construct() {
    parent::__construct(
      // widget ID
      'gwallcustom_sidebar_bancoprova',
      // widget name
      __('(GX) Banco Prova', ' gwallcustom_widget_bancoprova'),
      // widget description
      array( 'description' => __( 'Mostra gli articoli Banco Prova', 'gwallcustom_widget_bancoprova' ), )
    );
  }

  public function widget( $args, $instance ) {
    if ( !empty( $instance['title'] ) ) {
      $title = apply_filters( 'widget_title', $instance['title'] );
    } else {
      $title = '';
    }
    echo $args['before_widget'];
    //if title is present
    if ( ! empty( $title ) )
    echo $args['before_title'] . $title . $args['after_title'];
    //output
    ?>

      <!-- Banco Prova -->
      <div class="header_widget">
        <h2><i class="fas fa-shipping-fast fa-lg"></i> Banco prova</h2>
      </div>

      <?php
        // Credits: https://wordpress.stackexchange.com/a/103761
        //$my_query = new WP_Query('tag=banco-prova&showposts=1');
        $select_bancoprova = array('tag' => 'banco-prova, banco-prova-baby', 'showposts' => 1);
        $my_query = new WP_Query( $select_bancoprova );
        while ($my_query->have_posts()) : $my_query->the_post(); ?>
        <h3><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
        <?php the_excerpt(); ?>
      <?php endwhile; ?>

      <span class="smallcaps">Le tre prove pi&ugrave; recenti</span><br />
      <ul class="fa-ul gwallselection">
      <?php
        //$my_query = new WP_Query('tag=banco-prova&showposts=3&offset=1');
        $my_query = new WP_Query('tag=banco-prova&showposts=3');
        while ($my_query->have_posts()) : $my_query->the_post(); ?>
        <li><span class="fa-li"><i class="fas fa-shipping-fast"></i></span> <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></li>
      <?php endwhile; ?>
      </ul>

      <span class="smallcaps">Dal Banco Prova &quot;Baby&quot;</span><br />
      <ul class="fa-ul gwallselection">
      <?php
        $my_query = new WP_Query('tag=banco-prova-baby&showposts=3');
        while ($my_query->have_posts()) : $my_query->the_post(); ?>
        <li><span class="fa-li"><i class="fa-solid fa-child"></i></span> <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></li>
      <?php endwhile; ?>
      </ul>

      <p class="othernews">Qui di seguito invece ti elenco gli ultimi prodotti del &quot;<em><a href="https://gioxx.org/tag/banco-prova-lampo" />Banco Prova Lampo</a></em>&quot; d'uso quotidiano, riassunti in poche righe.</p>
      <ul class="fa-ul gwallselection">
      <?php
        $my_query = new WP_Query('tag=banco-prova-lampo&showposts=3');
        while ($my_query->have_posts()) : $my_query->the_post(); ?>
        <li><span class="fa-li"><i class="fas fa-box-open"></i></span> <a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></li>
      <?php endwhile; ?>
      </ul>

    <?php
    echo $args['after_widget'];
  }

  public function form( $instance ) {
    if ( isset( $instance[ 'title' ] ) )
    $title = $instance[ 'title' ];
    else
    $title = __( 'Banco Prova', 'gwallcustom_widget_bancoprova' );
    ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <?php
  }

  public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    return $instance;
  }
} // Banco Prova terminato

class wdg_Evidenza extends WP_Widget {
  function __construct() {
    parent::__construct(
      // widget ID
      'gwallcustom_sidebar_evidenza',
      // widget name
      __('(GX) In Evidenza', ' gwallcustom_widget_evidenza'),
      // widget description
      array( 'description' => __( 'Mostra il box per l\'articolo in evidenza', 'gwallcustom_widget_evidenza' ), )
    );
  }

  public function widget( $args, $instance ) {
    if ( !empty( $instance['title'] ) ) {
      $title = apply_filters( 'widget_title', $instance['title'] );
    } else {
      $title = '';
    }
    echo $args['before_widget'];
    //if title is present
    if ( ! empty( $title ) )
    echo $args['before_title'] . $title . $args['after_title'];
    //output
    ?>

		<!-- In Evidenza -->
		<div class="header_widget">
				<h2><i class="fas fa-crosshairs fa-lg"></i> In primo piano</h2>
		</div>

		<?php $my_query = new WP_Query('tag=in-evidenza&showposts=1');
			while ($my_query->have_posts()) : $my_query->the_post(); ?>
			<h3><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
			<?php the_excerpt(); ?>
		<?php endwhile; ?>

		<?php
    echo $args['after_widget'];
  }

  public function form( $instance ) {
    if ( isset( $instance[ 'title' ] ) )
    $title = $instance[ 'title' ];
    else
    $title = __( 'In evidenza', 'gwallcustom_widget_evidenza' );
    ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <?php
  }

  public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    return $instance;
  }
} // In Evidenza terminato

class wdg_Eventi extends WP_Widget {
  function __construct() {
    parent::__construct(
      // widget ID
      'gwallcustom_sidebar_eventi',
      // widget name
      __('(GX) Eventi', ' gwallcustom_widget_eventi'),
      // widget description
      array( 'description' => __( 'Mostra il box per l\'articolo da tenere d\'occhio', 'gwallcustom_widget_eventi' ), )
    );
  }

  public function widget( $args, $instance ) {
    if ( !empty( $instance['title'] ) ) {
      $title = apply_filters( 'widget_title', $instance['title'] );
    } else {
      $title = '';
    }
    echo $args['before_widget'];
    //if title is present
    if ( ! empty( $title ) )
    echo $args['before_title'] . $title . $args['after_title'];
    //output
    ?>

		<!-- Eventi -->
		<div class="header_widget">
				<h2><i class="fas fa-history fa-lg"></i> Da tenere d'occhio</h2>
		</div>

		<?php $my_query = new WP_Query('tag=eventi&showposts=1');
			while ($my_query->have_posts()) : $my_query->the_post(); ?>
			<h3><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
			<?php the_excerpt(); ?>
		<?php endwhile; ?>

		<?php
    echo $args['after_widget'];
  }

  public function form( $instance ) {
    if ( isset( $instance[ 'title' ] ) )
    $title = $instance[ 'title' ];
    else
    $title = __( 'Eventi', 'gwallcustom_widget_eventi' );
    ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <?php
  }

  public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    return $instance;
  }
} // Eventi terminato

class wdg_BancoProvaConsole extends WP_Widget {
  function __construct() {
    parent::__construct(
      // widget ID
      'gwallcustom_sidebar_bancoprovaconsole',
      // widget name
      __('(GX) Banco Prova Console', ' gwallcustom_sidebar_bancoprovaconsole'),
      // widget description
      array( 'description' => __( 'Mostra gli articoli Banco Prova Console', 'gwallcustom_sidebar_bancoprovaconsole' ), )
    );
  }

  public function widget( $args, $instance ) {
    if ( !empty( $instance['title'] ) ) {
      $title = apply_filters( 'widget_title', $instance['title'] );
    } else {
      $title = '';
    }
    echo $args['before_widget'];
    //if title is present
    if ( ! empty( $title ) )
    echo $args['before_title'] . $title . $args['after_title'];
    //output
    ?>

		<!-- Banco Prova: Console -->
		<div class="header_widget">
			<h2><i class="fab fa-xbox fa-lg"></i> Banco prova: Console</h2>
		</div>

		<?php $my_query = new WP_Query('tag=banco-prova-console&showposts=1');
		while ($my_query->have_posts()) : $my_query->the_post();
		// check if the post has a Post Thumbnail assigned to it.
		  if ( has_post_thumbnail() ) {
			the_post_thumbnail(array(375,175));
		  };?>
		<h3><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
		<?php
      the_excerpt();
      endwhile;
      // Blocco new gaming.gioxx.org, thanks to https://pippinsplugins.com/using-wp_remote_get-to-parse-json-from-remote-apis/
			$request = wp_safe_remote_get('https://gioxx.org/sub/gwg.json');
			if( is_wp_error( $request ) ) {
				return false; // Bail early
			}
			$body = wp_remote_retrieve_body( $request );
			$data = json_decode( $body );
			if( ! empty( $data ) ) {
        echo '<p class="othernews">Qui di seguito invece ti elenco le ultime notizie pubblicate su &quot;<em><a href="https://gaming.gioxx.org" />G:Gaming&Tech</a></em>&quot;:</p>';
				echo '<ul class="fa-ul gwallselection">';
				foreach( $data->items as $item ) {
					echo '<li><span class="fa-li"><i class="fab fa-xbox"></i></span> ';
						echo '<a href="' . esc_url( $item->link ) . '">' . $item->title . '</a>';
					echo '</li>';
				}
				echo '</ul>';
			}

    echo $args['after_widget'];
  }

  public function form( $instance ) {
    if ( isset( $instance[ 'title' ] ) )
    $title = $instance[ 'title' ];
    else
    $title = __( 'Banco Prova Console', 'gwallcustom_sidebar_bancoprovaconsole' );
    ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <?php
  }

  public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    return $instance;
  }
} // Banco Prova Console terminato

// Registrazione widget
function src_load_widgets() {
    // Eventi particolari
    register_widget( 'wdg_Covid19' );
    // Sidebar standard del blog
    register_widget( 'wdg_Donazioni' );
    register_widget( 'wdg_BancoProva' );
    register_widget( 'wdg_Evidenza' );
    register_widget( 'wdg_Eventi' );
    register_widget( 'wdg_BancoProvaConsole' );
}
add_action( 'widgets_init', 'src_load_widgets' );
