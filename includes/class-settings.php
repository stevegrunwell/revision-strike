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
	 * @var array A cache of plugin options.
	 */
	protected $options;

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
	 * Add the Tools > Revision Strike page.
	 */
	public function add_tools_page() {
		add_management_page(
			__( 'Revision Strike', 'revision-strike' ),
			_x( 'Revision Strike', 'Tools menu link', 'revision-strike' ),
			'edit_published_posts',
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
	 * Generate the Tools > Revision Strike page.
	 */
	public function tools_page() {
		$defaults = array(
			'days' => 30,
		);

		/** This filter is defined in includes/class-revision-strike.php. */
		$defaults['days'] = apply_filters( 'revisionstrike_post_types', $defaults['days'] );
?>

	<div class="wrap">
		<div id="icon-tools" class="icon32"></div>
		<h2><?php esc_html_e( 'Revision Strike', 'revision-strike' ); ?></h2>

		<form method="POST" action="<?php echo wp_nonce_url( 'tools.php?page=revision-strike', 'revision-strike' ); ?>">
			<p><?php esc_html_e(
				'Revision Strike will remove old revisions from the post database.',
				'revision-strike'
			); ?></p>

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Days', 'revision-strike' ); ?></th>
					<td>
						<input name="days" type="number" class="small-text" value="<?php echo absint( $defaults['days'] ); ?>" />
						<p class="description"><?php esc_html_e(
							'Revisions will be removed from posts that have been published at least this many days.',
							'revision-strike'
						); ?></p>
					</td>
				</tr>
			</table>

			<?php echo submit_button( __( 'Strike Revisions', 'revision-strike' ) ); ?>
		</form>
	</div>

<?php
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
