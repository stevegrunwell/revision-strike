<?php
/**
 * WordPress Settings API integration.
 *
 * @package Revision Strike
 * @author Steve Grunwell
 */

class RevisionStrikeSettings {

	/**
	 * @var array A cache of plugin options.
	 */
	protected $options;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->add_settings_section();
	}

	/**
	 * Add plugin settings sections.
	 */
	public function add_settings_section() {
		register_setting( 'writing', 'revision-strike', array( $this, 'sanitize_settings' ) );

		add_settings_section(
			'revision-strike',
			__( 'Revision Strike', 'revision-strike' ),
			null,
			'writing'
		);

		add_settings_field(
			'revision-strike-days',
			__( 'Expire Revisions after X Days', 'revision-strike' ),
			array( $this, 'days_field' ),
			'writing',
			'revision-strike'
		);
	}

	/**
	 * Generate the revision-strike[days] field.
	 */
	public function days_field() {
		printf(
			'<input name="revision-strike[days]" id="revision-strike-days" type="number" class="small-text" value="%d" /> %s',
			$this->get_option( 'days', 30 ),
			esc_html_x( 'Days', 'Label for revision-strike[days]', 'revision-strike' )
		);

		printf(
			'<p class="description">%s</p>',
			esc_html__(
				'Revisions on posts older than this many days will periodically purged.',
				'revision-strike'
			)
		);
	}

	/**
	 * Get an option from the plugin settings.
	 *
	 * @param string $option The option name.
	 * @param mixed $default Optional. The default value for this option. Default is an empty string.
	 */
	public function get_option( $option, $default = '' ) {
		if ( null === $this->options ) {
			$this->options = get_option( 'revision-strike', array() );
		}

		return isset( $this->options[ $option ] ) ? $this->options[ $option ] : $default;
	}

	/**
	 * Sanitize callback for the settings section.
	 *
	 * @param array $input Input to be sanitized.
	 * @return array The filtered input.
	 */
	public function sanitize_settings( $input ) {
		if ( isset( $input['days'] ) ) {
			$input['days'] = absint( $input['days'] );
		}

		return $input;
	}

}