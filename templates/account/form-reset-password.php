<?php
/**
 * Reset password form template
 *
 * @var string $login
 * @var string $key
 * @var bool $is_valid_key
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="w-[375px] max-w-[90%] py-16 mdl:py-28 mx-auto">
	<div class="flex flex-col items-center">
		<!-- Logo -->
		<div class="relative flex items-center justify-center w-full mb-12">
			<?php
			$custom_logo_id = get_theme_mod( 'custom_logo' );
			if ( $custom_logo_id ) {
				echo wp_get_attachment_image(
					$custom_logo_id,
					'full',
					false,
					array(
						'class'   => 'h-auto max-h-20 w-auto',
						'loading' => 'eager',
					)
				);
			} else {
				echo '<span class="text-2xl font-bold">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
			}
			?>
		</div>

		<?php if ( $is_valid_key ) : ?>
			<form id="digicommerce-reset-password-form" class="digi__form w-full">
				<p class="flex justify-start mb-4">
					<button type="button" id="back-to-login" class="return__link flex items-center gap-2 text-sm text-dark-blue hover:text-gold default-transition" onclick="window.location.href='<?php echo esc_url( get_permalink( DigiCommerce()->get_option( 'account_page_id' ) ) ); ?>'">
						<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
						<?php esc_html_e( 'Back to login', 'digicommerce' ); ?>
					</button>
				</p>

				<div class="bg-light-blue-bg rounded-xl p-4 mb-6 flex gap-4">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 63 48" width="40" height="30"><g clip-path="url(#clip0)"><path d="M55.4 46H22c-3.6 0-5.8-4-4-7l16.8-28c1.8-3 6.2-2.9 8 .1l16.5 28c1.9 3-.4 6.9-4 6.9z" fill="#FFE599"></path><path d="M46 46H8.3c-3.6 0-5.8-4-4-7L23.4 7.5c1.8-3 6.1-3 8 0L50 39c1.9 3-.3 7-4 7z" fill="#ccb161"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M22 46h24c3.7 0 5.9-4 4-7L34 12.3 18 39c-1.8 3 .4 7 4 7z" fill="#09053a"></path><rect width="15.5" height="4.1" rx="2.1" transform="matrix(0 -1 -1 0 28.4 32.6)" fill="#fff"></rect><rect width="4.2" height="4.1" rx="2.1" transform="matrix(0 -1 -1 0 28.4 38.9)" fill="#fff"></rect></g><defs><clipPath id="clip0"><path fill="#fff" d="M0 0h63v48H0z"></path></clipPath></defs></svg>
					<p class="text-sm text-hover-blue flex-1 m-0">
						<?php esc_html_e( 'Enter your new password below. For better security, it should contain at least 8 characters including uppercase, lowercase, numbers and special characters.', 'digicommerce' ); ?>
					</p>
				</div>

				<div id="reset-password-message" class="message hidden"></div>

				<input type="hidden" name="rp_key" value="<?php echo esc_attr( $key ); ?>">
				<input type="hidden" name="rp_login" value="<?php echo esc_attr( $login ); ?>">

				<div class="field mb-4 relative">
					<input type="password" id="password" name="password" class="default-transition" required>
					<label for="password">
						<?php esc_html_e( 'New password', 'digicommerce' ); ?>
					</label>

					<button type="button" class="pass__icon p-0 m-0 no-background default-transition">
						<svg class="w-6 h-6" data-hide fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
						<svg class="w-6 h-6 hidden" data-show fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
					</button>
				</div>

				<div class="password-strength">
					<div class="password-strength-meter">
						<div class="password-strength-meter-bar"></div>
					</div>
					<p class="password-strength-text"></p>
				</div>

				<!-- Password requirements -->
				<div class="mb-6">
					<ul class="text-xs text-gray-500 space-y-1">
						<li class="flex items-center gap-2" data-requirement="length">
							<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
							<?php esc_html_e( 'At least 8 characters', 'digicommerce' ); ?>
						</li>
						<li class="flex items-center gap-2" data-requirement="uppercase">
							<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
							<?php esc_html_e( 'One uppercase letter', 'digicommerce' ); ?>
						</li>
						<li class="flex items-center gap-2" data-requirement="lowercase">
							<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
							<?php esc_html_e( 'One lowercase letter', 'digicommerce' ); ?>
						</li>
						<li class="flex items-center gap-2" data-requirement="number">
							<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
							<?php esc_html_e( 'One number', 'digicommerce' ); ?>
						</li>
						<li class="flex items-center gap-2" data-requirement="special">
							<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
							<?php esc_html_e( 'One special character', 'digicommerce' ); ?>
						</li>
					</ul>
				</div>

				<button type="submit" class="digi__button w-full flex items-center justify-center gap-2 bg-gold hover:bg-dark-blue text-dark-blue hover:text-gold py-4 px-6 rounded-md default-transition">
					<span class="text"><?php esc_html_e( 'Reset password', 'digicommerce' ); ?></span>
					<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
				</button>

				<input type="hidden" name="expires" value="<?php echo esc_attr( $_GET['expires'] ); ?>">

				<?php wp_nonce_field( 'digicommerce_reset_password_nonce', 'digicommerce_reset_password_nonce' ); ?>
			</form>
		<?php else : ?>
			<div class="bg-light-blue-bg rounded-xl p-4 mb-6 flex gap-4">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 63 48" width="40" height="30"><g clip-path="url(#clip0)"><path d="M55.4 46H22c-3.6 0-5.8-4-4-7l16.8-28c1.8-3 6.2-2.9 8 .1l16.5 28c1.9 3-.4 6.9-4 6.9z" fill="#FFE599"></path><path d="M46 46H8.3c-3.6 0-5.8-4-4-7L23.4 7.5c1.8-3 6.1-3 8 0L50 39c1.9 3-.3 7-4 7z" fill="#ccb161"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M22 46h24c3.7 0 5.9-4 4-7L34 12.3 18 39c-1.8 3 .4 7 4 7z" fill="#09053a"></path><rect width="15.5" height="4.1" rx="2.1" transform="matrix(0 -1 -1 0 28.4 32.6)" fill="#fff"></rect><rect width="4.2" height="4.1" rx="2.1" transform="matrix(0 -1 -1 0 28.4 38.9)" fill="#fff"></rect></g><defs><clipPath id="clip0"><path fill="#fff" d="M0 0h63v48H0z"></path></clipPath></defs></svg>
				<p class="text-sm text-hover-blue flex-1 m-0">
					<?php esc_html_e( 'This password reset link is invalid or has expired.', 'digicommerce' ); ?>
					<a href="<?php echo esc_url( get_permalink( DigiCommerce()->get_option( 'account_page_id' ) ) ); ?>" class="font-medium underline hover:text-red-600">
						<?php esc_html_e( 'Request a new link', 'digicommerce' ); ?>
					</a>
				</p>
			</div>
		<?php endif; ?>
	</div>
</div>