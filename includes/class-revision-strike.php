<?php
/**
 * Primary plugin functionality.
 *
 * @package Revision Strike
 * @author Steve Grunwell
 */

/**
 * Class that hooks into WordPress, determines revision IDs, and strikes them from the database.
 */
class RevisionStrike {

	/**
	 * The plugin settings.
	 * @var RevisionStrikeSettings $settings
	 */
	public $settings;

	/**
	 * The canonical source for default settings.
	 * @var array $defaults
	 */
	protected $defaults;

	/**
	 * Information about Revision Strike's current state.
	 * @var array $statistics
	 */
	protected $statistics;

	/**
	 * The batch size when striking revisions.
	 */
	const BATCH_SIZE = 50;

	/**
	 * The action called to trigger the clean-up process.
	 */
	const STRIKE_ACTION = 'revisionstrike_strike_old_revisions';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->settings   = new RevisionStrikeSettings( $this );
		$this->defaults   = array(
			'days'      => 30,
			'limit'     => 50,
			'post_type' => 'post',
			'keep'      => 0,
		);
		$this->statistics = array(
			'count'   => 0, // Number of revision IDs found.
			'deleted' => 0, // Number of revisions deleted.
		);

		$this->add_hooks();
	}

	/**
	 * Register hooks within WordPress.
	 */
	public function add_hooks() {
		add_action( self::STRIKE_ACTION, array( $this, 'strike' ) );
		add_action( 'admin_init', array( $this->settings, 'add_settings_section' ) );
		add_action( 'admin_menu', array( $this->settings, 'add_tools_page' ) );
		add_action( 'wp_delete_post_revision', array( $this, 'count_deleted_revision' ) );
	}

	/**
	 * Increment $this->statistics['deleted'] by one every time a post revision is removed.
	 */
	public function count_deleted_revision() {
		$this->statistics['deleted']++;
	}

	/**
	 * Count the number of post revisions that are eligible to be struck for the given threshold and
	 * post types.
	 *
	 * @global $wpdb
	 *
	 * @param int    $days      The number of days old a post must be in order for its revisions to
	 *                          be struck.
	 * @param string $post_type Post types for which revisions should be struck.
	 * @return int The number of matching post revisions in the database.
	 */
	public function count_eligible_revisions( $days, $post_type ) {
		global $wpdb;

		$post_type = array_map( 'trim', explode( ',', $post_type ) );
		$count     = $wpdb->get_var( $wpdb->prepare(
			"
			SELECT COUNT(r.ID) FROM $wpdb->posts r
			LEFT JOIN $wpdb->posts p ON r.post_parent = p.ID
			WHERE r.post_type = 'revision' AND p.post_type IN ('%s') AND p.post_date < %s
			",
			implode( "', '", $post_type ),
			date( 'Y-m-d', time() - ( absint( $days ) * DAY_IN_SECONDS ) )
		) );

		return absint( $count );
	}

	/**
	 * Return the current default Revision Strike settings.
	 *
	 * @return array An array of default settings.
	 */
	public function get_defaults() {
		return $this->defaults;
	}

	/**
	 * Return the current statistics for this RevisionStrike instance.
	 *
	 * The statistics array contains the following keys:
	 * - count: The number of revision IDs found, up to the limit that was passed to strike().
	 * - deleted: The number of revisions that have been deleted. This number should always be <= the
	 *            value of "count".
	 *
	 * @return array An array of statistics.
	 */
	public function get_stats() {
		return $this->statistics;
	}

	/**
	 * Clean up ("strike") post revisions for posts that have been published for at least $days days.
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
			'days'      => $this->settings->get_option( 'days' ),
			'limit'     => $this->settings->get_option( 'limit' ),
			'post_type' => $this->settings->get_option( 'post_type' ),
			'keep'      => $this->settings->get_option( 'keep' ),
		);
		$args         = wp_parse_args( $args, $default_args );

		if ( null === $args['post_type'] ) {

			/**
			 * Set the default post type(s) for which revisions should be struck.
			 *
			 * @param string $post_type A comma-separated list of post types.
			 */
			$args['post_type'] = apply_filters( 'revisionstrike_post_types', 'post' );
		}

		// Calculate the number of batches to run.
		$limit       = self::BATCH_SIZE >= $args['limit'] ? $args['limit'] : self::BATCH_SIZE;
		$batch_count = ceil( $args['limit'] / $limit );

		for ( $i = 0; $i < $batch_count; $i++ ) {
			$revision_ids = $this->get_revision_ids( $args['days'], $limit, $args['post_type'], $args['keep'] );

			if ( ! empty( $revision_ids ) ) {
				foreach ( $revision_ids as $revision_id ) {
					wp_delete_post_revision( $revision_id );
				}
			}
		}

	}

	/**
	 * Find revisions eligible to be removed from the database.
	 *
	 * @global $wpdb
	 *
	 * @param int   $days      The number of days since a post's publish date that must pass before
	 *                         we can purge the post revisions.
	 * @param int   $limit     The maximum number of revision IDs to retrieve.
	 * @param array $post_type The post types for which revisions should be located.
	 * @param int   $keep      Keep at least this number of revisions for a post, regardless of age
	 *
	 * @return array An array of revision IDs
	 */
	protected function get_revision_ids( $days, $limit, $post_type, $keep ) {

		// Return early if we don't have any eligible post types.
		if ( ! $post_type ) {
			return array();
		}

		$post_type    = array_map( 'trim', explode( ',', $post_type ) );

		// get a list of post IDs and their revisions
		$results = $this->query_post_and_revision_ids( $post_type, $days );

		// allow filtering of the query results
		$posts = apply_filters( 'revisionstrike_revisions_query_results', $results );

		// filter the number of revisions to keep based on post types
		$keep  = absint( apply_filters( 'revisionstrike_revisions_post_list_keep', $keep, $post_type ) );

		// scrub the list down so we're keeping the minimum number of revs per post
		$posts = apply_filters( 'revisionstrike_revisions_post_list', $this->scrub_posts_list( $results, $keep ) );

		// get a the list of revision IDs
		$revision_ids = array();
		foreach ( $posts as $post_id => $revisions ) {
			$revision_ids = array_merge( $revision_ids, wp_list_pluck( $revisions, 'revision_id' ) );
		}

		// limit the final list of revision IDs by the limit
		$revision_ids = array_slice( $revision_ids, 0, $limit );

		$this->statistics['count'] = count( $revision_ids );

		return array_map( 'absint', $revision_ids );
	}

	/**
	 * Queries the database for a list of post IDs and revisions
	 *
	 * @param  array  $post_type   post types
	 * @param  int    $days        The number of days since a post's publish date that must pass before
	 *                             we can purge the post revisions.
	 * @return array               list of objects with post_id, revision_id, and revision_date
	 */
	protected function query_post_and_revision_ids( $post_type, $days ) {

		global $wpdb;

		// we don't do a LIMIT here because we need all the revision IDs and
		// dates for a post so we can later sort by the revision date and
		// ensure we're only removing the oldest revisions

		// this might be doable completely in SQL by doing a join on a
		// subquery, but that gets tricky

		return $wpdb->get_results( $wpdb->prepare(
			"
			SELECT r.ID as revision_id, r.post_date as revision_date, p.ID as post_id FROM $wpdb->posts r
			INNER JOIN $wpdb->posts p ON r.post_parent = p.ID
			WHERE r.post_type = 'revision' AND p.post_type IN ('%s') AND p.post_date < %s
			ORDER BY p.post_date ASC
			",
			implode( "', '", $post_type ),
			date( 'Y-m-d', time() - ( absint( $days ) * DAY_IN_SECONDS ) )
		) );

	}

	/**
	 * Turns the list of post and revision IDs into a key/value array after
	 * scrubbing the list to keep the supplied number of revisions for each post
	 *
	 * @param  array $results results from the get_revision_ids() query
	 * @param  int   $keep    the number of posts to keep, regardless of age
	 * @return array          list of post IDs as the key and an array of revisions (ID and post_date) as the value
	 */
	protected function scrub_posts_list( $results, $keep ) {

		$posts = array();
		if ( ! empty( $results ) && is_array( $results ) ) {

			foreach( $results as $result ) {
				if ( ! isset( $posts[ $result->post_id ] ) ) {
					$posts[ $result->post_id ] = array();
				}

				// add the revisions to the post
				$posts[ $result->post_id ][] = array(
					'revision_id'   => $result->revision_id,
					'revision_date' => $result->revision_date,
					);
			}

		}

		foreach( array_keys( $posts ) as $post_id ) {

			// now sort the list of revisions for each post by revision
			// date so the oldest revisions are first
			$revisions = $posts[ $post_id ];
			usort( $revisions, array( $this, 'revision_date_compare' ) );
			$posts[ $post_id ] = $revisions;

			// then remove anything past the number we need to keep so the
			// oldest revisions we're allowed to remove are returned
			// ex: if we keep four revisions and there are six in the list,
			// we return the two oldest revision IDs
			$posts[ $post_id ] = array_slice( $posts[ $post_id ], 0, count( $posts[ $post_id ] ) - $keep );

		}

		return $posts;

	}

	/**
	 * Compares the revision_date in the supplied array
	 *
	 * @param  array $a first array
	 * @param  array $b second array
	 * @return int
	 */
	protected function revision_date_compare( $a, $b ) {
		return strtotime( $a['revision_date'] ) - strtotime( $b['revision_date'] );
	}

}
