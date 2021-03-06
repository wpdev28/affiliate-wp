<?php
/**
 * Integrations: WP Forms
 *
 * @package     AffiliateWP
 * @subpackage  Integrations
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2
 */

/**
 * Implements an integration for WP Forms.
 *
 * @since 1.2
 *
 * @see Affiliate_WP_Base
 */
class Affiliate_WP_WPForms extends Affiliate_WP_Base {

	/**
	 * The context for referrals. This refers to the integration that is being used.
	 *
	 * @access  public
	 * @since   1.2
	 */
	public $context = 'wpforms';

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   2.0
	*/
	public function init() {

		add_action( 'wpforms_process_complete', array( $this, 'add_pending_referral' ), 10, 4 );
		add_action( 'wpforms_paypal_standard_process_complete', array( $this, 'mark_referral_complete' ), 10, 4 );
		add_action( 'wpforms_stripe_process_complete', array( $this, 'mark_referral_complete' ), 10, 4 );
		add_action( 'wpforms_form_settings_general', array( $this, 'add_settings' ) );
		add_filter( 'affwp_referral_reference_column', array( $this, 'reference_link' ), 10, 2 );

	}

	/**
	 * Register the form-specific settings
	 *
	 * @since  2.0
	 * @return void
	 */
	public function add_settings( $instance ) {

		$options = array();
		foreach( affwp_get_referral_types() as $type_id => $type ) {
			$options[ $type_id ] =  $type['label'];
		}

		//  Enable affiliate referral creation for this form
		wpforms_panel_field(
			'checkbox',
			'settings',
			'affwp_allow_referrals',
			$instance->form_data,
			__( 'Allow referrals', 'affiliate-wp' )
		);

		wpforms_panel_field(
			'select',
			'settings',
			'affwp_referral_type',
			$instance->form_data,
			__( 'Referral type', 'affiliate-wp' ),
			array(
				'options' => $options
			)
		);
	}

	/**
	 * Records a pending referral when a pending payment is created
	 *
	 * @access  public
	 * @since   2.0
	*/
	public function add_pending_referral( $fields, $entry, $form_data, $entry_id ) {

		$affiliate_id = $this->affiliate_id;

		// Return if the customer was not referred or the affiliate ID is empty.
		if ( ! $this->was_referred() && empty( $affiliate_id ) ) {
			return; // Referral not created because affiliate was not referred.
		}

		// Entry ID.
		if ( ! $entry_id ) {
			$entry_id = strtolower( md5( uniqid() ) );
		}

		// get the customer email.
		foreach ( $fields as $field ) {
			if ( 'email' === $field['type'] ) {
				$this->email = $field['value'];
				break;
			}
		}

		// Create draft referral.
		$referral_id = $this->insert_draft_referral(
			$this->affiliate_id,
			array(
				'reference' => $entry_id,
			)
		);
		if ( ! $referral_id ) {
			$this->log( 'Draft referral creation failed.' );
			return;
		}

		// prevent referral creation unless referrals enabled for the form.
		if ( ! isset( $form_data['settings']['affwp_allow_referrals'] ) || ! $form_data['settings']['affwp_allow_referrals'] ) {
			$this->log( 'Referral not created because referrals are not enabled onform.' );
			$this->mark_referral_failed( $referral_id );
			return;
		}

		// Customers cannot refer themselves.
		if ( $this->is_affiliate_email( $this->email, $affiliate_id ) ) {
			$this->log( 'Referral not created because affiliate\'s own account was used.' );
			$this->mark_referral_failed( $referral_id );
			return;
		}

		$this->referral_type = isset( $form_data['settings']['affwp_referral_type'] ) ? $form_data['settings']['affwp_referral_type'] : 'sale';

		// get referral total.
		$total = 0;
		if ( function_exists( 'wpforms_get_total_payment' ) ) {
			$total = wpforms_get_total_payment( $fields );
		}
		$referral_total = $this->calculate_referral_amount( $total, $entry_id );

		// use form title as description.
		$description = $form_data['settings']['form_title'];

		// use products purchased as description.
		if ( $this->get_product_description( $fields ) ) {
			$description = $this->get_product_description( $fields );
		}

		// Hydrates the previously created referral.
		$this->hydrate_referral(
			$referral_id,
			array(
				'status'      => 'pending',
				'amount'      => $referral_total,
				'description' => $description,
			)
		);
		$this->log( sprintf( 'WPForms referral #%d updated to pending successfully.', $referral_id ) );

		// set the referral to "unpaid" if there's no total.
		if ( empty( $referral_total ) || 0 == $total ) {
			$this->complete_referral( $entry_id );
		}
	}

	/**
	 * Sets a referral to unpaid when payment is completed
	 *
	 * @access  public
	 * @since   2.0
	*/
	public function mark_referral_complete( $fields, $form_data, $entry_id, $data ) {
		$this->complete_referral( $entry_id );
	}

	/**
	 * Sets up the reference link in the Referrals table
	 *
	 * @access  public
	 * @since   2.0
	*/
	public function reference_link( $reference = 0, $referral ) {

		if ( empty( $referral->context ) || 'wpforms' != $referral->context ) {
			return $reference;
		}

		if ( ! $reference || 32 == strlen( $reference ) ) {
			return '';
		}

		$url = admin_url( 'admin.php?page=wpforms-entries&view=details&entry_id=' . $reference );

		return '<a href="' . esc_url( $url ) . '">' . $reference . '</a>';
	}

	/**
	 * Builds an array of all the products purchased in the form
	 *
	 * @access  public
	 * @since   2.0
	*/
	public function get_product_description( $fields = array() ) {

		$description = array();

		// get the customer email
		foreach ( $fields as $field ) {

			// single items
			if ( $field['type'] === 'payment-single' ) {
				$description[] = $field['name'];
			}

			// multiple items
			if ( $field['type'] === 'payment-multiple' ) {
				$description[] = $field['name'] . ' | ' . $field['value_choice'];
			}

		}

		return implode( ', ', $description );

	}

	/**
	 * Runs the check necessary to confirm this plugin is active.
	 *
	 * @since 2.5
	 *
	 * @return bool True if the plugin is active, false otherwise.
	 */
	function plugin_is_active() {
		return function_exists( 'wpforms' );
	}
}

	new Affiliate_WP_WPForms;