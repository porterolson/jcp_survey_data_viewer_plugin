<?php
/**
 * Survey data list table.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class JCPSDV_List_Table extends WP_List_Table {

	/**
	 * Survey table name.
	 *
	 * @var string
	 */
	private $table_name = '';

	/**
	 * Column names from the survey table.
	 *
	 * @var array<int, string>
	 */
	private $columns = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'jcpsdv-survey-row',
				'plural'   => 'jcpsdv-survey-rows',
				'ajax'     => false,
			)
		);

		global $wpdb;
		$this->table_name = $wpdb->prefix . 'job_survey';
		$this->columns    = $this->discover_columns();
	}

	/**
	 * Return discovered table name.
	 *
	 * @return string
	 */
	public function get_table_name() {
		return $this->table_name;
	}

	/**
	 * Return visible columns for the page.
	 *
	 * @return array<int, string>
	 */
	public function get_visible_columns() {
		return $this->columns;
	}

	/**
	 * List table columns.
	 *
	 * @return array<string, string>
	 */
	public function get_columns() {
		$columns = array();

		if ( empty( $this->columns ) ) {
			$columns['missing_table'] = __( 'Survey Table Status', 'jcp-survey-data-viewer' );
			return $columns;
		}

		foreach ( $this->columns as $column ) {
			$columns[ $column ] = $this->format_column_label( $column );
		}

		return $columns;
	}

	/**
	 * Sortable columns.
	 *
	 * @return array<string, array<int, string|bool>>
	 */
	protected function get_sortable_columns() {
		$sortable = array();

		foreach ( $this->columns as $column ) {
			$sortable[ $column ] = array( $column, false );
		}

		return $sortable;
	}

	/**
	 * Render default column value.
	 *
	 * @param array<string, mixed> $item Row data.
	 * @param string               $column_name Column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		if ( 'missing_table' === $column_name ) {
			return esc_html__( 'The job_survey table was not found for this WordPress site prefix.', 'jcp-survey-data-viewer' );
		}

		if ( ! array_key_exists( $column_name, $item ) ) {
			return '';
		}

		return $this->format_value( $item[ $column_name ] );
	}

	/**
	 * Prepare rows.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		if ( empty( $this->columns ) ) {
			$this->items = array(
				array(
					'missing_table' => 'missing',
				),
			);
			$this->set_pagination_args(
				array(
					'total_items' => 1,
					'per_page'    => 1,
					'total_pages' => 1,
				)
			);
			return;
		}

		$per_page     = 20;
		$current_page = max( 1, $this->get_pagenum() );
		$offset       = ( $current_page - 1 ) * $per_page;
		$query        = $this->get_query_parts_from_request();
		$total_items  = $this->count_items( $query );

		$this->items = $this->get_items( $query, $per_page, $offset );
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => max( 1, (int) ceil( $total_items / $per_page ) ),
			)
		);
	}

	/**
	 * Export all rows for the current request filters.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_export_items() {
		if ( empty( $this->columns ) ) {
			return array();
		}

		return $this->get_items( $this->get_query_parts_from_request() );
	}

	/**
	 * Export all rows from the survey table without request filters.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_all_export_items() {
		if ( empty( $this->columns ) ) {
			return array();
		}

		return $this->get_items(
			array(
				'where_sql' => '1=1',
				'params'    => array(),
				'orderby'   => $this->get_default_orderby(),
				'order'     => 'DESC',
			)
		);
	}

	/**
	 * Discover survey table columns.
	 *
	 * @return array<int, string>
	 */
	private function discover_columns() {
		global $wpdb;

		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->table_name ) );
		if ( $table_exists !== $this->table_name ) {
			return array();
		}

		$results = $wpdb->get_results( "DESCRIBE {$this->table_name}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( empty( $results ) ) {
			return array();
		}

		$columns = array();
		foreach ( $results as $column ) {
			if ( isset( $column['Field'] ) ) {
				$columns[] = (string) $column['Field'];
			}
		}

		return $this->prioritize_columns( $columns );
	}

	/**
	 * Build query parts from request filters.
	 *
	 * @return array<string, mixed>
	 */
	private function get_query_parts_from_request() {
		global $wpdb;

		$where  = array( '1=1' );
		$params = array();

		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		if ( '' !== $search && ! empty( $this->columns ) ) {
			$like         = '%' . $wpdb->esc_like( $search ) . '%';
			$search_parts = array();

			foreach ( $this->columns as $column ) {
				$search_parts[] = 'CAST(`' . esc_sql( $column ) . '` AS CHAR) LIKE %s';
				$params[]       = $like;
			}

			$where[] = '(' . implode( ' OR ', $search_parts ) . ')';
		}

		$filter_column = isset( $_GET['filter_column'] ) ? sanitize_key( wp_unslash( $_GET['filter_column'] ) ) : '';
		$filter_value  = isset( $_GET['filter_value'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_value'] ) ) : '';
		if ( '' !== $filter_value && in_array( $filter_column, $this->columns, true ) ) {
			$where[]  = 'CAST(`' . esc_sql( $filter_column ) . '` AS CHAR) LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $filter_value ) . '%';
		}

		$orderby_request = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
		$order           = isset( $_GET['order'] ) ? strtoupper( sanitize_key( wp_unslash( $_GET['order'] ) ) ) : 'DESC';
		$orderby         = in_array( $orderby_request, $this->columns, true ) ? $orderby_request : $this->get_default_orderby();
		$order           = 'ASC' === $order ? 'ASC' : 'DESC';

		return array(
			'where_sql' => implode( ' AND ', $where ),
			'params'    => $params,
			'orderby'   => $orderby,
			'order'     => $order,
		);
	}

	/**
	 * Count rows for the active query.
	 *
	 * @param array<string, mixed> $query Query parts.
	 * @return int
	 */
	private function count_items( $query ) {
		global $wpdb;

		$sql      = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$query['where_sql']}";
		$prepared = ! empty( $query['params'] ) ? $wpdb->prepare( $sql, $query['params'] ) : $sql;

		return (int) $wpdb->get_var( $prepared );
	}

	/**
	 * Fetch survey rows for the active query.
	 *
	 * @param array<string, mixed> $query Query parts.
	 * @param int|null             $limit Optional limit.
	 * @param int                  $offset Optional offset.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_items( $query, $limit = null, $offset = 0 ) {
		global $wpdb;

		$sql    = "SELECT * FROM {$this->table_name} WHERE {$query['where_sql']} ORDER BY `" . esc_sql( $query['orderby'] ) . "` {$query['order']}";
		$params = $query['params'];

		if ( null !== $limit ) {
			$sql      .= ' LIMIT %d OFFSET %d';
			$params[] = (int) $limit;
			$params[] = (int) $offset;
		}

		$prepared = ! empty( $params ) ? $wpdb->prepare( $sql, $params ) : $sql;
		return $wpdb->get_results( $prepared, ARRAY_A );
	}

	/**
	 * Choose a default sort column.
	 *
	 * @return string
	 */
	private function get_default_orderby() {
		$preferred = array( 'date', 'survey_id', 'id' );

		foreach ( $preferred as $column ) {
			if ( in_array( $column, $this->columns, true ) ) {
				return $column;
			}
		}

		return ! empty( $this->columns ) ? $this->columns[0] : 'survey_id';
	}

	/**
	 * Format a database value for table output.
	 *
	 * @param mixed $value Raw cell value.
	 * @return string
	 */
	private function format_value( $value ) {
		if ( null === $value ) {
			return '<span class="jcpsdv-cell"><em>' . esc_html__( 'NULL', 'jcp-survey-data-viewer' ) . '</em></span>';
		}

		if ( is_bool( $value ) ) {
			return '<span class="jcpsdv-cell">' . ( $value ? '1' : '0' ) . '</span>';
		}

		if ( is_array( $value ) || is_object( $value ) ) {
			return '<span class="jcpsdv-cell jcpsdv-cell--long"><code>' . esc_html( wp_json_encode( $value, JSON_PRETTY_PRINT ) ) . '</code></span>';
		}

		$string_value = (string) $value;
		if ( '' === $string_value ) {
			return '<span class="jcpsdv-cell"></span>';
		}

		if ( $this->looks_like_json( $string_value ) ) {
			$decoded = json_decode( $string_value, true );
			if ( JSON_ERROR_NONE === json_last_error() ) {
				return '<span class="jcpsdv-cell jcpsdv-cell--long"><details><summary>' . esc_html__( 'View JSON', 'jcp-survey-data-viewer' ) . '</summary><code>' . esc_html( wp_json_encode( $decoded, JSON_PRETTY_PRINT ) ) . '</code></details></span>';
			}
		}

		if ( strlen( $string_value ) > 120 ) {
			return '<span class="jcpsdv-cell jcpsdv-cell--long"><details><summary>' . esc_html( mb_substr( $string_value, 0, 120 ) . '...' ) . '</summary><div>' . esc_html( $string_value ) . '</div></details></span>';
		}

		return '<span class="jcpsdv-cell">' . esc_html( $string_value ) . '</span>';
	}

	/**
	 * Check whether a string appears to be JSON.
	 *
	 * @param string $value Candidate value.
	 * @return bool
	 */
	private function looks_like_json( $value ) {
		$trimmed = trim( $value );

		return '' !== $trimmed && (
			( '{' === $trimmed[0] && '}' === substr( $trimmed, -1 ) ) ||
			( '[' === $trimmed[0] && ']' === substr( $trimmed, -1 ) )
		);
	}

	/**
	 * Convert raw database column names into cleaner table labels.
	 *
	 * @param string $column Raw column name.
	 * @return string
	 */
	private function format_column_label( $column ) {
		$label = str_replace( '_', ' ', $column );
		$label = preg_replace( '/(?<!^)([A-Z])/', ' $1', $label );
		$label = trim( (string) $label );

		return ucwords( $label );
	}

	/**
	 * Move high-value tracing columns to the front of the table.
	 *
	 * @param array<int, string> $columns Raw discovered columns.
	 * @return array<int, string>
	 */
	private function prioritize_columns( $columns ) {
		$priority = array(
			'date',
			'survey_id',
			'session_id',
			'ip',
			'treatment_group',
			'post_url',
			'job_ad_url',
			'survey_url',
			'user_id',
			'id',
		);
		$ordered  = array();

		foreach ( $priority as $column ) {
			if ( in_array( $column, $columns, true ) ) {
				$ordered[] = $column;
			}
		}

		foreach ( $columns as $column ) {
			if ( ! in_array( $column, $ordered, true ) ) {
				$ordered[] = $column;
			}
		}

		return $ordered;
	}
}
