<?php
/**
 * Mock class for WP-CLI-related utilities.
 */

namespace cli;

if ( class_exists( 'Table' ) ) {
	return;
}

class Table {

	public function addRow() {}
	public function display() {}
	public function setHeaders() {}

}