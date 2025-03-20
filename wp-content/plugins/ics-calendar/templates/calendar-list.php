<?php
// Require object
if (empty($ics_data)) { return false; }

global $R34ICS;

$start_of_week = get_option('start_of_week', 0);

$date_format = r34ics_date_format($args['format'], true);

$ics_calendar_classes = apply_filters('r34ics_calendar_classes', null, $args, true);

$today = r34ics_date('Ymd');

// Feed colors custom CSS
if (!empty($ics_data['colors'])) {
	r34ics_feed_colors_css($ics_data, true);
}

// Prepare event details toggle lightbox
if ($args['toggle'] === 'lightbox') {
	r34ics_lightbox_container();
}

// Pagination
// Initial opacity depends on whether or not we are using AJAX and pagination
$initial_opacity = 0;
if (!empty($args['pagination'])) {
	$pagination = intval($args['pagination']);
	// Don't show month headers if pagination is equal to 1
	if ($pagination == 1) { $args['nomonthheaders'] = true; }
}
else {
	$pagination = false;
	$initial_opacity = !empty($args['ajax']) ? 0 : 1;
}
?>

<section class="<?php echo esc_attr($ics_calendar_classes); ?>" id="<?php echo esc_attr($ics_data['guid']); ?>" style="opacity: <?php echo intval($initial_opacity); ?>;">

	<?php
	// Title and description
	if (!empty($ics_data['title'])) {
		?>
		<<?php echo esc_attr($args['htmltagtitle']); ?> class="ics-calendar-title"><?php echo wp_kses_post($ics_data['title'] ?: ''); ?></<?php echo esc_attr($args['htmltagtitle']); ?>>
		<?php
	}
	if (!empty($ics_data['description'])) {
		?>
		<p class="ics-calendar-description"><?php echo wp_kses_post($ics_data['description'] ?: ''); ?></p>
		<?php
	}
	
	// Empty calendar message
	if (empty($ics_data['events']) || r34ics_is_empty_array($ics_data['events'])) {
		?>
		<p class="ics-calendar-error"><?php esc_html_e('No events found.', 'ics-calendar'); ?></p>
		<?php
	}
	
	// Display calendar
	else {

		// Actions before rendering calendar wrapper (can include additional template output)
		do_action('r34ics_display_calendar_before_wrapper', $view, $args, $ics_data);

		// Color code key
		if (empty($args['legendposition']) || $args['legendposition'] == 'above') {
			echo wp_kses(($R34ICS->color_key_html($args, $ics_data) ?: ''), r34ics_color_key_allowed());
		}
	
		// Pagination HTML
		$pagination_html = '';
		if (!empty($pagination)) {
			ob_start();
			?>
			<div class="ics-calendar-paginate-wrapper" aria-hidden="true">
				<a href="#<?php echo esc_attr($ics_data['guid']); ?>" class="ics-calendar-paginate prev">&larr; <?php esc_html_e('Previous Page', 'ics-calendar'); ?></a>
				<a href="#<?php echo esc_attr($ics_data['guid']); ?>" class="ics-calendar-paginate next"><?php esc_html_e('Next Page', 'ics-calendar'); ?> &rarr;</a>
			</div>
			<?php
			$pagination_html = ob_get_clean();
		}
		
		// Write calendar to output buffer so we can determine whether or not to display pagination
		// This is because the exact number of displayed events isn't known until we iterate through the output
		ob_start();

		// Build monthly calendars
		$i = 0;
		$skip_i = 0;
		$multiday_events_used = array();
		$years = $ics_data['events'];

		// Pagination?
		if (!empty($pagination)) {
			$p_i = 0; $p_c = 0; $pagination_open = false;
		}

		// Reverse?
		if ($args['reverse']) { krsort($years); }

		?>
		<article class="ics-calendar-list-wrapper">

			<?php
			foreach ((array)$years as $year => $months) {

				// Reverse?
				if ($args['reverse']) { krsort($months); }

				foreach ((array)$months as $month => $days) {
					$ym = $year . $month;
			
					// Is this month in range? If not, skip to the next
					if (!r34ics_month_in_range($ym, $ics_data)) { continue; }
			
					$m = intval($month);
					$month_label = ucwords(r34ics_date($args['formatmonthyear'], $m.'/1/'.$year));
					$month_label_shown = false;
					$month_uid = $ics_data['guid'] . '-' . $ym;
							
					// Build month's calendar
					if (isset($days)) {

						// Reverse?
						if ($args['reverse']) { krsort($days); }

						foreach ((array)$days as $day => $day_events) {
							$date = r34ics_date('Ymd', $m.'/'.$day.'/'.$year);
							$d = r34ics_date('d', $date);
							$dow = r34ics_date('w', $date);
							$wknum = r34ics_date('W', $date . ' +' . (1 - $start_of_week) . 'day'); // PHP week numbers start on Monday
							
							// Is this past/present/future?
							$rel2today = null;
							if ($date < $today) { $rel2today = 'past'; }
							elseif ($date == $today) { $rel2today = 'today'; }
							elseif ($date > $today) { $rel2today = 'future'; }
				
							// Pagination?
							if (!empty($pagination)) {
								if ($p_i == 0 && empty($pagination_open)) {
									?>
									<div class="ics-calendar-pagination" data-page="<?php echo intval($p_c); ?>" data-rel2today="<?php echo esc_attr($rel2today); ?>">
									<?php
									$p_c++; $p_i = 0; $pagination_open = true;
								}
							}

							// Pull out multi-day events and display them separately first
							foreach ((array)$day_events as $time => $events) {

								foreach ((array)$events as $event_key => $event) {

									// We're ONLY looking for multiday events right now
									if (empty($event['multiday'])) { continue; }

									// Give this instance its own unique ID, since multiple instances of a recurring event will have the same UID
									$multiday_instance_uid = $event['uid'] . '-' . $event['multiday']['start_date'];

									// Skip event if under the skip limit (but be sure to count it in $multiday_events_used!) 
									if (!empty($args['skip']) && $skip_i < $args['skip']) {
										if (!in_array($multiday_instance_uid, $multiday_events_used)) {
											$multiday_events_used[] = $multiday_instance_uid;
											$skip_i++;
										}
										continue;
									}

									// Have we used this event yet?
									if (!in_array($multiday_instance_uid, $multiday_events_used)) {

										$day_label = r34ics_multiday_date_label($date_format, $event, $args, $month_uid, $month_label);
										$day_uid = $ics_data['guid'] . '-' . r34ics_uid();

										// Display month label if needed
										if (empty($args['nomonthheaders']) && isset($month_label_shown) && empty($month_label_shown)) {
											?>
											<<?php echo esc_attr($args['htmltagmonth']); ?> class="ics-calendar-label" id="<?php echo esc_attr($month_uid); ?>"><?php echo wp_kses_post($month_label ?: ''); ?></<?php echo esc_attr($args['htmltagmonth']); ?>>
											<?php
											$month_label_shown = true;
										}
										?>
										<div class="ics-calendar-date-wrapper" data-date="<?php echo esc_attr(wp_strip_all_tags($day_label)); ?>" data-dow="<?php echo esc_attr($dow); ?>" data-wknum="<?php echo esc_attr($wknum); ?>" data-rel2today="<?php echo esc_attr($rel2today); ?>" data-events-count="1" data-feed-keys="<?php echo intval($event['feed_key']); ?>">
											<<?php echo esc_attr($args['htmltagdate']); ?> class="ics-calendar-date" id="<?php echo esc_attr($day_uid); ?>"><?php echo wp_kses_post($day_label ?: ''); ?></<?php echo esc_attr($args['htmltagdate']); ?>>
											<dl class="events" aria-labelledby="<?php echo esc_attr($day_uid); ?>">

												<?php
												$has_desc = r34ics_has_desc($args, $event);
										
												?><dd class="<?php echo esc_attr(r34ics_event_css_classes($event, $time, $args)); ?>" data-feed-key="<?php echo intval($event['feed_key']); ?>"<?php
													if (!empty($ics_data['colors'][$event['feed_key']]['base'])) { echo ' data-feed-color="' . esc_attr($ics_data['colors'][$event['feed_key']]['base']) . '"'; }
													if (!empty($event['categories'])) { echo ' data-categories="' . esc_attr($event['categories']) . '"'; }
												?>>
													<?php
													// Event label (title)
													echo wp_kses_post($R34ICS->event_label_html($args, $event, (!empty($has_desc) ? array('has_desc') : null)) ?: '');

													// Sub-label
													echo wp_kses_post($R34ICS->event_sublabel_html($args, $event, null) ?: '');

													// Description/Location/Organizer
													echo wp_kses_post($R34ICS->event_description_html($args, $event, null, $has_desc) ?: '');
													?>
												</dd><?php
										
												// We've now used this event
												$multiday_events_used[] = $multiday_instance_uid;
												$i++;
												if (!empty($args['count']) && $i >= intval($args['count'])) {
													echo '</dl></div>';
													if (!empty($pagination)) { echo '</div>'; $p_i = 0; $pagination_open = false; }
													break(5);
												}
												?>

											</dl>
										</div>
										<?php
										// Pagination?
										if (!empty($pagination)) { $p_i++; }
									}

									// Remove event from array (to skip day if it only has multi-day events)
									unset($day_events[$time][$event_key]);
								}

								// Remove time from array if all of its events have been removed
								if (empty($day_events[$time])) { unset($day_events[$time]); }

								// Pagination?
								if (!empty($pagination) && $p_i >= $pagination) { echo '</div>'; $p_i = 0; $pagination_open = false; }
							}
					
							// Skip day if all of its events were multi-day
							if (empty($day_events)) { continue; }
					
							// Loop through day events
							$all_day_indicator_shown = !empty($args['hidealldayindicator']);
							$day_label_shown = false;
							$day_events_count = r34ics_day_events_count($day_events);
							$day_feed_keys = r34ics_day_events_feed_keys($day_events, '|');
							$day_uid = $ics_data['guid'] . '-' . $ym . $d;
							foreach ((array)$day_events as $time => $events) {
								foreach ((array)$events as $event) {

									// We're NOT looking for multiday events right now (these should all be removed above already)
									if (!empty($event['multiday'])) { continue; }

									// Skip event if under the skip limit
									if (!empty($args['skip']) && $skip_i < $args['skip']) {
										$skip_i++; continue;
									}

									// Display month label if needed
									if (empty($args['nomonthheaders']) && empty($month_label_shown)) {
										?>
										<<?php echo esc_attr($args['htmltagmonth']); ?> class="ics-calendar-label" id="<?php echo esc_attr($month_uid); ?>"><?php echo wp_kses_post($month_label ?: ''); ?></<?php echo esc_attr($args['htmltagmonth']); ?>>
										<?php
										$month_label_shown = true;
									}
				
									// Show day label if not yet displayed
									if (empty($day_label_shown)) {
										$day_label = r34ics_date($date_format, $month.'/'.$day.'/'.$year);
										$day_uid = $ics_data['guid'] . '-' . $year . $month . $day;
										?>
										<div class="ics-calendar-date-wrapper" data-date="<?php echo esc_attr(wp_strip_all_tags($day_label)); ?>" data-dow="<?php echo esc_attr($dow); ?>" data-wknum="<?php echo esc_attr($wknum); ?>" data-rel2today="<?php echo esc_attr($rel2today); ?>" data-events-count="<?php echo intval($day_events_count); ?>" data-feed-keys="<?php echo esc_attr($day_feed_keys); ?>">
											<<?php echo esc_attr($args['htmltagdate']); ?> class="ics-calendar-date" id="<?php echo esc_attr($day_uid); ?>"><?php echo wp_kses_post($day_label ?: ''); ?></<?php echo esc_attr($args['htmltagdate']); ?>>
											<dl class="events" aria-labelledby="<?php echo esc_attr($day_uid); ?>">
										<?php
										$day_label_shown = true;
									}

									$has_desc = r34ics_has_desc($args, $event);
									if ($time == 'all-day') {

										if (empty($args['hidetimes']) && !$all_day_indicator_shown) {
								
											?><dt class="all-day-indicator" data-feed-key="<?php echo intval($event['feed_key']); ?>"<?php
												if (!empty($ics_data['colors'][$event['feed_key']]['base'])) { echo ' data-feed-color="' . esc_attr($ics_data['colors'][$event['feed_key']]['base']) . '"'; }
												if (!empty($event['categories'])) { echo ' data-categories="' . esc_attr($event['categories']) . '"'; }
											?>><?php esc_html_e('All Day', 'ics-calendar'); ?></dt><?php
									
											$all_day_indicator_shown = true;
										}
								
										?><dd class="<?php echo esc_attr(r34ics_event_css_classes($event, $time, $args)); ?>" data-feed-key="<?php echo intval($event['feed_key']); ?>"<?php
											if (!empty($ics_data['colors'][$event['feed_key']]['base'])) { echo ' data-feed-color="' . esc_attr($ics_data['colors'][$event['feed_key']]['base']) . '"'; }
											if (!empty($event['categories'])) { echo ' data-categories="' . esc_attr($event['categories']) . '"'; }
										?>>
											<?php
											// Event label (title)
											echo wp_kses_post($R34ICS->event_label_html($args, $event, (!empty($has_desc) ? array('has_desc') : null)) ?: '');

											// Sub-label
											echo wp_kses_post($R34ICS->event_sublabel_html($args, $event, null) ?: '');

											// Description/Location/Organizer
											echo wp_kses_post($R34ICS->event_description_html($args, $event, null, $has_desc) ?: '');
											?>
										</dd><?php
								
									}
									else {

										if (empty($args['hidetimes']) && !empty($event['start'])) {
								
											?><dt class="time" data-feed-key="<?php echo intval($event['feed_key']); ?>"<?php
												if (!empty($ics_data['colors'][$event['feed_key']]['base'])) { echo ' data-feed-color="' . esc_attr($ics_data['colors'][$event['feed_key']]['base']) . '"'; }
												if (!empty($event['categories'])) { echo ' data-categories="' . esc_attr($event['categories']) . '"'; }
											?>><?php
											echo wp_kses_post($event['start'] ?: '');
											if (!empty($event['end']) && $event['end'] != $event['start']) {
												if (empty($args['showendtimes'])) {
													?>
													<span class="end_time show_on_hover">&#8211; <?php echo wp_kses_post($event['end'] ?: ''); ?></span>
													<?php
												}
												else {
													?>
													<span class="end_time">&#8211; <?php echo wp_kses_post($event['end'] ?: ''); ?></span>
													<?php
												}
											}
											?></dt><?php
									
										}
								
										?><dd class="<?php echo esc_attr(r34ics_event_css_classes($event, $time, $args)); ?>" data-feed-key="<?php echo intval($event['feed_key']); ?>"<?php
											if (!empty($ics_data['colors'][$event['feed_key']]['base'])) { echo ' data-feed-color="' . esc_attr($ics_data['colors'][$event['feed_key']]['base']) . '"'; }
											if (!empty($event['categories'])) { echo ' data-categories="' . esc_attr($event['categories']) . '"'; }
										?>>
											<?php
											// Event label (title)
											echo wp_kses_post($R34ICS->event_label_html($args, $event, (!empty($has_desc) ? array('has_desc') : null)) ?: '');

											// Sub-label
											echo wp_kses_post($R34ICS->event_sublabel_html($args, $event, null) ?: '');

											// Description/Location/Organizer
											echo wp_kses_post($R34ICS->event_description_html($args, $event, null, $has_desc) ?: '');
											?>
										</dd><?php
								
									}
									$i++;
									if (!empty($args['count']) && $i >= intval($args['count'])) {
										if (!empty($day_label_shown)) {
											echo '</dl></div>';
											if (!empty($pagination)) { echo '</div>'; $p_i = 0; $pagination_open = false; }
										}
										break(5);
									}

									// Pagination?
									if (!empty($pagination)) { $p_i++; }
								}
							}
							if (!empty($day_label_shown)) {
								echo '</dl></div>';
							}
					
							// Pagination?
							if (!empty($pagination) && $p_i >= $pagination) { echo '</div>'; $p_i = 0; $pagination_open = false; }
						}
					}
				}
			}
			?>
		</article>

		<?php
		$calendar_output = ob_get_clean();
		
		// Pagination?
		if (!empty($pagination_html) && $p_c > 1) {
			if (in_array($args['paginationposition'], array('below','both'))) {
				$calendar_output .= $pagination_html;
			}
			else {
				$calendar_output = $pagination_html . $calendar_output;
			}
		}
		
		// Render calendar output
		echo wp_kses_post($calendar_output ?: '');
		
		// Color code key
		if (!empty($args['legendposition']) && $args['legendposition'] == 'below') {
			echo wp_kses(($R34ICS->color_key_html($args, $ics_data) ?: ''), r34ics_color_key_allowed());
		}
	
		// Actions after rendering calendar wrapper (can include additional template output)
		do_action('r34ics_display_calendar_after_wrapper', $view, $args, $ics_data);

	}
	?>

</section>
