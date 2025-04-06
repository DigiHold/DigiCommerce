<?php
/**
 * Welcome email template
 *
 * @var string $username
 * @var string $password
 * @var string $login_url
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>
		<?php
		printf(
			// translators: %s: website
			esc_html__( 'Welcome to %s', 'digicommerce' ),
			get_bloginfo( 'name' ) // phpcs:ignore
		);
		?>
	</title>
	<style type="text/css">
		<?php echo wp_strip_all_tags( DigiCommerce_Emails::instance()->get_styles() ); // phpcs:ignore ?>
	</style>
</head>
<body>
	<div class="container">
		<?php echo wp_kses_post( DigiCommerce_Emails::instance()->get_header() ); ?>

		<div class="content">
			<h2>
				<?php
				printf(
					// translators: %s: Site title.
					esc_html__( 'Welcome to %s', 'digicommerce' ),
					get_bloginfo( 'name' ) // phpcs:ignore
				);
				?>
			</h2>
			
			<p>
				<?php
				printf(
					// translators: %s: Site title.
					esc_html__( 'Thanks for creating an account on %s. Your account has been successfully created, and you can now log in using the following credentials:', 'digicommerce' ),
					get_bloginfo( 'name' ) // phpcs:ignore
				);
				?>
			</p>

			<div class="credentials-box">
				<p><strong><?php esc_html_e( 'Email:', 'digicommerce' ); ?></strong> <?php echo esc_html( $email ); ?></p>
				<p><strong><?php esc_html_e( 'Password:', 'digicommerce' ); ?></strong> <?php echo esc_html( $password ); ?></p>
			</div>

			<p class="important-note">
				<?php esc_html_e( 'For your security, we recommend changing your password after your first login.', 'digicommerce' ); ?>
			</p>

			<div class="button-container">
				<a href="<?php echo esc_url( $login_url ); ?>" class="button">
					<?php esc_html_e( 'Login to Your Account', 'digicommerce' ); ?>
				</a>
			</div>
		</div>

		<?php echo wp_kses_post( DigiCommerce_Emails::instance()->get_footer() ); ?>
	</div>
</body>
</html>