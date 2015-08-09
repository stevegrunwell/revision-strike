<?php
/**
 * The Tools > Revision Strike page.
 *
 * @package Revision Strike
 * @author Steve Grunwell
 */

if ( ! isset( $instance, $defaults ) ) {
	return wp_die( __( 'You cannot access this page directly', 'revision-strike' ) );
}

// Handle the execution of revisions
if ( isset( $_GET['nonce'], $_POST['days'], $_POST['limit'] ) && wp_verify_nonce( $_GET['nonce'], 'revision-strike' ) ) {
	$args = array(
		'days'  => absint( $_POST['days'] ),
		'limit' => absint( $_POST['limit'] ),
	);
	$instance->strike( $args );

	// @todo success message
}

?>

<div class="wrap">
	<div id="icon-tools" class="icon32"></div>
	<h2><?php esc_html_e( 'Revision Strike', 'revision-strike' ); ?></h2>

	<form method="POST" action="<?php echo wp_nonce_url( 'tools.php?page=revision-strike', 'revision-strike', 'nonce' ); ?>">
		<p><?php esc_html_e(
			'Revision Strike will remove old revisions from the post database.',
			'revision-strike'
		); ?></p>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Minimum post age', 'revision-strike' ); ?></th>
				<td>
					<input name="days" type="number" class="small-text" value="<?php echo absint( $defaults['days'] ); ?>" />
					<p class="description"><?php esc_html_e(
						'Revisions will be removed from posts that have been published at least this many days ago.',
						'revision-strike'
					); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Limit', 'revision-strike' ); ?></th>
				<td>
					<input name="limit" type="number" class="small-text" value="<?php echo absint( $defaults['limit'] ); ?>" />
					<p class="description"><?php esc_html_e(
						'The maximum number of revisions to delete at one time.',
						'revision-strike'
					); ?></p>
				</td>
			</tr>
		</table>

		<?php echo submit_button( __( 'Strike Revisions', 'revision-strike' ), 'primary', null ); ?>
	</form>
</div>