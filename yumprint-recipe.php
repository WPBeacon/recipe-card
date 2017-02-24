<?php
/*
Plugin Name: Recipe Card
Plugin URI: http://yumprint.com/recipecard
Description: Create beautiful recipes that readers can print, save and review. Recipe Card optimizes your recipes for search engines and generates nutrition facts.
Version: 1.1.7
Author: Yumprint
Author URI: http://yumprint.com
License: GPLv2 or later
*/

/*  Copyright 2012  Yumprint  (email : support@yumprint.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

$yumprint_secure_api_host = "https://api.yumprint.com";
$yumprint_api_host = "http://api.yumprint.com";
$yumprint_secure_host = "https://yumprint.com";
$yumprint_host = "http://yumprint.com";
if (!function_exists('add_action')) {
	echo "This is a plugin and is not meant to be invoked directly.";
	exit;
}

if (!defined('YUMPRINT_VERSION_KEY'))
    define('YUMPRINT_VERSION_KEY', 'yumprint_version');

if (!defined('YUMPRINT_VERSION_NUM'))
    define('YUMPRINT_VERSION_NUM', '1.1.3');

$yumprint_db_version = "1.0.0";

add_option(YUMPRINT_VERSION_KEY, YUMPRINT_VERSION_NUM);

register_activation_hook(__FILE__, 'yumprint_recipe_install');
add_action('plugins_loaded', 'yumprint_recipe_install');

add_action('init', 'add_yumprint_recipe_button');
add_action('admin_menu', 'yumprint_recipe_admin_menu');
add_action('admin_init', 'yumprint_recipe_admin_init');

$yumprint_directory = get_option('siteurl') . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__));

add_action("plugins_loaded", "yumprint_recipe_load_translation");

function yumprint_recipe_load_translation() {
	global $yumprint_directory;
	load_plugin_textdomain("yumprint-recipe", false, dirname(plugin_basename(__FILE__)) . "/languages");
}


function yumprint_recipe_admin_init() {
	global $yumprint_directory;
	wp_register_style('yumprint-recipe-theme-style', $yumprint_directory . "/css/wordpress-theme.css");
	wp_register_script('yumprint-recipe-theme-script', $yumprint_directory . "/js/wordpress-theme.js");
}

function add_yumprint_recipe_button() {
	if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
		return;
	}

	if (get_user_option('rich_editing') == 'true') {
		add_filter('mce_external_plugins', 'add_yumprint_recipe_tinymce_plugin');
		add_filter('mce_buttons', 'add_yumprint_recipe_tinymce_button');
		add_action('admin_print_scripts', 'yumprint_recipe_admin_scripts');
		add_action('admin_print_styles', 'yumprint_recipe_admin_styles');
	}
}

function yumprint_recipe_mce_css($mce_css) {
	if (!empty($mce_css)) {
		$mce_css .= ",";
	}

	$mce_css .= plugins_url('css/editor.css', __FILE__ );

	return $mce_css;
}

add_filter('mce_css', 'yumprint_recipe_mce_css');

function add_yumprint_recipe_tinymce_button($buttons) {
	$buttons[] = 'separator';
	$buttons[] = 'yumprintRecipe';
	return $buttons;
}

function add_yumprint_recipe_tinymce_plugin($plugins) {
	global $yumprint_directory;
	$plugins['yumprintRecipe'] = $yumprint_directory . '/js/editor.js';
	$plugins['noneditable'] = $yumprint_directory . '/js/noneditable.js';
	return $plugins;
}

function yumprint_recipe_admin_menu() {
	global $yumprint_directory;

	$page = add_menu_page('Recipe Card Themes', 'Recipe Card', 'edit_others_posts', 'yumprint_recipe_themes', 'yumprint_recipe_themes', $yumprint_directory . '/images/menu-icon.png');

	add_action('admin_print_styles-' . $page, 'yumprint_recipe_admin_styles');
	add_action('admin_print_scripts-' . $page, 'yumprint_recipe_admin_settings_scripts');
}

function yumprint_recipe_admin_settings_scripts() {
	wp_enqueue_script('yumprint-recipe-theme-script');
}

function yumprint_recipe_admin_styles() {
	wp_enqueue_style('yumprint-recipe-theme-style');
	wp_enqueue_style('thickbox');
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'yumprint_recipe_add_plugin_action_links' );

function yumprint_recipe_add_plugin_action_links($links) {
    $blog_name = get_bloginfo("name");

	return array_merge(
		array(
			'settings' => '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=yumprint_recipe_themes">Settings</a>',
			'feedback' => '<a href="mailto:feedback@yumprint.com?subject=Feedback from ' . $blog_name . '" target="_blank">Feedback</a>'
		),
		$links
	);
}

add_action('wp_enqueue_scripts', 'yumprint_recipe_custom_scripts');

function yumprint_recipe_custom_scripts() {
	global $yumprint_directory;

	wp_register_style('yumprint-recipe-theme-layout', $yumprint_directory . "/css/layout.css");
	wp_enqueue_style('yumprint-recipe-theme-layout');

	wp_enqueue_script('jquery');

	wp_register_script('yumprint-recipe-post', $yumprint_directory . "/js/post.js");
	wp_enqueue_script('yumprint-recipe-post');
}

function yumprint_recipe_themes() {
	global $wpdb;
	global $yumprint_directory;

	$table_name = $wpdb->prefix . "yumprint_recipe_theme";
	$themes = $wpdb->get_col("SELECT theme FROM $table_name WHERE name != 'Current' ORDER BY id DESC");
	$saved_themes = implode(",", $themes);

	$theme = $wpdb->get_row("SELECT theme FROM $table_name WHERE name = 'Current'");
	if (empty($theme)) {
		$applied_theme = "null";
	} else {
		$applied_theme = $theme->theme;
	}

	echo '<script type="text/javascript">window.yumprintRecipeAppliedTheme = ' . $applied_theme . '; window.yumprintRecipeSavedThemes = [' . $saved_themes . '];</script>';
	echo '<iframe id="yumprint-recipe-themes" src="' . $yumprint_directory . '/html/theme.html"></iframe>';
}

function yumprint_recipe_admin_scripts() {
	wp_enqueue_script('media-upload');
	wp_enqueue_script('thickbox');
	wp_enqueue_script('my-upload');
	wp_enqueue_script('jquery');
}

$yumprint_recipe_chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_";
$yumprint_recipe_char_map = array();

function yumprint_recipe_create_map() {
	global $yumprint_recipe_chars;
	global $yumprint_recipe_char_map;

	for ($i = 0; $i < strlen($yumprint_recipe_chars); $i++) {
		$ch = substr($yumprint_recipe_chars, $i, 1);
		$yumprint_recipe_char_map[$ch] = $i;
	}
}

yumprint_recipe_create_map();

function yumprint_recipe_to_id($n) {
	global $yumprint_recipe_chars;

	if ($n === 0) {
		return $yumprint_recipe_chars[0];
	}

	$digits = "";
	while ($n > 0) {
		$digits .= $yumprint_recipe_chars[$n % 64];
		$n = floor($n / 64);
	}

	return strrev($digits);
}

function yumprint_recipe_from_id($id) {
	global $yumprint_recipe_char_map;

	$id = strrev($id);

	$sum = 0;
	for ($i = 0; $i < strlen($id); $i++) {
		$ch = substr($id, $i, 1);
		$sum += ($yumprint_recipe_char_map[$ch] * pow(64, $i));
	}

	return $sum;
}

function yumprint_recipe_install() {
	global $wpdb;
	global $yumprint_db_version;

	$installed_version = get_option("yumprint_db_version");

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	if ($installed_version != $yumprint_db_version) {
		$table_name = $wpdb->prefix . "yumprint_recipe_recipe";
		$sql = "CREATE TABLE $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			recipe TEXT NOT NULL,
			nutrition TEXT NOT NULL,
			reviews TEXT,
			yumprint_id BIGINT(20) UNSIGNED,
			yumprint_key TEXT NOT NULL,
			created TIMESTAMP DEFAULT NOW() NOT NULL,
			post_id BIGINT(20) UNSIGNED
			) CHARACTER SET UTF8;";

		dbDelta($sql);

		$theme_table_name = $wpdb->prefix . "yumprint_recipe_theme";
		$theme_sql = "CREATE TABLE $theme_table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			name TEXT NOT NULL,
			theme TEXT NOT NULL
			) CHARACTER SET UTF8;";

		dbDelta($theme_sql);

		$view_table_name = $wpdb->prefix . "yumprint_recipe_view";
		$view_sql = "CREATE TABLE $view_table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			recipe_id BIGINT(20) NOT NULL,
			viewed TIMESTAMP DEFAULT NOW() NOT NULL
			) CHARACTER SET UTF8;";

		dbDelta($view_sql);

		$theme_row = $wpdb->get_row("SELECT * FROM $theme_table_name WHERE name='Current'");
		if (empty($theme_row)) {
			$default_theme = '{"name":"Current","description":"Live on blog","color":{"name":"Old Movie","title":"#414141","subheader":"#414141","save":"#666666","stat":"#808080","text":"#414141","print":"#bfbfbf","saveHighlight":"#808080","printHighlight":"#d9d9d9","titleHighlight":"#5a5a5a","subheaderHighlight":"#5a5a5a","statHighlight":"#9a9a9a","textHighlight":"#5a5a5a","saveText":"#ffffff","printText":"#ffffff"},"background":{"name":"No Border","background":"white","box":"white","border":{"style":"none","width":1,"corner":0,"color":"rgb(220, 220, 220)"},"innerBorder":{"style":"solid","width":1,"corner":0,"color":"rgb(220, 220, 220)"},"boxBorder":{"style":"solid","width":1,"corner":0,"color":"rgb(220, 220, 220)"}},"font":{"name":"Sans Basic","header":{"name":"Helvetica Neue","size":22,"transform":"none","bold":false,"italic":false,"underline":false,"family":"Helvetica Neue,Helvetica,Arial,sans-serif","websafe":true},"subheader":{"name":"Helvetica Neue","size":18,"transform":"none","bold":false,"italic":false,"underline":false,"family":"Helvetica Neue,Helvetica,Arial,sans-serif","websafe":true},"body":{"name":"Helvetica Neue","size":14,"transform":"none","bold":false,"italic":false,"underline":false,"family":"Helvetica Neue,Helvetica,Arial,sans-serif","websafe":true},"info":{"name":"Helvetica Neue","size":14,"transform":"none","bold":false,"italic":false,"underline":false,"family":"Helvetica Neue,Helvetica,Arial,sans-serif","websafe":true},"button":{"name":"Helvetica Neue","size":13,"transform":"none","bold":false,"italic":false,"underline":false,"family":"Helvetica Neue,Helvetica,Arial,sans-serif","websafe":true}},"layout":{"name":"Standard","style":"blog-yumprint-standard","picture":true,"description":true,"stats":true,"nutrition":true,"reviews":true,"print":true,"sectionHeaders":true,"brand":false, "condensed":false}}';

			$theme_data = array(
				"name" => "Current",
				"theme" => $default_theme
				);

			$wpdb->insert($theme_table_name, $theme_data);
		}

		add_option("yumprint_db_version", $yumprint_db_version);
	}
}

function yumprint_recipe_save_recipe() {
	global $wpdb;

	$x = $_POST['data'];

	$recipe = $x["recipe"];
	$recipe_id = $x["recipeId"];
	$yumprint_id = $x["yumprintId"];
	$nutrition = $x["nutrition"];
	$recipe_key = $x["yumprintKey"];
	$post_id = $x["post"];

	foreach ($nutrition as $key => &$value) {
		$value = floatval($value);
	}

	$json_recipe =json_encode($recipe);

	// BEGIN VERY BIZARRE JSON ENCODE FIX
	$json_recipe = str_replace("\\\'", "'", $json_recipe);
	$json_recipe = str_replace("\\\"", "\"", $json_recipe);
	$json_recipe = str_replace("\\\\", "\\", $json_recipe);
	// END VERY BIZARRE JSON ENCODE FIX

	$data = array(
		"recipe" => $json_recipe,
		"yumprint_id" => yumprint_recipe_from_id($yumprint_id),
		"nutrition" => json_encode($nutrition),
		"yumprint_key" => $recipe_key,
		"post_id" => $post_id
		);
	$table_name = $wpdb->prefix . "yumprint_recipe_recipe";

	if (!empty($recipe_id)) {
		$wpdb->update($table_name, $data, array('id' => $recipe_id));
	} else {
		$wpdb->insert($table_name, $data);
		$recipe_id = $wpdb->insert_id;
	}

	echo json_encode(array("recipeId" => $recipe_id));
	die();
}

function yumprint_recipe_load_recipe() {
	global $wpdb;

	$recipeId = intval($_POST['data']);

	$table_name = $wpdb->prefix . "yumprint_recipe_recipe";
	$recipe_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id=%d", $recipeId));
	if (empty($recipe_row)) {
		return json_encode(FALSE);
	}

	$recipe = json_decode($recipe_row->recipe);

	echo json_encode(array("recipe" => $recipe, "yumprintId" => yumprint_recipe_to_id($recipe_row->yumprint_id), "yumprintKey" => $recipe_row->yumprint_key));
	die();
}

function yumprint_recipe_delete_recipe() {
	global $wpdb;

	$recipeId = intval($_POST['data']);

	$table_name = $wpdb->prefix . "yumprint_recipe_recipe";
	$wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id=%d", $recipeId));

	echo json_encode(TRUE);
	die();
}

function yumprint_recipe_title_recipe() {
	global $wpdb;

	$recipeId = intval($_POST['data']);

	$table_name = $wpdb->prefix . "yumprint_recipe_recipe";
	$recipe_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id=%d", $recipeId));
	if (empty($recipe_row)) {
		return json_encode(FALSE);
	}

	$recipe = json_decode($recipe_row->recipe);
	$title = $recipe->title;

	echo json_encode($title);
	die();
}

function yumprint_recipe_save_theme() {
	global $wpdb;

	$theme = $_POST['data'];
	$theme_name = $theme["name"];

	$theme = yumprint_recipe_load_theme($theme);

	$data = array(
		"name" => $theme_name,
		"theme" => json_encode($theme)
		);
	$table_name = $wpdb->prefix . "yumprint_recipe_theme";

	$theme_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE name=%s", $theme_name));
	if (empty($theme_row)) {
		$wpdb->insert($table_name, $data);
	} else {
		$wpdb->update($table_name, $data, array('id' => $theme_row->id));
	}

	echo json_encode(TRUE);
	die();
}

function yumprint_recipe_remove_theme() {
	global $wpdb;

	$theme_name = $_POST['data'];

	$table_name = $wpdb->prefix . "yumprint_recipe_theme";
	$wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE name=%s", $theme_name));

	echo json_encode(TRUE);
	die();
}

function yumprint_recipe_load_font($font) {
	$font["size"] = intval($font["size"]);
	$font["bold"] = $font["bold"] === "true";
	$font["italic"] = $font["italic"] === "true";
	$font["underline"] = $font["underline"] === "true";
	$font["websafe"] = $font["websafe"] === "true";

	return $font;
}

function yumprint_recipe_load_theme($theme) {
	$theme["background"]["border"]["width"] = intval($theme["background"]["border"]["width"]);
	$theme["background"]["border"]["corner"] = intval($theme["background"]["border"]["corner"]);
	$theme["background"]["innerBorder"]["width"] = intval($theme["background"]["innerBorder"]["width"]);
	$theme["background"]["innerBorder"]["corner"] = intval($theme["background"]["innerBorder"]["corner"]);
	$theme["background"]["boxBorder"]["width"] = intval($theme["background"]["boxBorder"]["width"]);
	$theme["background"]["boxBorder"]["corner"] = intval($theme["background"]["boxBorder"]["corner"]);

	$theme["font"]["header"] = yumprint_recipe_load_font($theme["font"]["header"]);
	$theme["font"]["subheader"] = yumprint_recipe_load_font($theme["font"]["subheader"]);
	$theme["font"]["body"] = yumprint_recipe_load_font($theme["font"]["body"]);
	$theme["font"]["info"] = yumprint_recipe_load_font($theme["font"]["info"]);
	$theme["font"]["button"] = yumprint_recipe_load_font($theme["font"]["button"]);

	$theme["layout"]["picture"] = $theme["layout"]["picture"] !== "false";
	$theme["layout"]["description"] = $theme["layout"]["description"] !== "false";
	$theme["layout"]["stats"] = $theme["layout"]["stats"] !== "false";
	$theme["layout"]["nutrition"] = $theme["layout"]["nutrition"] !== "false";
	$theme["layout"]["reviews"] = $theme["layout"]["reviews"] !== "false";
	$theme["layout"]["print"] = $theme["layout"]["print"] !== "false";
    $theme["layout"]["sectionHeaders"] = $theme["layout"]["sectionHeaders"] !== "false";
    $theme["layout"]["brand"] = $theme["layout"]["brand"] === "true";
    $theme["layout"]["condensed"] = $theme["layout"]["condensed"] === "true";
    $theme["layout"]["numberedIngredients"] = $theme["layout"]["numberedIngredients"] === "true";
    $theme["layout"]["numberedMethods"] = $theme["layout"]["numberedMethods"] === "true";
    $theme["layout"]["numberedNotes"] = $theme["layout"]["numberedNotes"] === "true";

	return $theme;
}

add_action('wp_ajax_yumprint_recipe_prompt', 'yumprint_recipe_prompt');

add_action('wp_ajax_yumprint_recipe_save_recipe', 'yumprint_recipe_save_recipe');

add_action('wp_ajax_yumprint_recipe_load_recipe', 'yumprint_recipe_load_recipe');

add_action('wp_ajax_yumprint_recipe_delete_recipe', 'yumprint_recipe_delete_recipe');

add_action('wp_ajax_yumprint_recipe_title_recipe', 'yumprint_recipe_title_recipe');

add_action('wp_ajax_yumprint_recipe_save_theme', 'yumprint_recipe_save_theme');

add_action('wp_ajax_yumprint_recipe_remove_theme', 'yumprint_recipe_remove_theme');

add_action('wp_ajax_yumprint_recipe_update_reviews', 'yumprint_recipe_update_reviews');

add_action('wp_ajax_nopriv_yumprint_recipe_update_reviews', 'yumprint_recipe_update_reviews');

function yumprint_recipe_prompt() {
	$type = $_POST['data'];

	update_option("yumprint_recipe_prompt_" . $type, "true");
}

function yumprint_recipe_update_reviews() {
	global $wpdb;

	$reviews = $_POST['data'];

	$id = yumprint_recipe_from_id($reviews["id"]);
	$rating = floatval($reviews["rating"]);
	$count = intval($reviews["count"]);

	$data = array(
		"reviews" => json_encode(array("rating" => $rating, "count" => $count))
		);

	$table_name = $wpdb->prefix . "yumprint_recipe_recipe";
	$wpdb->update($table_name, $data, array("yumprint_id" => $id));

	echo json_encode(TRUE);
	die();
}


function yumprint_recipe_time($time) {
	if (empty($time)) {
		return null;
	}

	$time = intval($time);

	$minutes = $time % 60;
	$hours = floor(($time - $minutes) / 60);

	if ($hours > 0) {
		$result = $hours . " hr";
	} else {
		$result = "";
	}

	if ($minutes > 0) {
		$result = $result . " " . $minutes . " min";
	}

	return trim($result);
}

function yumprint_recipe_create_font($font) {
	return (object) array(
		"family" => $font->family,
		"size" => $font->size . "px",
		"transform" => $font->transform,
		"decoration" => $font->underline ? "underline" : "none",
		"weight" => $font->bold ? "bold" : "normal",
		"style" => $font->italic ? "italic" : "normal",
		"websafe" => $font->websafe
	);
}

function yumprint_recipe_get_style() {
	global $wpdb;

	$theme_table_name = $wpdb->prefix . "yumprint_recipe_theme";
	$theme_row = $wpdb->get_row("SELECT * FROM $theme_table_name WHERE name='Current'");
	if (empty($theme_row)) {
		$theme = null;
	} else {
		$theme = json_decode($theme_row->theme);
	}
	return $theme;
}

// create ISO formatted times (see http://en.wikipedia.org/wiki/ISO_8601#Durations)

function yumprint_format_ISO($time) {
    $isoTime = str_replace(" hr", "H", $time);
    $isoTime = str_replace(" min", "M", $isoTime);
    $isoTime = str_replace(" ", "", $isoTime);
    $isoTime = "PT" . $isoTime;
    
    return $isoTime;
}

// renders HTML for the recipe
//   includes hRecipe (see http://microformats.org/wiki/hrecipe)
//   includes recipe microdata (see http://schema.org/Recipe)
function yumprint_recipe_render_recipe($recipeId) {
	global $wpdb;
	global $yumprint_host;

	// get recipe
	$recipe_table_name = $wpdb->prefix . "yumprint_recipe_recipe";
	$recipe_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $recipe_table_name WHERE id=%d", intval($recipeId)));
	if (empty($recipe_row)) {
		return "";
	}

	// record view
	$data = array(
		"recipe_id" => $recipe_row->id
		);
	$view_table_name = $wpdb->prefix . "yumprint_recipe_view";
	$wpdb->insert($view_table_name, $data);

	$recipe = json_decode($recipe_row->recipe);
	$nutrition = $recipe_row->nutrition !== null ? json_decode($recipe_row->nutrition) : array();
	$created = $recipe_row->created;
	$reviews = $recipe_row->reviews !== null ? json_decode($recipe_row->reviews) : null;

	$title = $recipe->title;
	$summary = $recipe->summary;
	$prep_time = yumprint_recipe_time($recipe->prepTime);
	$cook_time = yumprint_recipe_time($recipe->cookTime);
	$total_time = yumprint_recipe_time($recipe->totalTime);
	$serves = $recipe->servings;
	$yields = $recipe->yields;
	$adapted = $recipe->adapted;
	$adapted_link = $recipe->adaptedLink;
	$author = $recipe->author;
	$image = $recipe->image;
    
    $prep_time_standard = yumprint_format_ISO($prep_time);
    $cook_time_standard = yumprint_format_ISO($cook_time);
    $total_time_standard = yumprint_format_ISO($total_time);
    
	// nutrition
	$type = null;
	$unit = 1;
	if (!empty($serves)) {
		$type = "Servings";
		$unit = floatval($serves);
	} else if (!empty($yields)) {
		$type = "Yields";
		$unit = floatval($yields);
	}

	$div = !empty($unit) ? $unit : 1;

	foreach ($nutrition as $key => &$value) {
		$value /= $div;
	}

	$grams = round($nutrition->grams);
	$oz = round($nutrition->grams * .035274, 1);
	$calories = round($nutrition->calories);
	$calories_from_fat = round($nutrition->caloriesFromFat);
	$total_fat = round($nutrition->totalFat);
	$total_fat_dv = round($nutrition->totalFat * 100 / 65);
	$saturated_fat = round($nutrition->saturatedFat);
	$saturated_fat_dv = round($nutrition->saturatedFat * 100 / 20);
	$trans_fat = round($nutrition->transFat);
	$polyunsaturated_fat = round($nutrition->polyunsaturatedFat);
	$monounsaturated_fat = round($nutrition->monounsaturatedFat);
	$cholesterol = round($nutrition->cholesterol);
	$cholesterol_dv = round($nutrition->cholesterol * 100 / 300);
	$sodium = round($nutrition->sodium);
	$sodium_dv = round($nutrition->sodium * 100 / 2400);
	$total_carbohydrates = round($nutrition->totalCarbohydrates);
	$total_carbohydrates_dv = round($nutrition->totalCarbohydrates * 100 / 300);
	$dietary_fiber = round($nutrition->dietaryFiber);
	$dietary_fiber_dv = round($nutrition->dietaryFiber * 100 / 25);
	$sugars = round($nutrition->sugars);
	$protein = round($nutrition->protein);
	$unsaturated_fat = $polyunsaturated_fat + $monounsaturated_fat;
    $vitamin_a = round($nutrition->vitaminA * 100);
    $vitamin_c = round($nutrition->vitaminC * 100);
    $calcium = round($nutrition->calcium * 100);
    $iron = round($nutrition->iron * 100);

	// get styles
	$theme = yumprint_recipe_get_style();

	$showSpacer = $theme->background->innerBorder->style !== "none" || ((!empty($prep_time) || !empty($cook_time) || !empty($total_time)) && $theme->layout->stats !== FALSE && $theme->layout->style !== "blog-yumprint-stat-focus");
	$showPicture = $theme->layout->picture !== FALSE;
	$showSummary = $theme->layout->description !== FALSE;
	$showReviews = $theme->layout->reviews !== FALSE;
	$showStats = $theme->layout->stats !== FALSE;
	$showNutrition = $theme->layout->nutrition !== FALSE;
	$showPrint = $theme->layout->print !== FALSE;
    $showSectionHeaders = $theme->layout->sectionHeaders !== FALSE;
    $showBrand = isset($theme->layout->brand) && $theme->layout->brand !== FALSE;
    $showCondensed = isset($theme->layout->condensed) && $theme->layout->condensed !== FALSE;
    $showNumberedIngredients = isset($theme->layout->numberedIngredients) && $theme->layout->numberedIngredients !== FALSE;
    $showNumberedMethods = isset($theme->layout->numberedMethods) && $theme->layout->numberedMethods !== FALSE;
    $showNumberedNotes = isset($theme->layout->numberedNotes) && $theme->layout->numberedNotes !== FALSE;
    
    $condensed = $showCondensed ? "blog-yumprint-condensed" : "";
    $numberedIngredients = $showNumberedIngredients ? "blog-yumprint-numbered-ingredients" : "";
    $numberedMethods = $showNumberedMethods ? "blog-yumprint-numbered-methods" : "";
    $numberedNotes = $showNumberedNotes ? "blog-yumprint-numbered-notes" : "";

	// get reviews
	if (!empty($reviews)) {
		foreach ($reviews as $key => &$value) {
			$value = floatval($value);
		}
		$review_count = $reviews->count;
		$review_rating = $reviews->rating;
	} else {
		$review_count = 0;
		$review_rating = 0;
	}

	$recipe_id = yumprint_recipe_to_id($recipe_row->yumprint_id);

	ob_start();

echo <<<HTML
    <div class="blog-yumprint-recipe {$theme->layout->style} {$condensed} {$numberedIngredients} {$numberedMethods} {$numberedNotes}" yumprintrecipe="$recipe_id" itemscope itemtype="http://schema.org/Recipe">
HTML;

    if (!empty($image)) {
echo <<<HTML
    <img class="blog-yumprint-google-image" src="$image" style="display:block;position:absolute;left:-10000px;top:-10000px;" itemprop="image" />
HTML;
    }
    
	if (!empty($image) && $showPicture) {
echo <<<HTML
		<div class="blog-yumprint-photo-top" style="background-image: url($image)"></div>
HTML;
	}

echo <<<HTML
	<div class="blog-yumprint-recipe-title" itemprop="name">$title</div>
HTML;

	if (!empty($created)) {
echo <<<HTML
	<div class="blog-yumprint-recipe-published" itemprop="datePublished">$created</div>
HTML;
	}

	if (!empty($image) && $showPicture) {
echo <<<HTML
		<img class="blog-yumprint-photo-top-large" src="$image" />
HTML;
	}

    $serves_text = __("Serves", "yumprint-recipe");
    $yields_text = __("Yields", "yumprint-recipe");

    if ($showStats && !empty($serves)) {
echo <<<HTML
    <div class="blog-yumprint-serves">$serves_text $serves</div>
HTML;
    } else if ($showStats && !empty($yields)) {
echo <<<HTML
    <div class="blog-yumprint-serves">$yields_text <span itemprop="recipeYield">$yields</span></div>
HTML;
    }
        
    if (!empty($summary) && $showSummary) {
echo <<<HTML
    <div class="blog-yumprint-recipe-summary" itemprop="description">$summary</div>
HTML;
	}

    echo <<<HTML
	<div class="blog-yumprint-header">
HTML;

	$write_review_text = __("Write a review", "yumprint-recipe");

	if ($showReviews) {
echo <<<HTML
		<div class='blog-yumprint-stars-reviews' itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating" color="{$theme->color->save}" highlightcolor="{$theme->color->saveHighlight}" emptycolor="{$theme->color->print}" rating="{$review_rating}" count="{$review_count}">
			<div class='blog-yumprint-star-wrapper'>
				<div class='blog-yumprint-star-container'></div>
				<meta itemprop="ratingValue" content="{$review_rating}" />
				<meta itemprop="bestRating" content="4" />
				<div class="blog-yumprint-review-count" itemprop="reviewCount" content="{$review_count}"></div>
			</div>
			<div class='blog-yumprint-write-review'>$write_review_text</div>
		</div>
HTML;
	}

	$save_recipe_text = __("Save Recipe", "yumprint-recipe");

echo <<<HTML
		<div class="blog-yumprint-save blog-yumprint-action"><a href="{$yumprint_host}/app/object/{$recipe_id}">$save_recipe_text</a></div>
HTML;

	$print_text = __("Print", "yumprint-recipe");

	if ($showPrint) {
echo <<<HTML
		<div class="blog-yumprint-print blog-yumprint-action">$print_text</div>
HTML;
	}

echo <<<HTML
	</div>
HTML;

	if ($showSpacer) {
echo <<<HTML
	<div class="blog-yumprint-spacer"></div>
HTML;
	}

	$prep_time_text = __("Prep Time", "yumprint-recipe");
	$cook_time_text = __("Cook Time", "yumprint-recipe");
	$total_time_text = __("Total Time", "yumprint-recipe");

	if ($showStats && (!empty($prep_time) || !empty($cook_time) || !empty($total_time))) {
echo <<<HTML
	<div class="blog-yumprint-info-bar">
HTML;

	if (!empty($prep_time)) {
echo <<<HTML
		<div class="blog-yumprint-infobar-section">
			<div class="blog-yumprint-infobar-section-title">$prep_time_text</div>
			<div class="blog-yumprint-infobar-section-data" itemprop="prepTime" datetime="$prep_time_standard">$prep_time <span class="value-title" title="$prep_time_standard"></span></div>
		</div>
HTML;
	}

	if (!empty($cook_time)) {
echo <<<HTML
		<div class="blog-yumprint-infobar-section">
			<div class="blog-yumprint-infobar-section-title">$cook_time_text</div>
			<div class="blog-yumprint-infobar-section-data" itemprop="cookTime" datetime="$cook_time_standard">$cook_time <span class="value-title" title="$cook_time_standard"></span></div>
		</div>
HTML;
	}

	if (!empty($total_time)) {
echo <<<HTML
		<div class="blog-yumprint-infobar-section">
			<div class="blog-yumprint-infobar-section-title">$total_time_text</div>
			<div class="blog-yumprint-infobar-section-data" itemprop="totalTime" datetime="$total_time_standard">$total_time <span class="value-title" title="$total_time_standard"></span></div>
		</div>
HTML;
	}

echo <<<HTML
	</div>
HTML;

	}


echo <<<HTML
	<div class="blog-yumprint-recipe-contents">
HTML;

	if (!empty($image) && $showPicture) {
echo <<<HTML
		<div class="blog-yumprint-photo-middle" style="background-image: url($image)"></div>
HTML;
	}

	if ($showStats && (!empty($prep_time) || !empty($cook_time) || !empty($total_time))) {
echo <<<HTML
		<div class="blog-yumprint-info-box">
HTML;

	if (!empty($prep_time)) {
echo <<<HTML
		<div class="blog-yumprint-infobox-section">
			<div class="blog-yumprint-infobox-section-title">$prep_time_text</div>
			<div class="duration blog-yumprint-infobox-section-data" itemprop="prepTime" dateTime="$prep_time_standard">$prep_time <span class="value-title" title="$prep_time_standard"></span></div>
		</div>
HTML;
	}

	if (!empty($cook_time)) {
echo <<<HTML
		<div class="blog-yumprint-infobox-section">
			<div class="blog-yumprint-infobox-section-title">$cook_time_text</div>
			<div class="duration blog-yumprint-infobox-section-data" itemprop="cookTime" dateTime="$cook_time_standard">$cook_time <span class="value-title" title="$cook_time_standard"></span></div>
		</div>
HTML;
	}

	if (!empty($total_time)) {
echo <<<HTML
		<div class="blog-yumprint-infobox-section">
			<div class="blog-yumprint-infobox-section-title">$total_time_text</div>
			<div class="duration blog-yumprint-infobox-section-data" itemprop="totalTime" datetime="$total_time_standard">$total_time <span class="value-title" title="$total_time_standard"></span></div>
		</div>
HTML;
	}

echo <<<HTML
	</div>
HTML;
	}

	$calories_text = __("calories", "yumprint-recipe");
	$gram_text = __("g", "yumprint-recipe");
	$nutrition_fact_text = __("Nutrition Facts", "yumprint-recipe");
	$serving_size_text = __("Serving Size", "yumprint-recipe");
	$label_grams_text = __("g", "yumprint-recipe");

	if ($showNutrition) {
echo <<<HTML
	<div class="blog-yumprint-nutrition-box">
		<div style="display: none;" itemprop="nutrition" itemscope itemtype ="http://schema.org/NutritionInformation">
			<div itemprop="calories">{$calories} calories</div>
			<div itemprop="carbohydrateContent">{$total_carbohydrates} g</div>
			<div itemprop="cholesterolContent">{$cholesterol} g</div>
			<div itemprop="fatContent">{$total_fat} g</div>
			<div itemprop="proteinContent">{$protein} g</div>
			<div itemprop="saturatedFatContent">{$saturated_fat} g</div>
			<div itemprop="servingSize">{$grams} g</div>
			<div itemprop="sodiumContent">{$sodium} g</div>
			<div itemprop="sugarContent">{$sugars} g</div>
			<div itemprop="transFatContent">{$trans_fat} g</div>
			<div itemprop="unsaturatedFatContent">{$unsaturated_fat} g</div>
		</div>
		<div class='blog-yumprint-nutrition-header'>{$nutrition_fact_text}</div>
		<div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-serving-size-container'>
			<div class='blog-yumprint-nutrition-left'>{$serving_size_text}</div>
			<div class='blog-yumprint-nutrition-right'>{$grams}{$label_grams_text}</div>
		</div>
HTML;

	if ($type === "Servings" || $type === "Yields") {
echo <<<HTML
		<div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-servings'>
			<div class='blog-yumprint-nutrition-left'>$type</div>
			<div class='blog-yumprint-nutrition-right'>$unit</div>
		</div>
HTML;
	}

	$amount_per_serving_text = __("Amount Per Serving", "yumprint-recipe");
	$label_calories_text = __("Calories", "yumprint-recipe");
	$label_calories_from_fat_text = __("Calories from Fat", "yumprint-recipe");
	$label_daily_value_text = __("% Daily Value *", "yumprint-recipe");
	$label_total_fat_text = __("Total Fat", "yumprint-recipe");
	$label_saturated_fat_text = __("Saturated Fat", "yumprint-recipe");
	$label_trans_text = __("Trans", "yumprint-recipe");
	$label_fat_text = __("Fat", "yumprint-recipe");
	$label_pufa_text = __("Polyunsaturated Fat", "yumprint-recipe");
	$label_mufa_text = __("Monounsaturated Fat", "yumprint-recipe");
	$label_cholesterol_text = __("Cholesterol", "yumprint-recipe");
	$label_milligrams_text = __("mg", "yumprint-recipe");
	$label_percent_text = __("%", "yumprint-recipe");
	$label_sodium_text = __("Sodium", "yumprint-recipe");
	$label_total_carbohydrates_text = __("Total Carbohydrates", "yumprint-recipe");
	$label_dietary_fiber_text = __("Dietary Fiber", "yumprint-recipe");
	$label_sugars_text = __("Sugars", "yumprint-recipe");
	$label_protein_text = __("Protein", "yumprint-recipe");
	$label_vitamin_a_text = __("Vitamin A", "yumprint-recipe");
	$label_vitamin_c_text = __("Vitamin C", "yumprint-recipe");
	$label_iron_text = __("Iron", "yumprint-recipe");
	$label_calcium_text = __("Calcium", "yumprint-recipe");
	$label_sub_text = __("* Percent Daily Values are based on a 2,000 calorie diet. Your Daily Values may be higher or lower depending on your calorie needs.", "yumprint-recipe");
	$label_wrong_text = __("Does this look wrong?", "yumprint-recipe");

echo <<<HTML
		<div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-very-thick-line blog-yumprint-nutrition-bold blog-yumprint-nutrition-amount'>$amount_per_serving_text</div>
		<div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-line'>
			<div class='blog-yumprint-nutrition-left'><span class='blog-yumprint-nutrition-bold'>$label_calories_text</span> <span class='blog-yumprint-nutrition-calories-value'>$calories</span></div>
			<div class='blog-yumprint-nutrition-right'>$label_calories_from_fat_text <span class='blog-yumprint-nutrition-calories-from-fat-value'>$calories_from_fat</span></div>
		</div>
		<div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-bold blog-yumprint-nutrition-thick-line'>
			<div class='blog-yumprint-nutrition-right'>$label_daily_value_text</div>
		</div>
		<div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-line'>
			<div class='blog-yumprint-nutrition-left'>$label_total_fat_text <span class='blog-yumprint-nutrition-total-fat-value'>$total_fat</span>$label_grams_text</div>
			<div class='blog-yumprint-nutrition-right blog-yumprint-nutrition-bold'><span class='blog-yumprint-nutrition-total-fat-daily-value'>$total_fat_dv</span>$label_percent_text</div>
		</div>
		<div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-indent blog-yumprint-nutrition-line'>
			<div class='blog-yumprint-nutrition-left'>$label_saturated_fat_text <span class='blog-yumprint-nutrition-saturated-fat-value'>$saturated_fat</span>$label_grams_text</div>
			<div class='blog-yumprint-nutrition-right blog-yumprint-nutrition-bold'><span class='blog-yumprint-nutrition-saturated-fat-daily-value'>$saturated_fat_dv</span>$label_percent_text</div>
		</div>
		<div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-indent blog-yumprint-nutrition-line'><span class='blog-yumprint-nutrition-italic'>$label_trans_text</span> $label_fat_text <span class='blog-yumprint-nutrition-trans-fat-value'>$trans_fat</span>$label_grams_text</div>
		<div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-indent blog-yumprint-nutrition-line'>$label_pufa_text <span class='blog-yumprint-nutrition-pu-fat-value'>$polyunsaturated_fat</span>$label_grams_text</div>
		<div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-indent blog-yumprint-nutrition-line'>$label_mufa_text <span class='blog-yumprint-nutrition-mu-fat-value'>$monounsaturated_fat</span>$label_grams_text</div>
        <div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-line'>
			<div class='blog-yumprint-nutrition-left'>$label_cholesterol_text <span class='blog-yumprint-nutrition-cholesterol-value'>$cholesterol</span>$label_milligrams_text</div>
			<div class='blog-yumprint-nutrition-right blog-yumprint-nutrition-bold'><span class='blog-yumprint-nutrition-cholesterol-daily-value'>$cholesterol_dv</span>$label_percent_text</div>
		</div>
		<div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-line'>
			<div class='blog-yumprint-nutrition-left'>$label_sodium_text <span class='blog-yumprint-nutrition-sodium-value'>$sodium</span>$label_milligrams_text</div>
			<div class='blog-yumprint-nutrition-right blog-yumprint-nutrition-bold'><span class='blog-yumprint-nutrition-sodium-daily-value'>$sodium_dv</span>$label_percent_text</div>
		</div>
		<div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-line'>
			<div class='blog-yumprint-nutrition-left'>$label_total_carbohydrates_text <span class='blog-yumprint-nutrition-total-carbohydrates-value'>$total_carbohydrates</span>$label_grams_text</div>
			<div class='blog-yumprint-nutrition-right blog-yumprint-nutrition-bold'><span class='blog-yumprint-nutrition-total-carbohydrates-daily-value'>$total_carbohydrates_dv</span>$label_percent_text</div>
		</div>
		<div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-indent blog-yumprint-nutrition-line'>
			<div class='blog-yumprint-nutrition-left'>$label_dietary_fiber_text <span class='blog-yumprint-nutrition-dietary-fiber-value'>$dietary_fiber</span>$label_grams_text</div>
			<div class='blog-yumprint-nutrition-right blog-yumprint-nutrition-bold'><span class='blog-yumprint-nutrition-dietary-fiber-daily-value'>$dietary_fiber_dv</span>$label_percent_text</div>
		</div>
        <div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-indent blog-yumprint-nutrition-line'>$label_sugars_text <span class='blog-yumprint-nutrition-sugars-value'>$sugars</span>$label_grams_text</div>
        <div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-line blog-yumprint-nutrition-protein'>$label_protein_text <span class='blog-yumprint-nutrition-protein-value'>$protein</span>$label_grams_text</div>
		<div class='blog-yumprint-nutrition-very-thick-line '></div>
        <div class='blog-yumprint-nutrition-line'><div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-vitamin-wrap blog-yumprint-nutrition-vitamin-left'><div class='blog-yumprint-nutrition-vitamin'>$label_vitamin_a_text</div><div class='blog-yumprint-nutrition-vitamin-value'><span class='blog-yumprint-nutrition-vitamin-a-value'>$vitamin_a</span>$label_percent_text</div></div><div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-vitamin-wrap'><div class='blog-yumprint-nutrition-vitamin'>$label_vitamin_c_text</div><div class='blog-yumprint-nutrition-vitamin-value'><span class='blog-yumprint-nutrition-vitamin-c-value'>$vitamin_c</span>$label_percent_text</div></div></div>
        <div class='blog-yumprint-nutrition-line'><div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-vitamin-wrap blog-yumprint-nutrition-vitamin-left'><div class='blog-yumprint-nutrition-vitamin'>$label_calcium_text</div><div class='blog-yumprint-nutrition-vitamin-value'><span class='blog-yumprint-nutrition-vitamin-calcium-value'>$calcium</span>$label_percent_text</div></div><div class='blog-yumprint-nutrition-item blog-yumprint-nutrition-vitamin-wrap'><div class='blog-yumprint-nutrition-vitamin'>$label_iron_text</div><div class='blog-yumprint-nutrition-vitamin-value'><span class='blog-yumprint-nutrition-iron-value'>$iron</span>$label_percent_text</div></div></div>
        <div class='blog-yumprint-nutrition-last-item blog-yumprint-nutrition-line'>$label_sub_text</div>
		<div class='blog-yumprint-nutrition-item blog-yumprint-report-error-wrapper'>
			<a href="mailto:nutrition@yumprint.com?subject=Nutrition suggestion for recipe id {$recipe_id}" target="_blank" class='blog-yumprint-report-error'>$label_wrong_text</a>
		</div>
	</div>
HTML;
	}

	$label_ingredient_text = __("Ingredients", "yumprint-recipe");
	$label_instruction_text = __("Instructions", "yumprint-recipe");
	$label_note_text = __("Notes", "yumprint-recipe");

	$section_index = 0;
	$first = TRUE;
	if (!empty($recipe->ingredients)) {
		foreach ($recipe->ingredients as $section) {
echo <<<HTML
		<div class="blog-yumprint-ingredient-section" yumprintsection="$section_index">
HTML;
		if (!empty($section->title) && $showSectionHeaders) {
echo <<<HTML
			<div class="blog-yumprint-subheader">$section->title</div>
HTML;
		} else if ($first && $showSectionHeaders) {
echo <<<HTML
                <div class="blog-yumprint-subheader">$label_ingredient_text</div>
HTML;
		}
            
echo <<<HTML
			<ol class='blog-yumprint-ingredients'>
HTML;

		$line_index = 0;
		if (!empty($section->lines)) {
			foreach ($section->lines as $line) {
echo <<<HTML
				<li class="blog-yumprint-ingredient-item" yumprintitem="$line_index" itemprop="ingredients">$line</li>
HTML;
			$line_index++;
			}
		}

echo <<<HTML
			</ol>
		</div>
HTML;
		$section_index++;
		$first = FALSE;
		}
	}


	$first = TRUE;
	if (!empty($recipe->directions)) {
		foreach ($recipe->directions as $section) {
echo <<<HTML
		<div class="blog-yumprint-method-section" yumprintsection="$section_index">
HTML;
		if (!empty($section->title) && $showSectionHeaders) {
echo <<<HTML
			<div class="blog-yumprint-subheader">$section->title</div>
HTML;
		} else if ($first && $showSectionHeaders) {
echo <<<HTML
			<div class="blog-yumprint-subheader">$label_instruction_text</div>
HTML;
		}
            
echo <<<HTML
			<ol class="blog-yumprint-methods" itemprop="recipeInstructions">
HTML;

		$line_index = 0;
		if (!empty($section->lines)) {
			foreach ($section->lines as $line) {
echo <<<HTML
				<li class="blog-yumprint-method-item" yumprintitem="$line_index">$line</li>
HTML;
			$line_index++;
			}
		}

echo <<<HTML
			</ol>
		</div>
HTML;
		$section_index++;
		$first = FALSE;
		}
	}

	$first = TRUE;
	if (!empty($recipe->notes)) {
		foreach ($recipe->notes as $section) {
echo <<<HTML
		<div class="blog-yumprint-note-section" yumprintsection="$section_index">
HTML;
		if (!empty($section->title) && $showSectionHeaders) {
echo <<<HTML
			<div class="blog-yumprint-subheader">$section->title</div>
HTML;
		} else if ($first && $showSectionHeaders) {
echo <<<HTML
			<div class="blog-yumprint-subheader">$label_note_text</div>
HTML;
		}

echo <<<HTML
			<ol class='blog-yumprint-notes'>
HTML;

		$line_index = 0;
		if (!empty($section->lines)) {
			foreach ($section->lines as $line) {
echo <<<HTML
				<li class="blog-yumprint-note-item" yumprintitem="$line_index">$line</li>
HTML;
			$line_index++;
			}
		}

echo <<<HTML
			</ol>
		</div>
HTML;
		$section_index++;
		$first = FALSE;
		}
	}

	$label_by_text = __("By", "yumprint-recipe");

    if (!empty($author)) {
echo <<<HTML
    <div class="author blog-yumprint-author" itemprop="author">$label_by_text $author</div>
HTML;
	}

    $adapted_text = __("Adapted from", "yumprint-recipe");

	if (!empty($adapted)) {
echo <<<HTML
    <div class="blog-yumprint-adapted">
    $adapted_text
HTML;

    if (!empty($adapted_link)) {
echo <<<HTML
    <a class="blog-yumprint-adapted-link" href="$adapted_link">
HTML;
    }

echo <<<HTML
    $adapted
HTML;

    if (!empty($adapted_link)) {
echo <<<HTML
    </a>
HTML;
    }
echo <<<HTML
    </div>
HTML;
    }

    $label_beta_text = __("beta", "yumprint-recipe");

    $label_macro_calories_text = __("calories", "yumprint-recipe");
    $label_macro_fat_text = __("fat", "yumprint-recipe");
    $label_macro_protein_text = __("protein", "yumprint-recipe");
    $label_macro_carbs_text = __("carbs", "yumprint-recipe");
    $label_more_text = __("more", "yumprint-recipe");

	if ($showNutrition) {
echo <<<HTML
		<div class="blog-yumprint-nutrition-bar">
			<div class="blog-yumprint-nutrition-beta">$label_beta_text</div>
            <div class="blog-yumprint-nutrition-section">
                <div class="blog-yumprint-nutrition-section-title">$label_macro_calories_text</div>
                <div class="blog-yumprint-nutrition-section-data">$calories</div>
            </div>
            <div class="blog-yumprint-nutrition-section">
                <div class="blog-yumprint-nutrition-section-title">$label_macro_fat_text</div>
                <div class="blog-yumprint-nutrition-section-data">{$total_fat}{$label_grams_text}</div>
            </div>
            <div class="blog-yumprint-nutrition-section">
                <div class="blog-yumprint-nutrition-section-title">protein</div>
                <div class="blog-yumprint-nutrition-section-data">{$protein}{$label_grams_text}</div>
            </div>
            <div class="blog-yumprint-nutrition-section">
                <div class="blog-yumprint-nutrition-section-title">carbs</div>
                <div class="blog-yumprint-nutrition-section-data">{$total_carbohydrates}{$label_grams_text}</div>
            </div>
            <div class="blog-yumprint-nutrition-more">$label_more_text</div>
        </div>
        <div class="blog-yumprint-nutrition-border"></div>
HTML;
	}

	$label_adapted_text = __("Adapted from", "yumprint-recipe");

    if (!empty($adapted)) {
echo <<<HTML
        <div class="blog-yumprint-adapted-print">
        $label_adapted_text $adapted
        </div>
HTML;
    }
    
    $blog_name = get_bloginfo("name");
    $blog_url = network_site_url("/");
echo <<<HTML
    <div class="blog-yumprint-recipe-source">{$blog_name} {$blog_url}</div>
HTML;

	$label_wordpress_text = __("Wordpress Recipe Plugin", "yumprint-recipe");
	$label_by_small_text = __("by", "yumprint-recipe");

    if ($showBrand) {
echo <<<HTML
	<div class="blog-yumprint-brand"><a href="http://wordpress.org/extend/plugins/recipe-card/">$label_wordpress_text</a> $label_by_small_text <a href="{$yumprint_host}/recipecard">Recipe Card</a></div>
HTML;
	}

echo <<<HTML
		</div>
	</div>
HTML;

	$result = ob_get_contents();
	ob_end_clean();

	return $result;
}

add_action('wp_head', 'yumprint_recipe_wp_head');

function yumprint_recipe_wp_head() {
	global $yumprint_directory;

	$theme = yumprint_recipe_get_style();

	$header_font = yumprint_recipe_create_font($theme->font->header);
	$subheader_font = yumprint_recipe_create_font($theme->font->subheader);
	$body_font = yumprint_recipe_create_font($theme->font->body);
	$info_font = yumprint_recipe_create_font($theme->font->info);
	$button_font = yumprint_recipe_create_font($theme->font->button);

	$showReviews = $theme->layout->reviews !== FALSE;

	$fonts = array();

	if (!$header_font->websafe) {
		$fonts[] = $header_font->family;
	}
	if (!$subheader_font->websafe) {
		$fonts[] = $subheader_font->family;
	}
	if (!$body_font->websafe) {
		$fonts[] = $body_font->family;
	}
	if (!$info_font->websafe) {
		$fonts[] = $info_font->family;
	}
	if (!$button_font->websafe) {
		$fonts[] = $button_font->family;
	}

	$families = str_replace(" ", "+", join("|", array_unique($fonts)));

	$ajaxurl = admin_url('admin-ajax.php');
	$blogurl = network_site_url("/");

echo <<<HTML
<script type="text/javascript">
	window.yumprintRecipePlugin = "{$yumprint_directory}";
	window.yumprintRecipeAjaxUrl = "{$ajaxurl}";
	window.yumprintRecipeUrl = "{$blogurl}";
</script>
HTML;
    
echo <<<HTML
<!--[if lte IE 8]>
<script type="text/javascript">
    window.yumprintRecipeDisabled = true;
</script>
<![endif]-->
<style type="text/css">
HTML;

	if (!empty($families)) {
echo <<<HTML
	@import url(http://fonts.googleapis.com/css?family={$families});
HTML;
	}

	$contentsBorderColor = $theme->background->innerBorder->color;
	$contentsBorderStyle = $theme->background->innerBorder->style;
	$contentsBorderWidth = $theme->background->innerBorder->width;
	if ($contentsBorderStyle === "none") {
		$contentsBorderStyle = "solid";
		$contentsBorderColor = "transparent";
		$contentsBorderWidth = 1;
	}

echo <<<HTML
    .blog-yumprint-recipe .blog-yumprint-recipe-title {
    	color: {$theme->color->title};
    }
    .blog-yumprint-recipe .blog-yumprint-subheader, .blog-yumprint-recipe .blog-yumprint-infobar-section-title, .blog-yumprint-recipe .blog-yumprint-infobox-section-title, .blog-yumprint-nutrition-section-title {
        color: {$theme->color->subheader};
    }
    .blog-yumprint-recipe .blog-yumprint-save, .blog-yumprint-recipe .blog-yumprint-header .blog-yumprint-save a {
    	background-color: {$theme->color->save};
    	color: {$theme->color->saveText} !important;
    }
    .blog-yumprint-recipe .blog-yumprint-save:hover, .blog-yumprint-recipe .blog-yumprint-header .blog-yumprint-save:hover a {
    	background-color: {$theme->color->saveHighlight};
    }
    .blog-yumprint-recipe .blog-yumprint-adapted-link, .blog-yumprint-nutrition-more, .blog-yumprint-report-error {
        color: {$theme->color->save};
    }
    .blog-yumprint-recipe .blog-yumprint-infobar-section-data, .blog-yumprint-recipe .blog-yumprint-infobox-section-data, .blog-yumprint-recipe .blog-yumprint-adapted, .blog-yumprint-recipe .blog-yumprint-author, .blog-yumprint-recipe .blog-yumprint-serves, .blog-yumprint-nutrition-section-data {
        color: {$theme->color->stat};
    }
    .blog-yumprint-recipe .blog-yumprint-recipe-summary, .blog-yumprint-recipe .blog-yumprint-ingredient-item, .blog-yumprint-recipe .blog-yumprint-method-item, .blog-yumprint-recipe .blog-yumprint-note-item, .blog-yumprint-write-review, .blog-yumprint-nutrition-box {
        color: {$theme->color->text};
    }
    .blog-yumprint-write-review:hover, .blog-yumprint-nutrition-more:hover, .blog-yumprint-recipe .blog-yumprint-adapted-link:hover {
        color: {$theme->color->saveHighlight};
    }
    .blog-yumprint-recipe .blog-yumprint-nutrition-bar:hover .blog-yumprint-nutrition-section-title {
        color: {$theme->color->subheaderHighlight};
    }
    .blog-yumprint-recipe .blog-yumprint-nutrition-bar:hover .blog-yumprint-nutrition-section-data {
        color: {$theme->color->statHighlight};
    }

    .blog-yumprint-recipe .blog-yumprint-print {
    	background-color: {$theme->color->print};
    	color: {$theme->color->printText};
    }
    .blog-yumprint-recipe .blog-yumprint-print:hover {
    	background-color: {$theme->color->printHighlight};
    }
    .blog-yumprint-recipe {
    	background-color: {$theme->background->background};
    	border-color: {$theme->background->border->color};
    	border-style: {$theme->background->border->style};
    	border-width: {$theme->background->border->width}px;
    	border-radius: {$theme->background->border->corner}px;
    }
    .blog-yumprint-recipe .blog-yumprint-recipe-contents {
    	border-top-color: {$contentsBorderColor};
    	border-top-width: {$contentsBorderWidth}px;
    	border-top-style: {$contentsBorderStyle};
    }
    .blog-yumprint-recipe .blog-yumprint-info-bar, .blog-yumprint-recipe .blog-yumprint-nutrition-bar, .blog-yumprint-nutrition-border {
    	border-top-color: {$theme->background->innerBorder->color};
    	border-top-width: {$theme->background->innerBorder->width}px;
    	border-top-style: {$theme->background->innerBorder->style};
    }
    .blog-yumprint-nutrition-line, .blog-yumprint-nutrition-thick-line, .blog-yumprint-nutrition-very-thick-line {
    	border-top-color: {$theme->background->innerBorder->color};
    }
    .blog-yumprint-recipe .blog-yumprint-info-box, .blog-yumprint-nutrition-box {
    	background-color: {$theme->background->box};
    	border-color: {$theme->background->boxBorder->color};
    	border-style: {$theme->background->boxBorder->style};
    	border-width: {$theme->background->boxBorder->width}px;
    	border-radius: {$theme->background->boxBorder->corner}px;
    }
    .blog-yumprint-recipe .blog-yumprint-recipe-title {
		font-family: {$header_font->family}, Helvetica Neue, Helvetica, Tahoma, Sans Serif, Sans;
		font-size: {$header_font->size};
		font-weight: {$header_font->weight};
		font-style: {$header_font->style};
		text-transform: {$header_font->transform};
		text-decoration: {$header_font->decoration};
    }
    .blog-yumprint-recipe .blog-yumprint-subheader {
		font-family: {$subheader_font->family}, Helvetica Neue, Helvetica, Tahoma, Sans Serif, Sans;
		font-size: {$subheader_font->size};
		font-weight: {$subheader_font->weight};
		font-style: {$subheader_font->style};
		text-transform: {$subheader_font->transform};
		text-decoration: {$subheader_font->decoration};
    }
    .blog-yumprint-recipe .blog-yumprint-recipe-summary, .blog-yumprint-recipe .blog-yumprint-ingredients, .blog-yumprint-recipe .blog-yumprint-methods, .blog-yumprint-recipe .blog-yumprint-notes, .blog-yumprint-write-review, .blog-yumprint-nutrition-box {
		font-family: {$body_font->family}, Helvetica Neue, Helvetica, Tahoma, Sans Serif, Sans;
		font-size: {$body_font->size};
		font-weight: {$body_font->weight};
		font-style: {$body_font->style};
		text-transform: {$body_font->transform};
		text-decoration: {$body_font->decoration};
    }
    .blog-yumprint-recipe .blog-yumprint-info-bar, .blog-yumprint-recipe .blog-yumprint-info-box, .blog-yumprint-recipe .blog-yumprint-adapted, .blog-yumprint-recipe .blog-yumprint-author, .blog-yumprint-recipe .blog-yumprint-serves, .blog-yumprint-recipe .blog-yumprint-infobar-section-title, .blog-yumprint-recipe .blog-yumprint-infobox-section-title,.blog-yumprint-recipe .blog-yumprint-nutrition-bar, .blog-yumprint-nutrition-section-title, .blog-yumprint-nutrition-more {
		font-family: {$info_font->family}, Helvetica Neue, Helvetica, Tahoma, Sans Serif, Sans;
		font-size: {$info_font->size};
		font-weight: {$info_font->weight};
		font-style: {$info_font->style};
		text-transform: {$info_font->transform};
		text-decoration: {$info_font->decoration};
    }
    .blog-yumprint-recipe .blog-yumprint-action {
		font-family: {$button_font->family}, Helvetica Neue, Helvetica, Tahoma, Sans Serif, Sans;
		font-size: {$button_font->size};
		font-weight: {$button_font->weight};
		font-style: {$button_font->style};
		text-transform: {$button_font->transform};
		text-decoration: {$button_font->decoration};
    }
HTML;

    if ($showReviews) {
echo <<<HTML
    .blog-yumprint-header {
        width: 100% !important;
    }
HTML;
    }
                
echo <<<HTML
    </style>
HTML;
                
}
                
                
function yumprint_recipe_shortcode($atts, $content = null) {
	extract(shortcode_atts(array(
		"id" => "-1"
	), $atts));

	return yumprint_recipe_render_recipe($id);
}

add_shortcode('yumprint-recipe', 'yumprint_recipe_shortcode');

add_action('yumprint_recipe_widget_update_action', 'yumprint_recipe_widget_update');

register_activation_hook(__FILE__, 'yumprint_recipe_activation');
register_deactivation_hook(__FILE__, 'yumprint_recipe_deactivation');

function yumprint_recipe_activation() {
	wp_schedule_event(time(), 'daily', 'yumprint_recipe_widget_update_action');
}

function yumprint_recipe_deactivation() {
	wp_clear_scheduled_hook('yumprint_recipe_widget_update_action');
}

function yumprint_recipe_widget_update() {
	global $yumprint_secure_api_host;
	global $wpdb;
	global $wp_version;

	// get recipe info
	$recipe_table_name = $wpdb->prefix . "yumprint_recipe_recipe";
	$view_table_name = $wpdb->prefix . "yumprint_recipe_view";
	$recipes = $wpdb->get_results("SELECT r.id, r.yumprint_id, r.yumprint_key, r.post_id, count(*) as views FROM $recipe_table_name r JOIN $view_table_name v on v.recipe_id=r.id GROUP BY r.id");

	// organize data
	$data = array();
	foreach ($recipes as $recipe) {
		$data[] = array(
			"id" => yumprint_recipe_to_id($recipe->yumprint_id),
			"key" => $recipe->yumprint_key,
			"permalink" => get_permalink($recipe->post_id),
			"views" => $recipe->views
		);
	}

	$blog = array(
		"name" => get_bloginfo("name"),
		"url" => network_site_url("/"),
		"host" => "wordpress",
		"hostVersion" => strval($wp_version),
		"widgetVersion" => get_option(YUMPRINT_VERSION_KEY)
	);

	$web_data = array(
		"body" => array(
			"data" => json_encode($data),
			"blog" => json_encode($blog)
		),
		"timeout" => 60
	);

	// call
	$response = wp_remote_post($yumprint_secure_api_host . "/widget/update", $web_data);

	// update recipes
	if (!is_wp_error($response)) {
		$wpdb->query("DELETE FROM $view_table_name");
		$result = json_decode($response["body"]);
		if (!empty($result) && !empty($result->success) && !empty($result->result)) {
			foreach ($result->result as $recipe) {
				$id = yumprint_recipe_from_id($recipe->id);
				$nutrition = $recipe->nutrition;
				foreach ($nutrition as $key => &$value) {
					$value = floatval($value);
				}
				$nutrition = json_encode($nutrition);
				$reviews = $recipe->reviews;
				foreach ($reviews as $key => &$value) {
					$value = floatval($value);
				}
				$reviews = json_encode($reviews);
				$wpdb->update($recipe_table_name, array("nutrition" => $nutrition, "reviews" => $reviews), array('yumprint_id' => $id));
			}
		}
	}
}

function yumprint_recipe_add_admin_scripts($hook) {
	wp_enqueue_script('jquery');
	wp_enqueue_style('wp-pointer');
	wp_enqueue_script('wp-pointer');
}

function yumprint_recipe_print_footer_scripts() {
	$show_editor_prompt = get_option('yumprint_recipe_prompt_editor');
	$show_post_prompt = get_option('yumprint_recipe_prompt_post');
	$show_theme_prompt = get_option('yumprint_recipe_prompt_theme');

echo <<<HTML
	<script type='text/javascript'>
		jQuery(function () {
			if (!jQuery("body").pointer) {
				return;
			}
			if ("{$show_editor_prompt}" !== "true" && (/post\.php/.test(window.location.pathname) || /post-new\.php/.test(window.location.pathname))) {
				var f = function () {
					if (!jQuery("#content_yumprintRecipe").length) {
						setTimeout(f, 500);
					} else {
						jQuery('#content_yumprintRecipe').pointer({
							content: "<h3>Recipe Card</h3><p>Click the Recipe Card icon to insert a recipe</p>",
							position: {
								edge: 'bottom',
								align: 'left',
								offset: '-50 -10'
							}
						}).pointer('open');
						jQuery.post(ajaxurl, { action: 'yumprint_recipe_prompt', data: 'editor' });
					}
				};
				f();
			} else if ("{$show_post_prompt}" !== "true" && /yumprint_recipe_themes/.test(window.location.search)) {
				jQuery("#menu-posts").pointer({
					content: "<h3>Recipe Card</h3><p>Click here to create a recipe once you have chosen a template</p>",
					position: 'top'
				}).pointer('open');
				jQuery.post(ajaxurl, { action: 'yumprint_recipe_prompt', data: 'post' });
			} else if ("{$show_theme_prompt}" !== "true") {
				jQuery("#toplevel_page_yumprint_recipe_themes").pointer({
					content: "<h3>Recipe Card</h3><p>Click here to create your recipe template</p>",
					position: 'top'
				}).pointer('open');
				jQuery.post(ajaxurl, { action: 'yumprint_recipe_prompt', data: 'theme' });
			}
		});
	</script>
HTML;
}

add_action('admin_enqueue_scripts', 'yumprint_recipe_add_admin_scripts');
add_action('admin_print_footer_scripts', 'yumprint_recipe_print_footer_scripts');

?>