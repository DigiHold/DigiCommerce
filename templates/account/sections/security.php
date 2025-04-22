<?php
/**
 * Profile security section template
 *
 * This template can be overridden by copying it to yourtheme/digicommerce/account/sections/security.php
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<!-- Security Header -->
<div class="flex flex-col pb-6">
	<h2 class="text-[2rem] leading-normal font-bold text-dark-blue m-0 no-margin"><?php esc_html_e( 'Change your password', 'digicommerce' ); ?></h2>
	<p class="text-medium m-0 no-margin"><?php esc_html_e( 'You need to meet all the password requirements to have a strong password.', 'digicommerce' ); ?></p>
</div>

<form id="digicommerce-password-form" class="digi__form w-full m-0">
	<div>
		<!-- Alert Message -->
		<div id="password-message" class="hidden rounded-md p-4 mb-6"></div>

		<!-- Password Fields -->
		<div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-4">
			<div class="field relative">
				<input type="password" id="current_password" name="current_password" class="default-transition" required>
				<label for="current_password">
					<?php esc_html_e( 'Current password', 'digicommerce' ); ?>
				</label>

				<button type="button" class="pass__icon p-0 m-0 no-background default-transition">
					<svg class="w-6 h-6" data-hide fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
					<svg class="w-6 h-6 hidden" data-show fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
				</button>
			</div>

			<div class="field relative">
				<input type="password" id="new_password" name="new_password" class="default-transition" required>
				<label for="new_password">
					<?php esc_html_e( 'New password', 'digicommerce' ); ?>
				</label>

				<button type="button" class="pass__icon p-0 m-0 no-background default-transition">
					<svg class="w-6 h-6" data-hide fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
					<svg class="w-6 h-6 hidden" data-show fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
				</button>
			</div>
		</div>

		<div class="password-strength">
			<div class="password-strength-meter">
				<div class="password-strength-meter-bar"></div>
			</div>
			<p class="password-strength-text"></p>
		</div>

		<!-- Password requirements -->
		<div class="mb-6">
			<ul class="m-0 list-none p-0 text-xs text-gray-500 space-y-1">
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

		<?php wp_nonce_field( 'digicommerce_change_password_nonce', 'digicommerce_change_password_nonce' ); ?>

		<div class="pt-6">
			<div class="flex justify-end">
				<button type="submit" class="digi__button w-full flex items-center justify-center gap-2 bg-gold hover:bg-dark-blue text-dark-blue hover:text-gold py-4 px-6 rounded-md default-transition">
					<span class="text"><?php esc_html_e( 'Update password', 'digicommerce' ); ?></span>
					<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
				</button>
			</div>
		</div>
	</div>
</form>