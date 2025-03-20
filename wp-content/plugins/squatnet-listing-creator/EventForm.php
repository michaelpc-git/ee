<?php

namespace SquatnetListingCreator;

use \DateTime;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;
use Nette\Http\Session;
use SquatnetListingCreator\Locations;
use SquatnetListingCreator\RadarConnector;

require_once( ABSPATH . 'wp-admin/includes/media.php' );
require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/image.php' );
include( ABSPATH . 'wp-includes/pluggable.php' );

class EventForm {
	private Form $form;
	private \Twig\Environment $twig;
	private Locations $locations;
	private RadarConnector $radar;
	private Config $config;
	// TODO: Cache these from the API at some point? Feels brittle
	private $types = array(
		'ff3e2872-6140-4645-98f0-784d656a9c5c' => 'Gig',
		'e85a688d-03ac-4008-a3cb-1adb7e8f718a' => 'Action/Protest',
		'68197b93-2ece-4b0f-9a76-d9e99bda2603' => 'Film',
		'2a7f6975-4c01-4777-8611-dffe0306c06f' => 'Workshop',
		'8463bb01-e974-4785-9c2d-b95d87c9ee2d' => 'Presentation',
		'20a888f9-54c1-4767-8af1-40de3d1d2636' => 'Meeting',
		false                                  => 'Other',
	);
	// TODO: Cache these from the API at some point? Feels brittle
	private $prices = array(
		'dfb542fc-f5b5-491d-87be-95f1c0813d57' => 'Free',
		'9d943d0c-e2bf-408e-9110-4bfb044f60c0' => 'By donation',
		'7e7e09ce-138d-4c92-8e45-a5156c2a7176' => 'Other',
	);

	public function __construct(
		Locations $locations = null,
		RadarConnector $radar = null,
		Config $config = null
	) {

		$this->locations = $locations ? $locations : new Locations();
		$this->radar     = $radar ? $radar : new RadarConnector();
		$this->config    = $config ? $config : new Config();
		$this->form      = new Form();

		$loader     = new \Twig\Loader\FilesystemLoader( __DIR__ . '/views' );
		$this->twig = new \Twig\Environment( $loader );

		$twig_render = new \Twig\TwigFunction(
			'dump',
			function ( $arg ) {
				dump( $arg );
			}
		);

		$this->twig->addFunction( $twig_render );

		$form = $this->form;

		$form->addText( 'title', 'Event Title:' )
			->setRequired();
		$form->addText( 'start', 'Start Date:' )
			->setHtmlType( 'datetime-local' )
			->setRequired();
		$form->addText( 'end', 'End Date:' )
			->setHtmlType( 'datetime-local' )
			->setRequired();
		$form->addTextArea( 'description', 'Description (include full address of location):' );
		$form->addSelect( 'location', 'Location:', array_merge( array( null => 'If Location not found, choose "London".' ), $this->locations->getFormLocations() ) )->setRequired();
		$form->addUpload( 'poster', 'Poster:' )->addRule( $form::Image, 'Poster must be an image' );
		$form->addUpload( 'flyer', 'Flyer:' )->addRule( $form::Image, 'Flyer must be an image' );
		$form->addMultiSelect(
			'type',
			'Type of event:',
			$this->types
		);
		$form->addSelect(
			'price',
			'Admission:',
			$this->prices
		)->setRequired();
		$form->addText(
			'fee',
			'If other put your price here'
		);
		$form->addText( 'cop_check', 'Fill this in if you\'re a cop:' )
			->addRule( $form::BLANK, 'ACAB' );
		$form->addSubmit( 'submit', 'Submit' );
		// dump( [$form, $form->isSuccess()] );

		if ( $form->isSuccess() ) {
			$data       = $form->getValues();
			$date_start = new DateTime( $data['start'] );
			$date_end   = new DateTime( $data['end'] );
			$timezone   = new \DateTimezone( 'Europe/London' );
			$date_start->setTimezone( $timezone );
			$date_end->setTimezone( $timezone );
			$this->radar->authenticate();
			$uuid_data    = array( 'id' => $this->config->get( 'group_uuid' ) );
			$categories   = array_map(
				function ( $item ) {
					return array( 'id' => $item );
				},
				$data['type']
			);
			$request_data = array(
				'type'             => 'event',
				'title'            => $data['title'],
				'title_field'      => $data['title'],
				// TODO: Allow user to select language from WordPress interface
					  'language'   => 'en',
				'status'           => 0,
				'event_status'     => 'confirmed',
				'price'            => $data['fee'],
				'og_group_request' => $uuid_data ? $uuid_data : array(),
				'body'             => array(
					'value'   => $data['description'],
					'summary' => '',
					'format'  => 'plain_text',
				),
				'offline'          => array( array( 'id' => $data['location'] ) ),
				'date_time'        => array(
					array(
						'time_start' => $date_start->format( 'Y-m-d H:i:s' ),
						'time_end'   => $date_end->format( 'Y-m-d H:i:s' ),
						// TODO: Make configurable based on location
						'timezone'   => 'Europe/London',
					),
				),
				'category'         => $categories,
				'price_category'   => array( array( 'id' => $data['price'] ) ),

			);
			try {
				$this->radar->post(
					'node',
					$request_data
				);
				$this->insertRecord( $data );
				$this->form->reset();

				wp_redirect( $_POST['_wp_http_referer'] . '?form_success=true' ); // redirect back to your contact form
			} catch ( RequestException $e ) {
			}
		}
	}

	public function insertRecord( ArrayHash $data ) {

		// $flyer  = $data->flyer ? $data->flyer : $data->poster;
		$poster = $data->poster ? $data->poster : $data->flyer;

		// if ( $flyer && $flyer->hasFile() ) {
		// 	$flyer_file = array(
		// 		'name'     => $flyer->name,
		// 		'type'     => $flyer->getContentType(),
		// 		'tmp_name' => $flyer->getTemporaryFile(),
		// 		'size'     => $flyer->size,
		// 	);
		// 	$sideload   = wp_handle_sideload( $flyer_file, array( 'test_form' => false ) );
		// 	$flyer_id   = wp_insert_attachment(
		// 		array(
		// 			'guid'           => $sideload['url'],
		// 			'post_mime_type' => $sideload['type'],
		// 			'post_title'     => basename( $sideload['file'] ),
		// 			'post_content'   => '',
		// 			'post_status'    => 'inherit',
		// 		),
		// 		0
		// 	);
		// }

		if ( $poster && $poster->hasFile() ) {
			$poster_file = array(
				'name'     => $poster->name,
				'type'     => $poster->getContentType(),
				'tmp_name' => $poster->getTemporaryFile(),
				'size'     => $poster->size,
			);
			$sideload    = wp_handle_sideload( $poster_file, array( 'test_form' => false ) );
			$poster_id   = wp_insert_attachment(
				array(
					'guid'           => $sideload['url'],
					'post_mime_type' => $sideload['type'],
					'post_title'     => basename( $sideload['file'] ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				),
				0
			);
		}

		$request_id = wp_insert_post(
			array(
				'post_type'    => 'squatnet_event',
				'post_title'   => 'Request for event: ' . $data->title,
				'post_content' => $data->description,
			)
		);

		$start_date = new DateTime( $data->start );
		$end_date   = new DateTime( $data->end );

		update_post_meta( $request_id, 'start', $start_date->format( 'Y-m-d H:i:s' ) );
		update_post_meta( $request_id, 'end', $end_date->format( 'Y-m-d H:i:s' ) );
		update_post_meta(
			$request_id,
			'location',
			$this->locations->getFormLocations()[ $data->location ]
		);
		update_post_meta(
			$request_id,
			'price',
			$data->fee
		);
		// update_post_meta( $request_id, 'flyer', $flyer_id ? $flyer_id : $poster_id );
		if ( isset($poster_id) ) {
			update_post_meta( $request_id, 'poster', $poster_id );
		}

	}

	public function render(): string {
		$nonce = wp_nonce_field(
			SQUATNET_WP_LC_NS . '_create_event',
			'_wpnonce',
			true,
			false
		);

		if ( ! $this->config->get( 'password' ) || ! $this->config->get( 'username' ) || ! $this->config->get( 'group_uuid' ) || ! $this->locations->getFormLocations() ) {
			return '<p><strong>Your squatnet settings are not set up yet, please configure your account on the settings page.</strong></p>';
		}

		// This shows all form data on the same page as the form. Debugging only!

		return $this->twig->render(
			'form.twig',
			array(
				'form'      => $this->form,
				'nonce'     => $nonce,
				'prefix'    => SQUATNET_WP_LC_NS,
				'submitted' => isset( $_GET['form_success'] ),
			)
		);
		// return $this->form->__toString();
	}
}
