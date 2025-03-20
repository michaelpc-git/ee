<?php

function r34ics_getting_started_go_pro_html() {
	ob_start();
	?>
	<div class="r34ics-pro-mo">
		<h3>
			<?php esc_html_e('Do even more with...', 'ics-calendar'); ?><br />
			<a href="https://icscalendar.com/pro" target="_blank"><img src="<?php echo esc_url(plugins_url('assets/ics-calendar-pro-logo-2023.svg', dirname(dirname(__FILE__)))); ?>" alt="ICS Calendar Pro" width="240" height="84" /></a><span>&amp;</span><a href="https://icscalendar.com/pro" target="_blank"><img src="<?php echo esc_url(plugins_url('assets/ics-events-logo.svg', dirname(dirname(__FILE__)))); ?>" alt="ICS Events" width="200" height="84" /></a>
		</h3>
		
		<div class="r34ics-pro-features">
			<div>
				<h4><?php esc_html_e('ICS Events', 'ics-calendar'); ?></h4>
				<p><?php
				/* translators: 1: HTML tag 2: HTML tag 3: Plugin name (do not translate) */
				printf(esc_html__('%1$sNew!%2$s Turns %3$s into a full calendar system. Create and manage events directly in WordPress and even integrate them seamlessly with your existing feeds.', 'ics-calendar'), '<strong style="color: crimson;">', '</strong>', 'ICS Calendar Pro');
				?></p>
				<h4><?php esc_html_e('Additional Views', 'ics-calendar'); ?></h4>
				<p><?php esc_html_e('Full, Up Next, Masonry, Month with Sidebar, Widget, and more.', 'ics-calendar'); ?></p>
				<h4><?php esc_html_e('Customizer', 'ics-calendar'); ?></h4>
				<p><?php esc_html_e('Easily modify your calendar color palettes, fonts, and more, site-wide.', 'ics-calendar'); ?></p>
			</div>
			<div>
				<h4><?php esc_html_e('Calendar Builder', 'ics-calendar'); ?></h4>
				<p><?php esc_html_e('Configure all calendar settings with an easy visual interface... no need to manually type shortcodes.', 'ics-calendar'); ?></p>
				<h4><?php esc_html_e('Enhanced Features', 'ics-calendar'); ?></h4>
				<p><?php esc_html_e('Additional capabilities are added to the core Month, Basic, List and Week views.', 'ics-calendar'); ?></p>
				<p class="large"><?php
				/* translators: 1. Link (do not translate) */
				printf(esc_html__('Visit %1$s to learn more.', 'ics-calendar'), '<strong><a href="https://icscalendar.com/pro/" target="_blank">icscalendar.com/pro</a></strong>');
				?></p>
			</div>
		</div>

		<p class="aligncenter"><a href="https://icscalendar.com/pro/" target="_blank" class="button button-primary" style="font-size: larger;"><?php esc_html_e('Go PRO!', 'ics-calendar'); ?></a></p>
	</div>
	<?php
	$output = apply_filters('r34ics_getting_started_go_pro_html', ob_get_clean());
	return $output;
}

?>

<div class="inside" id="getting-started" data-current="current">

	<h2><?php esc_html_e('Getting Started', 'ics-calendar'); ?></h2>
	
	<?php
	if (get_option('r34ics_admin_first_run')) {
		?>
		<p><mark class="alert"><?php
		/* translators: 1: Plugin name (do not translate) and HTML tags 2. HTML tag 3. HTML tag 4. HTML tag 5. HTML tag */
		printf(esc_html__('Thank you for installing %1$s. Before creating your first calendar shortcode, please visit your %2$sGeneral Settings%3$s page and verify that your site language, timezone and date/time format settings are correct. See our %4$sUser Guide%5$s for more information.', 'ics-calendar'), '<strong>ICS Calendar</strong>', '<a href="' . esc_url(admin_url('options-general.php')) . '">', '</a>', '<a href="https://icscalendar.com/general-wordpress-settings/" target="_blank">', '</a>')
		?></mark></p>
		<?php
		update_option('r34ics_admin_first_run', false);
	}
	?>

	<p><?php
	/* translators: 1. HTML tag 2. HTML tag */
	printf(esc_html__('An extensive %1$sUser Guide%2$s is available on our website. Below are some quick links to key resources in the documentation.', 'ics-calendar'), '<strong><a href="https://icscalendar.com/user-guide/">', '</a></strong>');
	?></p>
	
	<ul>
		<?php do_action('r34ics_admin_getting_started_links_before'); ?>
		<li><a href="https://icscalendar.com/getting-started/" target="_blank"><?php esc_html_e('Getting Started', 'ics-calendar'); ?></a></li>
		<li><a href="https://icscalendar.com/general-wordpress-settings/" target="_blank"><?php esc_html_e('General WordPress Settings', 'ics-calendar'); ?></a></li>
		<li><a href="https://icscalendar.com/shortcode-overview/" target="_blank"><?php esc_html_e('Shortcode Overview', 'ics-calendar'); ?></a></li>
		<li><a href="https://icscalendar.com/shortcode-builder/" target="_blank"><?php esc_html_e('Shortcode Builder', 'ics-calendar'); ?></a></li>
		<li><a href="https://icscalendar.com/icsdocs/" target="_blank"><?php esc_html_e('Shortcode Parameters Reference', 'ics-calendar'); ?></a></li>
		<li><a href="https://icscalendar.com/faqs-and-tips/" target="_blank"><?php esc_html_e('FAQs and Tips', 'ics-calendar'); ?></a></li>
		<li><a href="https://icscalendar.com/css-guide/" target="_blank"><?php esc_html_e('CSS Guide', 'ics-calendar'); ?></a></li>
		<li><a href="https://icscalendar.com/developer/" target="_blank"><?php esc_html_e('Developer Resources', 'ics-calendar'); ?></a></li>
		<?php do_action('r34ics_admin_getting_started_links_after'); ?>
	</ul>
	
	<?php echo wp_kses_post(r34ics_getting_started_go_pro_html()); ?>

</div>
