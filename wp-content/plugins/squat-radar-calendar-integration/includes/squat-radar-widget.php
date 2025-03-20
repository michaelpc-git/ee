<?php
/**
 * Squat Radar Events Widget.
 *
 * @package squat-radar
 * @since 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class Squat_Radar_Widget extends WP_Widget {

	public function __construct() {
		$widget_ops = array(
			'classname' => 'squat-radar-widget',
			'description' => 'Radar Events List',
		);

		$this->connector = new Squat_Radar_Connector();

		parent::__construct( 'Squat_Radar', 'Squat Radar Events', $widget_ops );
	}

        /**
         * Register the widget
         */
  public static function register_widget() {
    register_widget( __CLASS__ );
		add_action( 'wp_ajax_squat_radar_events', [__CLASS__, 'ajax_callback'] );
		add_action( 'wp_ajax_nopriv_squat_radar_events', [__CLASS__, 'ajax_callback'] );
		wp_register_script( 'squat-radar-widget',  SQUAT_RADAR_URL . 'assets/squat-radar.js', ['jquery'] );
		add_action( 'wp_enqueue_scripts', [__CLASS__, 'widget_style'] );

		add_action( 'squat_radar_widget_cache_cron', [__CLASS__, 'cache_cron'] );
		add_option( 'squat_radar_widget_cron_run', []);
	}

	/**
	 * Enqueue scripts callback, add CSS.
	 */
	static public function widget_style() {
		wp_register_style( 'squat-radar-widget',  SQUAT_RADAR_URL . 'assets/squat-radar.css' );
		wp_enqueue_style( 'squat-radar-widget' );
	}

	/**
	 * Cron action.
	 *
	 * Uses an option to keep track of when run, and updates any (experimental) widgets that update using a cron period instead of ajax.
	 */
	public static function cache_cron() {
		$now = time();
		$last_run = get_option('squat_radar_widget_cron_run', []);
		foreach (self::cron_instances() as $number => $instance) {
			if (! isset($last_run[$number]) || $last_run[$number] + $instance['cache_expire'] < $now ) {
				if (self::cache_refresh($instance)) {
					$last_run[$number] = $now; 
				}
			}
		}
		update_option('squat_radar_widget_cron_run', $last_run);
	}

	/**
	 * Refresh an individual widget instance for cache_cron().
	 */
	protected static function cache_refresh($instance) {
		$connector = new Squat_Radar_Connector();

    $languages = apply_filters('wpml_active_languages', NULL);
    $languages = is_array($languages) ? array_keys($languages) : [];
    $languages = array_merge([$instance['url']['keys']['language']], $languages);
		foreach ($languages as $language) {
			try {
				// Force update. Don't set expire.
				$data = $connector->events($instance['url']['keys']['facets'], $instance['fields'], $language, $instance['limit'], 0, TRUE );
			}
			catch ( Squat_Radar_Connector_Exception $e ) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Implementation of WP_Widget::widget().
	 *
	 * Outputs the events for the correct instance of the widget.
	 */
	public function widget( $args, $instance ) {
		$widget_id = 'squat_radar_widget_' . $this->number; 
		
		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		if ( ! empty($instance['use_cron']) ) {
			try {
				echo self::instance_events_html($instance);
			}
			catch ( Squat_Radar_Connector_Exception $e ) {
				if ( current_user_can( 'administrator' ) ) {
					echo $e->getCode() . ': ' . $e->getMessage();
				}
				echo '<div id="' . $widget_id . '" class="squat-radar-widget"><a href="' . esc_url_raw( $instance['url']['value'] ) . '">'
					. esc_url( $instance['url']['value'] ) 
					. '</a></div>';
			}
		}
		else {
      wp_enqueue_script( 'squat-radar-widget');
      wp_add_inline_script('squat-radar-widget', 
        'const squat_radar_widget = ' . json_encode( [
          'ajaxurl' => admin_url( 'admin-ajax.php' ),
          $widget_id => $this->number,
        ] ) . ';', 'before');

			echo '<div id="' . $widget_id . '" class="squat-radar-widget squat-radar-ajax"><a href="' . esc_url_raw( $instance['url']['value'] ) . '">'
				. esc_url( $instance['url']['value'] ) 
				. '</a></div>';
		}

		echo $args['after_widget'];
	}

	/**
	 * Action callback for AJAX widget display.
	 */
	public static function ajax_callback() {
		if ( ! array_key_exists('instance', $_POST) ) {
			wp_die();
		}

		$data = [];

		// Load instance configuration from ID.
		$instance_number = (int) $_POST['instance'];
		$widget_options_all = get_option('widget_squat_radar');
		if ( ! isset($widget_options_all[$instance_number]) )  {
			wp_die();
		}

		try {
			$data['html'] = self::instance_events_html($widget_options_all[$instance_number]);
		}
		catch ( Squat_Radar_Connector_Exception $e ) {
			$data = ['is_error' => TRUE];
			if ( current_user_can( 'administrator' ) ) {
				$data['error']['code'] = $e->getCode();
				$data['error']['message'] = $e->getMessage();
			}
		}

		wp_send_json($data);
	}

	public static function instance_events_html($instance) {
		$language = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : $instance['url']['keys']['language'];
		$connector = new Squat_Radar_Connector();
		$data = $connector->events($instance['url']['keys']['facets'], $instance['fields'], $language, $instance['limit'], $instance['cache_expire']);
		$html = '';
		foreach ($data['result'] as $id => $event) {
			$output = apply_filters( 'squat_radar_format_event', $event, $instance['fields'], ['instance' => $instance] );
			$html .= implode(' ', $output);
		}

		return $html;
	}

	/**
	 * Implementation of WP_Widget::form().
	 *
	 * Widget options.
	 */
	public function form( $instance ) {
		//
		// Title.
		//
		$field_id = esc_attr( $this->get_field_id( 'title' ) );
		$field_name =  esc_attr( $this->get_field_name( 'title' ) ); 
		$field_label = esc_attr( 'Title:', 'squat-radar' );
		$field_value = empty( $instance['title'] ) ? '' : esc_attr( $instance['title'] );
		$field_class = 'widefat';
		echo "<p>";
		echo "<label for=\"$field_id\">$field_label</label>";
		echo "<input class=\"$field_class\" id=\"$field_id\" name=\"$field_name\" type=\"text\" value=\"$field_value\">";
		echo "</p>";

		// 
		// Limit
		//
		$field_id = esc_attr( $this->get_field_id( 'limit' ) );
		$field_name =  esc_attr( $this->get_field_name( 'limit' ) ); 
		$field_label = esc_attr( 'Max number of events to display:', 'squat-radar' );
		$field_value = empty( $instance['limit'] ) ? '10' : (int) $instance['limit'];
		$field_class = 'tiny-text';
		echo "<p>";
		echo "<label for=\"$field_id\">$field_label</label>";
		echo "<input class=\"$field_class\" id=\"$field_id\" name=\"$field_name\" type=\"number\" step=\"1\" min=\"1\" value=\"$field_value\" size=\"3\">";
		echo "</p>";


		//
		// URL.
		//
		$field_error = ! empty( $instance['url']['error'] );
		$field_id = esc_attr( $this->get_field_id( 'url' ) );
		$field_name =  esc_attr( $this->get_field_name( 'url' ) ); 
		$field_label = esc_attr( 'Event Search URL:', 'squat-radar' );
		$field_value = empty( $instance['url']['value'] ) ? '' : esc_attr( $instance['url']['value'] );
		$field_class = 'widefat' . $field_error ? ' error' : '';
		echo "<p>";
		echo "<label for=\"$field_id\">$field_label</label>";
		echo "<input class=\"$field_class\" id=\"$field_id\" name=\"$field_name\" type=\"text\" value=\"$field_value\">";
		echo "</p>";
		if ( $field_error ) {
			echo '<div class="description error">' . __('The URL was not recognised as a Radar Events search result. It needs to include the domain and the rest of the /events/search/path like: https://radar.squat.net/en/events/city/City_Name/group/123 Start from <a href="https://radar.squat.net/en/events" target="_blank">https://radar.squat.net/en/events</a> and use the filters in the right hand colunm there before copying the URL from your browser address bar.', 'squat-radar') . '</div>';
		}
		else {
			echo '<div class="description">' . __('Go to <a href="https://radar.squat.net/en/events" target="_blank">https://radar.squat.net/en/events</a> and filter for the events you want to show. Then copy the URL from your address bar into here. It will look similar to: https://radar.squat.net/en/events/city/City_Name/group/123 for example the URL to show all international callouts is https://radar.squat.net/en/events/callout/international-callout', 'squat-radar') . '</div>';
		}

		if ( empty($instance['url']['error']) && ! empty( $instance['url']['keys'] ) ) {
			echo '<hr>';
			echo '<p>' . __('Currently selecting events:', 'squat-radar') . '</p>';
			echo '<dl>';
			echo '<dt>' . __('Default language', 'squat-radar') . '</dt>';
			echo '<dd>' . esc_html($instance['url']['keys']['language']) . '</dd>';
			foreach ($instance['url']['keys']['facets'] as $key => $value) {
				echo '<dt>' . esc_html($key) . '</dt>';
				echo '<dd>' . esc_html($value) . '</dd>';
			}
			echo '</dl>';

		}

		echo '<hr>';
		echo '<fieldset>';
		echo '<legend>' . __('Fields', 'squat-radar') . '</legend>';
		echo '<p>';
		// Some sensible checkbox defaults.
		if ( empty($instance['fields']) ) {
			$instance['fields'] = [
				'title_field' => '',
				'date_time:time_start' => '',
				'body:summary' => '',
				'category' => '',
				'offline:address' => '',
				'offline:map' => '',
				'url' => '',
			];
		}
		foreach ($this->preset_fields() as $api_field_name => $field_label) {
			$field_id = esc_attr( $this->get_field_id( 'field-' . $api_field_name ) );
			$field_name =  esc_attr( $this->get_field_name( 'field-' . $api_field_name ) ); 
			$field_label = esc_attr( $field_label );
			$checked = '';
			if ( isset($instance['fields'][$api_field_name]) ) {
				unset($instance['fields'][$api_field_name]);
				$checked = ' checked="checked"';
			}
			echo "<input type=\"checkbox\" class=\"checkbox\" id=\"$field_id\" name=\"$field_name\"$checked />";
			echo "<label for=\"$field_id\">$field_label</label><br />";
		}
		echo '</p>';
		echo '</fieldset>';

		// ADVANCED
		echo '<hr>';
		echo '<fieldset>';
		echo '<legend>' . __('Advanced settings', 'squat-radar') . '</legend>';
		//
		// Fields.
		//
		$field_id = esc_attr( $this->get_field_id( 'fields' ) );
		$field_name =  esc_attr( $this->get_field_name( 'fields' ) ); 
		$field_label = esc_attr( 'Additional fields:', 'squat-radar' );
		$field_value = empty( $instance['fields'] ) ? '' : esc_attr( implode( ', ', $instance['fields'] ) );
		$field_class = 'widefat';
		echo "<p>";
		echo "<label for=\"$field_id\">$field_label</label>";
		echo "<input class=\"$field_class\" id=\"$field_id\" name=\"$field_name\" type=\"text\" value=\"$field_value\">";
		echo "</p>";
		echo '<div class="description">' . __('A comma seperated list of field API names. Examples: phone, price, flyer, offline:address:thoroughfare. Some fields might need an additonal filter to format them properly.') . '</div>';

		//
		// Cache expiry.
		//
		$field_id = esc_attr( $this->get_field_id( 'cache_expire' ) );
		$field_name =  esc_attr( $this->get_field_name( 'cache_expire' ) ); 
		$field_label = esc_attr( 'Cache length:', 'squat-radar' );
		$field_value = empty( $instance['cache_expire'] ) ? 10800 : (int) $instance['cache_expire']; 
		$field_class = 'widefat';
		echo "<p>";
		echo "<label for=\"$field_id\">$field_label</label>";
		echo "<select class=\"$field_class\" id=\"$field_id\" name=\"$field_name\">";
		echo '<option value="3600"' . selected( $field_value, 3600 ) . '>' . __('1 hour') . '</option>';
		echo '<option value="10800"' . selected( $field_value, 10800 ) . '>' . __('3 hours') . '</option>';
		echo '<option value="43200"' . selected( $field_value, 43200 ) . '>' . __('12 hours') . '</option>';
		echo "</select>";
		echo "</p>";
		echo '<div class="description">' . __('Length of time the cache of events will be kept. Longer faster, but updated less often.') . '</div>';
		
		$field_id = esc_attr( $this->get_field_id( 'use_cron' ) );
		$field_name =  esc_attr( $this->get_field_name( 'use_cron' ) ); 
		$field_label = esc_attr__( 'Use cron' );
		$use_cron = isset($instance['use_cron']) ? (bool) $instance['use_cron'] : false;
		$checked = checked( $use_cron, TRUE, FALSE );
		echo "<input type=\"checkbox\" class=\"checkbox\" id=\"$field_id\" name=\"$field_name\"$checked />";
		echo "<label for=\"$field_id\">$field_label</label><br />";
		echo '<div class="description">' . __('Do not use AJAX, but always display the cached version of the events. Update the cache after the expiry length using cron. Works best if you have a regular external cronjob running.') . '</div>';

		echo '</fieldset>';

	}

	/**
	 * Implementation of WP_Widget::update().
	 *
	 * Save widget options.
	 */
	public function update( $new_instance, $old_instance ) {
    $options = [];
		if ( ! empty( $new_instance['title'] ) ) {
			$options['title'] = sanitize_text_field( $new_instance['title'] );
		}
		else {
			$options['title'] = '';
		}

    if ( ! empty($new_instance['url']) ) {
      // The value passed here changes somewhere post WP5.4.
      // More recent versions have the options array in the instance.
      if (is_string($new_instance['url'])) {
        $url = $new_instance['url'];
      }
      else {
        $url = $new_instance['url']['value'];
      }
			$keys = $this->connector->decode_search_url($url);
			$options['url']['keys'] = $keys;
			$options['url']['value'] = $url;
			if (empty($keys)) {
				$options['url']['error'] = 'URL not recognised';
			}
		}
		else {
			$options['url'] = ['value' => '', 'keys' => []];
		}

    // When called by 5.9 ajax this contains the already set array.
    // Just check it's sane. 
    $options['fields'] = is_array($new_instance['fields']) ? $new_instance['fields'] : [];
    array_filter($options['fields'], function ($v, $k) {
      return ($v == $k) && (preg_match('([^a-zA-Z_:])', $k) === 0);
    }, ARRAY_FILTER_USE_BOTH);

		foreach ($this->preset_fields() as $field_name => $field_label) {
			if ( ! empty($new_instance['field-' . $field_name]) ) {
				$options['fields'][$field_name] = $field_name;
			}
		}

    if ( ! empty($new_instance['fields']) && is_string($new_instance['fields']) ) {
			$matches = [];
			preg_match_all('/([a-zA-Z_:]+)/', $new_instance['fields'], $matches);
			$options['fields'] += array_combine($matches[0], $matches[0]);
		}

		if ( ! empty( $new_instance['limit'] ) ) {
			$options['limit'] = (int) $new_instance['limit'];
		}

		if ( ! empty( $new_instance['cache_expire'] ) ) {
			$options['cache_expire'] = (int) $new_instance['cache_expire'];
		}
		else {
			$options['cache_expire'] = 10800;
		}

		if ( empty( $new_instance['use_cron'] ))  {
			$options['use_cron'] = FALSE;
			$cron_instances = self::cron_instances();
			unset($cron_instances[$this->number]);
			if ( empty($cron_instances) && ($timestamp = wp_next_scheduled( 'squat_radar_widget_cache_cron' ) )) {
				wp_unschedule_event( $timestamp, 'squat_radar_widget_cache_cron' );
			}
		}
		else {
			$options['use_cron'] = TRUE;
			self::cache_refresh($options);
			if ( ! wp_next_scheduled( 'squat_radar_widget_cache_cron' ) ) {
				    wp_schedule_event( time() + $options['cache_expire'], 'hourly', 'squat_radar_widget_cache_cron');
			}
		}

		return $options;
	}

	public function preset_fields() {
		return [
			'title_field' => __( 'Title' ),
			'event_status' => __( 'Event status (proposed, or cancelled)' ),
			'date_time' => __( 'Date and Time (start and optional end)' ),
			'date_time:time_start' => __( 'Date and Time (start only)' ),
			'body' => __( 'Body' ),
			'body:summary' => __( 'Body (teaser, summary)' ),
			'category' => __( 'Categories' ),
			'topic' => __( 'Tags' ),
			'offline:address' => __( 'Address' ),
			'offline:map' => __( 'Map (link)' ),
			'og_group_ref' => __( 'Groups' ),
			'price_category' => __( 'Price category' ),
			'image:file:url' => __( 'Image' ),
			'link' => __( 'Event URL (entered not Radar)' ),
			'url' => __( 'More link (to event on Radar)' ),
		];
	}

	public static function cron_instances() {
		$cron_instances = [];
		$instances = get_option( 'widget_squat_radar' );
		foreach ($instances as $number => $instance) {
			if (! empty($instance['use_cron']) ) {
				$cron_instances[$number] = $instance;
			}
		}

		return $cron_instances;
	}

}
