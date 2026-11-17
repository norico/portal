<?php
/**
 * Portal Admin - General Tab Template
 * 
 * @package Portal
 * @var array $settings Current settings for this tab.
 */

defined( 'ABSPATH' ) || exit;

// Note: Nécessaire pour que la chaine soit traduite.
__('General', 'portal');

// Get saved sites or initialize empty array
$portal_sites = $settings['portal_sites'] ?? [];
if ( ! is_array( $portal_sites ) ) {
	$portal_sites = [];
}
?>
<table class="form-table">
	<tbody>
		<tr>
			<th scope="row">
				<label><?php esc_html_e( 'WordPress Sites', 'portal' ); ?></label>
			</th>
			<td>
				<fieldset>
					<legend class="screen-reader-text">
						<?php esc_html_e( 'Add WordPress site URLs to fetch RSS feeds from', 'portal' ); ?>
					</legend>
					
					<div id="portal-sites-container" class="portal-sites-container">
						<?php if ( ! empty( $portal_sites ) ) : ?>
							<?php foreach ( $portal_sites as $index => $site_data ) : ?>
								<?php 
								// Handle both old format (string) and new format (array)
								if ( is_array( $site_data ) ) {
									$site_url = $site_data['site_url'] ?? '';
									$feed_url = $site_data['feed_url'] ?? false;
									$is_valid = $site_data['valid'] ?? false;
								} else {
									$site_url = $site_data;
									$feed_url = false;
									$is_valid = false;
								}
								?>
								<div class="portal-site-row" data-index="<?php echo esc_attr( $index ); ?>">
									<div class="portal-site-input-wrapper">
										<input type="url" 
											name="portal_sites[]" 
											value="<?php echo esc_url( $site_url ); ?>" 
											class="regular-text portal-site-url <?php echo ! empty( $site_url ) && $is_valid ? 'has-check' : ''; ?>" 
											placeholder="<?php esc_attr_e( 'https://example.com', 'portal' ); ?>" 
											required>
										<?php if ( ! empty( $site_url ) && $is_valid ) : ?>
											<span class="portal-input-check" title="<?php esc_attr_e( 'Valid RSS feed detected', 'portal' ); ?>">✓</span>
										<?php endif; ?>
									</div>
									
									<?php if ( ! empty( $site_url ) && $is_valid && $feed_url ) : ?>
										<a href="<?php echo esc_url( $feed_url ); ?>" 
											class="button secondary portal-feed-button" 
											target="_blank" 
											title="<?php esc_attr_e( 'View RSS Feed', 'portal' ); ?>">
											<?php esc_html_e( 'RSS', 'portal' ); ?>
										</a>
									<?php endif; ?>
									
									<button type="button" 
										class="button secondary portal-remove-site" 
										title="<?php esc_attr_e( 'Remove', 'portal' ); ?>">
										<?php esc_html_e( 'Remove', 'portal' ); ?>
									</button>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<div class="portal-site-row" data-index="0">
								<div class="portal-site-input-wrapper">
									<input type="url" 
										name="portal_sites[]" 
										class="regular-text portal-site-url" 
										placeholder="<?php esc_attr_e( 'https://example.com', 'portal' ); ?>" 
										required>
								</div>
								<button type="button" 
									class="button secondary portal-remove-site" 
									title="<?php esc_attr_e( 'Remove', 'portal' ); ?>">
									<?php esc_html_e( 'Remove', 'portal' ); ?>
								</button>
							</div>
						<?php endif; ?>
					</div>
					
					<button type="button" 
						id="portal-add-site" 
						class="button secondary">
						<?php esc_html_e( 'Add WordPress Site', 'portal' ); ?>
					</button>
					
					<p class="description">
						<?php esc_html_e( 'Enter WordPress site URLs (one per field). The plugin will automatically fetch the RSS feed.', 'portal' ); ?>
					</p>
					<p class="description">
						<?php esc_html_e( 'Example: ', 'portal' ); ?>
						<code>https://example.com</code>
					</p>
				</fieldset>
			</td>
		</tr>
	</tbody>
</table>

<style>
	.portal-sites-container {
		margin-bottom: 10px;
	}
	.portal-site-row {
		margin-bottom: 8px;
		display: flex;
		gap: 8px;
		align-items: center;
		flex-wrap: wrap;
	}
	.portal-site-input-wrapper {
		position: relative;
		flex: 1;
		max-width: 400px;
	}
	.portal-site-input-wrapper input[type="url"] {
		width: 100%;
		padding-right: 28px;
		box-sizing: border-box;
	}
	.portal-input-check {
		position: absolute;
		right: 8px;
		top: 50%;
		transform: translateY(-50%);
		color: #4CAF50;
		font-size: 18px;
		pointer-events: none;
	}
	.portal-site-row .portal-remove-site {
		flex: 0 0 auto;
		min-width: 80px;
	}
	.portal-remove-site {
		margin-top: 0;
	}
	.portal-feed-button {
		flex: 0 0 auto;
		margin-top: 0;
	}
</style>

<script>
	jQuery(document).ready(function($) {
		var container = $('#portal-sites-container');
		var rowCount = container.find('.portal-site-row').length;

		// Add new site row
		$('#portal-add-site').on('click', function(e) {
			e.preventDefault();
			var index = rowCount++;
			var newRow = $(
				'<div class="portal-site-row" data-index="' + index + '">' +
					'<div class="portal-site-input-wrapper">' +
						'<input type="url" name="portal_sites[]" class="regular-text portal-site-url" placeholder="<?php esc_attr_e( 'https://example.com', 'portal' ); ?>" required>' +
					'</div>' +
					'<button type="button" class="button secondary portal-remove-site" title="<?php esc_attr_e( 'Remove', 'portal' ); ?>"><?php esc_html_e( 'Remove', 'portal' ); ?></button>' +
				'</div>'
			);
			container.append(newRow);
		});

		// Remove site row
		$(document).on('click', '.portal-remove-site', function(e) {
			e.preventDefault();
			var row = $(this).closest('.portal-site-row');
			
			// Always keep at least one row
			if (container.find('.portal-site-row').length > 1) {
				row.remove();
				rowCount--;
				// Renumber indices
				container.find('.portal-site-row').each(function(i) {
					$(this).attr('data-index', i);
				});
			} else {
				// Clear the input instead of removing
				row.find('input').val('');
			}
		});
	});
</script>
