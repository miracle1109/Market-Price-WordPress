<?php
/**
 * BuddyPress - Groups Loop
 *
 * Querystring is set via AJAX in _inc/ajax.php - bp_legacy_theme_object_filter().
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>

<?php

/**
 * Fires before the display of groups from the groups loop.
 *
 * @since 1.2.0
 */
do_action( 'bp_before_groups_loop' ); ?>

<?php if ( bp_has_groups( bp_ajax_querystring( 'groups' ) ) ) : ?>

	<div id="pag-top" class="pagination">

		<div class="pag-count" id="group-dir-count-top">

			<?php bp_groups_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="group-dir-pag-top">
			<?php bp_groups_pagination_links(); ?>
		</div>

	</div>

	<?php

	/**
	 * Fires before the listing of the groups list.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_before_directory_groups_list' ); ?>

	<ul id="groups-list" class="item-list">


    <?php
        $boombox_buddypress = Boombox_Buddypress::get_instance();
        /**
        * Hook into group members count to add required HTML tags
         */
        add_filter( 'bp_core_number_format', array( $boombox_buddypress, 'make_number_rounded' ), 10, 1 );
    ?>

	<?php while ( bp_groups() ) : bp_the_group(); ?>

		<li <?php bp_group_class(); ?>>
            <div class="item-table">
                <?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
                    <div class="item-avatar item-cell">
                        <a href="<?php bp_group_permalink(); ?>"><?php bp_group_avatar( 'type=full&width=100&height=100' ); ?></a>
                    </div>
                <?php endif; ?>

                <div class="item-data item-cell">
                    <div class="item-header">
                        <div class="item-title"><a href="<?php bp_group_permalink(); ?>"><?php bp_group_name(); ?></a></div>
                        <div class="item-activity"><span class="activity"><?php printf( __( 'active %s', 'buddypress' ), bp_get_group_last_active() ); ?></span></div>
                    </div>

                    <div class="item-desc">
                        <?php bp_group_description_excerpt(); ?>
                    </div>

                    <div class="item-meta">
                        <?php bp_group_type(); ?> / <?php bp_group_member_count(); ?>
                    </div>
                </div>

                <?php
                /**
                 * Fires inside the listing of an individual group listing item.
                 *
                 * @since 1.1.0
                 */
                do_action( 'bp_directory_groups_item' ); ?>
                <div class="item-action item-cell">
                    <?php

                    /**
                     * Fires inside the action section of an individual group listing item.
                     *
                     * @since 1.1.0
                     */
                    do_action( 'bp_directory_groups_actions' ); ?>
                </div>
            </div>
		</li>

	<?php endwhile; ?>

    <?php remove_filter( 'bp_core_number_format', array( $boombox_buddypress, 'make_number_rounded' ), 10 ); ?>

	</ul>

	<?php

	/**
	 * Fires after the listing of the groups list.
	 *
	 * @since 1.1.0
	 */
	do_action( 'bp_after_directory_groups_list' ); ?>

	<div id="pag-bottom" class="pagination">

		<div class="pag-count" id="group-dir-count-bottom">

			<?php bp_groups_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="group-dir-pag-bottom">

			<?php bp_groups_pagination_links(); ?>

		</div>

	</div>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'There were no groups found.', 'buddypress' ); ?></p>
	</div>

<?php endif; ?>

<?php

/**
 * Fires after the display of groups from the groups loop.
 *
 * @since 1.2.0
 */
do_action( 'bp_after_groups_loop' ); ?>
