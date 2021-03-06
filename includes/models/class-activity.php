<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Class Gravity_Flow_Activity
 *
 * @copyright   Copyright (c) 2015-2017, Steven Henty S.L.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
class Gravity_Flow_Activity {

	public static function get_activity_log_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'gravityflow_activity_log';
	}

	public static function get_lead_table_name() {
		return GFFormsModel::get_lead_table_name();
	}

	public static function get_entry_table_name() {
		return Gravity_Flow_Common::get_entry_table_name();
	}

	public static function get_events( $limit = 400, $objects = array( 'workflow', 'step', 'assignee' ) ) {
		global $wpdb;

		$log_objects_placeholders = array_fill( 0, count( $objects ), '%s' );
		$log_objects_in_list = $wpdb->prepare( implode( ', ', $log_objects_placeholders ), $objects );

		$activity_table = self::get_activity_log_table_name();
		$entry_table = self::get_entry_table_name();
		$sql     = $wpdb->prepare( "
SELECT * FROM {$activity_table} a
INNER JOIN {$entry_table} l ON a.lead_id = l.id AND l.status = 'active'
WHERE a.log_object IN ( $log_objects_in_list )

ORDER BY a.id DESC LIMIT %d", $limit );
		$results = $wpdb->get_results( $sql );

		return $results;
	}

	public static function get_report_data_for_all_forms( $start_date, $end_date = '' ) {
		global $wpdb;

		$activity_table = self::get_activity_log_table_name();
		$entry_table     = self::get_entry_table_name();

		$form_ids = self::get_form_ids();
		if ( empty( $form_ids ) ) {
			return false;
		}
		$in_str_arr    = array_fill( 0, count( $form_ids ), '%d' );
		$in_str        = join( ',', $in_str_arr );
		$form_id_clause = $wpdb->prepare( "AND a.form_id IN ($in_str)", $form_ids );

		$sql     = $wpdb->prepare( "
SELECT a.form_id, count(a.id) as c, ROUND( AVG(duration) ) as av
FROM {$activity_table} a
INNER JOIN {$entry_table} l ON a.lead_id = l.id AND l.status = 'active'
WHERE a.log_object = 'workflow' AND a.log_event = 'ended'
AND a.date_created >= %s
{$form_id_clause}
GROUP BY a.form_id", $start_date );

		$results = $wpdb->get_results( $sql );

		return $results;

	}

	public static function get_report_data_for_form( $form_id, $start_date, $end_date = '' ) {
		global $wpdb;

		$activity_table = self::get_activity_log_table_name();
		$entry_table     = self::get_entry_table_name();

		$sql     = $wpdb->prepare( "
SELECT MONTH(a.date_created) as month, count(a.id) as c, ROUND( AVG(a.duration) ) as av
FROM {$activity_table} a
INNER JOIN {$entry_table} l ON a.lead_id = l.id AND l.status = 'active'
WHERE log_object = 'workflow' AND log_event = 'ended'
  AND a.form_id = %d
  AND a.date_created >= %s
GROUP BY YEAR(a.date_created), MONTH(a.date_created)", $form_id, $start_date );

		$results = $wpdb->get_results( $sql );

		return $results;

	}

	public static function get_report_data_for_form_by_step( $form_id, $start_date, $end_date = '' ) {
		global $wpdb;

		$activity_table = self::get_activity_log_table_name();
		$entry_table     = self::get_entry_table_name();

		$sql     = $wpdb->prepare( "
SELECT a.feed_id, count(a.id) as c, ROUND( AVG(a.duration) ) as av
FROM {$activity_table} a
INNER JOIN {$entry_table} l ON a.lead_id = l.id AND l.status = 'active'
WHERE log_object = 'step' AND log_event = 'ended'
  AND a.form_id = %d
  AND a.date_created >= %s
GROUP BY a.feed_id", $form_id, $start_date );

		$results = $wpdb->get_results( $sql );

		return $results;

	}

	public static function get_report_data_for_step_by_assignee( $step_id, $start_date, $end_date = '' ) {
		global $wpdb;

		$activity_table = self::get_activity_log_table_name();
		$entry_table     = self::get_entry_table_name();

		$sql     = $wpdb->prepare( "
SELECT a.assignee_id, a.assignee_type, count(a.id) as c, ROUND( AVG(a.duration) ) as av
FROM {$activity_table} a
INNER JOIN {$entry_table} l ON a.lead_id = l.id AND l.status = 'active'
WHERE log_object = 'assignee' AND log_event = 'status' AND log_value NOT IN ('pending', 'removed')
  AND a.feed_id = %d
  AND a.date_created >= %s
GROUP BY a.assignee_id, a.assignee_type", $step_id, $start_date );

		$results = $wpdb->get_results( $sql );

		return $results;

	}

	public static function get_report_data_for_form_by_assignee( $form_id, $start_date, $end_date = '' ) {
		global $wpdb;

		$activity_table = self::get_activity_log_table_name();
		$entry_table     = self::get_entry_table_name();

		$sql     = $wpdb->prepare( "
SELECT a.assignee_id, a.assignee_type, count(a.id) as c, ROUND( AVG(a.duration) ) as av
FROM {$activity_table} a
INNER JOIN {$entry_table} l ON a.lead_id = l.id AND l.status = 'active'
WHERE a.log_object = 'assignee' AND a.log_event = 'status' AND a.log_value NOT IN ('pending', 'removed')
  AND a.form_id = %d
  AND a.date_created >= %s
GROUP BY a.assignee_id, a.assignee_type", $form_id, $start_date );

		$results = $wpdb->get_results( $sql );

		return $results;

	}

	public static function get_report_data_for_all_forms_by_assignee( $start_date, $end_date = '' ) {
		global $wpdb;

		$activity_table = self::get_activity_log_table_name();
		$entry_table     = self::get_entry_table_name();

		$sql     = $wpdb->prepare( "
SELECT a.assignee_id, a.assignee_type, count(a.id) as c, ROUND( AVG(a.duration) ) as av
FROM {$activity_table} a
INNER JOIN {$entry_table} l ON a.lead_id = l.id AND l.status = 'active'
WHERE a.log_object = 'assignee' AND a.log_event = 'status' AND log_value NOT IN ('pending', 'removed')
  AND a.date_created >= %s
GROUP BY a.assignee_id, a.assignee_type", $start_date );

		$results = $wpdb->get_results( $sql );

		return $results;

	}

	public static function get_report_data_for_assignee_by_month( $assignee_type, $assignee_id, $start_date, $end_date = '' ) {
		global $wpdb;

		$activity_table = self::get_activity_log_table_name();
		$lead_table     = self::get_lead_table_name();

		$form_ids = self::get_form_ids();
		if ( empty( $form_ids ) ) {
			return false;
		}
		$in_str_arr    = array_fill( 0, count( $form_ids ), '%d' );
		$in_str        = join( ',', $in_str_arr );
		$form_id_clause = $wpdb->prepare( "AND a.form_id IN ($in_str)", $form_ids );

		$sql     = $wpdb->prepare( "
SELECT YEAR(a.date_created) as year, MONTH(a.date_created) as month, count(a.id) as c, ROUND( AVG(a.duration) ) as av
FROM {$activity_table} a
INNER JOIN {$lead_table} l ON a.lead_id = l.id AND l.status = 'active'
WHERE a.log_object = 'assignee' AND a.log_event = 'status' AND a.log_value NOT IN ('pending', 'removed')
  AND a.assignee_type = %s AND a.assignee_id = %s
  AND a.date_created >= %s
  {$form_id_clause}
GROUP BY YEAR(a.date_created), MONTH(a.date_created)", $assignee_type, $assignee_id, $start_date );

		$results = $wpdb->get_results( $sql );

		return $results;
	}

	public static function get_form_ids() {
		return gravity_flow()->get_workflow_form_ids();
	}
}
