<?php
/**
 * @package		AutomateWoo/Admin/Views
 */

$all_checks_success = true

?>

<table class="aw_system_check_table wc_status_table widefat" cellspacing="0"><tbody>

	<?php foreach ( AW()->system_checker->get_checks() as $check ):

		/** @var AW_System_Check $check */
		$check = new $check();
		$response = $check->run();

		if ( $response['success'] == false ) $all_checks_success = false;

		?>

		<tr>
			<td class="">
				<?php echo $check->title ?>
			</td>


			<td class="help">
				<?php if ( $check->description ): ?>
					<?php echo AW()->admin->help_tip( $check->description ); ?>
				<?php endif ?>
			</td>

			<td class="">

				<?php if ( $response['success'] == true ): ?>
					<mark class="yes">&#10004; <?php echo $response['message'] ?></mark>
				<?php elseif ( $response['success'] == false ): ?>
					<mark class="error">&#10005; <?php echo $response['message'] ?></mark>
				<?php else: ?>

				<?php endif; ?>

			</td>

		</tr>

	<?php endforeach; ?>

</tbody></table>

<?php

if ( $all_checks_success )
{
	delete_transient( 'automatewoo_background_system_check_errors' );
}
