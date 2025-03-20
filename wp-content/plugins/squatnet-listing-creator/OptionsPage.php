<?php

namespace SquatnetListingCreator;

use SquatnetListingCreator\Config;
use SquatnetListingCreator\Locations;

class OptionsPage {

	private Config $config;

	private Locations $locations;

	public function __construct( Config $config = null, Locations $locations = null ) {
		$this->config        = $config ? $config : new Config();
		$this->locations     = $locations ? $locations : new Locations();
		$this->fieldsettings = json_decode(
			file_get_contents( __DIR__ . '/optionfields.json' ),
			true
		);
		add_action(
			'admin_menu',
			array( $this, 'addMenu' )
		);
		add_action(
			'admin_init',
			array( $this, 'initSettings' )
		);
		add_action( 'updated_option', array( $this, 'locationsRecache' ), 10, 3 );
	}

	public function locationsRecache( string $option_name, $oldval, $newval ) {
		if ( $option_name === 'SquatnetListingCreator_settings' && isset( $newval['geographic_area'] ) &&
			(
				isset( $oldval['geographic_area'] ) &&
				$oldval['geographic_area'] !== $newval['geographic_area'] || ! isset( $oldval['geographic_area'] )
			)
		) {
			$this->locations->cacheLocations( $newval['geographic_area'] );
		}
	}

	public function addMenu() {
		add_options_page( 'Squatnet Listing Creator', 'Squatnet Listing Creator', 'manage_options', 'squatnet_listing_creator', array( $this, 'renderPage' ) );
	}

	public function initSettings() {
		register_setting( 'pluginPage', SQUATNET_WP_LC_NS . '_settings' );
		foreach ( $this->fieldsettings as $key => $field_data ) {
			$this->addSettingsData( $field_data );
		}
	}

	public function addSettingsData( $field_data ) {
		$section_slug = SQUATNET_WP_LC_NS . '_pluginPage_section_' . $field_data['slug'];
		add_settings_section(
			$section_slug,
			__( 'Important Settings', SQUATNET_WP_LC_NS ),
			array( $this, 'renderSection' ),
			'pluginPage',
			$field_data
		);

		foreach ( $field_data['fields'] as $slug => $field ) {
			$this->addSettingsField( $section_slug, $slug, $field );
		}
	}

	public function addSettingsField( string $section_slug, string $slug, array $data ) {

		add_settings_field(
			$data['slug'],
			__(
				$data['name'],
				SQUATNET_WP_LC_NS
			),
			array( $this, 'renderField' ),
			'pluginPage',
			$section_slug,
			$data
		);
	}

	public function renderSection( array $data ) {
		if ( isset( $data['desc'] ) ) {
			echo __( $data['desc'], 'SquatnetListingCreator' );
		}
	}

	public function renderField( array $args ) {
		if ( in_array( $args['type'], array( 'text', 'password', 'email', 'url' ) )
		) {
			$this->fieldText( $args );
		} elseif ( $args['type'] === 'geoselect' ) {
			$this->fieldGeoSelect( $args );
		}
	}

	public function fieldText( array $args ) {
		$current_val = $this->config->get( $args['slug'] );
		if ( ! $current_val && isset( $args['default'] ) ) {
			$current_val = $args['default'];
		}
		?>
	<input 
	  type="<?php echo $args['type']; ?>"
	  name="<?php echo SQUATNET_WP_LC_NS; ?>_settings[<?php echo $args['slug']; ?>]" 
	  value="<?php echo $current_val; ?>">
		<?php
		if ( isset( $args['desc'] ) ) {
			?>
			<p><em><?php echo $args['desc']; ?></em></p>
			<?php
		}
	}

	public function fieldGeoSelect( array $args ) {
		$options   = get_option( SQUATNET_WP_LC_NS . '_settings' );
		$value     = isset( $options[ $args['slug'] ] ) ? $options[ $args['slug'] ] : null;
		$locations = $this->locations->getAll( 'options' );
		$name      = false;
		if ( $locations ) {
			$name = array_column( $locations, 'filter' );
		} else {
			?>
			<strong style="color: red;">Pease add you squatnet account information before selecting a value. You will need to select a locality to allow your users to add events.</strong>
			<?php
			return;
		}
		$locations && array_multisort( $name, SORT_ASC, $locations );
		$locations = $locations !== false ? $locations : array();
		?>
	<select name='<?php echo SQUATNET_WP_LC_NS; ?>_settings[<?php echo $args['slug']; ?>]'>
		<?php foreach ( $locations as $index => $location ) : ?>
		  <option 
			value="<?php echo $location['filter']; ?>" 
			<?php selected( $value, $location['filter'] ); ?>
		  >
			<?php echo $location['formatted']; ?>
		  </option>
		<?php endforeach ?>
	</select>
		<?php
		if ( isset( $args['desc'] ) ) {
			?>
			<p><em><?php echo $args['desc']; ?></em></p>
			<?php
		}
	}

	public function renderPage() {
		?>
	<form action='options.php' method='post'>

	  <h2>Squatnet Listing Creator</h2>

		<?php
			settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button();
		?>

	</form>
		<?php
	}
}
