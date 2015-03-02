<?php

namespace Smartling\WP\Controller;

use Smartling\Bootstrap;
use Smartling\WP\JobEngine;
use Smartling\WP\WPAbstract;
use Smartling\WP\WPHookInterface;

class SettingsController extends WPAbstract implements WPHookInterface {

	public function wp_enqueue () {
		wp_enqueue_script(
			$this->getPluginInfo()->getName() . "settings",
			$this->getPluginInfo()->getUrl() . 'js/smartling-connector-admin.js',
			array ( 'jquery' ),
			$this->getPluginInfo()->getVersion(),
			false
		);
		wp_register_style(
			$this->getPluginInfo()->getName(),
			$this->getPluginInfo()->getUrl() . 'css/smartling-connector-admin.css',
			array (),
			$this->getPluginInfo()->getVersion(),
			'all'
		);
		wp_enqueue_style( $this->getPluginInfo()->getName() );
	}

	public function register () {
		add_action( 'admin_enqueue_scripts', array ( $this, 'wp_enqueue' ) );
		add_action( 'admin_menu', array ( $this, 'menu' ) );
		add_action( 'network_admin_menu', array ( $this, 'menu' ) );
		add_action( 'admin_post_smartling_settings', array ( $this, 'save' ) );
		add_action( 'admin_post_smartling_run_cron', array ( $this, 'run_cron' ) );
	}

	public function menu () {
		add_submenu_page(
			'smartling-submissions-page',
			'Settings',
			'Settings',
			'Administrator',
			'smartling-settings',
			array (
				$this,
				'view'
			)
		);
	}

	/**
	 * Starts cron job
	 * @throws \Exception
	 */
	public function run_cron()
	{
		ignore_user_abort(true);
		set_time_limit(0);

		/**
		 * @var JobEngine $jobEngine
		 */
		$jobEngine = Bootstrap::getContainer()->get('wp.cron');
		$jobEngine->doWork();

		wp_die('Cron job triggered. Now you can safely close this window / browser tab.');
	}


	public function getSiteLocales () {
		return $this->getConnector()->getLocales();
	}

	public function save () {
		$settings = $_REQUEST['smartling_settings'];

		$options       = $this->getPluginInfo()->getSettingsManager();
		$accountInfo   = $options->getAccountInfo();
		$targetLocales = $options->getLocales();

		if ( array_key_exists( 'apiUrl', $settings ) ) {
			$accountInfo->setApiUrl( $settings['apiUrl'] );
		}

		if ( array_key_exists( 'projectId', $settings ) ) {
			$accountInfo->setProjectId( $settings['projectId'] );
		}

		if ( array_key_exists( 'apiKey', $settings ) ) {
			$accountInfo->setKey( $settings['apiKey'] );
		}

		if ( array_key_exists( 'retrievalType', $settings ) ) {
			$accountInfo->setRetrievalType( $settings['retrievalType'] );
		}

		if ( array_key_exists( 'callbackUrl', $settings ) ) {
			$accountInfo->setCallBackUrl( $settings['callbackUrl'] == 'on' ? true : false );
		}

		if ( array_key_exists( 'autoAuthorize', $settings ) ) {
			$accountInfo->setAutoAuthorize( 'on' === $settings['autoAuthorize'] );
		} else {
			$accountInfo->setAutoAuthorize( false );
		}

		if ( array_key_exists( 'defaultLocale', $settings ) ) {
			$default = explode( "-", $settings['defaultLocale'] );

			if ( 2 === count( $default ) ) {
				$targetLocales->setDefaultBlog( $default[0] );
				$targetLocales->setDefaultLocale( $default[1] );
			}
		}

		if ( array_key_exists( 'targetLocales', $settings ) ) {
			$locales = array ();
			foreach ( $settings['targetLocales'] as $key => $locale ) {
				$locales[] = array (
					'locale'  => $key,
					'target'  => $locale['target'],
					'enabled' => array_key_exists( 'enabled', $locale ) && 'on' === $locale['enabled'],
					'blog'    => $locale['blog']
				);
			}
			$existLocales = array ();
			foreach ( $targetLocales->getTargetLocales( true ) as $targetLocale ) {
				$exist = false;
				foreach ( $locales as $locale ) {
					if ( $locale['blog'] == $targetLocale->getBlog() ) {
						$exist = true;
						break;
					}
				}
				if ( ! $exist ) {
					$existLocales[] = $targetLocale->toArray();
				}
			}
			$locales = array_merge( $locales, $existLocales );
			$targetLocales->setTargetLocales( $locales );
		}

		$options->save();

		wp_redirect( $_REQUEST['_wp_http_referer'] );
	}
}