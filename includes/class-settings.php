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
	 *
	 * @var RevisionStrike $instance
	 */
	protected $instance;

	/**
	 * A cached copy of the plugin options array.
	 *
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

		add_settings_field(
			'revision-strike-keep',
			__( 'Revisions to Keep', 'revision-strike' ),
			array( $this, 'keep_field' ),
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
			absint( $this->get_option( 'days' ) ),
			esc_html_x( 'Days', 'Label for revision-strike[days]', 'revision-strike' )
		);

		printf(
			'<p class="description">%s</p>',
			esc_html__(
				'A post must be published at least this many days before its revisions can be removed.',
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
			absint( $this->get_option( 'limit' ) ),
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
	 * Generate the revision-strike[keep] field.
	 */
	public function keep_field() {
		printf(
			'<input name="revision-strike[keep]" id="revision-strike-keep" type="number" class="small-text" value="%d" /> %s',
			absint( $this->get_option( 'keep' ) ),
			esc_html_x( 'Revisions', 'Label for revision-strike[keep]', 'revision-strike' )
		);

		printf(
			'<p class="description">%s</p>',
			esc_html__(
				'Keep at least this many revisions per post.',
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
			'days'  => $this->get_option( 'days' ),
			'limit' => $this->get_option( 'limit' ),
			'keep'  => $this->get_option( 'keep' ),
		);
		$instance = $this->instance;

		require_once dirname( __FILE__ ) . '/tools.php';
	}

	/**
	 * Get an option from the plugin settings.
	 *
	 * @param string $option  The option name.
	 * @param mixed  $default Optional. The default value for this option. Default is null, which
	 *                        will pull its value from $this->instance->defaults.
	 */
	public function get_option( $option, $default = null ) {
		if ( null === $this->options ) {
			$this->options = get_option( 'revision-strike', array() );
		}
		$defaults = $this->instance->get_defaults();

		if ( isset( $this->options[ $option ] ) ) {
			return $this->options[ $option ];

		} elseif ( null === $default && isset( $defaults[ $option ] ) ) {
			return $defaults[ $option ];

		} else {
			return $default;
		}
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
				$input['limit'] = absint( $this->instance->defaults['limit'] );
			}
		}

		if ( isset( $input['keep'] ) ) {
			$input['keep'] = absint( $input['keep'] );
			if ( 0 === $input['keep'] ) {
				$input['keep'] = absint( $this->instance->defaults['keep'] );
			}
		}

		return $input;
	}
}
