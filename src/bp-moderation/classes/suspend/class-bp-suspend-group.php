<?php
/**
 * BuddyBoss Suspend Group Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Suspend
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Group.
 *
 * @since BuddyBoss 2.0.0
 */
class BP_Suspend_Group extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'groups';

	/**
	 * BP_Moderation_Group constructor.
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {

		$this->item_type = self::$type;

		// Manage hidden list.
		add_action( "bp_suspend_hide_{$this->item_type}", array( $this, 'manage_hidden_group' ), 10, 3 );
		add_action( "bp_suspend_unhide_{$this->item_type}", array( $this, 'manage_unhidden_group' ), 10, 4 );

		/**
		 * Suspend code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		add_filter( 'bp_groups_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_groups_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_group_search_join_sql', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_group_search_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Get Blocked member's group ids
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $member_id member id.
	 *
	 * @return array
	 */
	public static function get_member_group_ids( $member_id ) {
		$group_ids = array();

		$groups = groups_get_groups(
			array(
				'moderation_query'   => false,
				'type'               => 'alphabetical',
				'creator_id'         => $member_id,
				'fields'             => 'ids',
				'show_hidden'        => true,
				'per_page'           => 0,
				'update_meta_cache'  => false,
				'update_admin_cache' => false,
			)
		);

		if ( ! empty( $groups['groups'] ) ) {
			$group_ids = $groups['groups'];
		}

		return $group_ids;
	}


	/**
	 * Prepare group Join SQL query to filter blocked Group
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $join_sql Group Join sql.
	 * @param array  $args     Query arguments.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $args = array() ) {

		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $join_sql;
		}

		$join_sql .= $this->exclude_joint_query( 'g.id' );

		/**
		 * Filters the hidden Group Where SQL statement.
		 *
		 * @since BuddyBoss 2.0.0
		 *
		 * @param array $join_sql Join sql query
		 * @param array $class    current class object.
		 */
		$join_sql = apply_filters( 'bp_suspend_group_get_join', $join_sql, $this );

		return $join_sql;
	}

	/**
	 * Prepare group Where SQL query to filter blocked Group
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $where_conditions Group Where sql.
	 * @param array $args             Query arguments.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions, $args = array() ) {
		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $where_conditions;
		}

		$where                  = array();
		$where['suspend_where'] = $this->exclude_where_query();

		/**
		 * Filters the hidden group Where SQL statement.
		 *
		 * @since BuddyBoss 2.0.0
		 *
		 * @param array $where Query to hide suspended user's group.
		 * @param array $class current class object.
		 */
		$where = apply_filters( 'bp_suspend_group_get_where_conditions', $where, $this );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions;
	}

	/**
	 * Hide related content of group
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int      $group_id      group id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param array    $args          parent args.
	 */
	public function manage_hidden_group( $group_id, $hide_sitewide, $args = array() ) {

		$suspend_args = wp_parse_args(
			$args,
			array(
				'item_id'   => $group_id,
				'item_type' => self::$type,
			)
		);

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		BP_Core_Suspend::add_suspend( $suspend_args );
		$this->hide_related_content( $group_id, $hide_sitewide, $args );
	}

	/**
	 * Un-hide related content of group
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int      $group_id      group id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param int      $force_all     un-hide for all users.
	 * @param array    $args          parent args.
	 */
	public function manage_unhidden_group( $group_id, $hide_sitewide, $force_all, $args = array() ) {
		$suspend_args = wp_parse_args(
			$args,
			array(
				'item_id'   => $group_id,
				'item_type' => self::$type,
			)
		);

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		BP_Core_Suspend::remove_suspend( $suspend_args );
		$this->unhide_related_content( $group_id, $hide_sitewide, $force_all, $args );
	}

	/**
	 * Get Activity's comment ids
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int   $group_id group id.
	 * @param array $args     parent args.
	 *
	 * @return array
	 */
	protected function get_related_contents( $group_id, $args = array() ) {
		$related_contents = array();

		if ( bp_is_active( 'activity' ) ) {
			$related_contents[ BP_Suspend_Activity::$type ] = BP_Suspend_Activity::get_group_activity_ids( $group_id );
		}

		if ( bp_is_active( 'forums' ) ) {
			$forum_id = groups_get_groupmeta( $group_id, 'forum_id' );
			if ( is_array( $forum_id ) && ! empty( $forum_id[0] ) ) {
				$related_contents[ BP_Suspend_Forum::$type ] = array( $forum_id[0] );
			}
		}

		return $related_contents;
	}
}