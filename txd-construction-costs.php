<?php
/*
Plugin Name: TXD Construction Costs
Plugin URI: http://qqngoc.net/
Description: Chuc nang tinh chi phi xay nha
Version: 0.1
Author: qqngoc2988@gmail.com
*/

if ( ! defined( 'WPINC' ) ) {
  die;
}

define('TXDCC_PLUG_URL', untrailingslashit(plugin_dir_url(__FILE__)));
define('TXDCC_PLUG_DIR', untrailingslashit(plugin_dir_path(__FILE__)));

define('TXDCC_HOUSE_TYPE', [ // Loại công trình
  'town' => 'Nhà phố',
  'villa' => 'Biệt thự',
  'office' => 'Nhà văn phòng',
]);

define('TXDCC_DESIGN_TYPE', [ // loại thiết kế
  'all' => 'Trọn gói',
  'exterior' => 'Kiến trúc',
  'interior' => 'Nội thất',
]);

define('TXDCC_PILE_TYPE', [ // loại cọc
  '200x200' => 'Cọc 200x200',
  '250x250' => 'Cọc 250x250',
  'D500' => 'Cọc nhồi D500',
]);

define('TXDCC_QUALITY', [ // chất lượng
  'medium' => 'Trung bình',
  'quite' => 'Khá',
  'good' => 'Tốt',
  'best' => 'Tinh',
]);

define('TXDCC_CONTINGENCY_RATE', 0.05); // tỉ lệ tính dự phòng 5%

define('TXDCC_AVERAGE_PILE_DEPTH', 10); // độ sâu cọc trung bình (m)

define('TXDCC_PLANK_WIDTH', 4); // chiều dài 1 cừ (m)

define('TXDCC_PRESSED_PLANK_PRICE', 290000); // đơn giá ép cừ

/*
define('TXDCC_DESIGN_PRICING', [ // đơn giá thiết kế, key là TXDCC_DESIGN_TYPE
  'all' => ['ex'=>150000, 'in'=>100000], // [kiến trúc, nội thất]
  'exterior' => ['ex'=>150000, 'in'=>0], // [kiến trúc, nội thất]
  'interior' => ['ex'=>0, 'in'=>150000], // [kiến trúc, nội thất]
]);

define('TXDCC_PILE_TABLE', [ // hệ số và đơn giá theo loại cọc, key là TXDCC_PILE_TYPE
  'coefficient' => [], // hệ số tính tim cọ
  'price' => [],  // đơn giá ep cọc
]);

define('TXDCC_HOUSE_PRICING', [ // đơn giá theo loại nhà
  'foundation' => [], // đơn giá móng
  'rough_build' => [],  // đơn giá thô xây dựng
  'water_electricity' => [],  // đơn giá thô điện nước
  'exterior_completion' => [[]], // đơn giá hoàn thiện theo chất lượng,
  'interior_completion' => [[]], // đơn giá nội thất theo chất lượng,
]);
*/

register_activation_hook( __FILE__, 'txdcc_activate' );
register_deactivation_hook( __FILE__, 'txdcc_deactive' );

function txdcc_activate() {
  add_option( 'txdcc_design_pricing', [], false, false );
  add_option( 'txdcc_pile_table', [], false, false );
  add_option( 'txdcc_house_pricing', [], false, false );
}

function txdcc_deactive() {

}

function txdcc_format_content($raw_string) {
  global $wp_embed;

  $content = wp_kses_post( $raw_string );
  $content = wptexturize($content);
  $content = convert_chars($content);
  $wp_embed->run_shortcode($content);
  $content = $wp_embed->autoembed($content);
  $content = convert_smilies($content);
  $content = wpautop($content);
  $content = shortcode_unautop($content);
  $content = prepend_attachment($content);
  $content = wp_filter_content_tags($content);
  $content = capital_P_dangit($content);
  $content = do_shortcode($content);

  return $content;
}

function txdcc_chuso() {
  return ['không','một','hai','ba','bốn','năm','sáu','bảy','tám','chín'];
}

function txdcc_dochangchuc($so,$daydu) {
  $mangso = txdcc_chuso();
  $chuoi = "";
  $chuc = intval($so/10,0);
  $donvi = $so%10;
  if ($chuc>1) {
    $chuoi = " ".$mangso[$chuc]." mươi";
    if ($donvi==1) { 
      $chuoi .= " mốt";
    } 
  } else if ($chuc==1) {
    $chuoi = " mười";
    if ($donvi==1) { 
      $chuoi .= " một";
    } 
  } else if ($daydu && $donvi>0) {
    $chuoi = " lẻ";
  }
  if ($donvi==5 && $chuc>1) {
    $chuoi .= " lăm";
  } else if ($donvi>1||($donvi==1&&$chuc==0)) {
    $chuoi .= " ".$mangso[$donvi];
  }
  return $chuoi;
}
function txdcc_docblock($so,$daydu) {
  $mangso = txdcc_chuso();
  $chuoi = "";
    $tram = intval($so/100,0);
    $so = $so%100;
  if ($daydu || $tram>0) {
    $chuoi = " ".$mangso[$tram]." trăm";
    $chuoi .= txdcc_dochangchuc($so,true);
  } else {
    $chuoi = txdcc_dochangchuc($so,false);
  }
  return $chuoi;
}
function txdcc_dochangtrieu($so,$daydu) {
  $chuoi = "";
    $trieu = intval($so/1000000,0);
    $so = $so%1000000;
  if ($trieu>0) {
    $chuoi = txdcc_docblock($trieu,$daydu)." triệu";
    $daydu = true;
  }
  $nghin = intval($so/1000,0);
  $so = $so%1000;
  if ($nghin>0) {
    $chuoi .= txdcc_docblock($nghin,$daydu)." nghìn";
    $daydu = true;
  }
  if ($so>0) {
    $chuoi .= txdcc_docblock($so,$daydu);
  }
  return $chuoi;
}

function txdcc_doc_so($so) {
  $mangso = txdcc_chuso();
  if ($so==0)
    return $mangso[0];
  $chuoi = ""; $hauto = "";
  do {
    $ty = $so%1000000000;

    $so = intval($so/1000000000,0);
    if ($so>0) {
      $chuoi = txdcc_dochangtrieu($ty,true).$hauto.$chuoi;
    } else {
      $chuoi = txdcc_dochangtrieu($ty,false).$hauto.$chuoi;
    }
    $hauto = " tỷ";
  } while ($so>0);
  return $chuoi;
}

require_once TXDCC_PLUG_DIR.'/wp-async-request.php';
require_once TXDCC_PLUG_DIR.'/wp-background-process.php';
require_once TXDCC_PLUG_DIR.'/class-txdcc-admin.php';