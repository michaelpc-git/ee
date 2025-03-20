<?php
/**
 * Squat Radar Events Formatter.
 *
 * Provides filters to format the output of Squat Radar Events.
 *
 * 'squat_radar_format_event' allows the whole event to be formatted.
 * Default filter Squat_Radar_Formatter::format_event().
 *
 * 'squat_radar_field_html' formats individual fields.
 * Basic implementation Squat_Radar_Formatter::field_html().
 *
 * @package squat-radar
 * @since 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class Squat_Radar_Formatter {

	/**
	 * Register filters with Wordpress.
	 */
	static public function register() {
		// Filter to go through fields and then call filters to turn these into HTML.
		add_filter('squat_radar_format_event', [__CLASS__, 'format_event'], 10, 3);

		// Filters to turn each individual field into HTML.
		//
		// $value is the data from the field and can be an array or string.
		//
		// These filters extract data from arrays based on the field structure.
		// If you make a change it is a requirement to sanitize
		// anything that will be output. 
		add_filter('squat_radar_field_html', [__CLASS__, 'field_date_html'], 5, 4);
		add_filter('squat_radar_field_html', [__CLASS__, 'field_location_html'], 5, 4);
		add_filter('squat_radar_field_html', [__CLASS__, 'field_link_html'], 5, 4);
		add_filter('squat_radar_field_html', [__CLASS__, 'field_summary_html'], 5, 4);
		// Field 'url' was already turned into a <a> link, by field_link_html.
		// The field_image_html is an example of an override with more specificity.
		add_filter('squat_radar_field_html', [__CLASS__, 'field_image_html'], 7, 4);
		// If $value is an array it is flattened into a string here.
		// If $value != $original it will _not_ be sanitized, assumption is that it has been already.
		add_filter('squat_radar_field_html', [__CLASS__, 'field_html'], 10, 4);
		// $value is always a string from this point.
		// These filters just add additional wrapper markup.
		add_filter('squat_radar_field_html', [__CLASS__, 'field_title_html'], 15, 4);
	}

	/**
	 * Implementation of 'squat_radar_format_event'.
	 *
	 * Formats an API event array into HTML.
	 *
	 * @param array $event
	 *  The event array from the API. Nested field names with values.
	 * @param array $fields
	 *  The field names required for display. Colons used to denote nesting.
	 * @param array $context
	 *
	 * @return string
	 *  HTML.
	 */
	static public function format_event($event, $fields, $context) {

		$context['event'] = $event;
		$output = [];
		$event_status = self::getValue( $event, ['event_status'] );
		$output[] = '<div class="squat-radar radar-event radar-event-' . $event_status . '">';
		foreach ($fields as $field) {
			$field_tree = explode(':', $field);
			$value = self::getValue($event, $field_tree);
			$field_tree = array_reverse($field_tree);
			$output[] = apply_filters('squat_radar_field_html', $value, $value, $field_tree, $context);
		}
		$output[] = '</div>';
		return $output;

	}

	/**
	 * Basic implementation of 'squat_radar_field_html' filter.
	 *
	 * Put the output into HTML.
	 *
	 * @param array|string $value
	 *   The field value being manipulated to become HTML to be displayed.
	 * @param array|string $original
	 *   The original value of the field before any changes by filters.
	 * @param array $field
	 *   The field tree. $field[0] being the name of the present field. $field[1]
	 *   being any parent etc.
	 * @param array $context
	 *
	 * @return string
	 *   Flattend array with additional default classes.
	 */
	static public function field_html($value, $original, $field, $context) {
		if ($value != $original) {
			return $value;
		}

		if (is_array($value)) {
			if ( ! empty($value['value']) ) {
				$value = $value['value'];
			}
			elseif ( ! empty($value['title']) ) {
				$value = $value['title'];
			}
			elseif ( ! empty($value['name']) ) {
				$value = $value['name'];
			}
			elseif ( ! empty($value[0]['value']) ) {
				foreach ($value as $row) {
					$values[] = $row['value'];
				}
				$value = $values;
			}
			elseif ( ! empty($value[0]['title']) ) {
				foreach ($value as $row) {
					$titles[] = $row['title'];
				}
				$value = $titles;
			}
			elseif ( ! empty($value[0]['name']) ) {
				foreach ($value as $row) {
					$names[] = $row['name'];
				}
				$value = $names;
			}
		}

		if (is_array($value)) {
			$output = '<ul class="squat-radar-' . sanitize_html_class($field[0]) . ' squat-radar-list">';
			foreach ($value as $row) {
				$output .= '<li class="squat-radar-item-' . sanitize_html_class($field[0]) . '">' . sanitize_text_field( $row ) . '</li>';
			}
			$output .= '</ul>';

			return $output;
		}
		else {
			$value = '<span class="squat-radar-' . sanitize_html_class($field[0]) . '">' . wp_kses_post( $value ) . '</span>';
		}

		return $value;
	}
		
	/**
	 * Date field formatting implementation of 'squat_radar_field_html' filter.
	 */
	static public function field_date_html($value, $original, $field, $context) {

		switch ($field[0]) {
			case 'created':
			case 'updated':
				$output = '';
				if ($value) {
					$output = date_i18n( get_option( 'date_format' ), $value );
					$placeholder = ($field[0] == 'created') ? __('Created: %s', 'squat-radar') : __('Updated: %s', 'squat-radar');
					$output = '<span class="squat-radar-meta-data squat-radar-' . $field[0] . '">' . sprintf($placeholder, $output) . '</span>';
				}
				return $output;

			// "date_time": [
			//   {
			//     "value": "1556442000",
			//     "value2": "1556442000",
			//     "duration": 0,
			//     "time_start": "2019-04-28T11:00:00+02:00",
			//     "time_end": "2019-04-28T11:00:00+02:00",
			//     "rrule": null
			//   }
			// ],
			case 'date_time':
	
				$output = '';
				// There can only be one date. With repeat etc. but just one.
				// Repeating events will appear as a new item for each repeat in the feed.
				$value = $value[0];
				$output = '<span class="squat-radar-event-start-end">';
				$output .= self::field_date_format( $value['time_start'], 'start' );
				if ($value['time_start'] != $value['time_end']) {
					$time_only = ( substr($value['time_start'], 0, 10) == substr($value['time_end'], 0, 10) );
					$output .= ' - ' . self::field_date_format( $value['time_end'], 'end', $time_only );
				}
				$output .= '</span>';
				return $output;

			case 'time_start':
				$value = $value[0];
				$output = '<span class="squat-radar-event-start">'; 
				$output .= self::field_date_format($value, 'start');
				$output .= '</span>';
				return $output;

			case 'time_end':
				$value = $value[0];
				$output = '<span class="squat-radar-event-end">'; 
				$output .= self::field_date_format($value, 'end');
				$output .= '</span>';
				return $output;

	  	}

		return $value;
	}

	private static function field_date_format($time, $start_end, $time_only = FALSE) {

		$date_format = get_option('squat_radar_date_format', 'j M Y');
		$time_format = get_option('squat_radar_time_format', 'H:i');

		// Remove offset to stop time being converted to UTC.
		$time = substr($time, 0, -6);

		$output = '<span class="squat-radar-datetime squat-radar-datetime-' . $start_end .'">';
		if ( ! $time_only ) {
			$output .= '<span class="squat-radar-date">';
			$output .= date_i18n($date_format, strtotime($time));
			$output .= '</span> ';
		}
		$output .= '<span class="squat-radar-time">';
		$output .= date_i18n($time_format, strtotime($time));
		$output .= '</span></span>';

		return $output;
	}
	
	/**
	 * Location field implementation of 'squat_radar_field_html' filter.
	 *
	 * "offline": [
	 *   {
	 *     "uri": "https://radar.squat.net/api/1.2/location/b5786379-da49-4026-8c4e-bcc1a1563284",
	 *     "id": "b5786379-da49-4026-8c4e-bcc1a1563284",
	 *     "resource": "location",
	 *     "title": "Yorck-Kino Yorckstr. 86  Berlin Deutschland",
	 *     "map": {
	 *       "geom": "POINT (13.3853499 52.4930248)",
	 *       "geo_type": "point",
	 *       "lat": "52.493024800000",
	 *       "lon": "13.385349900000",
	 *       "left": "13.385349900000",
	 *       "top": "52.493024800000",
	 *       "right": "13.385349900000",
	 *       "bottom": "52.493024800000",
	 *       "srid": null,
	 *       "latlon": "52.493024800000,13.385349900000",
	 *       "schemaorg_shape": ""
	 *     }
	 *   }
	 * ]
 	*/
	static public function field_location_html($value, $original, $field, $context) {
		switch ($field[0]) {
			case 'map':
				$output = [];
				foreach ($value as $map) {
					if ( is_array($map) && ! empty($map['lat']) && $map['lat'] !== NULL && $map['lon'] !== NULL ) {
						$this_output = '<span class="squat-radar-location squat-radar-location-map-link">';
						$lat = $map['lat'];
						$lon = $map['lon'];
						$this_output .= "<a href=\"https://www.openstreetmap.org/?mlat=$lat&mlon=$lon#map=14/$lat/$lon\" target=\"_blank\">";
						$this_output .= __('[Map]', 'squat-radar');
						$this_output .= '</a></span>';
						$output[] = $this_output;
					}
				}
				return implode(', ', $output);

			case 'address':
				$output = [];
				foreach ($value as $address) {
					if ( is_array($address) ) {
						$this_address = [];
						foreach (['name_line', 'thoroughfare', 'locality', 'postal_code', 'country'] as $field_name) {
							if (! empty($address[$field_name])) {
								$this_line = '<span class="squat-radar-location-' . $field_name . '">';
								$this_line .= sanitize_text_field($address[$field_name]);
								$this_line .= '</span>';
								$this_address[] = $this_line;
							}
						}
						
						$this_output = '<span class="squat-radar-location squat-radar-location-address">';
						$this_output .= implode(', ', $this_address);
						$this_output .= '</span>';
						$output[] = $this_output;
					}
				}
				return implode('; ', $output);
		}

		return $value;
	}

	/**
 	 * Item Radar links implementation of 'squat_radar_field_html' filter.
	 */
	static public function field_link_html($value, $original, $field, $context) {
		if ( ($field[0] == 'title' || $field[0] == 'title_field') && ! empty($context['event']['url'])) {
			return '<a target="_blank" href="' . esc_url($context['event']['url']) . '" class="squat-radar-url squat-radar-url-title">' . sanitize_text_field( $value ) . '</a>';
		}
	
		if ($field[0] == 'url' && count($field) == 1) {
			return '<a target="_blank" href="' . esc_url_raw($value) . '" class="squat-radar-url squat-radar-url-more">' . __('more…', 'squat-radar') . '</a>';
		}
		elseif ($field[0] == 'url') {
			$title = esc_url($value);
			array_shift($field);
			if (is_array($field)) {
				$field_tree = array_reverse($field);
				$sibling_fields = self::getValue($context['event'], $field_tree);
				$class = 'squat-radar-url-link';
				if (! empty($sibling_fields['title']) ) {
					$title = sanitize_text_field( $sibling_fields['title']);
					$class = 'squat-radar-url-title';
				}
				elseif ( ! empty($sibling_fields['name']) ) {
					$title = sanitize_text_field( $sibling_fields['name']);
					$class = 'squat-radar-url-name';
				}
			}
			return '<a target="_blank" href="' . esc_url_raw($value) . '" class="squat-radar-url ' . $class . '">' . $title . '</a>';
		}	
		
		if ($field[0] == 'link') {
			return '<a target="_blank" href="' . esc_url_raw($value['url']) . '" class="squat-radar-url squat-radar-url-link">' . esc_url($value['url']) . '</a>';
		}

		return $value;
	}

	/**
 	 * Item Radar summary implementation of 'squat_radar_field_html' filter.
	 */
	static public function field_summary_html($value, $original, $field, $context) {
		if ( $field[0] == 'summary' ) {
			// Summary is only populated if there is an explict summary.
			$value = trim($value);
			if ( empty( $value ) ) {
				array_shift($field);
				if (is_array($field)) {
					$field_tree = array_reverse($field);
					$sibling_fields = self::getValue($context['event'], $field_tree);
					if (! empty( $sibling_fields['value'] ) ) {
						$value = wp_trim_words( $sibling_fields['value'], 30 );
					}
				}
			}
			
			if ( ! empty($value) ) {	
				$value = '<span class="squat-radar-body-summary">' . wp_kses_post( $value ) . '</span>';
			}
		}

		return $value;
	}

	/**
	 * Format image implementation of 'squat_radar_field_html' filter.
	 *
	 * Intentionally run after field_link_html. Showing how to override an existing filter.
	 * image:file:url
	 */
	static public function field_image_html($value, $original, $field, $context) {
		if ( isset($field[0]) && $field[0] == 'url' &&
		     isset($field[1]) && $field[1] == 'file' &&
		     isset($field[2]) && $field[2] == 'image'
		) {
	      		return '<img src="'. esc_url_raw($original) .'" class="squat-radar-image" \>';
	    	}

	    	return $value;
	}


	/**
 	 * Title field HTML implementation of 'squat_radar_field_html' filter.
	 */
	static public function field_title_html($value, $original, $field, $context) {
		if (($field[0] == 'title' || $field[0] == 'title_field') && count($field) == 1) {
	    		$value = '<h3 class="squat-radar-title">' . $value . '</h3>';
		}

		return $value;
  	}

	/**
	 * Retrieves a value from a nested array with variable depth.
	 *
	 * Handles on level of multiple[] values on a key.
	 * It will work for deeper multiples, but return the top match.
	 *
	 * @param array $array
	 *   The array from which to get the value.
	 * @param array $parents
	 *   An array of parent keys of the value, starting with the outermost key.
	 * @param bool $key_exists
	 *   (optional) If given, an already defined variable that is altered by
	 *   reference.
	 *
	 * @return mixed
	 *   The requested nested value. Possibly NULL if the value is NULL or not all
	 *   nested parent keys exist. $key_exists is altered by reference and is a
	 *   Boolean that indicates whether all nested parent keys exist (TRUE) or not
	 *   (FALSE). This allows to distinguish between the two possibilities when
	 *   NULL is returned.
	 */
	public static function &getValue(array &$array, array $parents, &$key_exists = NULL) {
		$ref =& $array;
		while ($parent = array_shift($parents)) {
			if (is_array($ref) && array_key_exists($parent, $ref)) {
				$ref =& $ref[$parent];
			}
			elseif (is_array($ref) && isset($ref[0])) {
				$multiple = [];
				array_unshift($parents, $parent);
				foreach ($ref as &$value) {
					$multiple[] = self::getValue($value, $parents, $key_exists);
				}
				if (!empty($multiple) ) {
					return $multiple;
				}
				else {
					$key_exists = FALSE;
					$null = NULL;
					return $null;
				}
			}
			else {
				$key_exists = FALSE;
				$null = NULL;
				return $null;
			}
		}
		$key_exists = TRUE;
		return $ref;
	}
}
