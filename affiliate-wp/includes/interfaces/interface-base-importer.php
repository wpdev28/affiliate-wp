<?php
/**
 * Base Importer Interface
 *
 * @package     AffiliateWP
 * @subpackage  Core/Interfaces
 * @copyright   Copyright (c) 2017, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.0
 */

namespace AffWP\Utils\Importer;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Promise for structuring importers.
 *
 * @since 2.0
 */
interface Base {

	/**
	 * Determines whether the current user can perform an import.
	 *
	 * @access public
	 * @since  2.0
	 *
	 * @return bool Whether the current user can perform an import.
	 */
	public function can_import();

	/**
	 * Prepares the data for import.
	 *
	 * @access public
	 * @since  2.0
	 *
	 * @return array[] Multi-dimensional array of data for import.
	 */
	public function get_data();

	/**
	 * Performs the import process.
	 *
	 * @access public
	 * @since  2.0
	 *
	 * @return void
	 */
	public function import();

}
