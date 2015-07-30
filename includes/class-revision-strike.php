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
	 * @var int $expiration_threshold The cached number of days since a post's publishing before its
	 *                                revisions can be removed.
	 */
	protected $expiration_threshold;

	/**
	 * The action called to trigger the clean-up process.
	 */
	const STRIKE_ACTION = 'revisionstrike_strike_old_revisions';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->add_hooks();
	}

	/**
	 * Register hooks within WordPress.
	 */
	public function add_hooks() {
		add_action( self::STRIKE_ACTION, array( $this, 'strike' ) );
		add_action( 'admin_init', array( $this, 'settings' ) );
	}

	/**
	 * Register the plugin settings page.
	 */
	public function settings() {
		$this->settings = new RevisionStrikeSettings;
	}

	/**
	 * Clean up ("strike") post revisions for posts older than a certain number of seconds.
	 *
	 * @param int|bool $days The number of days old a post must be in order for its revisions to be
	 *                       struck. A boolean FALSE will defer to get_revision_expiration_time().
	 */
	public function strike( $days = false ) {
		if ( false === $days ) {
			$days = $this->get_revision_expiration_threshold();
		}

		// Collect the revision IDs
		$revision_ids = $this->get_revision_ids( $days, 50 );

		if ( ! empty( $revision_ids ) ) {
			foreach ( $revision_ids as $revision_id ) {
				wp_delete_post_revision( $revision_id );
			}
		}
	}

	/**
	 * Get the number of days since a post's publish date that must have passed before revisions can
	 * be struck.
	 *
	 * @return int A number of days.
	 */
	protected function get_revision_expiration_threshold() {
		if ( ! is_null( $this->expiration_threshold ) ) {
			return $this->expiration_threshold;
		}

		$time = 30; // @todo pull this from a settings page

		// Cache the result
		$this->expiration_threshold = intval( $time );

		return $this->expiration_threshold;
	}

	/**
	 * Find revisions eligible to be removed from the database.
	 *
	 * @global $wpdb
	 *
	 * @param int $days  The number of days since a post's publish date that must pass before we can
	 *                   purge the post revisions.
	 * @param int $limit The maximum number of revisions to remove on each pass.
	 *
	 * @return array An array of post IDs (unless 'fields' is manipulated in $args).
	 */
	protected function get_revision_ids( $days, $limit = 50 ) {
		global $wpdb;

		/**
		 * Controls the post types for which revisions should be automatically be purged.
		 *
		 * @param array $post_types An array of post types.
		 */
		$post_types = apply_filters( 'revisionstrike_post_types', array( 'post' ) );

		// Return early if we don't have any eligible post types
		if ( empty( $post_types ) ) {
			return array();
		}

		$limit = absint( $limit );
		$query = $wpdb->get_col( $wpdb->prepare(
			"
			SELECT r.ID FROM $wpdb->posts r
			LEFT JOIN $wpdb->posts p ON r.post_parent = p.ID
			WHERE r.post_type = 'revision' AND p.post_type IN ('%s') AND p.post_date < %s
			LIMIT %d
			",
			implode( "', '", (array) $post_types ),
			date( 'Y-m-d', time() - ( absint( $days ) * DAY_IN_SECONDS ) ),
			$limit
		) );

		return array_map( 'absint', $query );
	}

}