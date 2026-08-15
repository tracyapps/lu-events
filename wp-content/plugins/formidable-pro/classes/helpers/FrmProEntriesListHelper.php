<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProEntriesListHelper extends FrmEntriesListHelper {

	/**
	 * A search term can match a field in a repeater or an embedded form. The row that
	 * matches then lives in a child form rather than in the form being listed, so it has
	 * to be swapped for its parent entry before the list can be paginated. That case
	 * skips the SQL LIMIT and paginates in PHP instead.
	 *
	 * @since 6.34
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $per_page;

		$this->set_per_page();

		$join_form_in_query = false;
		$s_query            = $this->get_search_query( $join_form_in_query );
		$order              = $this->get_order_by();
		$embedded_form_ids  = $this->get_embedded_form_ids_to_search();

		if ( ! $this->is_searching_child_forms( $s_query, $embedded_form_ids ) ) {
			// Every matching row belongs to the listed form, so let the database paginate and count.
			$this->items = FrmEntry::getAll( $s_query, $order, $this->get_limit( $per_page ), true, $join_form_in_query );
			$this->set_total_items( $s_query );
			$this->prepare_pagination();
			return;
		}

		// Load every match without a limit so child entries can be mapped back to their parents.
		$this->items = FrmEntry::getAll( $s_query, $order, false, true, $join_form_in_query );

		if ( $embedded_form_ids ) {
			$s_query_embedded = $this->get_search_query_for_embedded_forms( $embedded_form_ids );
			$this->items     += FrmEntry::getAll( $s_query_embedded, $order, false, true, $join_form_in_query );
		}

		$this->replace_child_entries_with_parents( $order );
		$this->paginate_loaded_items( $per_page );
		$this->prepare_pagination();
	}

	/**
	 * The ids of the forms embedded in the form being listed. Only returns ids while a
	 * search is running, since there is nothing to look for in them otherwise.
	 *
	 * @since 6.34
	 *
	 * @return array
	 */
	private function get_embedded_form_ids_to_search() {
		if ( ! $this->is_searching_for_term() ) {
			return array();
		}

		return FrmProFormsHelper::get_embedded_form_ids( $this->params['form'] );
	}

	/**
	 * Returns true when the search covers repeater or embedded forms. Matches in those
	 * forms have to be resolved to parent entries in PHP, so the database cannot paginate
	 * the result.
	 *
	 * @since 6.34
	 *
	 * @param array $s_query          The search query for the listed form.
	 * @param array $embedded_form_ids The ids of forms embedded in the listed form.
	 *
	 * @return bool
	 */
	private function is_searching_child_forms( $s_query, $embedded_form_ids ) {
		if ( ! $this->is_searching_for_term() ) {
			return false;
		}

		if ( $embedded_form_ids ) {
			return true;
		}

		// get_form_ids() adds the repeater form ids to the query while searching.
		return ! empty( $s_query['it.form_id'] ) && is_array( $s_query['it.form_id'] ) && count( $s_query['it.form_id'] ) > 1;
	}

	/**
	 * Reduces items that were loaded without a SQL LIMIT down to the requested page.
	 *
	 * @since 6.34
	 *
	 * @param int $per_page
	 *
	 * @return void
	 */
	private function paginate_loaded_items( $per_page ) {
		$this->total_items = count( $this->items );
		$offset            = ( $this->get_pagenum() - 1 ) * $per_page;
		$this->items       = array_slice( $this->items, $offset, $per_page );
	}

	/**
	 * Prepares search query for embedded forms.
	 *
	 * @since 6.34
	 *
	 * @param array $embedded_form_ids
	 *
	 * @return array
	 */
	private function get_search_query_for_embedded_forms( $embedded_form_ids ) {
		$s_query_embedded = array(
			'it.form_id' => $embedded_form_ids,
		);

		$s   = $this->get_search_term();
		$fid = $this->get_param( array( 'param' => 'fid' ) );

		return FrmProEntriesHelper::get_search_str( $s_query_embedded, $s, $embedded_form_ids, $fid );
	}

	/**
	 * Returns true if searching for a term.
	 *
	 * @since 6.34
	 *
	 * @return bool
	 */
	private function is_searching_for_term() {
		return '' !== $this->get_search_term();
	}

	/**
	 * @since 6.34
	 *
	 * @return string
	 */
	private function get_search_term() {
		return $this->get_param(
			array(
				'param'    => 's',
				'sanitize' => 'sanitize_text_field',
			)
		);
	}

	/**
	 * @since 6.34
	 *
	 * @param int $form_id
	 *
	 * @return array
	 */
	protected function get_form_ids( $form_id ) {
		if ( ! $this->is_searching_for_term() ) {
			return array( (int) $form_id );
		}

		$form_ids   = FrmProFormsHelper::get_repeater_form_ids( $form_id );
		$form_ids[] = $form_id;

		return $form_ids;
	}

	/**
	 * Swaps repeater and embedded form entries for the parent entries they belong to. Those
	 * child entries matched the search but are not rows in this list.
	 *
	 * @since 6.34
	 *
	 * @param string $order
	 *
	 * @return void
	 */
	private function replace_child_entries_with_parents( $order ) {
		$child_entries = array_filter(
			$this->items,
			function ( $item ) {
				return ! empty( $item->parent_item_id );
			}
		);

		if ( ! $child_entries ) {
			return;
		}

		$item_ids = array_unique(
			array_merge(
				wp_list_pluck( $this->items, 'id' ),
				wp_list_pluck( $child_entries, 'parent_item_id' )
			)
		);

		/*
		 * Query again so the entries come back in the requested order without duplicates.
		 * Restricting to the listed form drops the child entries themselves, along with any
		 * parent that belongs to another form using the same embedded form.
		 */
		$this->items = FrmEntry::getAll(
			array(
				'it.id'      => $item_ids,
				'it.form_id' => $this->params['form'],
			),
			$order,
			false,
			true,
			false
		);
	}

	public function get_bulk_actions() {
		$actions = array(
			'bulk_delete' => __( 'Delete', 'formidable' ),
		);

		if ( ! current_user_can( 'frm_delete_entries' ) ) {
			unset( $actions['bulk_delete'] );
		}

		// $actions['bulk_export'] = __( 'Export to XML', 'formidable-pro' );
		if ( $this->params['form'] ) {
			$actions['bulk_csv'] = __( 'Export to CSV', 'formidable-pro' );
		}

		return $actions;
	}

	protected function extra_tablenav( $which ) {
		parent::extra_tablenav( $which );
		$is_footer    = $which !== 'top';
		$entries_args = array(
			'entries_count'                    => $this->total_items,
			'bulk_delete_confirmation_message' => $this->confirm_bulk_delete(),
		);
		FrmProEntriesHelper::before_table( $is_footer, $this->params['form'], $entries_args );
	}

	/**
	 * The fields offered in the search dropdown come from the listed form plus its repeater
	 * and embedded forms, so a search can target a field in any of them.
	 *
	 * @since 6.34
	 *
	 * @param int $form_id
	 *
	 * @return array
	 */
	private function get_entries_search_box_where( $form_id ) {
		return array(
			'fi.form_id' => FrmProFormsHelper::get_searchable_form_ids( $form_id ),
		);
	}

	public function search_box( $text, $input_id ) {
		if ( ! $this->has_items() && ! isset( $_REQUEST['s'] ) ) {
			return;
		}

		if ( isset( $this->params['form'] ) ) {
			$form = FrmForm::getOne( $this->params['form'] );
		} else {
			$form = FrmForm::get_published_forms( array(), 1 );
		}

		if ( ! $form ) {
			return;
		}

		$where                = $this->get_entries_search_box_where( $form->id );
		$where['fi.type not'] = FrmField::no_save_fields();

		$field_list = FrmField::getAll( $where, 'field_order' );
		$fid        = $this->get_param( array( 'param' => 'fid' ) );
		$input_id   = $input_id . '-search-input';
		$search_str = $this->get_search_term();

		foreach ( array( 'orderby', 'order' ) as $get_var ) {
			$var_value = FrmAppHelper::get_param( $get_var, '', 'request', 'sanitize_text_field' );

			if ( $var_value ) {
				echo '<input type="hidden" name="' . esc_attr( $get_var ) . '" value="' . esc_attr( $var_value ) . '" />';
			}
		}

		$options = self::get_entry_search_options( $field_list );
?>
<div class="frm-search">
	<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
	<?php FrmAppHelper::icon_by_class( 'frmfont frm_search_icon' ); ?>
	<input type="text" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php echo esc_attr( $search_str ); ?>" class="frm-search-input" />
	<?php
	if ( ! $field_list ) {
			submit_button( $text, 'button', false, false, array( 'id' => 'search-submit' ) );
			echo '</div>';
			return;
	}
	?>
	<select name="fid" class="hide-if-js">
		<?php
		foreach ( $options as $v => $opt ) {
			?>
			<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $fid, $v ); ?>>
				<?php echo esc_html( $opt ); ?>
			</option>
			<?php
		}
		?>
	</select>

	<div class="button dropdown hide-if-no-js" id="search-submit">
		<a href="#" id="frm-fid-search" class="frm-dropdown-toggle" data-toggle="dropdown">
			<?php esc_html_e( 'Search', 'formidable' ); ?>
			<b class="caret"></b>
		</a>
		<ul class="frm-dropdown-menu <?php echo esc_attr( is_rtl() ? 'dropdown-menu-left' : 'dropdown-menu-right' ); ?>" id="frm-fid-search-menu" role="menu" aria-labelledby="frm-fid-search">
			<?php
			foreach ( $options as $v => $opt ) {
				?>
			<li>
				<a href="#" id="fid-<?php echo esc_attr( $v ); ?>">
					<?php echo esc_html( $opt ); ?>
				</a>
			</li>
				<?php
			}
			?>
		</ul>
	</div>
		<?php
		submit_button( $text, 'button hide-if-js', false, false, array( 'id' => 'search-submit' ) );
		?>

</div>
<?php
	}

	/**
	 * @since 4.04.02
	 *
	 * @param array $field_list
	 */
	private static function get_entry_search_options( $field_list ) {
		$options = array(
			''            => '&mdash; ' . __( 'All Fields', 'formidable-pro' ) . ' &mdash;',
			'created_at'  => __( 'Entry creation date', 'formidable' ),
			'id'          => __( 'Entry ID', 'formidable' ),
			'description' => __( 'Entry description', 'formidable-pro' ),
		);

		foreach ( $field_list as $f ) {
			$value             = $f->type === 'user_id' ? 'user_id' : $f->id;
			$options[ $value ] = FrmAppHelper::truncate( $f->name, 30 );
		}

		return apply_filters( 'frm_admin_search_options', $options, compact( 'field_list' ) );
	}
}
