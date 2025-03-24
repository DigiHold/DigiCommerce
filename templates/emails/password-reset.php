<?php
/**
 * Password reset email template
 *
 * @var string $reset_url
 * @var string $user_login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$styles = DigiCommerce_Emails::instance()->get_styles();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php esc_html_e( 'Password Reset Request', 'digicommerce' ); ?></title>
	<style type="text/css">
		<?php echo $styles; // phpcs:ignore ?>
	</style>
</head>
<body>
	<div class="container">
		<?php echo wp_kses_post( DigiCommerce_Emails::instance()->get_header() ); ?>

		<div class="content">
			<h2><?php esc_html_e( 'Password Reset Request', 'digicommerce' ); ?></h2>
			
			<p>
			<?php
			printf(
				/* translators: %s: Username */
				esc_html__( 'Hello %s,', 'digicommerce' ),
				esc_html( $user_login )
			);
			?>
			</p>

			<p><?php esc_html_e( 'Someone has requested a password reset for the following account:', 'digicommerce' ); ?></p>
			
			<p>
				<strong><?php esc_html_e( 'Site Name:', 'digicommerce' ); ?></strong>
				<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
			</p>

			<p>
				<strong><?php esc_html_e( 'Username:', 'digicommerce' ); ?></strong>
				<?php echo esc_html( $user_login ); ?>
			</p>

			<p><?php esc_html_e( 'If this was a mistake, you can safely ignore this email and nothing will happen.', 'digicommerce' ); ?></p>

			<p><?php esc_html_e( 'To reset your password, click the button below:', 'digicommerce' ); ?></p>

			<div class="button-container">
				<a href="<?php echo esc_url( $reset_url ); ?>" class="button">
					<?php esc_html_e( 'Reset Password', 'digicommerce' ); ?>
				</a>
			</div>

			<div class="security-box">
				<h4><?php esc_html_e( 'Security Notice', 'digicommerce' ); ?></h4>
				<ul>
					<li><?php esc_html_e( 'This link will expire in 1 hour.', 'digicommerce' ); ?></li>
					<li><?php esc_html_e( 'If you did not request this password reset, please contact us immediately.', 'digicommerce' ); ?></li>
					<li><?php esc_html_e( 'Never share this link with anyone.', 'digicommerce' ); ?></li>
				</ul>
			</div>

			<p class="link-note">
				<?php esc_html_e( 'If the button above doesn\'t work, copy and paste this link in your browser:', 'digicommerce' ); ?><br>
				<span class="reset-link"><?php echo esc_url( $reset_url ); ?></span>
			</p>
		</div>

		<?php echo wp_kses_post( DigiCommerce_Emails::instance()->get_footer() ); ?>
	</div>
</body>
</html>