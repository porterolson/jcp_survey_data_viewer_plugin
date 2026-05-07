<?php
/**
 * Admin screens.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class JCPSDV_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
		add_action( 'admin_init', array( $this, 'maybe_export_survey_json' ) );
	}

	/**
	 * Register admin pages.
	 *
	 * @return void
	 */
	public function register_admin_pages() {
		add_users_page(
			__( 'Survey Data', 'jcp-survey-data-viewer' ),
			__( 'Survey Data', 'jcp-survey-data-viewer' ),
			'list_users',
			'jcpsdv-survey-data',
			array( $this, 'render_survey_page' )
		);
	}

	/**
	 * Render survey data page.
	 *
	 * @return void
	 */
	public function render_survey_page() {
		if ( ! current_user_can( 'list_users' ) ) {
			wp_die( esc_html__( 'You do not have permission to access survey data.', 'jcp-survey-data-viewer' ) );
		}

		$table = new JCPSDV_List_Table();
		$table->prepare_items();
		$columns = $table->get_visible_columns();
		?>
		<div class="wrap jcpsdv-admin">
			<?php $this->render_admin_styles(); ?>
			<h1><?php esc_html_e( 'Survey Data', 'jcp-survey-data-viewer' ); ?></h1>
			<p class="description">
				<?php echo esc_html( sprintf( __( 'Showing data from the %s table. Scroll horizontally to view all survey fields.', 'jcp-survey-data-viewer' ), $table->get_table_name() ) ); ?>
			</p>
			<?php $this->render_active_view_summary(); ?>
			<form method="get">
				<input type="hidden" name="page" value="jcpsdv-survey-data" />
				<?php $this->render_filters( $columns ); ?>
				<?php $this->render_export_button(); ?>
				<?php $table->search_box( __( 'Search Survey Data', 'jcp-survey-data-viewer' ), 'jcpsdv-survey-search' ); ?>
				<div class="jcpsdv-table-wrap">
					<?php $table->display(); ?>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render filter controls.
	 *
	 * @param array<int, string> $columns Available columns.
	 * @return void
	 */
	private function render_filters( $columns ) {
		$selected_column = isset( $_GET['filter_column'] ) ? sanitize_key( wp_unslash( $_GET['filter_column'] ) ) : '';
		$filter_value    = isset( $_GET['filter_value'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_value'] ) ) : '';
		$clear_url       = add_query_arg(
			array(
				'page' => 'jcpsdv-survey-data',
			),
			admin_url( 'users.php' )
		);
		?>
		<p class="search-box" style="display:flex;gap:8px;flex-wrap:wrap;max-width:none;">
			<select name="filter_column">
				<option value=""><?php esc_html_e( 'All Columns', 'jcp-survey-data-viewer' ); ?></option>
				<?php foreach ( $columns as $column ) : ?>
					<option value="<?php echo esc_attr( $column ); ?>" <?php selected( $selected_column, $column ); ?>>
						<?php echo esc_html( $column ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<input type="text" name="filter_value" value="<?php echo esc_attr( $filter_value ); ?>" placeholder="<?php esc_attr_e( 'Filter value', 'jcp-survey-data-viewer' ); ?>" />
			<?php submit_button( __( 'Apply Filters', 'jcp-survey-data-viewer' ), 'secondary', 'filter_action', false ); ?>
			<a class="button button-link-delete" href="<?php echo esc_url( $clear_url ); ?>"><?php esc_html_e( 'Clear View Filters', 'jcp-survey-data-viewer' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Render export action.
	 *
	 * @return void
	 */
	private function render_export_button() {
		$query_args = array(
			'page' => 'jcpsdv-survey-data',
		);
		$query_args['export_jcpsdv_json'] = '1';
		$query_args['_wpnonce']           = wp_create_nonce( 'jcpsdv_export_json' );
		$export_url                       = add_query_arg( array_map( 'sanitize_text_field', $query_args ), admin_url( 'users.php' ) );
		?>
		<p>
			<a class="button button-secondary" href="<?php echo esc_url( $export_url ); ?>">
				<?php esc_html_e( 'Export All Survey Data JSON', 'jcp-survey-data-viewer' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Render page-specific admin styles to keep the wide survey table readable.
	 *
	 * @return void
	 */
	private function render_admin_styles() {
		?>
		<style>
			.jcpsdv-admin .search-box {
				float: none;
				margin: 12px 0;
			}

			.jcpsdv-admin .notice {
				margin: 12px 0 16px;
			}

			.jcpsdv-admin .tablenav.top,
			.jcpsdv-admin .tablenav.bottom {
				display: flex;
				gap: 12px;
				align-items: center;
				flex-wrap: wrap;
			}

			.jcpsdv-admin .tablenav-pages,
			.jcpsdv-admin .bulkactions,
			.jcpsdv-admin .actions {
				float: none;
			}

			.jcpsdv-admin .jcpsdv-table-wrap {
				overflow: auto;
				max-width: 100%;
				max-height: 72vh;
				border: 1px solid #dcdcde;
				border-radius: 8px;
				background: #fff;
				box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
			}

			.jcpsdv-admin .jcpsdv-table-wrap table.wp-list-table {
				width: max-content;
				min-width: 100%;
				table-layout: auto;
				border-collapse: separate;
				border-spacing: 0;
				margin: 0;
			}

			.jcpsdv-admin .jcpsdv-table-wrap thead th,
			.jcpsdv-admin .jcpsdv-table-wrap tfoot th {
				position: sticky;
				top: 0;
				z-index: 2;
				background: #f6f7f7;
				white-space: nowrap;
				font-weight: 600;
				padding: 10px 12px;
				border-bottom: 1px solid #dcdcde;
			}

			.jcpsdv-admin .jcpsdv-table-wrap tbody td {
				padding: 10px 12px;
				vertical-align: top;
				border-bottom: 1px solid #f0f0f1;
				white-space: nowrap;
			}

			.jcpsdv-admin .jcpsdv-table-wrap tbody tr:nth-child(even) td {
				background: #fcfcfc;
			}

			.jcpsdv-admin .jcpsdv-table-wrap th.column-primary,
			.jcpsdv-admin .jcpsdv-table-wrap td.column-primary,
			.jcpsdv-admin .jcpsdv-table-wrap th[class^="column-"],
			.jcpsdv-admin .jcpsdv-table-wrap td[class^="column-"] {
				min-width: 130px;
			}

			.jcpsdv-admin .jcpsdv-table-wrap th.column-date,
			.jcpsdv-admin .jcpsdv-table-wrap td.column-date,
			.jcpsdv-admin .jcpsdv-table-wrap th.column-ip,
			.jcpsdv-admin .jcpsdv-table-wrap td.column-ip,
			.jcpsdv-admin .jcpsdv-table-wrap th.column-survey_id,
			.jcpsdv-admin .jcpsdv-table-wrap td.column-survey_id {
				min-width: 160px;
			}

			.jcpsdv-admin .jcpsdv-table-wrap .jcpsdv-cell {
				display: block;
				min-width: 110px;
			}

			.jcpsdv-admin .jcpsdv-table-wrap .jcpsdv-cell--long {
				min-width: 220px;
			}

			.jcpsdv-admin .jcpsdv-table-wrap .jcpsdv-cell details {
				white-space: normal;
			}

			.jcpsdv-admin .jcpsdv-table-wrap .jcpsdv-cell code,
			.jcpsdv-admin .jcpsdv-table-wrap .jcpsdv-cell div {
				white-space: pre-wrap;
				word-break: break-word;
			}
		</style>
		<?php
	}

	/**
	 * Show the current view state so hidden filters/search/page state are obvious.
	 *
	 * @return void
	 */
	private function render_active_view_summary() {
		$parts = array();

		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		if ( '' !== $search ) {
			$parts[] = sprintf( __( 'search: %s', 'jcp-survey-data-viewer' ), $search );
		}

		$filter_column = isset( $_GET['filter_column'] ) ? sanitize_key( wp_unslash( $_GET['filter_column'] ) ) : '';
		$filter_value  = isset( $_GET['filter_value'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_value'] ) ) : '';
		if ( '' !== $filter_column && '' !== $filter_value ) {
			$parts[] = sprintf( __( 'filter: %1$s contains "%2$s"', 'jcp-survey-data-viewer' ), $filter_column, $filter_value );
		}

		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
		$order   = isset( $_GET['order'] ) ? strtoupper( sanitize_key( wp_unslash( $_GET['order'] ) ) ) : '';
		if ( '' !== $orderby ) {
			$parts[] = sprintf( __( 'sorted by: %1$s %2$s', 'jcp-survey-data-viewer' ), $orderby, $order ? $order : 'DESC' );
		}

		$paged = isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 0;
		if ( $paged > 1 ) {
			$parts[] = sprintf( __( 'page: %d', 'jcp-survey-data-viewer' ), $paged );
		}

		if ( empty( $parts ) ) {
			return;
		}
		?>
		<div class="notice notice-warning inline">
			<p>
				<strong><?php esc_html_e( 'Active survey table view:', 'jcp-survey-data-viewer' ); ?></strong>
				<?php echo esc_html( implode( ' | ', $parts ) ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Export all survey rows as JSON.
	 *
	 * @return void
	 */
	public function maybe_export_survey_json() {
		if ( ! is_admin() || ! current_user_can( 'list_users' ) ) {
			return;
		}

		$should_export = isset( $_GET['page'], $_GET['export_jcpsdv_json'] )
			&& 'jcpsdv-survey-data' === sanitize_text_field( wp_unslash( $_GET['page'] ) )
			&& '1' === sanitize_text_field( wp_unslash( $_GET['export_jcpsdv_json'] ) );

		if ( ! $should_export ) {
			return;
		}

		check_admin_referer( 'jcpsdv_export_json' );

		$table = new JCPSDV_List_Table();
		$rows  = $table->get_all_export_items();

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=jcpsdv-survey-data-' . gmdate( 'Y-m-d-H-i-s' ) . '.json' );

		echo wp_json_encode(
			array(
				'exported_at' => gmdate( 'c' ),
				'table'       => $table->get_table_name(),
				'count'       => count( $rows ),
				'rows'        => $rows,
			),
			JSON_PRETTY_PRINT
		);
		exit;
	}
}
