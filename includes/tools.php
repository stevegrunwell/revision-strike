<?php
/**
 * The Tools > Revision Strike page.
 *
 * @package Revision Strike
 * @author  Steve Grunwell
 */

if ( ! isset( $instance, $defaults ) ) {
	return wp_die( esc_html__( 'You cannot access this page directly', 'revision-strike' ) );
}

// Handle the execution of revisions.
if ( isset( $_GET['nonce'], $_POST['days'], $_POST['limit'] ) && wp_verify_nonce( $_GET['nonce'], 'revision-strike' ) ) {
	$args  = array(
		'days'  => absint( $_POST['days'] ),
		'limit' => absint( $_POST['limit'] ),
	);
	$instance->strike( $args );
	$stats = $instance->get_stats();
	$class = 'error';

	if ( 0 === $stats['count'] ) {
		$message = __( 'No revisions were found that matched your criteria.', 'revision-strike' );

	} elseif ( 0 === $stats['deleted'] && 0 < $stats['count'] ) {
		$message = __( 'Something went wrong deleting post revisions, please try again!', 'revision-strike' );

	} else {
		$message = sprintf( _n(
			'One post revision has been deleted successfully!',
			'%d post revisions have been deleted successfully!',
			$stats['deleted'],
			'revision-strike'
		), $stats['deleted'] );
		$class   = 'updated';
	}

	printf(
		'<div class="%s"><p>%s</p></div>',
		esc_attr( $class ),
		esc_html( $message )
	);
}

?>

<div class="wrap">
	<div id="icon-tools" class="icon32"></div>
	<h2><?php esc_html_e( 'Revision Strike', 'revision-strike' ); ?></h2>

	<form method="POST" action="<?php echo esc_url( wp_nonce_url( 'tools.php?page=revision-strike', 'revision-strike', 'nonce' ) ); ?>">
		<p><?php
			esc_html_e(
				'Revision Strike will remove old revisions from the post database.',
				'revision-strike'
			);
		?></p>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Post Age Threshold', 'revision-strike' ); ?></th>
				<td>
					<input name="days" type="number" class="small-text" value="<?php echo absint( $defaults['days'] ); ?>" />
					<p class="description"><?php
						esc_html_e(
							'A post must be published at least this many days before its revisions can be removed.',
							'revision-strike'
						);
					?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Limit', 'revision-strike' ); ?></th>
				<td>
					<input name="limit" type="number" class="small-text" value="<?php echo absint( $defaults['limit'] ); ?>" />
					<p class="description"><?php
						esc_html_e(
							'The maximum number of revisions to delete.',
							'revision-strike'
						);
					?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Strike Revisions', 'revision-strike' ), 'primary', null ); ?>
	</form>
</div>
