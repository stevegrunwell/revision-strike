<?php
/**
 * Primary plugin functionality.
 *
 * @package Revision Strike
 * @author Steve Grunwell
 */

class RevisionStrike {

	/**
	 * @var RevisionStrikeSettings $settings The plugin settings.
	 */
	public $settings;

	/**
	 * The action called to trigger the clean-up process.
	 */
	const STRIKE_ACTION = 'revisionstrike_strike_old_revisions';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->settings = new RevisionStrikeSettings;

		$this->add_hooks();
	}

	/**
	 * Register hooks within WordPress.
	 */
	public function add_hooks() {
		add_action( self::STRIKE_ACTION, array( $this, 'strike' ) );
		add_action( 'admin_init', array( $this->settings, 'add_settings_section' ) );
	}

	/**
	 * Clean up ("strike") post revisions for posts older than a certain number of seconds.
	 *
	 * @param array $args {
	 *   Optional. An array of arguments.
	 *
	 *   @type int    $days      The number of days old a post must be in order for its revisions to
	 *                           be struck. Default is 30.
	 *   @type int    $limit     The maximum number of posts to delete in a single pass (when run as
	 *                           cron, per day). Default is 50.
	 *   @type string $post_type A comma-separated list of post types for which the revisions should
	 *                           be purged. By default, only revisions for posts will be purged.
	 * }
	 */
	public function strike( $args = array() ) {
		$default_args = array(
			'days'       => $this->settings->get_option( 'days', 30 ),
			'limit'      => 50,
			'post_type' => 'post',
		);
		$args         = wp_parse_args( $args, $default_args );

		// Collect the revision IDs
		$revision_ids = $this->get_revision_ids( $args['days'], $args['limit'], $args['post_type'] );

		if ( ! empty( $revision_ids ) ) {
			foreach ( $revision_ids as $revision_id ) {
				wp_delete_post_revision( $revision_id );
			}
		}
	}

	/**
	 * Find revisions eligible to be removed from the database.
	 *
	 * @global $wpdb
	 *
	 * @param int $days        The number of days since a post's publish date that must pass before
	 *                         we can purge the post revisions.
	 * @param int $limit       The maximum number of revision IDs to retrieve.
	 * @param array $post_type The post types for which revisions should be located.
	 *
	 * @return array An array of post IDs (unless 'fields' is manipulated in $args).
	 */
	protected function get_revision_ids( $days, $limit, $post_type ) {
		global $wpdb;

		// Return early if we don't have any eligible post types
		if ( ! $post_type ) {
			return array();
		}

		$post_type    = array_map( 'trim', explode( ',', $post_type ) );
		$revision_ids = $wpdb->get_col( $wpdb->prepare(
			"
			SELECT r.ID FROM $wpdb->posts r
			LEFT JOIN $wpdb->posts p ON r.post_parent = p.ID
			WHERE r.post_type = 'revision' AND p.post_type IN ('%s') AND p.post_date < %s
			LIMIT %d
			",
			implode( "', '", $post_type ),
			date( 'Y-m-d', time() - ( absint( $days ) * DAY_IN_SECONDS ) ),
			absint( $limit )
		) );

		return array_map( 'absint', $revision_ids );
	}

}
