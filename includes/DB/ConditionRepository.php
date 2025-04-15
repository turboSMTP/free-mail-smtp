<?php
namespace TurboSMTP\FreeMailSMTP\DB;

defined( 'ABSPATH' ) || exit;

class ConditionRepository {
	private $table;
	
	public function __construct( ) {
		global $wpdb;
		$this->table = $wpdb->prefix . 'free_mail_smtp_email_router_conditions';
	}
	
	public function get_condition( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result =  $wpdb->get_row(
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id )
		);

		return $result;
	}
	
	public function add_condition( $data ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert( $this->table, $data, $this->get_format( $data ) );
		if ( false === $result ) {
			return false;
		}
		return $wpdb->insert_id;
	}
	
	public function update_condition( $id, $data ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update( $this->table, $data, array( 'id' => $id ), $this->get_format( $data ), array( '%d' ) );
		return false !== $result;
	}
	
	public function delete_condition( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete( $this->table, array( 'id' => $id ), array( '%d' ) );
		return false !== $result;
	}
	
	public function load_all_conditions() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM $this->table" );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared


	}
	
	private function get_format( $data ) {
		$formats = array();
		foreach ( $data as $value ) {
			if ( is_int( $value ) ) {
				$formats[] = '%d';
			} elseif ( is_float( $value ) ) {
				$formats[] = '%f';
			} else {
				$formats[] = '%s';
			}
		}
		return $formats;
	}
}
