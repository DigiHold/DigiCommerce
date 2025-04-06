<?php
/**
 * Orders list table
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Orders', 'digicommerce' ); ?></h1>
	<hr class="wp-header-end">

	<h2 class="screen-reader-text"><?php esc_html_e( 'Filter orders list', 'digicommerce' ); ?></h2>

	<!-- Filter links -->
	<ul class="subsubsub">
		<li class="all">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=digi-orders&status=all' ) ); ?>"
				class="<?php echo 'all' === $current_status ? 'current' : ''; ?>">
				<?php esc_html_e( 'All', 'digicommerce' ); ?> <span class="count">(<?php echo esc_attr( $total_all ); ?>)</span>
			</a> |
		</li>
		<li class="completed">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=digi-orders&status=completed' ) ); ?>"
				class="<?php echo 'completed' === $current_status ? 'current' : ''; ?>">
				<?php esc_html_e( 'Completed', 'digicommerce' ); ?> <span class="count">(<?php echo esc_attr( $total_completed ); ?>)</span>
			</a> |
		</li>
		<li class="processing">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=digi-orders&status=processing' ) ); ?>"
				class="<?php echo 'processing' === $current_status ? 'current' : ''; ?>">
				<?php esc_html_e( 'Processing', 'digicommerce' ); ?> <span class="count">(<?php echo esc_attr( $total_processing ); ?>)</span>
			</a> |
		</li>
		<li class="cancelled">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=digi-orders&status=cancelled' ) ); ?>"
				class="<?php echo 'cancelled' === $current_status ? 'current' : ''; ?>">
				<?php esc_html_e( 'Cancelled', 'digicommerce' ); ?> <span class="count">(<?php echo esc_attr( $total_cancelled ); ?>)</span>
			</a> |
		</li>
		<li class="trash">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=digi-orders&status=trash' ) ); ?>"
				class="<?php echo 'trash' === $current_status ? 'current' : ''; ?>">
				<?php esc_html_e( 'Trash', 'digicommerce' ); ?> <span class="count">(<?php echo esc_attr( $total_trash ); ?>)</span>
			</a>
		</li>
	</ul>

	<form id="posts-filter" method="get">
		<?php wp_nonce_field( 'digi_orders_bulk_action', '_wpnonce_bulk' ); ?>
		<input type="hidden" name="page" value="digi-orders">
		<input type="hidden" name="status" value="<?php echo esc_attr( $current_status ); ?>">

		<p class="search-box">
			<label class="screen-reader-text" for="post-search-input"><?php esc_html_e( 'Search Orders', 'digicommerce' ); ?></label>
			<input type="search" id="post-search-input" name="s" value="<?php echo esc_attr( $search_query ); ?>">
			<?php wp_nonce_field( 'digicommerce_orders_search', 'search_nonce' ); ?>
			<input type="hidden" name="is_search" value="1">
			<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Orders', 'digicommerce' ); ?>">
		</p>

		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', 'digicommerce' ); ?></label>
				<select name="action" id="bulk-action-selector-top">
					<option value="-1"><?php esc_html_e( 'Bulk actions', 'digicommerce' ); ?></option>
					<?php if ( 'trash' === $current_status ) : ?>
						<option value="restore"><?php esc_html_e( 'Restore', 'digicommerce' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete Permanently', 'digicommerce' ); ?></option>
					<?php else : ?>
						<option value="mark_completed"><?php esc_html_e( 'Mark as Completed', 'digicommerce' ); ?></option>
						<option value="mark_processing"><?php esc_html_e( 'Mark as Processing', 'digicommerce' ); ?></option>
						<option value="mark_cancelled"><?php esc_html_e( 'Mark as Cancelled', 'digicommerce' ); ?></option>
						<option value="trash"><?php esc_html_e( 'Move to Trash', 'digicommerce' ); ?></option>
					<?php endif; ?>
				</select>
				<input type="submit" id="doaction" class="button action" value="<?php esc_attr_e( 'Apply', 'digicommerce' ); ?>">
			</div>

			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php
					printf(
						// translators: %s: total number of items
						esc_html( _n( '%s item', '%s items', $total_items, 'digicommerce' ) ),
						esc_html( number_format_i18n( $total_items ) )
					);
					?>
				</span>

				<?php if ( empty( $_GET['s'] ) ) : // phpcs:ignore ?>
					<?php
					$total_pages = ceil( $total_items / $per_page );
					if ( $total_pages > 1 ) {
						$current_page = max( 1, $pagenum );
						$base_url     = add_query_arg( 'paged', '%#%' );

						// Generate the pagination links
						$pagination_links = paginate_links(
							array(
								'base'      => $base_url,
								'format'    => '',
								'current'   => $current_page,
								'total'     => $total_pages,
								'prev_text' => '<span aria-hidden="true">‹</span><span class="screen-reader-text">' . esc_html__( 'Previous page', 'digicommerce' ) . '</span>',
								'next_text' => '<span aria-hidden="true">›</span><span class="screen-reader-text">' . esc_html__( 'Next page', 'digicommerce' ) . '</span>',
								'type'      => 'array',
							)
						);

						if ( $pagination_links ) {
							echo '<span class="pagination-links">';

							// First page link
							if ( $current_page > 1 ) {
								echo '<a class="first-page button" href="' . esc_url( add_query_arg( 'paged', 1 ) ) . '"><span class="screen-reader-text">' . esc_html__( 'First page', 'digicommerce' ) . '</span><span aria-hidden="true">«</span></a>';
							} else {
								echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>';
							}

							// Previous page link
							if ( $current_page > 1 ) {
								echo '<a class="prev-page button" href="' . esc_url( add_query_arg( 'paged', $current_page - 1 ) ) . '"><span class="screen-reader-text">' . esc_html__( 'Previous page', 'digicommerce' ) . '</span><span aria-hidden="true">‹</span></a>';
							} else {
								echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>';
							}

							// Page numbers input
							echo '<span class="paging-input">
                                <label for="current-page-selector" class="screen-reader-text">' . esc_html__( 'Current Page', 'digicommerce' ) . '</label>
                                <span class="tablenav-paging-text"> ' . esc_attr( $current_page ) . ' ' . esc_html__( 'of', 'digicommerce' ) . ' <span class="total-pages">' . esc_html( $total_pages ) . '</span></span>
                            </span>';

							// Next page link
							if ( $current_page < $total_pages ) {
								echo '<a class="next-page button" href="' . esc_url( add_query_arg( 'paged', $current_page + 1 ) ) . '"><span class="screen-reader-text">' . esc_html__( 'Next page', 'digicommerce' ) . '</span><span aria-hidden="true">›</span></a>';
							} else {
								echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>';
							}

							// Last page link
							if ( $current_page < $total_pages ) {
								echo '<a class="last-page button" href="' . esc_url( add_query_arg( 'paged', $total_pages ) ) . '"><span class="screen-reader-text">' . esc_html__( 'Last page', 'digicommerce' ) . '</span><span aria-hidden="true">»</span></a>';
							} else {
								echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>';
							}

							echo '</span>';
						}
					}
					?>
				<?php endif; ?>
			</div>
			<br class="clear">
		</div>

		<table class="digi-orders wp-list-table widefat fixed striped table-view-list posts">
			<thead>
				<tr>
					<td id="cb" class="manage-column column-cb check-column">
						<label class="screen-reader-text" for="cb-select-all-1">
							<?php esc_html_e( 'Select All', 'digicommerce' ); ?>
						</label>
						<input id="cb-select-all-1" type="checkbox">
					</td>
					<th scope="col" class="manage-column column-order">
						<?php esc_html_e( 'Order', 'digicommerce' ); ?>
					</th>
					<th scope="col" class="manage-column column-date">
						<?php esc_html_e( 'Date', 'digicommerce' ); ?>
					</th>
					<th scope="col" class="manage-column column-status">
						<?php esc_html_e( 'Status', 'digicommerce' ); ?>
					</th>
					<th scope="col" class="manage-column column-total">
						<?php esc_html_e( 'Total', 'digicommerce' ); ?>
					</th>
					<th scope="col" class="manage-column column-billing">
						<?php esc_html_e( 'Billing', 'digicommerce' ); ?>
					</th>
				</tr>
			</thead>

			<tbody id="the-list">
				<?php
				if ( ! empty( $orders ) ) :
					foreach ( $orders as $order ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
						?>
						<tr id="post-<?php echo esc_attr( $order['id'] ); ?>" class="iedit">
							<th scope="row" class="check-column">
								<label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $order['id'] ); ?>">
									<?php
									printf(
										// translators: %s: order number
										esc_html__( 'Select order %s', 'digicommerce' ),
										esc_attr( $order['order_number'] )
									);
									?>
								</label>
								<input id="cb-select-<?php echo esc_attr( $order['id'] ); ?>" 
										type="checkbox" name="post[]" 
										value="<?php echo esc_attr( $order['id'] ); ?>">
							</th>
							<td class="column-order has-row-actions">
								<?php
								$edit_link = add_query_arg(
									array(
										'action'   => 'edit',
										'id'       => $order['id'],
										'_wpnonce' => wp_create_nonce( 'edit_order_' . $order['id'] ),
									),
									admin_url( 'admin.php?page=digi-orders' )
								);
								?>
								<strong>
									<a class="row-title" href="<?php echo esc_url( $edit_link ); ?>">
										<?php echo esc_html( $order['order_number'] ); ?>
									</a>
								</strong>

								<?php
								// Generate a single nonce for each action type
								$actions       = array( 'trash', 'restore', 'delete_permanently' );
								$action_nonces = array();
								foreach ( $actions as $action ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
									$action_nonces[ $action ] = wp_create_nonce( "digi_order_{$action}_{$order['id']}" );
								}

								// Trash link
								$trash_link = add_query_arg(
									array(
										'action' => 'trash',
										'id'     => $order['id'],
										'nonce'  => $action_nonces['trash'],
									),
									admin_url( 'admin.php?page=digi-orders' )
								);

								// Restore link
								$restore_link = add_query_arg(
									array(
										'action' => 'restore',
										'id'     => $order['id'],
										'nonce'  => $action_nonces['restore'],
									),
									admin_url( 'admin.php?page=digi-orders' )
								);

								// Delete Permanently link
								$delete_link = add_query_arg(
									array(
										'action' => 'delete_permanently',
										'id'     => $order['id'],
										'nonce'  => $action_nonces['delete_permanently'],
									),
									admin_url( 'admin.php?page=digi-orders' )
								);
								?>
								
								<div class="row-actions">
									<?php if ( 'trash' === $order['status'] ) : ?>
										<span class="restore">
											<a href="<?php echo esc_url( $restore_link ); ?>" class="submitdelete" aria-label="<?php esc_html_e( 'Delete permanently', 'digicommerce' ); ?>">
												<?php esc_html_e( 'Restore', 'digicommerce' ); ?>
											</a> |
										</span>
										<span class="delete">
											<a href="<?php echo esc_url( $delete_link ); ?>" class="submitdelete" aria-label="<?php esc_html_e( 'Delete permanently', 'digicommerce' ); ?>">
												<?php esc_html_e( 'Delete Permanently', 'digicommerce' ); ?>
											</a>
										</span>
									<?php else : ?>
										<span class="edit">
											<a href="<?php echo esc_url( $edit_link ); ?>" 
												aria-label="<?php esc_html__( 'Edit', 'digicommerce' ); ?>">
												<?php esc_html_e( 'Edit', 'digicommerce' ); ?>
											</a> |
										</span>
										<span class="trash">
											<a href="<?php echo esc_url( $trash_link ); ?>" class="submitdelete" aria-label="<?php esc_html_e( 'Move to the Trash', 'digicommerce' ); ?>">
												<?php esc_html_e( 'Trash', 'digicommerce' ); ?>
											</a>
										</span>
									<?php endif; ?>
								</div>
							</td>
							<td class="column-date">
								<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order['date_created'] ) ) ); ?>
							</td>
							<td class="column-status">
								<span class="order-status status-<?php echo esc_attr( $order['status'] ); ?>">
									<?php echo esc_html( ucfirst( $order['status'] ) ); ?>
								</span>
							</td>
							<td class="column-total">
								<?php echo DigiCommerce_Product::instance()->format_price( $order['total'], 'total' ); // phpcs:ignore ?>
							</td>
							<td class="column-billing">
								<?php
								if ( ! empty( $order['first_name'] ) || ! empty( $order['last_name'] ) ) {
									echo esc_html( $order['first_name'] . ' ' . $order['last_name'] );
									if ( ! empty( $order['email'] ) ) {
										echo '<br>' . esc_html( $order['email'] );
									}
								} else {
									echo '—';
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr class="no-items">
						<td class="colspanchange" colspan="6">
							<?php esc_html_e( 'No orders found.', 'digicommerce' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>

			<tfoot>
				<tr>
					<td class="manage-column column-cb check-column">
						<label class="screen-reader-text" for="cb-select-all-2">
							<?php esc_html_e( 'Select All', 'digicommerce' ); ?>
						</label>
						<input id="cb-select-all-2" type="checkbox">
					</td>
					<th scope="col" class="manage-column column-order">
						<?php esc_html_e( 'Order', 'digicommerce' ); ?>
					</th>
					<th scope="col" class="manage-column column-date">
						<?php esc_html_e( 'Date', 'digicommerce' ); ?>
					</th>
					<th scope="col" class="manage-column column-status">
						<?php esc_html_e( 'Status', 'digicommerce' ); ?>
					</th>
					<th scope="col" class="manage-column column-total">
						<?php esc_html_e( 'Total', 'digicommerce' ); ?>
					</th>
					<th scope="col" class="manage-column column-billing">
						<?php esc_html_e( 'Billing', 'digicommerce' ); ?>
					</th>
				</tr>
			</tfoot>
		</table>

		<div class="tablenav bottom">
			<div class="alignleft actions bulkactions">
				<label for="bulk-action-selector-bottom" class="screen-reader-text">
					<?php esc_html_e( 'Select bulk action', 'digicommerce' ); ?>
				</label>
				<select name="action2" id="bulk-action-selector-bottom">
					<option value="-1"><?php esc_html_e( 'Bulk actions', 'digicommerce' ); ?></option>
					<?php if ( 'trash' === $current_status ) : ?>
						<option value="restore"><?php esc_html_e( 'Restore', 'digicommerce' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete Permanently', 'digicommerce' ); ?></option>
					<?php else : ?>
						<option value="mark_completed"><?php esc_html_e( 'Mark as Completed', 'digicommerce' ); ?></option>
						<option value="mark_processing"><?php esc_html_e( 'Mark as Processing', 'digicommerce' ); ?></option>
						<option value="mark_cancelled"><?php esc_html_e( 'Mark as Cancelled', 'digicommerce' ); ?></option>
						<option value="trash"><?php esc_html_e( 'Move to Trash', 'digicommerce' ); ?></option>
					<?php endif; ?>
				</select>
				<input type="submit" name="action2" id="doaction2" class="button action" 
						value="<?php esc_attr_e( 'Apply', 'digicommerce' ); ?>">
			</div>

			

			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php
					printf(
						esc_html(
							// translators: %s: total number of items
							_n(
								'%s item',
								'%s items',
								$total_items,
								'digicommerce'
							)
						),
						esc_html( number_format_i18n( $total_items ) )
					);
					?>
				</span>

				<?php
				if ( empty( $_GET['s'] ) ) : // phpcs:ignore
					$total_pages = ceil( $total_items / $per_page );
					if ( $total_pages > 1 ) {
						$current_page = max( 1, $pagenum );
						$base_url     = add_query_arg( 'paged', '%#%' );

						// Generate the pagination links
						$pagination_links = paginate_links(
							array(
								'base'      => $base_url,
								'format'    => '',
								'current'   => $current_page,
								'total'     => $total_pages,
								'prev_text' => '<span aria-hidden="true">‹</span><span class="screen-reader-text">' . esc_html__( 'Previous page', 'digicommerce' ) . '</span>',
								'next_text' => '<span aria-hidden="true">›</span><span class="screen-reader-text">' . esc_html__( 'Next page', 'digicommerce' ) . '</span>',
								'type'      => 'array',
							)
						);

						if ( $pagination_links ) {
							echo '<span class="pagination-links">';

							// First page link
							if ( $current_page > 1 ) {
								echo '<a class="first-page button" href="' . esc_url( add_query_arg( 'paged', 1 ) ) . '"><span class="screen-reader-text">' . esc_html__( 'First page', 'digicommerce' ) . '</span><span aria-hidden="true">«</span></a>';
							} else {
								echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>';
							}

							// Previous page link
							if ( $current_page > 1 ) {
								echo '<a class="prev-page button" href="' . esc_url( add_query_arg( 'paged', $current_page - 1 ) ) . '"><span class="screen-reader-text">' . esc_html__( 'Previous page', 'digicommerce' ) . '</span><span aria-hidden="true">‹</span></a>';
							} else {
								echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>';
							}

							// Page numbers input
							echo '<span class="paging-input">
                                <label for="current-page-selector" class="screen-reader-text">' . esc_html__( 'Current Page', 'digicommerce' ) . '</label>
                                <span class="tablenav-paging-text"> ' . esc_attr( $current_page ) . ' ' . esc_html__( 'of', 'digicommerce' ) . ' <span class="total-pages">' . esc_html( $total_pages ) . '</span></span>
                            </span>';

							// Next page link
							if ( $current_page < $total_pages ) {
								echo '<a class="next-page button" href="' . esc_url( add_query_arg( 'paged', $current_page + 1 ) ) . '"><span class="screen-reader-text">' . esc_html__( 'Next page', 'digicommerce' ) . '</span><span aria-hidden="true">›</span></a>';
							} else {
								echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>';
							}

							// Last page link
							if ( $current_page < $total_pages ) {
								echo '<a class="last-page button" href="' . esc_url( add_query_arg( 'paged', $total_pages ) ) . '"><span class="screen-reader-text">' . esc_html__( 'Last page', 'digicommerce' ) . '</span><span aria-hidden="true">»</span></a>';
							} else {
								echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>';
							}

							echo '</span>';
						}
					}
					?>
				<?php endif; ?>
			</div>
			<br class="clear">
		</div>
	</form>
</div>