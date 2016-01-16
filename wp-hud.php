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
	 * ## EXAMPLES
	 *
	 *     wp hud
	 *
	 */
	function __invoke( $args, $assoc_args ) {

		$this->bar( '~', array('w' => 20, 'text' => 'Install') );

		global $wp_version;
		WP_CLI::line( "Version: $wp_version" );

		#install type
		if ( is_multisite() ) {
			if ( defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL ) {
				WP_CLI::line('Multisite with subdomains');
			} else if ( defined( 'SUBDOMAIN_INSTALL' ) && ! SUBDOMAIN_INSTALL ) {
				WP_CLI::line('Multisite with subdirectories');
			}
			$site_count = get_site_option( 'blog_count' );
			WP_CLI::line( "$site_count sites on network" );

			## HOW MANY SITES?
		} else {
			WP_CLI::line('Single-site');
		}

		$this->bar( '~', array('w' => 20, 'text' => 'Updates') );

		#updates
		$update_data = $this->wp_get_update_data();
		if ( $update_data ) {
			WP_CLI::line( $update_data );
		} else {
			WP_CLI::line( "No pending updates." );
		}

		$this->bar( '~', array('w' => 20, 'text' => 'Plugins') );

		#plugins
		$plugins = count( get_plugins() );
		WP_CLI::line( "$plugins installed plugins" );

		$plugins = count( get_option('active_plugins', array() ) );
		WP_CLI::line( "$plugins active plugins" );

		$plugins = count( get_mu_plugins() );
		WP_CLI::line( "$plugins mu-plugins" );

		$this->bar( '~', array('w' => 20, 'text' => 'Dropins') );

		#dropins
		$dropins = array_keys( get_dropins() );
		$dropins_count = count( $dropins );
		WP_CLI::line( "$dropins_count drop-ins" );
		foreach( $dropins as $di ) {
			WP_CLI::line( $di );
		}

		$this->bar( '~', array('w' => 20, 'text' => 'Themes') );

		#themes
		$themes = count( wp_get_themes() );
		WP_CLI::line( "$themes installed themes" );

		$themes = wp_get_theme();
		WP_CLI::line( 'Active theme: ' . $themes['Name'] );

		$this->bar( '~', array('w' => 20, 'text' => 'Users') );

		#users
		$users = count_users();
		WP_CLI::line( "{$users['total_users']} users" );
		$total = count( $users['avail_roles'] );
		WP_CLI::line( "$total roles" );
		foreach( $users['avail_roles'] as $role => $count ) {
			WP_CLI::line( "$count users in $role" );
		}

		$this->bar( '~', array('w' => 20, 'text' => 'Content') );

		# content
		$post_types = get_post_types( array( 'public' => true ), OBJECT );
		foreach ( $post_types as $cpt ) {
			$slug = $cpt->name;
			$cpts = wp_count_posts( $slug );
			$total = array_sum( (array) $cpts );
			WP_CLI::line( "$total {$cpt->labels->name} ($cpts->publish published)" );
		}
	}

	/**
	 * Create a bar that spans with width of the console
	 *
	 * ## OPTIONS
	 *
	 * [<character>]
	 * : The character(s) to make the bar with. Default =
	 *
	 * [--c=<c>]
	 * : Color for bar. Default %p
	 *
	 * [--w=80]
	 * : Width percentage for bar. Default 100
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     wp hud bar
	 *
	 *     wp hud bar '-~' --color='%r'
	 *
	 *     wp hud bar '+-' --c='%r%3'
	 */
	function bar( $args = array(), $assoc_args = array() ) {
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
	 * Collect counts and UI strings for available updates
	 * Similar to core's function, capability checks removed
	 *
	 * @return string
	 */
	private function wp_get_update_data() {

		$counts = array( 'plugins' => 0, 'themes' => 0, 'wordpress' => 0, 'translations' => 0 );

		$update_plugins = get_site_transient( 'update_plugins' );
		if ( ! empty( $update_plugins->response ) )
			$counts['plugins'] = count( $update_plugins->response );
		$update_themes = get_site_transient( 'update_themes' );
		if ( ! empty( $update_themes->response ) )
			$counts['themes'] = count( $update_themes->response );
		$update_wordpress = get_core_updates( array('dismissed' => false) );
		if ( ! empty( $update_wordpress ) && ! in_array( $update_wordpress[0]->response, array('development', 'latest') ))
			$counts['wordpress'] = 1;
     	if ( wp_get_translation_updates() )
 			$counts['translations'] = 1;

		$titles = array();
		if ( $counts['wordpress'] )
			$titles['wordpress'] = sprintf( __( '%d WordPress Update'), $counts['wordpress'] );
		if ( $counts['plugins'] )
			$titles['plugins'] = sprintf( _n( '%d Plugin Update', '%d Plugin Updates', $counts['plugins'] ), $counts['plugins'] );
		if ( $counts['themes'] )
			$titles['themes'] = sprintf( _n( '%d Theme Update', '%d Theme Updates', $counts['themes'] ), $counts['themes'] );
		if ( $counts['translations'] )
			$titles['translations'] = __( 'Translation Updates' );

		$update_title = $titles ? esc_attr( implode( "\n", $titles ) ) : '';

		return $update_title;

	}

}

WP_CLI::add_command( 'hud', 'WP_HUD' );
