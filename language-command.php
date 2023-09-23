<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}
$wpcli_languageoffline_init = function () {
	if ( ! defined( 'WP_CONTENT_DIR' ) ) {

		class dummydb {
		
		}
		
		global $wpdb;
		$wpdb = new dummydb();

		define( 'WPINC', 'wp-includes' );
		define('WPLANG', 'de_DE');
		require_once APSPATH . WPINC . '/load.php';
		require_once APSPATH . WPINC . '/functions.php';
		require_once APSPATH . WPINC . '/default-constants.php';
		wp_initial_constants();
		define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
		define( 'WPMU_PLUGIN_DIR', WP_CONTENT_DIR . '/mu-plugins' );

		function __( $text, $domain = 'default' ) {
			return $text;
		}
		require_once APSPATH . WPINC . '/version.php';
		require_once APSPATH . WPINC . '/plugin.php';
		require_once APSPATH . WPINC . '/general-template.php';
		require_once APSPATH . WPINC . '/cache.php';
		wp_cache_init();
		require_once APSPATH . WPINC . '/class-wp-error.php';
		require_once APSPATH . WPINC . '/class-wp-http.php';
		require_once APSPATH . WPINC . '/class-wp-http-curl.php';
		require_once APSPATH . WPINC . '/class-wp-http-requests-hooks.php';
		require_once APSPATH . WPINC . '/class-wp-http-proxy.php';
		require_once APSPATH . WPINC . '/class-wp-http-response.php';
		require_once APSPATH . WPINC . '/class-wp-http-requests-response.php';
		require_once APSPATH . WPINC . '/http.php';
		require_once APSPATH . WPINC . '/formatting.php';
		require_once APSPATH . WPINC . '/pluggable.php';
		require_once APSPATH . 'wp-admin/includes/file.php';
		require_once APSPATH . 'wp-admin/includes/plugin.php';
		require_once APSPATH . WPINC . '/theme.php';
		require_once APSPATH . WPINC . '/update.php';
		require_once APSPATH . WPINC . '/kses.php';
		wp_set_lang_dir();

		runkit7_function_redefine('get_option', function ( $option, $default_value = false ) {
			switch ($option) {
				case 'siteurl': return 'wpoffline';
				case 'upload_path': return '';
				case 'upload_url_path': return '';
				case 'uploads_use_yearmonth_folders': return 1;
				case 'blog_charset': return 'UTF-8';
				case '_site_transient_update_core': return false;
				case '_site_transient_update_plugins': return false;
				case 'initial_db_version': return -1;
				case 'active_plugins': return custom_get_plugins();
				case 'timezone_string': return 'Europe/Berlin';
				case 'home': return 'wpoffline';
			}
			WP_CLI::warning("get_option " . $option . ' default ' . $default_value);
			undefined();
		});

		runkit7_function_redefine('delete_option', function ( $option ) {
			WP_CLI::debug("delete_option " . $option);
		});

		runkit7_function_redefine('add_option', function ( $option, $value = '', $deprecated = '', $autoload = 'yes' ) {
			WP_CLI::debug("add_option " . $option);
		});
		define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );

		function wp_get_pomo_file_data( $po_file ) {
			$headers = get_file_data(
				$po_file,
				array(
					'POT-Creation-Date'  => '"POT-Creation-Date',
					'PO-Revision-Date'   => '"PO-Revision-Date',
					'Project-Id-Version' => '"Project-Id-Version',
					'X-Generator'        => '"X-Generator',
				)
			);
			foreach ( $headers as $header => $value ) {
				// Remove possible contextual '\n' and closing double quote.
				$headers[ $header ] = preg_replace( '~(\\\n)?"$~', '', $value );
			}
			return $headers;
		}

		// Load WP-CLI utilities
		require WP_CLI_ROOT . '/php/utils-wp.php';

		function get_locale() {
			return WPLANG;
		}

		function home_url() {
			return "wpoffline";
		}

		function get_user_count() {
			return -1;
		}

		function wp_next_scheduled() {}

		function set_url_scheme( $url, $scheme = null ) {
			$orig_scheme = $scheme;
		
			if ( ! $scheme ) {
				$scheme = is_ssl() ? 'https' : 'http';
			} elseif ( 'admin' === $scheme || 'login' === $scheme || 'login_post' === $scheme || 'rpc' === $scheme ) {
				$scheme = is_ssl() || force_ssl_admin() ? 'https' : 'http';
			} elseif ( 'http' !== $scheme && 'https' !== $scheme && 'relative' !== $scheme ) {
				$scheme = is_ssl() ? 'https' : 'http';
			}
		
			$url = trim( $url );
			if ( str_starts_with( $url, '//' ) ) {
				$url = 'http:' . $url;
			}
		
			if ( 'relative' === $scheme ) {
				$url = ltrim( preg_replace( '#^\w+://[^/]*#', '', $url ) );
				if ( '' !== $url && '/' === $url[0] ) {
					$url = '/' . ltrim( $url, "/ \t\n\r\0\x0B" );
				}
			} else {
				$url = preg_replace( '#^\w+://#', $scheme . '://', $url );
			}
		
			/**
			 * Filters the resulting URL after setting the scheme.
			 *
			 * @since 3.4.0
			 *
			 * @param string      $url         The complete URL including scheme and path.
			 * @param string      $scheme      Scheme applied to the URL. One of 'http', 'https', or 'relative'.
			 * @param string|null $orig_scheme Scheme requested for the URL. One of 'http', 'https', 'login',
			 *                                 'login_post', 'admin', 'relative', 'rest', 'rpc', or null.
			 */
			return apply_filters( 'set_url_scheme', $url, $scheme, $orig_scheme );
		}

		function wp_get_installed_translations( $type ) {
			if ( 'themes' !== $type && 'plugins' !== $type && 'core' !== $type ) {
				return array();
			}
		
			$dir = 'core' === $type ? '' : "/$type";
		
			if ( ! is_dir( WP_LANG_DIR ) ) {
				return array();
			}
		
			if ( $dir && ! is_dir( WP_LANG_DIR . $dir ) ) {
				return array();
			}
		
			$files = scandir( WP_LANG_DIR . $dir );
			if ( ! $files ) {
				return array();
			}
		
			$language_data = array();
		
			foreach ( $files as $file ) {
				if ( '.' === $file[0] || is_dir( WP_LANG_DIR . "$dir/$file" ) ) {
					continue;
				}
				if ( ! str_ends_with( $file, '.po' ) ) {
					continue;
				}
				if ( ! preg_match( '/(?:(.+)-)?([a-z]{2,3}(?:_[A-Z]{2})?(?:_[a-z0-9]+)?).po/', $file, $match ) ) {
					continue;
				}
				if ( ! in_array( substr( $file, 0, -3 ) . '.mo', $files, true ) ) {
					continue;
				}
		
				list( , $textdomain, $language ) = $match;
				if ( '' === $textdomain ) {
					$textdomain = 'default';
				}
				$language_data[ $textdomain ][ $language ] = wp_get_pomo_file_data( WP_LANG_DIR . "$dir/$file" );
			}
			return $language_data;
		}

		function get_available_languages( $dir = null ) {
			$languages = array();
		
			$lang_files = glob( ( is_null( $dir ) ? WP_LANG_DIR : $dir ) . '/*.mo' );
			if ( $lang_files ) {
				foreach ( $lang_files as $lang_file ) {
					$lang_file = basename( $lang_file, '.mo' );
					if ( ! str_starts_with( $lang_file, 'continents-cities' ) && ! str_starts_with( $lang_file, 'ms-' ) &&
						! str_starts_with( $lang_file, 'admin-' ) ) {
						$languages[] = $lang_file;
					}
				}
			}
		
			/**
			 * Filters the list of available language codes.
			 *
			 * @since 4.7.0
			 *
			 * @param string[] $languages An array of available language codes.
			 * @param string   $dir       The directory where the language files were found.
			 */
			return apply_filters( 'get_available_languages', $languages, $dir );
		}

		$GLOBALS['wp_plugin_paths'] = [];

		function custom_get_plugins( $plugin_folder = '' ) {
		
			$wp_plugins  = array();
			$plugin_root = WP_PLUGIN_DIR;
			if ( ! empty( $plugin_folder ) ) {
				$plugin_root .= $plugin_folder;
			}
		
			// Files in wp-content/plugins directory.
			$plugins_dir  = @opendir( $plugin_root );
			$plugin_files = array();
		
			if ( $plugins_dir ) {
				while ( ( $file = readdir( $plugins_dir ) ) !== false ) {
					if ( str_starts_with( $file, '.' ) ) {
						continue;
					}
		
					if ( is_dir( $plugin_root . '/' . $file ) ) {
						$plugins_subdir = @opendir( $plugin_root . '/' . $file );
		
						if ( $plugins_subdir ) {
							while ( ( $subfile = readdir( $plugins_subdir ) ) !== false ) {
								if ( str_starts_with( $subfile, '.' ) ) {
									continue;
								}
		
								if ( str_ends_with( $subfile, '.php' ) ) {
									$plugin_files[] = "$file/$subfile";
								}
							}
		
							closedir( $plugins_subdir );
						}
					} else {
						if ( str_ends_with( $file, '.php' ) ) {
							$plugin_files[] = $file;
						}
					}
				}
		
				closedir( $plugins_dir );
			}
		
			if ( empty( $plugin_files ) ) {
				return $wp_plugins;
			}
		
			foreach ( $plugin_files as $plugin_file ) {
				if ( ! is_readable( "$plugin_root/$plugin_file" ) ) {
					continue;
				}
		
				// Do not apply markup/translate as it will be cached.
				$plugin_data = get_plugin_data( "$plugin_root/$plugin_file", false, false );
		
				if ( empty( $plugin_data['Name'] ) ) {
					continue;
				}
		
				$wp_plugins[] = $plugin_file;
			}
		
			return $wp_plugins;
		}

		

		foreach ( custom_get_plugins() as $plugin ) {
			WP_CLI::debug($plugin);
			WP_CLI::debug("" . wp_register_plugin_realpath( WP_PLUGIN_DIR . '/' . $plugin ));
		}
	}
};

$wpcli_language_autoloader = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $wpcli_language_autoloader ) ) {
	require_once $wpcli_language_autoloader;
}

$wpcli_language_check_requirements = function () {
	if ( \WP_CLI\Utils\wp_version_compare( '4.0', '<' ) ) {
		WP_CLI::error( 'Requires WordPress 4.0 or greater.' );
	}
};

WP_CLI::add_command(
	'language-offline core',
	'Core_Language_Command',
	array( 'before_invoke' => $wpcli_languageoffline_init )
);

WP_CLI::add_command(
	'language-offline plugin',
	'Plugin_Language_Command',
	array( 'before_invoke' => $wpcli_languageoffline_init )
);

WP_CLI::add_command(
	'language-offline theme',
	'Theme_Language_Command',
	array( 'before_invoke' => $wpcli_languageoffline_init )
);

if ( class_exists( 'WP_CLI\Dispatcher\CommandNamespace' ) ) {
	WP_CLI::add_command( 'language-offline', 'Language_Namespace' );
}
