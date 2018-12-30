<?php
/**
 * View for Save Setting Meta Box in Edit Typetalk Integration screen.
 *
 * @package WP_Typetalk
 * @subpackage View
 */
?>
<div class="submitbox" id="submitpost">

	<?php
	/**
	 * Hidden submit button early on so that the browser chooses the right
	 * button when form is submitted with Return key.
	 */
	?>
	<div style="display:none;">
		<?php submit_button( __( 'Save', 'typetalk' ), 'button', 'save' ); ?>
	</div>

	<?php // Always publish. ?>
	<input type="hidden" name="post_status" id="hidden_post_status" value="publish" />

	<div id="major-publishing-actions">

		<div id="delete-action">
		<?php
		if ( ! EMPTY_TRASH_DAYS ) {
			$delete_text = __( 'Delete Permanently', 'typetalk' );
		} else {
			$delete_text = __( 'Move to Trash', 'typetalk' );
		}
		?>
		<a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post->ID ); ?>"><?php echo $delete_text; ?></a>
		</div>

		<div id="publishing-action">
			<span class="spinner"></span>

			<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Save', 'typetalk' ) ?>" />
			<input name="save" type="submit" class="button button-primary button-large" id="publish" accesskey="p" value="<?php esc_attr_e( 'Save', 'typetalk' ); ?>" />
		</div>
		<div class="clear"></div>

	</div>
	<!-- #major-publishing-actions -->

	<div class="clear"></div>
</div>
<!-- #submitpost -->
