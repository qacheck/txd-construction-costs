<?php
require TXDCC_PLUG_DIR.'/PhpSpreadsheet/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class txdcc_admin {

	private static $instance = null;

	private function __construct() {

		add_action( 'admin_menu', [$this, 'create_admin_menu'] );

		add_action( 'admin_init', [$this, 'register_settings'] );

	}

	public function create_admin_menu() {
		add_management_page( 'Construction Cost Calculator', 'TXDCC', 'manage_options', 'txdcc', [$this, 'admin_page'] );
	}

	public function admin_page() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		?>
		<div class="wrap">
			<h2>TXD Utility Settings</h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'txdcc' ); ?>
				<?php do_settings_sections( 'txdcc' ); ?>
				<table class="form-table">
					<?php self::design_pricing(); ?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	private static function design_pricing() {

		?>
		<tr valign="top">
			<th scope="row" colspan="3" style="padding-bottom: 0;">ĐƠN GIÁ THIẾT KẾ</th>
		</tr>
		<?php
		foreach (TXDCC_DESIGN_TYPE as $key => $value) {
			?>
			<tr valign="top">
				<th scope="row"><?=esc_html($value)?></th>
				<td>Kiến trúc: <input type="text" value="" name="txdcc_design_pricing[<?=$key?>][ex]">vnđ</td>
				<td>Nội thất: <input type="text" value="" name="txdcc_design_pricing[<?=$key?>][in]">vnđ</td>
			</tr>
			<?php
		}
		?>
		<?php
	}

	public function register_settings() {
		register_setting( 'txdcc', 'txdcc_design_pricing' );
		register_setting( 'txdcc', 'txdcc_pile_table' );
		register_setting( 'txdcc', 'txdcc_house_pricing' );
	}

	public static function get_instance() {
		if(empty(self::$instance))
			self::$instance = new self();
		return self::$instance;
	}
}
txdcc_admin::get_instance();