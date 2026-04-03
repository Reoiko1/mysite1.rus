<?php
/**
 * Bakery Patisserie Shop Theme Page
 *
 * @package Bakery Patisserie Shop
 */

function bakery_patisserie_shop_admin_scripts() {
	wp_dequeue_script('bakery-patisserie-shop-custom-scripts');
}
add_action( 'admin_enqueue_scripts', 'bakery_patisserie_shop_admin_scripts' );

if ( ! defined( 'BAKERY_PATISSERIE_SHOP_FREE_THEME_URL' ) ) {
	define( 'BAKERY_PATISSERIE_SHOP_FREE_THEME_URL', 'https://www.themespride.com/products/bakery-patisserie-shop' );
}
if ( ! defined( 'BAKERY_PATISSERIE_SHOP_PRO_THEME_URL' ) ) {
	define( 'BAKERY_PATISSERIE_SHOP_PRO_THEME_URL', 'https://www.themespride.com/products/bakery-shop-wordpress-theme' );
}
if ( ! defined( 'BAKERY_PATISSERIE_SHOP_DEMO_THEME_URL' ) ) {
	define( 'BAKERY_PATISSERIE_SHOP_DEMO_THEME_URL', 'https://page.themespride.com/bakery-and-patisserie-shop/' );
}
if ( ! defined( 'BAKERY_PATISSERIE_SHOP_DOCS_THEME_URL' ) ) {
    define( 'BAKERY_PATISSERIE_SHOP_DOCS_THEME_URL', 'https://page.themespride.com/demo/docs/bakery-and-patisserie-shop/' );
}
if ( ! defined( 'BAKERY_PATISSERIE_SHOP_RATE_THEME_URL' ) ) {
    define( 'BAKERY_PATISSERIE_SHOP_RATE_THEME_URL', 'https://wordpress.org/support/theme/bakery-patisserie-shop/reviews/#new-post' );
}
if ( ! defined( 'BAKERY_PATISSERIE_SHOP_CHANGELOG_THEME_URL' ) ) {
    define( 'BAKERY_PATISSERIE_SHOP_CHANGELOG_THEME_URL', get_template_directory() . '/readme.txt' );
}
if ( ! defined( 'BAKERY_PATISSERIE_SHOP_SUPPORT_THEME_URL' ) ) {
    define( 'BAKERY_PATISSERIE_SHOP_SUPPORT_THEME_URL', 'https://wordpress.org/support/theme/bakery-patisserie-shop/' );
}
if ( ! defined( 'BAKERY_PATISSERIE_SHOP_THEME_BUNDLE' ) ) {
    define( 'BAKERY_PATISSERIE_SHOP_THEME_BUNDLE', 'https://www.themespride.com/products/wordpress-theme-bundle' );
}

/**
 * Add theme page
 */
function bakery_patisserie_shop_menu() {
	add_theme_page( esc_html__( 'About Theme', 'bakery-patisserie-shop' ), esc_html__( 'Begin Installation - Import Demo', 'bakery-patisserie-shop' ), 'edit_theme_options', 'bakery-patisserie-shop-about', 'bakery_patisserie_shop_about_display' );
}
add_action( 'admin_menu', 'bakery_patisserie_shop_menu' );

/**
 * Display About page
 */
function bakery_patisserie_shop_about_display() {
	$bakery_patisserie_shop_theme = wp_get_theme();
	?>
	<div class="wrap about-wrap full-width-layout">
		<!-- top-detail -->
		<?php
		// Only show if NOT dismissed
		if ( ! get_option('dismissed-get_started-detail', false ) ) { 
		?>
		    <!-- top-detail -->
		    <div class="detail-theme" id="detail-theme-box">
		        <button type="button" class="close-btn" id="close-detail-theme">
		            <?php esc_html_e( 'Dismiss', 'bakery-patisserie-shop' ); ?>
		        </button>
		        <h2><?php echo esc_html__( 'Hey, Thank you for Installing Bakery Patisserie Shop Theme!', 'bakery-patisserie-shop' ); ?></h2>

		        <a href="<?php echo esc_url( admin_url( 'themes.php?page=bakery-patisserie-shop-about' ) ); ?>">
		            <?php esc_html_e( 'Get Started', 'bakery-patisserie-shop' ); ?>
		        </a>
		        <a href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>" class="site-editor" target="_blank">
		            <?php esc_html_e( 'Site Editor', 'bakery-patisserie-shop' ); ?>
		        </a>

		        <a href="<?php echo esc_url( BAKERY_PATISSERIE_SHOP_PRO_THEME_URL ); ?>" class="pro-btn-theme" target="_blank">
		            <?php esc_html_e( 'Upgrade to Pro', 'bakery-patisserie-shop' ); ?>
		        </a>

		        <a href="<?php echo esc_url( BAKERY_PATISSERIE_SHOP_THEME_BUNDLE ); ?>" class="rate-theme" target="_blank">
		            <?php esc_html_e( 'Get Bundle', 'bakery-patisserie-shop' ); ?>
		        </a>
		    </div>
		<?php 
		} ?>
		
		<nav class="nav-tab-wrapper wp-clearfix bakery-patisserie-shop-tab-sec" aria-label="<?php esc_attr_e( 'Secondary menu', 'bakery-patisserie-shop' ); ?>">
		    <button class="nav-tab bakery-patisserie-shop-tablinks active"
		        onclick="bakery_patisserie_shop_open_tab(event, 'tp_demo_import')">
		        <?php esc_html_e( 'One Click Demo Import', 'bakery-patisserie-shop' ); ?>
		    </button>

		    <button class="nav-tab bakery-patisserie-shop-tablinks"
		        onclick="bakery_patisserie_shop_open_tab(event, 'tp_about_theme')">
		        <?php esc_html_e( 'About', 'bakery-patisserie-shop' ); ?>
		    </button>

		    <button class="nav-tab bakery-patisserie-shop-tablinks"
		        onclick="bakery_patisserie_shop_open_tab(event, 'tp_free_vs_pro')">
		        <?php esc_html_e( 'Compare Free Vs Pro', 'bakery-patisserie-shop' ); ?>
		    </button>

		    <button class="nav-tab bakery-patisserie-shop-tablinks"
		        onclick="bakery_patisserie_shop_open_tab(event, 'tp_changelog')">
		        <?php esc_html_e( 'Changelog', 'bakery-patisserie-shop' ); ?>
		    </button>

		    <button class="nav-tab bakery-patisserie-shop-tablinks blink wp-bundle"
		        onclick="bakery_patisserie_shop_open_tab(event, 'tp_get_bundle')">
		        <?php esc_html_e( 'Get WordPress Theme Bundle (120+ Themes)', 'bakery-patisserie-shop' ); ?>
		    </button>
		</nav>

		<?php
			bakery_patisserie_shop_demo_import();

			bakery_patisserie_shop_main_screen();

			bakery_patisserie_shop_changelog_screen();

			bakery_patisserie_shop_free_vs_pro();

			bakery_patisserie_shop_get_bundle();
		?>

		<p class="actions theme-btns">
			<a target="_blank"href="<?php echo esc_url( BAKERY_PATISSERIE_SHOP_FREE_THEME_URL ); ?>" class="theme-info-btn" target="_blank" target="_blank"><?php esc_html_e( 'Theme Info', 'bakery-patisserie-shop' ); ?></a>
			<a target="_blank" href="<?php echo esc_url( BAKERY_PATISSERIE_SHOP_DEMO_THEME_URL ); ?>" class="view-demo" target="_blank"><?php esc_html_e( 'View Demo', 'bakery-patisserie-shop' ); ?></a>
			<a target="_blank" href="<?php echo esc_url( BAKERY_PATISSERIE_SHOP_DOCS_THEME_URL ); ?>" class="instruction-theme" target="_blank"><?php esc_html_e( 'Theme Documentation', 'bakery-patisserie-shop' ); ?></a>
			<a target="_blank" href="<?php echo esc_url( BAKERY_PATISSERIE_SHOP_PRO_THEME_URL ); ?>" class="pro-btn-theme" target="_blank"><?php esc_html_e( 'Upgrade to pro', 'bakery-patisserie-shop' ); ?></a>
		</p>

		<h1><?php echo esc_html( $bakery_patisserie_shop_theme ); ?></h1>
		<div class="about-theme">
			<div class="theme-description">
				<p class="about-text content">
					<?php
					// Remove last sentence of description.
					$bakery_patisserie_shop_description = explode( '. ', $bakery_patisserie_shop_theme->get( 'Description' ) );
					array_pop( $bakery_patisserie_shop_description );

					$bakery_patisserie_shop_description = implode( '. ', $bakery_patisserie_shop_description );

					echo esc_html( $bakery_patisserie_shop_description . '.' );
				?></p>
				
			</div>
			<div class="theme-screenshot">
				<img src="<?php echo esc_url( $bakery_patisserie_shop_theme->get_screenshot() ); ?>" />
			</div>
		</div>
	<?php
}


/**
 * Output the Demo Import screen (JS tab based).
 */
function bakery_patisserie_shop_demo_import() {

	// Load whizzie demo importer
	$bakery_patisserie_shop_child_whizzie  = get_stylesheet_directory() . '/inc/whizzie.php';
	$bakery_patisserie_shop_parent_whizzie = get_template_directory() . '/inc/whizzie.php';

	if ( file_exists( $bakery_patisserie_shop_child_whizzie ) ) {
		require_once $bakery_patisserie_shop_child_whizzie;
	} elseif ( file_exists( $bakery_patisserie_shop_parent_whizzie ) ) {
		require_once $bakery_patisserie_shop_parent_whizzie;
	}

	/* ---------------------------------------------------------
	 * SAVE DEMO IMPORT STATUS
	 * --------------------------------------------------------- */
	if ( isset( $_GET['import-demo'] ) && $_GET['import-demo'] === 'true' ) {
		update_option( 'bakery_patisserie_shop_demo_imported', true );
		delete_option( 'bakery_patisserie_shop_demo_popup_shown' ); // allow popup once
	}

	/* ---------------------------------------------------------
	 * RESET DEMO (OPTIONAL)
	 * --------------------------------------------------------- */
	if ( isset( $_GET['reset-demo'] ) && $_GET['reset-demo'] === 'true' ) {
		delete_option( 'bakery_patisserie_shop_demo_imported' );
		delete_option( 'bakery_patisserie_shop_demo_popup_shown' );
		wp_safe_redirect( remove_query_arg( 'reset-demo' ) );
		exit;
	}

	$bakery_patisserie_shop_demo_imported  = get_option( 'bakery_patisserie_shop_demo_imported', false );
	$bakery_patisserie_shop_popup_shown    = get_option( 'bakery_patisserie_shop_demo_popup_shown', false );
	$bakery_patisserie_shop_show_popup_now = ( $bakery_patisserie_shop_demo_imported && ! $bakery_patisserie_shop_popup_shown );
	?>

	<div id="tp_demo_import" class="bakery-patisserie-shop-tabcontent">

	<?php if ( $bakery_patisserie_shop_demo_imported ) : ?>

		<!-- ================= SUCCESS STATE ================= -->
		<div class="content-row">
			<div class="col card success-demo text-center">
				<p class="imp-success">
					<?php esc_html_e( 'Demo Imported Successfully!', 'bakery-patisserie-shop' ); ?>
				</p><br>

				<div class="demo-button-three">
					<a class="button button-primary" href="<?php echo esc_url( home_url('/') ); ?>" target="_blank">
						<?php esc_html_e( 'View Site', 'bakery-patisserie-shop' ); ?>
					</a>

					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>" target="_blank">
						<?php esc_html_e( 'Edit Site', 'bakery-patisserie-shop' ); ?>
					</a>

					<?php if ( defined( 'BAKERY_PATISSERIE_SHOP_DOCS_THEME_URL' ) ) : ?>
						<a class="button button-primary" href="<?php echo esc_url( BAKERY_PATISSERIE_SHOP_DOCS_THEME_URL ); ?>" target="_blank">
							<?php esc_html_e( 'Documentation', 'bakery-patisserie-shop' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
			<div class="theme-price col card">
				<div class="price-flex">
					<div class="price-content">
						<h3><?php esc_html_e( 'Bakery Patisserie Shop WordPress Theme', 'bakery-patisserie-shop' ); ?></h3>
						<p class="main-flash"><?php 
						  printf(
						    /* translators: 1: bold FLASH DEAL text, 2: discount code */
						    esc_html__( '%1$s - Get 20%% Discount on All Themes, Use code %2$s', 'bakery-patisserie-shop' ),
						    '<strong class="bold-text">' . esc_html__( 'FLASH DEAL', 'bakery-patisserie-shop' ) . '</strong>',
						    '<strong class="bold-text">' . esc_html__( 'QBSALE20', 'bakery-patisserie-shop' ) . '</strong>'
						  ); 
						  ?></p>
						 <p>
						  <del><?php echo esc_html__( '$59', 'bakery-patisserie-shop' ); ?></del>
						  <strong class="bold-price"><?php echo esc_html__( '$39', 'bakery-patisserie-shop' ); ?></strong>
						</p>
					</div>
					<div class="price-img">
						<img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/theme-img.png" alt="theme-img" />
					</div>
				</div>
				<div class="main-pro-price">
					<a target="_blank" href="<?php echo esc_url( BAKERY_PATISSERIE_SHOP_PRO_THEME_URL ); ?>" class="pro-btn-theme price-pro" target="_blank"><?php esc_html_e( 'Upgrade To Premium Bakery Patisserie Shop WordPress Theme', 'bakery-patisserie-shop' ); ?></a>
				</div>
			</div>
		</div>

	<?php else : ?>

		<!-- ================= INSTALL STATE ================= -->
		<div class="content-row">
			<div class="col card demo-btn text-center">
				<form id="demo-importer-form" method="post">
					<p class="demo-title"><?php esc_html_e( 'Demo Importer', 'bakery-patisserie-shop' ); ?></p>
					<p class="demo-des">
						<?php esc_html_e( 'Import demo content with one click. You can customize everything later.', 'bakery-patisserie-shop' ); ?>
					</p>

					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Begin Installation – Import Demo', 'bakery-patisserie-shop' ); ?>
					</button>

					<div id="page-loader" style="display:none;margin-top:15px;">
						<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/loader.png' ); ?>" width="40">
						<p><?php esc_html_e( 'Importing demo, please wait...', 'bakery-patisserie-shop' ); ?></p>
					</div>
				</form>
			</div>
			<div class="theme-price col card">
				<div class="price-flex">
					<div class="price-content">
						<h3><?php esc_html_e( 'Bakery Patisserie Shop WordPress Theme', 'bakery-patisserie-shop' ); ?></h3>
						<p class="main-flash"><?php 
						  printf(
						    /* translators: 1: bold FLASH DEAL text, 2: discount code */
						    esc_html__( '%1$s - Get 20%% Discount on All Themes, Use code %2$s', 'bakery-patisserie-shop' ),
						    '<strong class="bold-text">' . esc_html__( 'FLASH DEAL', 'bakery-patisserie-shop' ) . '</strong>',
						    '<strong class="bold-text">' . esc_html__( 'QBSALE20', 'bakery-patisserie-shop' ) . '</strong>'
						  ); 
						  ?></p>
						 <p>
						  <del><?php echo esc_html__( '$59', 'bakery-patisserie-shop' ); ?></del>
						  <strong class="bold-price"><?php echo esc_html__( '$39', 'bakery-patisserie-shop' ); ?></strong>
						</p>
					</div>
					<div class="price-img">
						<img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/theme-img.png" alt="theme-img" />
					</div>
				</div>
				<div class="main-pro-price">
					<a target="_blank" href="<?php echo esc_url( BAKERY_PATISSERIE_SHOP_PRO_THEME_URL ); ?>" class="pro-btn-theme price-pro" target="_blank"><?php esc_html_e( 'Upgrade To Premium Bakery Patisserie Shop WordPress Theme', 'bakery-patisserie-shop' ); ?></a>
				</div>
			</div>
		</div>

		<script>
		jQuery(function($){
			$('#demo-importer-form').on('submit', function(e){
				e.preventDefault();
				if(confirm('<?php esc_html_e( 'Are you sure you want to import demo content?', 'bakery-patisserie-shop' ); ?>')){
					$('#page-loader').show();
					let url = new URL(window.location.href);
					url.searchParams.set('import-demo','true');
					window.location.href = url;
				}
			});
		});
		</script>

	<?php endif; ?>

	</div>

	<?php if ( $bakery_patisserie_shop_show_popup_now ) : ?>
	<!-- ================= SUCCESS POPUP (ONLY ONCE) ================= -->
	<div id="demo-success-modal" class="modal-overlay">
		<div class="modal-content">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/demo-icon.png' ); ?>" alt="">
			<h2><?php esc_html_e( 'Demo Successfully Imported!', 'bakery-patisserie-shop' ); ?></h2>

			<div class="modal-buttons">
				<a class="button button-primary" href="<?php echo esc_url( home_url('/') ); ?>" target="_blank">
					<?php esc_html_e( 'View Site', 'bakery-patisserie-shop' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( admin_url( 'themes.php?page=bakery-patisserie-shop-about' ) ); ?>">
					<?php esc_html_e( 'Go To Dashboard', 'bakery-patisserie-shop' ); ?>
				</a>
			</div>
		</div>
	</div>

	<script>
		document.addEventListener("DOMContentLoaded", function () {
			const modal = document.getElementById("demo-success-modal");
			if (!modal) return;

			modal.style.display = "flex";

			// Mark popup as permanently shown (only once)
			fetch('<?php echo esc_url( admin_url( 'admin-ajax.php?action=bakery_patisserie_shop_popup_done' ) ); ?>');

			// Close popup on ANY button click
			modal.querySelectorAll('a.button').forEach(function(btn){
				btn.addEventListener('click', function(){
					modal.style.display = "none";
				});
			});
		});
	</script>

	<?php endif; ?>

	<?php
}


/**
 * Output the main about screen.
 */
function bakery_patisserie_shop_main_screen() {
	
	?>
	<div id="tp_about_theme" class="bakery-patisserie-shop-tabcontent">
		<div class="content-row">
			<div class="feature-section two-col">
				<div class="col card">
					<h2 class="title"><?php esc_html_e( 'Theme Customizer', 'bakery-patisserie-shop' ); ?></h2>
					<p><?php esc_html_e( 'All Theme Options are available via Customize screen.', 'bakery-patisserie-shop' ) ?></p>
					<p><a target="_blank" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Customize', 'bakery-patisserie-shop' ); ?></a></p>
				</div>

				<div class="col card">
					<h2 class="title"><?php esc_html_e( 'Got theme support question?', 'bakery-patisserie-shop' ); ?></h2>
					<p><?php esc_html_e( 'Get genuine support from genuine people. Whether it\'s customization or compatibility, our seasoned developers deliver tailored solutions to your queries.', 'bakery-patisserie-shop' ) ?></p>
					<p><a target="_blank" href="<?php echo esc_url( BAKERY_PATISSERIE_SHOP_SUPPORT_THEME_URL ); ?>" class="button button-primary"><?php esc_html_e( 'Support Forum', 'bakery-patisserie-shop' ); ?></a></p>
				</div>
			</div>
			<div class="theme-price col card">
				<div class="price-flex">
					<div class="price-content">
						<h3><?php esc_html_e( 'Bakery Patisserie Shop WordPress Theme', 'bakery-patisserie-shop' ); ?></h3>
						<p class="main-flash"><?php 
						  printf(
						    /* translators: 1: bold FLASH DEAL text, 2: discount code */
						    esc_html__( '%1$s - Get 20%% Discount on All Themes, Use code %2$s', 'bakery-patisserie-shop' ),
						    '<strong class="bold-text">' . esc_html__( 'FLASH DEAL', 'bakery-patisserie-shop' ) . '</strong>',
						    '<strong class="bold-text">' . esc_html__( 'QBSALE20', 'bakery-patisserie-shop' ) . '</strong>'
						  ); 
						  ?></p>
						 <p>
						  <del><?php echo esc_html__( '$59', 'bakery-patisserie-shop' ); ?></del>
						  <strong class="bold-price"><?php echo esc_html__( '$39', 'bakery-patisserie-shop' ); ?></strong>
						</p>
					</div>
					<div class="price-img">
						<img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/theme-img.png" alt="theme-img" />
					</div>
				</div>
				<div class="main-pro-price">
					<a target="_blank" href="<?php echo esc_url( BAKERY_PATISSERIE_SHOP_PRO_THEME_URL ); ?>" class="pro-btn-theme price-pro" target="_blank"><?php esc_html_e( 'Upgrade To Premium Bakery Patisserie Shop WordPress Theme', 'bakery-patisserie-shop' ); ?></a>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Output the changelog screen.
 */
function bakery_patisserie_shop_changelog_screen() {
		global $wp_filesystem;
	?>
	<div id="tp_changelog" class="bakery-patisserie-shop-tabcontent">
	<div class="content-row">
		<div class="wrap about-wrap change-log">
			<?php
				$changelog_file = apply_filters( 'bakery_patisserie_shop_changelog_file', BAKERY_PATISSERIE_SHOP_CHANGELOG_THEME_URL );
				// Check if the changelog file exists and is readable.
				if ( $changelog_file && is_readable( $changelog_file ) ) {
					WP_Filesystem();
					$changelog = $wp_filesystem->get_contents( $changelog_file );
					$changelog_list = bakery_patisserie_shop_parse_changelog( $changelog );

					echo wp_kses_post( $changelog_list );
				}
			?>
		</div>
		<div class="theme-price col card">
				<div class="price-flex">
					<div class="price-content">
						<h3><?php esc_html_e( 'Bakery Patisserie Shop WordPress Theme', 'bakery-patisserie-shop' ); ?></h3>
						<p class="main-flash"><?php 
						  printf(
						    /* translators: 1: bold FLASH DEAL text, 2: discount code */
						    esc_html__( '%1$s - Get 20%% Discount on All Themes, Use code %2$s', 'bakery-patisserie-shop' ),
						    '<strong class="bold-text">' . esc_html__( 'FLASH DEAL', 'bakery-patisserie-shop' ) . '</strong>',
						    '<strong class="bold-text">' . esc_html__( 'QBSALE20', 'bakery-patisserie-shop' ) . '</strong>'
						  ); 
						  ?></p>
						 <p>
						  <del><?php echo esc_html__( '$59', 'bakery-patisserie-shop' ); ?></del>
						  <strong class="bold-price"><?php echo esc_html__( '$39', 'bakery-patisserie-shop' ); ?></strong>
						</p>
					</div>
					<div class="price-img">
						<img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/theme-img.png" alt="theme-img" />
					</div>
				</div>
				<div class="main-pro-price">
					<a target="_blank" href="<?php echo esc_url( BAKERY_PATISSERIE_SHOP_PRO_THEME_URL ); ?>" class="pro-btn-theme price-pro" target="_blank"><?php esc_html_e( 'Upgrade To Premium Bakery Patisserie Shop WordPress Theme', 'bakery-patisserie-shop' ); ?></a>
				</div>
			</div>
	</div>
</div>
	<?php
}

/**
 * Parse changelog from readme file.
 * @param  string $content
 * @return string
 */
function bakery_patisserie_shop_parse_changelog( $content ) {
	// Explode content with ==  to juse separate main content to array of headings.
	$content = explode ( '== ', $content );

	$changelog_isolated = '';

	// Get element with 'Changelog ==' as starting string, i.e isolate changelog.
	foreach ( $content as $key => $value ) {
		if (strpos( $value, 'Changelog ==') === 0) {
	    	$changelog_isolated = str_replace( 'Changelog ==', '', $value );
	    }
	}

	// Now Explode $changelog_isolated to manupulate it to add html elements.
	$changelog_array = explode( '= ', $changelog_isolated );

	// Unset first element as it is empty.
	unset( $changelog_array[0] );

	$changelog = '<pre class="changelog">';

	foreach ( $changelog_array as $value) {
		// Replace all enter (\n) elements with </span><span> , opening and closing span will be added in next process.
		$value = preg_replace( '/\n+/', '</span><span>', $value );

		// Add openinf and closing div and span, only first span element will have heading class.
		$value = '<div class="block"><span class="heading">= ' . $value . '</span></div>';

		// Remove empty <span></span> element which newr formed at the end.
		$changelog .= str_replace( '<span></span>', '', $value );
	}

	$changelog .= '</pre>';

	return wp_kses_post( $changelog );
}

/**
 * Import Demo data for theme using catch themes demo import plugin
 */
function bakery_patisserie_shop_free_vs_pro() {
	?>
	<div id="tp_free_vs_pro" class="bakery-patisserie-shop-tabcontent">
	<div class="content-row">
		<div class="wrap about-wrap change-log">
			<p class="about-description"><?php esc_html_e( 'View Free vs Pro Table below:', 'bakery-patisserie-shop' ); ?></p>
			<div class="vs-theme-table">
				<table>
					<thead>
						<tr><th scope="col"></th>
							<th class="head" scope="col"><?php esc_html_e( 'Free Theme', 'bakery-patisserie-shop' ); ?></th>
							<th class="head" scope="col"><?php esc_html_e( 'Pro Theme', 'bakery-patisserie-shop' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr class="odd" scope="row">
							<td headers="features" class="feature"><span><?php esc_html_e( 'Theme Demo Set Up', 'bakery-patisserie-shop' ); ?></span></td>
							<td><span class="dashicons dashicons-saved"></span></td>
							<td><span class="dashicons dashicons-saved"></span></td>
						</tr>
						<tr class="odd" scope="row">
							<td headers="features" class="feature"><?php esc_html_e( 'Additional Templates, Color options and Fonts', 'bakery-patisserie-shop' ); ?></td>
							<td><span class="dashicons dashicons-saved"></span></td>
							<td><span class="dashicons dashicons-saved"></span></td>
						</tr>
						<tr class="odd" scope="row">
							<td headers="features" class="feature"><?php esc_html_e( 'Included Demo Content', 'bakery-patisserie-shop' ); ?></td>
							<td><span class="dashicons dashicons-saved"></span></td>
							<td><span class="dashicons dashicons-saved"></span></td>
						</tr>
						<tr class="odd" scope="row">
							<td headers="features" class="feature"><?php esc_html_e( 'Section Ordering', 'bakery-patisserie-shop' ); ?></td>
							<td><span class="dashicons dashicons-no-alt"></span></td>
							<td><span class="dashicons dashicons-saved"></span></td>
						</tr>
						<tr class="odd" scope="row">
							<td headers="features" class="feature"><?php esc_html_e( 'Multiple Sections', 'bakery-patisserie-shop' ); ?></td>
							<td><span class="dashicons dashicons-no-alt"></span></td>
							<td><span class="dashicons dashicons-saved"></span></td>
						</tr>
						<tr class="odd" scope="row">
							<td headers="features" class="feature"><?php esc_html_e( 'Additional Plugins', 'bakery-patisserie-shop' ); ?></td>
							<td><span class="dashicons dashicons-saved"></span></td>
							<td><span class="dashicons dashicons-saved"></span></td>
						</tr>
						<tr class="odd" scope="row">
							<td headers="features" class="feature"><?php esc_html_e( 'Premium Technical Support', 'bakery-patisserie-shop' ); ?></td>
							<td><span class="dashicons dashicons-no-alt"></span></td>
							<td><span class="dashicons dashicons-saved"></span></td>
						</tr>
						<tr class="odd" scope="row">
							<td headers="features" class="feature"><?php esc_html_e( 'Access to Support Forums', 'bakery-patisserie-shop' ); ?></td>
							<td><span class="dashicons dashicons-no-alt"></span></td>
							<td><span class="dashicons dashicons-saved"></span></td>
						</tr>
						<tr class="odd" scope="row">
							<td headers="features" class="feature"><?php esc_html_e( 'Free updates', 'bakery-patisserie-shop' ); ?></td>
							<td><span class="dashicons dashicons-saved"></span></td>
							<td><span class="dashicons dashicons-saved"></span></td>
						</tr>
						<tr class="odd" scope="row">
							<td headers="features" class="feature"><?php esc_html_e( 'Unlimited Domains', 'bakery-patisserie-shop' ); ?></td>
							<td><span class="dashicons dashicons-saved"></span></td>
							<td><span class="dashicons dashicons-saved"></span></td>
						</tr>
						<tr class="odd" scope="row">
							<td headers="features" class="feature"><?php esc_html_e( 'Responsive Design', 'bakery-patisserie-shop' ); ?></td>
							<td><span class="dashicons dashicons-saved"></span></td>
							<td><span class="dashicons dashicons-saved"></span></td>
						</tr>
						<tr class="odd" scope="row">
							<td headers="features" class="feature"><?php esc_html_e( 'Live Customizer', 'bakery-patisserie-shop' ); ?></td>
							<td><span class="dashicons dashicons-saved"></span></td>
							<td><span class="dashicons dashicons-saved"></span></td>
						</tr>
						<tr class="odd" scope="row">
							<td class="feature feature--empty"></td>
							<td class="feature feature--empty"></td>
							<td headers="comp-2" class="td-btn-2"><a class="sidebar-button single-btn" href="<?php echo esc_url(BAKERY_PATISSERIE_SHOP_PRO_THEME_URL);?>" target="_blank"><?php esc_html_e( 'Go For Premium', 'bakery-patisserie-shop' ); ?></a></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="theme-price col card">
			<div class="price-flex">
				<div class="price-content">
					<h3><?php esc_html_e( 'Bakery Patisserie Shop WordPress Theme', 'bakery-patisserie-shop' ); ?></h3>
					<p class="main-flash"><?php 
					  printf(
					    /* translators: 1: bold FLASH DEAL text, 2: discount code */
					    esc_html__( '%1$s - Get 20%% Discount on All Themes, Use code %2$s', 'bakery-patisserie-shop' ),
					    '<strong class="bold-text">' . esc_html__( 'FLASH DEAL', 'bakery-patisserie-shop' ) . '</strong>',
					    '<strong class="bold-text">' . esc_html__( 'QBSALE20', 'bakery-patisserie-shop' ) . '</strong>'
					  ); 
					  ?></p>
					 <p>
					  <del><?php echo esc_html__( '$59', 'bakery-patisserie-shop' ); ?></del>
					  <strong class="bold-price"><?php echo esc_html__( '$39', 'bakery-patisserie-shop' ); ?></strong>
					</p>
				</div>
				<div class="price-img">
					<img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/theme-img.png" alt="theme-img" />
				</div>
			</div>
			<div class="main-pro-price">
				<a target="_blank" href="<?php echo esc_url( BAKERY_PATISSERIE_SHOP_PRO_THEME_URL ); ?>" class="pro-btn-theme price-pro" target="_blank"><?php esc_html_e( 'Upgrade To Premium Bakery Patisserie Shop WordPress Theme', 'bakery-patisserie-shop' ); ?></a>
			</div>
		</div>
	</div>
</div>
	<?php
}

function bakery_patisserie_shop_get_bundle() {
	?>
	<div id="tp_get_bundle" class="bakery-patisserie-shop-tabcontent">
		<div class="wrap about-wrap theme-main-bundle">
			<img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/theme-bundle.png" alt="theme-bundle" width="300" height="300" />
			<p class="bundle-link"><a target="_blank" href="<?php echo esc_url( BAKERY_PATISSERIE_SHOP_THEME_BUNDLE ); ?>" class="button button-primary bundle-btn"><?php esc_html_e( 'Buy WordPress Theme Bundle (120+ Themes)', 'bakery-patisserie-shop' ); ?></a></p>
		</div>
	</div>
	<?php
}