<?php
/**
 * Profile section template
 *
 * This template can be overridden by copying it to yourtheme/digicommerce/account/sections/profile.php
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<!-- Profile Header -->
<div class="flex flex-col pb-6">
	<h2 class="text-[2rem] leading-normal font-bold text-dark-blue m-0 no-margin"><?php esc_html_e( 'Profile Information', 'digicommerce' ); ?></h2>
	<p class="text-medium m-0 no-margin"><?php esc_html_e( 'Update your account information.', 'digicommerce' ); ?></p>
</div>

<form id="digicommerce-profile-form" class="digi__form w-full m-0">
	<div>
		<!-- Alert Message -->
		<div id="profile-message" class="hidden rounded-md p-4 mb-6"></div>

		<!-- Profile Fields -->
		<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
			<div class="field relative">
				<input type="text" id="billing_first_name" name="billing_first_name" class="default-transition" value="<?php echo esc_attr( $billing_info['first_name'] ?? '' ); ?>" required>
				<label for="billing_first_name">
					<?php esc_html_e( 'First name', 'digicommerce' ); ?>
				</label>
			</div>

			<div class="field relative">
				<input type="text" id="billing_last_name" name="billing_last_name" class="default-transition" value="<?php echo esc_attr( $billing_info['last_name'] ?? '' ); ?>" required>
				<label for="billing_last_name">
					<?php esc_html_e( 'Last name', 'digicommerce' ); ?>
				</label>
			</div>

			<div class="field relative">
				<input type="text" id="billing_email" name="billing_email" class="default-transition" value="<?php echo esc_attr( $billing_info['email'] ?? '' ); ?>" required>
				<label for="billing_email">
					<?php esc_html_e( 'Email address', 'digicommerce' ); ?>
				</label>
			</div>

			<div class="field relative">
				<input type="tel" id="billing_phone" name="billing_phone" class="default-transition" value="<?php echo esc_attr( $billing_info['phone'] ?? '' ); ?>">
				<label for="billing_phone">
					<?php esc_html_e( 'Phone number', 'digicommerce' ); ?>
				</label>
			</div>

			<div class="field relative">
				<input type="text" id="billing_company" name="billing_company" class="default-transition" value="<?php echo esc_attr( $billing_info['company'] ?? '' ); ?>">
				<label for="billing_company">
					<?php esc_html_e( 'Company name', 'digicommerce' ); ?>
				</label>
			</div>

			<div class="field relative">
				<input type="text" id="billing_vat_number" name="billing_vat_number" class="default-transition" value="<?php echo esc_attr( $billing_info['vat_number'] ?? '' ); ?>">
				<label for="billing_vat_number">
					<?php esc_html_e( 'VAT number', 'digicommerce' ); ?>
				</label>
			</div>

			<div class="field relative">
				<input type="text" id="billing_address" name="billing_address" class="default-transition" value="<?php echo esc_attr( $billing_info['address'] ?? '' ); ?>">
				<label for="billing_address">
					<?php esc_html_e( 'Address', 'digicommerce' ); ?>
				</label>
			</div>

			<div class="field relative">
				<input type="text" id="billing_postcode" name="billing_postcode" class="default-transition" value="<?php echo esc_attr( $billing_info['postcode'] ?? '' ); ?>">
				<label for="billing_postcode">
					<?php esc_html_e( 'Postal code', 'digicommerce' ); ?>
				</label>
			</div>

			<div class="field relative">
				<input type="text" id="billing_city" name="billing_city" class="default-transition" value="<?php echo esc_attr( $billing_info['city'] ?? '' ); ?>">
				<label for="billing_city">
					<?php esc_html_e( 'City', 'digicommerce' ); ?>
				</label>
			</div>

			<div class="field relative">
				<select id="billing_country" name="billing_country" class="shadow-sm border border-solid focus:ring-dark-blue focus:border-dark-blue block w-full sm:text-sm border-gray-300 rounded-md">
					<option value=""><?php esc_html_e( 'Select your country', 'digicommerce' ); ?></option>
					<?php
					$countries        = DigiCommerce()->get_countries();
					$selected_country = $billing_info['country'] ?? '';

					foreach ( $countries as $code => $country ) {
						printf(
							'<option value="%s" %s>%s</option>',
							esc_attr( $code ),
							selected( $code, $selected_country, false ),
							esc_html( $country['name'] )
						);
					}
					?>
				</select>
			</div>

			<div class="field relative col-span-2">
				<input type="text" id="billing_state" name="billing_state" class="default-transition" value="<?php echo esc_attr( $billing_info['state'] ?? '' ); ?>">
				<label for="billing_state">
					<?php esc_html_e( 'State', 'digicommerce' ); ?>
				</label>
			</div>
		</div>

		<?php wp_nonce_field( 'digicommerce_update_profile_nonce', 'digicommerce_update_profile_nonce' ); ?>

		<div class="pt-6">
			<div class="flex justify-end">
				<button type="submit" class="digi__button w-full flex items-center justify-center gap-2 bg-gold hover:bg-dark-blue text-dark-blue hover:text-gold py-4 px-6 rounded-md default-transition">
					<span class="text"><?php esc_html_e( 'Save changes', 'digicommerce' ); ?></span>
					<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
				</button>
			</div>
		</div>
	</div>
</form>