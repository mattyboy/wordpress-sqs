<?php
/*
Plugin Name: Wordpress Amazon SNS
Plugin URI: http://mattyboy.net
Description: Allows Wordpress to publish messages to Amazon Simple Notification Service
Author: Matt Weston
Author URI: http://mattyboy.net
Version: 1.0
*/

// Prevent direct access to plugin php
if (!function_exists('add_action')){exit;}

// Library
include "includes/aws.phar";
use Aws\Common\Credentials\Credentials;
use Aws\Sns\SnsClient;

// Globals
$plugin = plugin_basename( __FILE__ );
$plugin_name = "Wordpress SNS";
$plugin_slug = "wordpress-sns";
$plugin_version = "1.0";
$option_group = "wordpress-sns-group";
$option_name = "wordpress-sns-options";

add_action('init', 'plugin_init');
function plugin_init() {}

add_action('wp_head', 'wordpress_sns_head');
function wordpress_sns_head() {
global $plugin_slug;
?>
  <meta name='<?= $plugin_slug ?>' content='<?= time_hash() ?>'>
<?php
}

function wordpress_sns_scripts() {
  global $plugin_slug, $plugin_version;
  wp_register_script( "${plugin_slug}_script", plugins_url("/js/$plugin_slug.js", __FILE__ ), array('jquery'), $plugin_version, true);
  wp_enqueue_script( "${plugin_slug}_script" );
  wp_enqueue_style('custom-style',plugins_url("/css/$plugin_slug.css", __FILE__ ));
}
add_action( 'wp_enqueue_scripts', 'wordpress_sns_scripts' );

// create custom plugin settings menu
add_action('admin_menu', 'register_admin_menu');
function register_admin_menu() {
  global $plugin_name, $plugin_slug;
  // add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function);
  add_options_page("$plugin_name Settings", $plugin_name, 'manage_options', $plugin_slug, 'plugin_settings');
}

// displays message to say plugin not configured
add_action('admin_notices', 'register_admin_notices');
function register_admin_notices() {
  global $plugin_name, $plugin_slug, $option_name;
  $options = get_option($option_name);
  $screen = get_current_screen();

  // Check all option has a value
  $options_valid = true;
  if(is_array($options) == true) {
    foreach ($options as $key => $value) {
      if(empty($value) == true) $options_valid = false;
    }
  }

  // Show error if missing a plugin option
  if($options_valid == false || is_array($options) == false) {
    $settings_link = "<a href='options-general.php?page=$plugin_slug'>settings</a>";
    add_settings_error('option-unset', 'option-unset', "$plugin_name is not configured correctly, check plugin $settings_link", 'error' );
    if($screen->parent_base != 'options-general')
      settings_errors('option-unset', false, true);
  }
}

// Add settings link on plugin page
add_filter("plugin_action_links_$plugin", 'register_settings_link');
function register_settings_link($links) {
  global $plugin_slug;
  $settings_link = "<a href='options-general.php?page=$plugin_slug'>Settings</a>";
  array_unshift($links, $settings_link);
  return $links;
}

// Page used to manage settings
function plugin_settings() {
  global $plugin_name, $plugin_slug, $option_group;
?>
<div>
  <h2><?= $plugin_name ?> Settings</h2>
  <form action="options.php" method="post">
  <?php
  settings_fields($option_group);
  do_settings_sections($plugin_slug);
  submit_button();
  ?>
  </form>
</div>
<?php
}

// Register plugin settings
add_action('admin_init', 'register_settings');
function register_settings() {
  global $plugin_slug, $option_group, $option_name;
  $section_one = "aws-settings";
  $section_two = "topic-settings";
  $section_three = "help-settings";

  // add_settings_field( $id, $title, $callback, $page, $section = 'default', $args = array() )
  add_settings_field('access_key',       'AWS Access Key',    'aws_access_key', $plugin_slug, $section_one, args('access_key'));
  add_settings_field('secret_key',       'AWS Secret Key',    'aws_secret_key', $plugin_slug, $section_one, args('secret_key'));
  add_settings_field('region',           'AWS Region',        'aws_region',     $plugin_slug, $section_one, args('region'));
  add_settings_field('topic_arn',        'Topic ARN',         'aws_topic_arn',  $plugin_slug, $section_two, args('topic_arn'));
  add_settings_field('prefix',           'Subject Prefix',    'text',           $plugin_slug, $section_two, args('prefix'));
  add_settings_field('subject',          'Subject Default',   'text',           $plugin_slug, $section_two, args('subject'));
  //add_settings_field('text',   'Some Text', 'text',   $plugin_slug, $section_two, args('text'));
  //add_settings_field('yes_no', 'Yes or No', 'yes_no', $plugin_slug, $section_two, args('yes_no'));

  // add_settings_section( $id, $title, $callback, $page )
  add_settings_section($section_one,   'AWS Settings',   'aws_setting_callback',    $plugin_slug);
  add_settings_section($section_two,   'Topic Settings', 'topic_settings_callback', $plugin_slug);
  add_settings_section($section_three, 'Help Section',   'help_callback',           $plugin_slug);

  // register_setting( $option_group, $option_name, $sanitize_callback )
  register_setting($option_group, $option_name, 'sanitize_callback');
}

// Used to create simple array for add_settings_field
function args($name) { return array('name'=>$name); }
// Used for add_settings_section section_one
function aws_setting_callback() { echo "<p>Please provide your AWS Credentials</p>"; }
// Used for add_settings_section section_two
function topic_settings_callback() { echo "<p>Please choose a topic</p>"; }

// Render out a basic form
function help_callback() {
  ?>
<h4>Things to Note</h4>
<p>wpsns-form class is important. Do not leave it off.</p>
<h4>Example sns Form:</h4>
<pre>
  <form class="wpsns-form" action="/wp-admin/admin-ajax.php" method="post">
    <input name="contact" type="text" placeholder="Contact Name *" />
    <input name="email" type="text" placeholder="Email Address *" />
    <input name="subject" type="text" placeholder="Subject *" />
    <textarea name="message"></textarea>
    <button type="button">Submit</button>
  </form>
</pre>
  <?php
}

// Used for validation of saved option values
function sanitize_callback($input) {
  $valid = array();

  // Sanitize text fields
  $valid['access_key'] = sanitize_text_field($input['access_key']);
  $valid['secret_key'] = sanitize_text_field($input['secret_key']);
  $valid['topic_arn']  = sanitize_text_field($input['topic_arn']);
  $valid['region']     = sanitize_text_field($input['region']);
  $valid['prefix']     = sanitize_text_field($input['prefix']);
  $valid['subject']    = sanitize_text_field($input['subject']);

  // Email Fields
  //$valid['email'] = sanitize_email($input['email']);
  //if(sanitize_email($input['email']) != $input['email'] || empty($input['email']))
  //add_settings_error('email','email_error','You supplied an invalid email address','error');

  // use for debug input
  //error_log('input: '.print_r($input, TRUE),0);
  //error_log('valid: '.print_r($valid, TRUE),0);
  return $valid;
}

// Render input_string options used in add_settings_field
function aws_access_key($args) {
  global $option_name;
  $options = get_option($option_name);
  $field_name = $args['name'];
  $field_value = esc_attr($options[$field_name]);
  $format = '<input type="text" id="%2$s" name="%1$s[%2$s]" value="%3$s" size="20" placeholder="AKIAIOSFODNN7EXAMPLE" maxlength="20"/>';
  printf($format, $option_name, $field_name, $field_value, $field_placeholder);
}

function aws_secret_key($args) {
  global $option_name;
  $options = get_option($option_name);
  $field_name = $args['name'];
  $field_value = esc_attr($options[$field_name]);
  $format = '<input type="text" id="%2$s" name="%1$s[%2$s]" value="%3$s" size="40" placeholder="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY" maxlength="40"/>';
  printf($format, $option_name, $field_name, $field_value);
}

// TODO: Get list of available regions from AWS
function aws_region($args) {
  global $option_name;
  $options = get_option($option_name);
  $field_name = $args['name'];
  $field_value = esc_attr($options[$field_name]);
  $format = '<input type="text" id="%2$s" name="%1$s[%2$s]" value="%3$s" size="20" placeholder="us-west-1"/>';
  printf($format, $option_name, $field_name, $field_value);
}

// TODO: Get list of available topics straight from AWS
function aws_topic_arn($args) {
  global $option_name;
  $options = get_option($option_name);
  $field_name = $args['name'];
  $field_value = esc_attr($options[$field_name]);
  $format = '<input type="text" id="%2$s" name="%1$s[%2$s]" value="%3$s" size="60" placeholder="arn:aws:sns:us-west-1:123456789012:topicid"/>';
  printf($format, $option_name, $field_name, $field_value);
}

function yes_no($args) {
  global $option_name;
  $options = get_option($option_name);
  $field_name = $args['name'];
  $field_value = esc_attr($options[$field_name]);
  $format = '<select id="%2$s" name="%1$s[%2$s]"><option value="yes">Yes</option><option value="no">No</option></select>';
  if($field_value == 'yes') $format = str_replace('"yes"', '"yes" selected', $format);
  if($field_value == 'no') $format = str_replace('"no"', '"no" selected', $format);
  printf($format, $option_name, $field_name, $field_value);
}

function email($args) {
  global $option_name;
  $options = get_option($option_name);
  $field_name = $args['name'];
  $field_value = esc_attr($options[$field_name]);
  $format = '<input type="text" id="%2$s" name="%1$s[%2$s]" value="%3$s" placeholder="your.email@domain.com" size="70"/>';
  printf($format, $option_name, $field_name, $field_value, $field_placeholder);
}

function text($args) {
  global $option_name;
  $options = get_option($option_name);
  $field_name = $args['name'];
  $field_value = esc_attr($options[$field_name]);
  $format = '<input type="text" id="%2$s" name="%1$s[%2$s]" value="%3$s" placeholder="text value" size="50"/>';
  printf($format, $option_name, $field_name, $field_value, $field_placeholder);
}

// Register Wordpress AJAX functions
add_action("wp_ajax_nopriv_$plugin_slug", 'wordpress_sns_ajax');
add_action("wp_ajax_$plugin_slug", 'wordpress_sns_ajax');
function wordpress_sns_ajax() {
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $response = array('response'=>'GET NOT SUPPORTED');
    handle_response($response);
  }

  // Store redirect_url
  // $redirect = $_POST['redirect_url'];

  // Check if we have a valid time and hash
  $hash = post_var('hash');
  $time = post_var('time');
  if(empty($time) == true) {
    // no time was provided
    // bot trying to bypass form or
    // or forgot to add wpsns-form class to weblead form
    $response = array('response'=>'form incorrectly configured');
    handle_response($response);
  } else if(check_time($time, $hash)) {
    // was quicker then 5 seconds or longer then an hour
    // or hash of time does not match our hash
    // probably a bot
    $response = array('response'=>'free pass');
    handle_response($response);
  }

  // Get $_POST values for our message
  $post = array();
  foreach ($_POST as $key => $value) {
    $post[$key] = post_var($key);
  }

  // Send to AWS
  $aws = publish_sns($post);

  // Put data on session in case we need it later
  $_SESSION['data'] = $post;
  $response = array('response'=>"$aws");
  handle_response($response);
}

function publish_sns($post=array()) {
  global $option_name;
  $response = "unknown";
  try {
    // Get plugin values
    $options = get_option($option_name);
    $access_key = $options['access_key'];
    $secret_key = $options['secret_key'];
    $region     = $options['region'];
    $topic_arn  = $options['topic_arn'];

    $credentials = new Credentials($access_key, $secret_key);
    $sns = SnsClient::factory(array('credentials' => $credentials,'region' => $region));

    // Subject built from options or from posted value if exists
    $subject = $options['prefix'] . " " . $options['subject'];
    if($post['subject']) $subject  = $options['prefix'] . " " . $post['subject'];

    // Message build from posted contents
    $message  = "";
    foreach ($post as $key => $value) {
      $message  .= "$key: $value\r\n";
    }

    $payload = array(
      'TopicArn' => $topic_arn,
      'Subject' => $subject,
      'Message' => $message
    );

    // Get topic attributes
    $sns->publish($payload);

    $response = 'message published to topic';
  } catch (Exception $e) {
    return 'Caught exception: ' . $e->getMessage();
  }
  return $response;
}

function handle_response($response=array()) {
  header('Content-Type: application/json');
  echo json_encode($response);
  die(); // this is required to return a proper result
}

// Get a variable from $_POST
function post_var($name) {
  $value = ($_POST[$name] == null) ? "" : $_POST[$name];
  unset($_POST[$name]);
  return $value;
}

// Generate unique time based code for forms
function time_hash() {
  $time = microtime(true);
  $hash = encrypt($time);
  $json = array('time'=>$time,'hash'=>$hash);
  return json_encode($json);
}

function check_time($time, $hash) {
  $five_seconds = 5;
  $one_hour = 60*60;
  $newhash = encrypt($time);
  $now = microtime(true);
  $duration = $now-$time;
  $validTime = ($duration < $five_seconds || $duration > $one_hour);
  $validHash = ($hash == $newhash);
  return ($validHash && $validTime);
}

function encrypt($input) {
  $salted = wp_salt().$input;
  return base64_encode(hash('sha256', $salted));
}
