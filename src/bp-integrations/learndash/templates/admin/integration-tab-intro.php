<div class="wrap">
	
	<div class="bp-admin-card section-bp_ld-integration">
		<?php if( ! bp_is_active( 'groups' ) )  : ?>
			<h2><?php _e( 'Social Group <span>&mdash; BuddyBoss component requires to activate.</span>', 'buddyboss' ); ?></h2>
		<?php else: ?>
			<h2><?php _e( 'LearnDash <span>&mdash; requires plugin to activate</span>', 'buddyboss' ); ?></h2>
		<?php endif; ?>
		<p>
		<?php
			printf(
				__( 'BuddyBoss Platform has integration settings for %s. If using LearnDash we add the ability to sync LearnDash groups with social groups, to connect LearnDash courses to social groups, and more. If using our BuddyBoss Theme we also include styling for LearnDash.', 'buddyboss' ),
				sprintf(
					'<a href="%s">%s</a>',
					'https://learndash.idevaffiliate.com/111.html',
					__( 'LearnDash LMS', 'buddyboss' )
				)
			)
			?>
		</p>
		<br />
		<div class="bp-admin-card-bottom">
			<a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 62873,
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorials', 'buddyboss' ); ?></a>
		</div>
	</div>

</div>
