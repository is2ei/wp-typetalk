<?php
/**
 * View for Integration Setting Meta Box.
 *
 * @package WP_Typetalk
 * @subpackage View
 */
?>
<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row">
				<label for="typetalk_setting[endpoint_url]"><?php _e( 'Post message URL', 'typetalk' ); ?></label>
			</th>
			<td>
				<input type="text" class="regular-text" name="typetalk_setting[endpoint_url]" id="typetalk_setting[endpoint_url]" value="<?php echo ! empty( $setting['endpoint_url'] ) ? esc_url( $setting['endpoint_url'] ) : ''; ?>">
				<p class="description">
					<?php _e( 'Your post message URL. The format is <code>https://typetalk.com/api/v1/topics/:topicId?typetalkToken=TYPETALK_TOKEN</code>.', 'typetalk' ); ?>
				</p>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row">
				<?php _e( 'Events to Notify', 'typetalk' ); ?>
			</th>
			<td>
				<?php foreach ( $events as $event => $e ) : ?>
					<?php
					$field         = "typetalk_setting[events][$event]";
					$default_value = ! empty( $e['default'] ) ? $e['default'] : false;
					$value         = isset( $setting['events'][ $event ] ) ? $setting['events'][ $event ] : $default_value;
					?>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" value="1" <?php checked( $value ); ?>>
						<?php echo esc_html( $e['description'] ); ?>
					</label>
					<br>
			<?php endforeach; ?>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row">
				<label for="typetalk_setting[active]"><?php _e( 'Active', 'typetalk' ); ?></label>
			</th>
			<td>
				<label>
					<input type="checkbox" name="typetalk_setting[active]" id="typetalk_setting[active]" <?php checked( ! empty( $setting['active'] ) ? $setting['active'] : false ); ?>>
					<?php _e( 'Activate Notifications.', 'typetalk' ); ?>
				</label>
				<p class="description">
					<?php _e( 'Notification will not be sent if not checked.', 'typetalk' ); ?>
				</p>
			</td>
		</tr>

		<?php if ( 'publish' === $post->post_status ) : ?>
		<tr valign="top">
			<th scope="row"></th>
			<td>
				<div id="typetalk-test-notify">
					<input id="typetalk-test-notify-nonce" type="hidden" value="<?php echo esc_attr( wp_create_nonce( 'test_notify_nonce' ) ); ?>">
					<button class="button" id="typetalk-test-notify-button"><?php _e( 'Test send notification with this setting.', 'typetalk' ); ?></button>
					<div class="spinner is-active"></div>
				</div>
				<div id="typetalk-test-notify-response"></div>
			</td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>
