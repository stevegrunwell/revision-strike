<?php
/**
 * WordPress Settings API integration.
 *
 * @package Revision Strike
 * @author Steve Grunwell
 */

/**
 * Plugin configuration.
 */
class RevisionStrikeSettings {

	/**
	 * The current RevisionStrike instance.
	 * @var RevisionStrike $instance
	 */
	protected $instance;

	/**
	 * A cached copy of the plugin options array.
	 * @var array $options
	 */
	protected $options;

	/**
	 * Class constructor.
	 *
	 * @param RevisionStrike $instance The RevisionStrike instance to which this object belongs.
	 */
	public function __construct( $instance = null ) {
		$this->instance = $instance;
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

		add_settings_field(
			'revision-strike-limit',
			__( 'Revisions per Day', 'revision-strike' ),
			array( $this, 'limit_field' ),
			'writing',
			'revision-strike'
		);
	}

	/**
	 * Add the Tools > Revision Strike page.
	 */
	public function add_tools_page() {
		add_management_page(
			__( 'Revision Strike', 'revision-strike' ),
			_x( 'Revision Strike', 'Tools menu link', 'revision-strike' ),
			'edit_others_posts',
			'revision-strike',
			array( $this, 'tools_page' )
		);
	}

	/**
	 * Generate the revision-strike[days] field.
	 */
	public function days_field() {
		printf(
			'<input name="revision-strike[days]" id="revision-strike-days" type="number" class="small-text" value="%d" /> %s',
			absint( $this->get_option( 'days', 30 ) ),
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
	 * Generate the revision-strike[limit] field.
	 */
	public function limit_field() {
		printf(
			'<input name="revision-strike[limit]" id="revision-strike-limit" type="number" class="small-text" value="%d" /> %s',
			absint( $this->get_option( 'limit', 50 ) ),
			esc_html_x( 'Revisions', 'Label for revision-strike[limit]', 'revision-strike' )
		);

		printf(
			'<p class="description">%s</p>',
			esc_html__(
				'The maximum number of revisions to be removed each day. This works best when the value is higher than the average number of daily revisions.',
				'revision-strike'
			)
		);
	}

	/**
	 * Generate the Tools > Revision Strike page.
	 *
	 * This method works by setting the $default configuration, then loading tools.php, which is a
	 * more procedural file.
	 */
	public function tools_page() {
		$defaults = array(
			'days'  => $this->get_option( 'days', 30 ),
			'limit' => $this->get_option( 'limit', 50 ),
		);
		$instance = $this->instance;

		require_once dirname( __FILE__ ) . '/tools.php';
	}

	/**
	 * Get an option from the plugin settings.
	 *
	 * @param string $option  The option name.
	 * @param mixed  $default Optional. The default value for this option. Default is an empty string.
	 */
	public function get_option( $option, $default = '' ) {
		if ( null === $this->options ) {
			$this->options = get_option( 'revision-strike', array() );
		}

		return isset( $this->options[ $option ] ) ? $this->options[ $option ] : $default;
	}

	/**
	 * Sanitize callback for the 'revision-strike' settings section.
	 *
	 * @param array $input Input to be sanitized.
	 * @return array The filtered input.
	 */
	public function sanitize_settings( $input ) {
		if ( isset( $input['days'] ) ) {
			$input['days'] = absint( $input['days'] );
		}

		if ( isset( $input['limit'] ) ) {
			$input['limit'] = absint( $input['limit'] );
			if ( 0 === $input['limit'] ) {
				$input['limit'] = 50;
			}
		}

		return $input;
	}

}
