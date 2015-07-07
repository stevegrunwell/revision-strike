<?php
/**
 * Primary plugin functionality.
 *
 * @package Revision Strike
 * @author Steve Grunwell
 */

class RevisionStrike {

	/**
	 * @var int $revision_expiration_threshold The cached number of days since a post's publishing
	 *                                         before its revisions can be removed.
	 */
	protected $revision_expiration_threshold;

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
		if ( ! is_null( $this->revision_expiration_threshold ) ) {
			return $this->revision_expiration_threshold;
		}

		$time = 30; // @todo pull this from a settings page

		// Cache the result
		$this->revision_expiration_threshold = intval( $time );

		return $this->revision_expiration_threshold;
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

		$limit = absint( $limit );
		$query = $wpdb->get_results( $wpdb->prepare(
			"
			SELECT r.ID FROM $wpdb->posts r
			LEFT JOIN $wpdb->posts p ON r.post_parent = p.ID
			WHERE r.post_type = 'revision' AND p.post_date < %s
			LIMIT %d
			",
			date( 'Y-m-d', time() - ( absint( $days ) * DAY_IN_SECONDS ) ),
			$limit
		) );

		return array_map( 'absint', $query );
	}

}