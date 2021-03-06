<?php
/**
 * BuddyPress - Members Notifications Loop
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>
<form action="" method="post" id="notifications-bulk-management">
	<table class="notifications bbp-table-responsive">
		<thead>
			<tr>
				<th class="bulk-select-all">
                    <label class="bbp-checkbox" for="select-all-notifications">
                        <input id="select-all-notifications" type="checkbox">
                        <span class="bbp-checkbox-check"></span>
                        <span class="bp-screen-reader-text" for="select-all-notifications"><?php
                            /* translators: accessibility text */
                            _e( 'Select All Notifications', 'buddypress' );
                            ?></span>
                    </label>
                </th>
				<th class="title"><?php _e( 'Notification', 'buddypress' ); ?></th>
				<th class="date"><?php _e( 'Date Received', 'buddypress' ); ?></th>
				<th class="actions"><?php _e( 'Actions',    'buddypress' ); ?></th>
			</tr>
		</thead>

		<tbody>

			<?php while ( bp_the_notifications() ) : bp_the_notification(); ?>
				<tr>
					<td class="bulk-select-check">
                        <label for="<?php bp_the_notification_id(); ?>" class="bbp-checkbox">
                            <input id="<?php bp_the_notification_id(); ?>" type="checkbox" name="notifications[]" value="<?php bp_the_notification_id(); ?>" class="notification-check">
                            <span class="bbp-checkbox-check"></span>
                            <span class="bp-screen-reader-text"><?php
                                /* translators: accessibility text */
                                _e( 'Select this notification', 'buddypress' );
                                ?></span>
                        </label>
                    </td>
					<td class="notification-description"><?php bp_the_notification_description(); ?></td>
					<td class="notification-since"><?php bp_the_notification_time_since(); ?></td>
					<td class="notification-actions"><?php bp_the_notification_action_links(); ?></td>
				</tr>

			<?php endwhile; ?>

		</tbody>
	</table>

        <div class="bbp-filters">
            <div class="notifications-options-nav">
                <?php bp_notifications_bulk_management_dropdown(); ?>
            </div><!-- .notifications-options-nav -->
        </div>

	<?php wp_nonce_field( 'notifications_bulk_nonce', 'notifications_bulk_nonce' ); ?>
</form>
