<?php
/**
 * Login form template
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

		<!-- Login Form -->
		<form id="digicommerce-login-form" class="digi__form w-full">
			<?php
			// Safely get and sanitize the redirect_to parameter
			$redirect_to = '';
			if ( isset( $_GET['redirect_to'] ) ) { // phpcs:ignore
				$redirect_to = esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ); // phpcs:ignore
				
				// Additional security: ensure it's a local URL
				if ( ! wp_validate_redirect( $redirect_to ) ) {
					$redirect_to = '';
				}
			}
			?>
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>">

			<?php
			if ( $register_text ) :
				$class = 'm-0 no-margin';
			else :
				$class = 'mb-8';
			endif;
			?>

			<h2 class="text-center text-[1.625rem] leading-normal text-dark-blue <?php echo esc_attr( $class ); ?>"><?php esc_html_e( 'Login to your account', 'digicommerce' ); ?></h2>

			<?php if ( $register_text ) : ?>
				<p class="digi__register text-center text-medium mb-8"><?php echo wp_kses_post( $register_text ); ?></p>
			<?php endif; ?>
			
			<div id="login-message" class="message hidden"></div>
			
			<div class="field mb-4 relative">
				<input type="text" id="username" name="username" class="default-transition" required>
				<label for="username">
					<?php esc_html_e( 'Username or Email', 'digicommerce' ); ?>
				</label>
			</div>
			
			<div class="field mb-4 relative">
				<input type="password" id="password" name="password" class="default-transition" required>
				<label for="password">
					<?php esc_html_e( 'Password', 'digicommerce' ); ?>
				</label>

				<button type="button" class="pass__icon p-0 m-0 no-background default-transition">
					<svg class="w-6 h-6" data-hide fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
					<svg class="w-6 h-6 hidden" data-show fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
				</button>
			</div>

			<div class="flex items-center justify-between gap-4 mb-6">
				<label class="flex items-center gap-2 cursor-pointer m-0">
					<input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded border border-solid border-gray-300 text-dark-blue focus:ring-dark-blue">
					<span class="text-sm text-gray-700"><?php esc_html_e( 'Remember me', 'digicommerce' ); ?></span>
				</label>

				<button type="button" id="show-lost-password" class="text-sm text-dark-blue hover:text-hover-blue transition-colors no-background">
					<?php esc_html_e( 'Forgot password?', 'digicommerce' ); ?>
				</button>
			</div>

			<button type="submit" class="digi__button w-full flex items-center justify-center gap-2 bg-gold hover:bg-dark-blue text-dark-blue hover:text-gold py-4 px-6 rounded-md default-transition">
				<span class="text"><?php esc_html_e( 'Login', 'digicommerce' ); ?></span>
				<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
			</button>

			<?php wp_nonce_field( 'digicommerce_login_nonce', 'digicommerce_login_nonce' ); ?>

			<?php if ( $recaptcha_enabled ) : ?>
				<div class="g-recaptcha-branding flex items-center justify-center gap-4 mt-4">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="40" height="40"><path d="m505.879 256h-113.631-31.723l28.652-28.653c-13.159-61.484-67.76-107.6-133.179-107.6-5.901 0-11.807.371-17.461 1.108v-114.117c5.781-.369 11.56-.614 17.461-.614 95.918 0 179.168 53.982 221.102 133.3l28.778-28.777v145.353z" fill="#495586"/><path d="m198.692 280.963c-3.813-2.582-4.914-7.749-2.334-11.559 2.461-3.813 7.621-4.921 11.434-2.46l27.673 18.075 56.934-74.396c2.707-3.567 7.994-4.305 11.562-1.477 3.687 2.706 4.423 7.994 1.598 11.559l-61.365 80.3c-2.58 3.689-7.622 4.672-11.435 2.214z" fill="#303c64"/><path d="m255.999 6.124v113.624 31.728l-28.651-28.652c-61.488 13.157-107.597 67.755-107.597 133.176 0 5.901.492 11.804 1.102 17.46h-114.114c-.373-5.778-.618-11.559-.618-17.46 0-95.918 54.108-179.168 133.422-221.104l-28.893-28.772z" fill="#69a7ff"/><path d="m352.284 352.286 80.422 80.423c-45.253 45.129-107.721 73.167-176.707 73.167-95.918 0-179.168-54.108-221.101-133.424l-28.777 28.898v-145.35h113.63 31.723l-28.651 28.651c13.158 61.487 67.758 107.6 133.177 107.6 37.627-.001 71.693-15.249 96.284-39.965z" fill="#ababab"/></svg>
					<div class="flex flex-col">
						<span class="text-sm"><?php esc_html_e( 'protected by', 'digicommerce' ); ?> <strong>reCAPTCHA</strong></span>
						<div class="flex gap-1 text-[.64rem]">
							<a href="https://www.google.com/intl/fr/policies/privacy/" target="_blank" class="text-dark-blue hover:text-gold"><?php esc_html_e( 'Confidentiality', 'digicommerce' ); ?></a><span aria-hidden="true" role="presentation"> - </span><a href="https://www.google.com/intl/fr/policies/terms/" target="_blank" class="text-dark-blue hover:text-gold"><?php esc_html_e( 'Terms', 'digicommerce' ); ?></a>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</form>
		
		<?php
		if ( DigiCommerce()->get_option( 'register_form' ) ) {
			if ( $login_text ) :
				$class = 'm-0 no-margin';
			else :
				$class = 'mb-8';
			endif;
			?>
			<!-- Register Form -->
			<form id="digicommerce-register-form" class="digi__form w-full hidden">
				<h2 class="text-center text-[1.625rem] leading-normal text-dark-blue <?php echo esc_attr( $class ); ?>"><?php esc_html_e( 'Register an account', 'digicommerce' ); ?></h2>

				<?php if ( $login_text ) : ?>
					<p class="digi__login text-center text-medium mb-8"><?php echo wp_kses_post( $login_text ); ?></p>
				<?php endif; ?>

				<div id="register-message" class="message hidden"></div>
				
				<div class="field mb-4 relative">
					<input type="text" id="reg_username" name="username" class="default-transition" required>
					<label class="flex justify-start gap-[.1rem]" for="reg_username">
						<?php esc_html_e( 'Username', 'digicommerce' ); ?>
						<span class="text-red-500">*</span>
					</label>
				</div>

				<div class="field mb-4 relative">
					<input type="email" id="reg_email" name="email" class="default-transition" required>
					<label class="flex justify-start gap-[.1rem]" for="reg_email">
						<?php esc_html_e( 'Email Address', 'digicommerce' ); ?>
						<span class="text-red-500">*</span>
					</label>
				</div>

				<div class="field mb-4 relative">
					<input type="password" id="reg_password" name="password" class="default-transition" required>
					<label class="flex justify-start gap-[.1rem]" for="reg_password">
						<?php esc_html_e( 'Password', 'digicommerce' ); ?>
						<span class="text-red-500">*</span>
					</label>
					<button type="button" class="pass__icon p-0 m-0 no-background default-transition">
						<svg class="w-6 h-6" data-hide fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
						<svg class="w-6 h-6 hidden" data-show fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
					</button>
				</div>

				<div class="field mb-4 relative">
					<input type="password" id="reg_password_repeat" name="password_repeat" class="default-transition" required>
					<label class="flex justify-start gap-[.1rem]" for="reg_password_repeat">
						<?php esc_html_e( 'Repeat Password', 'digicommerce' ); ?>
						<span class="text-red-500">*</span>
					</label>
					<button type="button" class="pass__icon p-0 m-0 no-background default-transition">
						<svg class="w-6 h-6" data-hide fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
						<svg class="w-6 h-6 hidden" data-show fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
					</button>
				</div>

				<button type="submit" class="digi__button w-full flex items-center justify-center gap-2 bg-gold hover:bg-dark-blue text-dark-blue hover:text-gold py-4 px-6 rounded-md default-transition">
					<span class="text"><?php esc_html_e( 'Create an account', 'digicommerce' ); ?></span>
					<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
				</button>

				<?php wp_nonce_field( 'digicommerce_register_nonce', 'digicommerce_register_nonce' ); ?>

				<?php if ( $recaptcha_enabled ) : ?>
					<div class="g-recaptcha-branding flex items-center justify-center gap-4 mt-4">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="40" height="40"><path d="m505.879 256h-113.631-31.723l28.652-28.653c-13.159-61.484-67.76-107.6-133.179-107.6-5.901 0-11.807.371-17.461 1.108v-114.117c5.781-.369 11.56-.614 17.461-.614 95.918 0 179.168 53.982 221.102 133.3l28.778-28.777v145.353z" fill="#495586"/><path d="m198.692 280.963c-3.813-2.582-4.914-7.749-2.334-11.559 2.461-3.813 7.621-4.921 11.434-2.46l27.673 18.075 56.934-74.396c2.707-3.567 7.994-4.305 11.562-1.477 3.687 2.706 4.423 7.994 1.598 11.559l-61.365 80.3c-2.58 3.689-7.622 4.672-11.435 2.214z" fill="#303c64"/><path d="m255.999 6.124v113.624 31.728l-28.651-28.652c-61.488 13.157-107.597 67.755-107.597 133.176 0 5.901.492 11.804 1.102 17.46h-114.114c-.373-5.778-.618-11.559-.618-17.46 0-95.918 54.108-179.168 133.422-221.104l-28.893-28.772z" fill="#69a7ff"/><path d="m352.284 352.286 80.422 80.423c-45.253 45.129-107.721 73.167-176.707 73.167-95.918 0-179.168-54.108-221.101-133.424l-28.777 28.898v-145.35h113.63 31.723l-28.651 28.651c13.158 61.487 67.758 107.6 133.177 107.6 37.627-.001 71.693-15.249 96.284-39.965z" fill="#ababab"/></svg>
						<div class="flex flex-col">
							<span class="text-sm"><?php esc_html_e( 'protected by', 'digicommerce' ); ?> <strong>reCAPTCHA</strong></span>
							<div class="flex gap-1 text-[.64rem]">
								<a href="https://www.google.com/intl/fr/policies/privacy/" target="_blank" class="text-dark-blue hover:text-gold"><?php esc_html_e( 'Confidentiality', 'digicommerce' ); ?></a><span aria-hidden="true" role="presentation"> - </span><a href="https://www.google.com/intl/fr/policies/terms/" target="_blank" class="text-dark-blue hover:text-gold"><?php esc_html_e( 'Terms', 'digicommerce' ); ?></a>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</form>
		<?php } ?>

		<!-- Lost Password Form -->
		<form id="digicommerce-lost-password-form" class="digi__form w-full hidden">
			<p class="flex justify-start mb-4">
				<button type="button" id="back-to-login" class="return__link flex items-center gap-2 text-sm text-dark-blue hover:text-gold no-background default-transition">
					<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
					<?php esc_html_e( 'Back to login', 'digicommerce' ); ?>
				</button>
			</p>

			<div class="bg-light-blue-bg rounded-xl p-4 mb-6 flex gap-4">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 63 48" width="40" height="30"><g clip-path="url(#clip0)"><path d="M55.4 46H22c-3.6 0-5.8-4-4-7l16.8-28c1.8-3 6.2-2.9 8 .1l16.5 28c1.9 3-.4 6.9-4 6.9z" fill="#FFE599"></path><path d="M46 46H8.3c-3.6 0-5.8-4-4-7L23.4 7.5c1.8-3 6.1-3 8 0L50 39c1.9 3-.3 7-4 7z" fill="#ccb161"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M22 46h24c3.7 0 5.9-4 4-7L34 12.3 18 39c-1.8 3 .4 7 4 7z" fill="#09053a"></path><rect width="15.5" height="4.1" rx="2.1" transform="matrix(0 -1 -1 0 28.4 32.6)" fill="#fff"></rect><rect width="4.2" height="4.1" rx="2.1" transform="matrix(0 -1 -1 0 28.4 38.9)" fill="#fff"></rect></g><defs><clipPath id="clip0"><path fill="#fff" d="M0 0h63v48H0z"></path></clipPath></defs></svg>
				<p class="text-sm text-dark-blue flex-1 m-0 no-margin">
					<?php esc_html_e( 'Enter your email address and we will send you instructions to reset your password.', 'digicommerce' ); ?>
				</p>
			</div>

			<div id="lost-password-message" class="message hidden"></div>

			<div class="field mb-4 relative">
				<input type="email" id="user_email" name="user_email" class="default-transition" required>
				<label for="user_email">
					<?php esc_html_e( 'Email address', 'digicommerce' ); ?>
				</label>
			</div>

			<button type="submit" class="digi__button w-full flex items-center justify-center gap-2 bg-gold hover:bg-dark-blue text-dark-blue hover:text-gold py-4 px-6 rounded-md default-transition">
				<span class="text"><?php esc_html_e( 'Reset password', 'digicommerce' ); ?></span>
				<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
				</svg>
			</button>

			<?php wp_nonce_field( 'digicommerce_lost_password_nonce', 'digicommerce_lost_password_nonce' ); ?>

			<?php if ( $recaptcha_enabled ) : ?>
				<div class="g-recaptcha-branding flex items-center justify-center gap-4 mt-4">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="40" height="40"><path d="m505.879 256h-113.631-31.723l28.652-28.653c-13.159-61.484-67.76-107.6-133.179-107.6-5.901 0-11.807.371-17.461 1.108v-114.117c5.781-.369 11.56-.614 17.461-.614 95.918 0 179.168 53.982 221.102 133.3l28.778-28.777v145.353z" fill="#495586"/><path d="m198.692 280.963c-3.813-2.582-4.914-7.749-2.334-11.559 2.461-3.813 7.621-4.921 11.434-2.46l27.673 18.075 56.934-74.396c2.707-3.567 7.994-4.305 11.562-1.477 3.687 2.706 4.423 7.994 1.598 11.559l-61.365 80.3c-2.58 3.689-7.622 4.672-11.435 2.214z" fill="#303c64"/><path d="m255.999 6.124v113.624 31.728l-28.651-28.652c-61.488 13.157-107.597 67.755-107.597 133.176 0 5.901.492 11.804 1.102 17.46h-114.114c-.373-5.778-.618-11.559-.618-17.46 0-95.918 54.108-179.168 133.422-221.104l-28.893-28.772z" fill="#69a7ff"/><path d="m352.284 352.286 80.422 80.423c-45.253 45.129-107.721 73.167-176.707 73.167-95.918 0-179.168-54.108-221.101-133.424l-28.777 28.898v-145.35h113.63 31.723l-28.651 28.651c13.158 61.487 67.758 107.6 133.177 107.6 37.627-.001 71.693-15.249 96.284-39.965z" fill="#ababab"/></svg>
					<div class="flex flex-col">
						<span class="text-sm"><?php esc_html_e( 'protected by', 'digicommerce' ); ?> <strong>reCAPTCHA</strong></span>
						<div class="flex gap-1 text-[.64rem]">
							<a href="https://www.google.com/intl/fr/policies/privacy/" target="_blank" class="text-dark-blue hover:text-gold"><?php esc_html_e( 'Confidentiality', 'digicommerce' ); ?></a><span aria-hidden="true" role="presentation"> - </span><a href="https://www.google.com/intl/fr/policies/terms/" target="_blank" class="text-dark-blue hover:text-gold"><?php esc_html_e( 'Terms', 'digicommerce' ); ?></a>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</form>

		<div class="flex justify-center mt-16">
			<a href="<?php echo esc_url( home_url() ); ?>" class="return__link flex items-center gap-2 font-nb text-base text-dark-blue hover:text-gold">
				<svg xmlns="http://www.w3.org/2000/svg" width="14" height="15" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M4 12a.7.7 0 001.1-1L2.8 8.3h9.4a.7.7 0 100-1.4H2.8l2.3-2.7a.7.7 0 00-1-1l-3.3 4-.1.1c-.1.3 0 .6.2.8L4 12z"/></svg>
				<?php
				esc_html_e( 'Return to ', 'digicommerce' );
				echo esc_html( get_bloginfo( 'name' ) );
				?>
			</a>
		</div>
	</div>
</div>