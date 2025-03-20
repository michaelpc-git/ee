<?php
/**
 * Squat Radar Connector.
 *
 * Fetch data from Radar API.
 *
 * @package squat-radar
 * @since 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class Squat_Radar_Connector {

	const BASE_URL = 'https://radar.squat.net';
	const API_EVENTS = '/api/1.2/search/events.json';

	/**
	 * Retrieve array of events from API based on a query.
	 *
	 * @see self::encode_api_query() for $query values.
	 *
	 * @param array $query
	 *  Key value pairs for the API query.
	 * 
	 * @return array
	 *  Array of events.
	 *
	 * @throws Squat_Radar_Connector_Exception
	 *  When events are not returned, but a timeout or API error.
	 */
	function get_events( $query ) {

		$url = self::BASE_URL . self::API_EVENTS . '?' . build_query( $query );
		$response = wp_remote_get( $url );
		if ( is_wp_error( $response ) ) {
			throw new Squat_Radar_Connector_Exception( $response->get_error_message() );
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( $code != 200) {
			throw new Squat_Radar_Connector_Exception( wp_remote_retrieve_body( $response ), $code );
		}
		return json_decode( wp_remote_retrieve_body( $response ), true);

	}

	/**
	 * Turn a Radar frontend Search URL into facets key value and language values.
	 *
	 * @param string $url
	 *  The https://radar.squat.net/events filtered URL.
	 *
	 * @return array
	 *  [ 'language' => language code, 'facets' => [key => value] ].
	 */
	function decode_search_url( $url ) {
		$matches = [];
		$result = [];
		// Urldecode not required here because of the regex match.
		// Radar paramaters here are transcoded so will match.
		if (preg_match('|//radar.squat.net/([a-z]{2})/events/([a-zA-Z0-9\-/]*)|', $url, $matches)) {
			$result['language'] = $matches[1];
			foreach (array_chunk(explode('/', $matches[2]), 2) as $key_value_pair) {
				$result['facets'][$key_value_pair[0]] = $key_value_pair[1];
			}
		}

		return $result;
	}

	/**
	 * Encode a query key value from facets, fields, language, limit.
	 *
	 * @param array $facets
	 *  Optional. Facet key => filter value array.
	 * @param array $fields
	 *  Optional. Index array of API field names to retrieve.
	 * @param string $language
	 *  Optional. Language code.
	 * @param int $limit
	 *  Optional. Maximum number to items to return.
	 *
	 * @return array
	 *  Array for use in self::get_events().
	 */
	function encode_api_query( $facets = [], $fields = [], $language = '', $limit = 10 ) {
		$query = [];

		// Urlencode should do nothing here @see comment in decode_search_url.
		// If someone has snuck something in it will however help.
		foreach ( $facets as $key => $value ) {
			$query['facets[' . urlencode($key) . ']'][] = urlencode($value);
		}
		if ( ! empty($fields) ) {
			// {raw}urlencode is encoding : and , both of which are valid pchar.
			$query['fields'] = preg_replace('/[^a-z_:,]/', '', implode(',', $fields));
		}
		if ( ! empty($language) ) {
			$query['language'] = urlencode($language);
		}
		if ( ! empty($limit) ) {
			$query['limit'] = urlencode($limit);
		}
		return $query;
	}

	/**
	 * Return events meeting argument criteria. Either from cache, or retrieved from API.
	 *
	 * @param array $facets
	 *  Facet name key => filter value.
	 * @param array $fields
	 *  Optional. Array of key names.
	 * @param string $language
	 *  Optional. Language code.
	 * @param int $limit
	 *  Maximum number of items to return.
	 * @param int $expiration
	 *  Seconds to cache results. 0 never expires.
	 * @param bool $reset
	 *  Force a cache reset.
	 *
	 * @return array
	 *  Array of event arrays, values keyed by field name.
	 */
	function events( $facets, $fields = [], $language = NULL, $limit = 10, $expiration = 10800, $reset = FALSE ) {
		// Fields we often want to get data out of but not necessarily are chosen to be shown.
		$fields = array_merge($fields, ['uuid', 'title', 'body:value', 'url', 'event_status']);
		$transient_key = 'squat_radar_events_' . sha1(implode($facets) . implode($fields) . $language . $limit);
		if (! $reset && $data = get_transient( $transient_key )) {
			return $data;
		}
		$query = $this->encode_api_query( $facets, $fields, $language, $limit );
		$events = $this->get_events($query);

		set_transient( $transient_key, $events, $expiration );
		return $events;
	}
}

class Squat_Radar_Connector_Exception extends Exception { }
