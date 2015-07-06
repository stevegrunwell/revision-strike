<?php
/**
 * Primary plugin functionality.
 *
 * @package Revision Strike
 * @author Steve Grunwell
 */

class RevisionStrike {

	/**
	 * @var int $revision_expiration_time The cached revision expiration time.
	 */
	protected $revision_expiration_time;

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
	}

	/**
	 * Clean up ("strike") post revisions for posts older than a certain number of seconds.
	 */
	public function strike() {
		$strike_before_timestamp = time() - $this->get_revision_expiration_time();
		$revision_ids            = $this->get_revision_ids( $strike_before_timestamp, 50 );

		if ( ! empty( $revision_ids ) ) {
			foreach ( $revision_ids as $revision_id ) {
				wp_delete_post_revision( $revision_id );
			}
		}
	}

	/**
	 * Get the number of seconds since a post's publish date that must have passed before revisions
	 * can be struck.
	 *
	 * @return int A number of seconds.
	 */
	protected function get_revision_expiration_time() {
		if ( ! is_null( $this->revision_expiration_time ) ) {
			return $this->revision_expiration_time;
		}

		$time = 30 * DAY_IN_SECONDS; // Default: 30 days

		/**
		 * Controls the age (in seconds) a published post much reach before its revisions are eligible
		 * to be purged.
		 *
		 * @param int $time The age (in seconds) a published post must reach before it can be purged.
		 */
		$time = apply_filters( 'revisionstrike_expiration_time', $time );

		// Cache the result
		$this->revision_expiration_time = intval( $time );

		return $this->revision_expiration_time;
	}

	/**
	 * Find revisions eligible to be removed from the database.
	 *
	 * @global $wpdb
	 *
	 * @param int $timestamp The Unix timestamp of the time before which all revisions should be
	 *                       purged from the database: time() - $this->get_revision_expiration_time().
	 * @param int $limit     The maximum number of revisions to remove on each pass.
	 *
	 * @return array An array of post IDs (unless 'fields' is manipulated in $args).
	 */
	protected function get_revision_ids( $expiration_time, $limit = 50 ) {
		global $wpdb;

		$limit = absint( $limit );
		$query = $wpdb->get_results( $wpdb->prepare(
			"
			SELECT r.ID FROM $wpdb->posts r
			LEFT JOIN $wpdb->posts p ON r.post_parent = p.ID
			WHERE r.post_type = 'revision' AND p.post_date < %s
			LIMIT %d
			",
			date( 'Y-m-d', strtotime( $expiration_time ) ),
			$limit
		) );

		return array_map( 'absint', $query );
	}

}