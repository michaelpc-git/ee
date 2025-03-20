<?php
namespace SquatnetListingCreator;

use SquatnetListingCreator\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;

class RadarConnector {

	private $client;

	private $headers;

	private $cookies;

	private $config;

	function __construct(
		Config $config = null, Client $client = null, CookieJar $cookies = null
	) {

		if ( is_null( $config ) ) {
			$config = new Config();
		}

		$this->config = $config;

		$headers       = $this->config->get( 'headers' );
		$this->headers = array_merge(
			isset( $headers ) ? $headers : array(),
			array(
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			)
		);

		if ( is_null( $client ) ) {
			$client = new Client(
				array(
					'base_uri' => $this->config->get( 'api_url' ),
					'timeout'  => $this->config->get( 'timeout' ),
				)
			);
		}

		$this->client = $client;

		if ( is_null( $cookies ) ) {
			$cookies = new CookieJar();
		}

		$this->cookies = $cookies;
	}

	/**
   * Authenticate.
   *
   * @throws GuzzleHttp\Exception\RequestException
   *   401 unauthorized login failure.
   */
	public function authenticate(): void {
		$response = $this->request(
			'POST',
			'user/login',
			true,
			array(
				'json' => array(
					'username' => $this->config->get( 'username' ),
					'password' => $this->config->get( 'password' ),
				),
			)
		);

		$auth                          = json_decode( $response->getBody() );
		$this->headers['X-CSRF-Token'] = $auth->token;
	}

	/**
   * Guzzle Client::request wrapped for logging, and authentication.
   */
	public function request( $method, $path, $authenticated = false, array $options = array() ): Response {
		if ( $authenticated ) {
			$options += array(
				'cookies' => $this->cookies,
				'headers' => $this->headers,
			);
		}

		try {
			$response = $this->client->request( $method, $path, $options );
		} catch ( RequestException $e ) {
			// @todo logging.
			throw $e;
		}

		return $response;
	}

	public function get( $path, $authenticated = false, array $options = array() ) {
		return $this->request( 'GET', $path, $authenticated, $options );
	}

	public function post( $path, array $data, array $options = array() ) {
		$options += array(
			'json' => $data,
		);

		return $this->request( 'POST', $path, true, $options );
	}

}
