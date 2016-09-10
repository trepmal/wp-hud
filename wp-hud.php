<?php
// Plugin Name: WPHUD

if ( !defined( 'WP_CLI' ) ) return;

/**
 * WP HUD
 */
class WP_HUD extends WP_CLI_Command {

	/**
	 * Quick look at this install
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: none
	 * options:
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp hud
	 *     wp hud --format=json
	 *
	 */
	function __invoke( $args, $assoc_args ) {

		$store = array(
			'version'             => '',
			'multisite'           => is_multisite(),
			'multisite-subdomain' => '',
			'multisite-blogs'     => 0,
			'updates'             => array(),
			'plugins'             => array(),
			'themes'              => array(),
			'dropins'             => array(),
			'users'               => array(),
			'content'             => array(),
		);

		global $wp_version;
		$store['version'] = $wp_version;

		// install type
		if ( is_multisite() ) {
			if ( defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL ) {
				$store['multisite-subdomain'] = SUBDOMAIN_INSTALL;
			}
			$site_count = get_site_option( 'blog_count' );
			$store['multisite-blogs'] = $site_count;
		} else {
			unset( $store['multisite-subdomain'] );
			unset( $store['multisite-blogs'] );
		}

		// updates
		$update_data = $this->wp_get_update_data();
		$store['updates'] = $update_data;

		// plugins
		$plugins = count( get_plugins() );
		$store['plugins']['installed'] = $plugins;

		$plugins = count( get_option( 'active_plugins', array() ) );
		$store['plugins']['active'] = $plugins;

		$plugins = count( get_mu_plugins() );
		$store['plugins']['mu'] = $plugins;

		// dropins
		$dropins = array_keys( get_dropins() );
		$store['dropins']['list']      = $dropins;
		$store['dropins']['installed'] = count( $store['dropins']['list'] );

		// themes
		$themes = count( wp_get_themes() );
		$store['themes']['installed'] = $themes;

		$themes = wp_get_theme();
		$store['themes']['active'] = $themes['Name'];

		// users
		$users = count_users();
		$store['users'] = $users;
		$store['users']['roles_list'] = array_keys( get_editable_roles() );
		$store['users']['roles']      = count( $store['users']['roles_list'] );

		if ( ! is_multisite() ) {

			// content
			$post_types = get_post_types( array( 'public' => true ), OBJECT );
			$store['content']['post_types'] = count( $post_types );
			$published = array();
			foreach ( $post_types as $cpt ) {
				$slug = $cpt->name;
				$cpts = wp_count_posts( $slug );
				$published[] = $cpts->publish;
			}
			$store['content']['published'] = array_sum( $published );

		}

		if ( isset( $assoc_args['format'] ) ) { switch ( $assoc_args['format'] ) {
			case 'json' :

				WP_CLI::line( json_encode( $store ) );
				return;

			break;
		} }

		$this->human_print( $store );

	}

	private function human_print( $store ) {

		$this->bar( '~', array('w' => 20, 'text' => 'Install') );

		WP_CLI::line( "Version: {$store['version']}" );

		if ( $store['multisite'] ) {
			if ( $store['multisite-subdomain'] ) {
				WP_CLI::line( 'Multisite with subdomains' );
			} elseif ( $store['multisite'] ) {
				WP_CLI::line( 'Multisite with subdirectories' );
			}
			WP_CLI::line( "{$store['multisite-blogs']} sites on network" );
		} else {
			WP_CLI::line( 'Single-site' );
		}

		$this->bar( '~', array( 'w' => 20, 'text' => 'Updates' ) );

		if ( 0 == array_sum( $store['updates'] ) ) {
			WP_CLI::line( 'No pending updates.' );
		} else {
			$counts = $store['updates'];
			if ( $counts['wordpress'] ) {
				WP_CLI::line( sprintf( __( '%d WordPress Update'), $counts['wordpress'] ) );
			}
			if ( $counts['plugins'] ) {
				WP_CLI::line( sprintf( _n( '%d Plugin Update', '%d Plugin Updates', $counts['plugins'] ), $counts['plugins'] ) );
			}
			if ( $counts['themes'] ) {
				WP_CLI::line( sprintf( _n( '%d Theme Update', '%d Theme Updates', $counts['themes'] ), $counts['themes'] ) );
			}
			if ( $counts['translations'] ) {
				WP_CLI::line( __( 'Translation Updates' ) );
			}
		}

		$this->bar( '~', array( 'w' => 20, 'text' => 'Plugins' ) );

		WP_CLI::line( "{$store['plugins']['installed']} installed plugins" );
		WP_CLI::line( "{$store['plugins']['active']} active plugins" );
		WP_CLI::line( "{$store['plugins']['mu']} mu-plugins" );

		$this->bar( '~', array( 'w' => 20, 'text' => 'Dropins' ) );

		WP_CLI::line( "{$store['dropins']['installed']} drop-ins" );

		$this->bar( '~', array( 'w' => 20, 'text' => 'Themes' ) );

		WP_CLI::line( "{$store['themes']['installed']} installed themes" );
		WP_CLI::line( "Active theme: {$store['themes']['active']}" );

		$this->bar( '~', array( 'w' => 20, 'text' => 'Users' ) );

		WP_CLI::line( "{$store['users']['total_users']} users" );
		WP_CLI::line( "{$store['users']['roles']} roles" );

		foreach ( $store['users']['avail_roles'] as $role => $count ) {
			WP_CLI::line( "$count users in $role" );
		}

		if ( ! is_multisite() ) {

			$this->bar( '~', array( 'w' => 20, 'text' => 'Content' ) );

			WP_CLI::line( "{$store['content']['post_types']} public post types" );
			WP_CLI::line( "{$store['content']['published']} published items" );

		}

	}

	/**
	 * Create a bar that spans with width of the console
	 *
	 * @param array $args Only expects a zero-indexed value, the character to build the bar with
	 * @param array $assoc_args
	 *               string  'c'    Color value. Default %p
	 *               integer 'w'    Width percentage, 0-100. Default 100
	 *               string  'text' Message to show in bar
	 */
	private function bar( $args = array(), $assoc_args = array() ) {
		$char = isset( $args[0] ) ? $args[0] : '=';
		$cols = \cli\Shell::columns();
		if ( isset( $assoc_args['w'] ) ) {
			$cols = floor( $cols*($assoc_args['w']/100) );
		}
		$line = substr( str_repeat($char, $cols), 0, $cols );


		if ( isset( $assoc_args['text'] ) ) {
			$text = "$char$char$char {$assoc_args['text']} ";
			$len = strlen( $text );
			$line = $text . substr( $line, $len );
		}

		if ( ! isset( $assoc_args['c'] ) ) {
			$color = '%p'; // https://github.com/jlogsdon/php-cli-tools/blob/master/lib/cli/Colors.php#L113
		} else {
			$color = $assoc_args['c'];
			$color = '%'. trim( $color, '%' );
		}

		WP_CLI::line( WP_CLI::colorize( $color . $line .'%n' ) );
	}


	/**
	 * Collect counts for available updates
	 *
	 * @return string
	 */
	private function wp_get_update_data() {

		$counts = array( 'plugins' => 0, 'themes' => 0, 'wordpress' => 0, 'translations' => 0 );

		$update_plugins = get_site_transient( 'update_plugins' );
		if ( ! empty( $update_plugins->response ) ) {
			$counts['plugins'] = count( $update_plugins->response );
		}
		$update_themes = get_site_transient( 'update_themes' );
		if ( ! empty( $update_themes->response ) ) {
			$counts['themes'] = count( $update_themes->response );
		}
		$update_wordpress = get_core_updates( array('dismissed' => false) );
		if ( ! empty( $update_wordpress ) && ! in_array( $update_wordpress[0]->response, array('development', 'latest') )) {
			$counts['wordpress'] = 1;
		}
		if ( wp_get_translation_updates() ) {
			$counts['translations'] = 1;
		}

		return $counts;

	}

}

WP_CLI::add_command( 'hud', 'WP_HUD' );
