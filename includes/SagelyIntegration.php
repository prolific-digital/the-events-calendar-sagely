<?php

namespace ProlificDigital\SagelyIntegration\Includes;

defined( 'ABSPATH' ) || exit;

class SagelyIntegration {

  public function __construct() {
    add_action('admin_menu', [$this, 'add_settings_page']);
    add_filter('cron_schedules', [self::class, 'custom_cron_schedule']);
    add_action('sagely_event_sync', [$this, 'run_daily_sync']);

    // Self-heal: sites activated before 1.1.0 never got the cron event,
    // because the activation hook was registered from this file on
    // plugins_loaded and never fired. Schedule it if it is missing.
    add_action('init', [self::class, 'schedule_event_sync']);

    // Reorder the submenu after it's been added
    add_action('admin_menu', [$this, 'reorder_submenu'], 999);
  }

  // Check if The Events Calendar is active
  private function check_events_calendar_active() {
    if (!function_exists('tribe_events')) {
      add_action('admin_notices', [$this, 'admin_notice']);
      return false;
    }
    return true;
  }

  // Admin notice if The Events Calendar is not active
  public function admin_notice() {
    echo '<div class="notice notice-error"><p>' . esc_html__('Sagely Integration for The Events Calendar requires The Events Calendar Plugin to be installed and active.', 'tec-sagely') . '</p></div>';
  }

  /**
   * Activation callback, registered from the main plugin file.
   *
   * The cron_schedules filter is not registered yet during the activation
   * request (the constructor runs on plugins_loaded), so add it first or
   * wp_schedule_event() rejects the custom recurrence.
   */
  public static function activate() {
    if (false === has_filter('cron_schedules', [self::class, 'custom_cron_schedule'])) {
      add_filter('cron_schedules', [self::class, 'custom_cron_schedule']);
    }
    self::schedule_event_sync();
  }

  /**
   * Deactivation callback, registered from the main plugin file.
   */
  public static function deactivate() {
    self::clear_scheduled_event_sync();
  }

  // Schedule the cron event if the plugin is configured and it is missing.
  // Runs on activation and on every init (self-heal).
  public static function schedule_event_sync() {
    $api_key = get_option('sagely_api_key');
    $sync_frequency = max(1, (int) get_option('sagely_sync_frequency', 24)); // Default to 24 hours if not set

    if ($api_key && !wp_next_scheduled('sagely_event_sync')) {
      // Calculate the next interval based on the sync frequency
      $next_run_time = time() + $sync_frequency * HOUR_IN_SECONDS;
      wp_schedule_event($next_run_time, 'custom_sagely_interval', 'sagely_event_sync');
    }
  }

  // Clear every scheduled instance of the sync event.
  public static function clear_scheduled_event_sync() {
    wp_clear_scheduled_hook('sagely_event_sync');
  }


  // Hook the sync method to the daily cron event
  public function run_daily_sync() {
    if ($this->check_events_calendar_active()) {
      $this->sync_events();
    }
  }

  // Add a custom interval based on the sync frequency in hours
  public static function custom_cron_schedule($schedules) {
    $sync_frequency = max(1, (int) get_option('sagely_sync_frequency', 24)); // Default to 24 hours if not set
    $interval = $sync_frequency * 3600; // Convert hours to seconds

    $schedules['custom_sagely_interval'] = [
      'interval' => $interval,
      'display'  => sprintf(__('Every %d Hours', 'tec-sagely'), $sync_frequency),
    ];

    return $schedules;
  }


  // Add the settings page under The Events Calendar menu
  public function add_settings_page() {
    if ($this->check_events_calendar_active()) {
      $hook = add_submenu_page(
        'edit.php?post_type=tribe_events',
        __('Sagely API Settings', 'tec-sagely'),
        __('Sagely API', 'tec-sagely'),
        'manage_options',
        'sagely-api-settings',
        [$this, 'render_settings_page']
      );
      if ($hook) {
        add_action('load-' . $hook, [$this, 'handle_settings_submission']);
      }
    }
  }

  public function reorder_submenu() {
    global $submenu;

    $parent_slug = 'edit.php?post_type=tribe_events';
    if (isset($submenu[$parent_slug])) {
      $sagely_menu = [];
      $other_menus = [];

      // Separate Sagely API Settings from other menu items
      foreach ($submenu[$parent_slug] as $menu) {
        if ($menu[2] === 'sagely-api-settings') {
          $sagely_menu = $menu;
        } else {
          $other_menus[] = $menu;
        }
      }

      // Rebuild the submenu array with Sagely API Settings at the end
      $submenu[$parent_slug] = array_merge($other_menus, [$sagely_menu]);
    }
  }


  /**
   * Message shown above the settings form after a submission.
   *
   * @var string
   */
  private $message = '';

  /**
   * Notice class for $message ('updated' or 'error').
   *
   * @var string
   */
  private $message_type = 'updated';

  /**
   * Handle the settings and Sync Now forms.
   *
   * Runs on the page's load-{hook} action, before any output, so a failed
   * nonce or capability check can stop the request cleanly.
   */
  public function handle_settings_submission() {
    if (!isset($_POST['sagely_api_key']) && !isset($_POST['sagely_sync_frequency']) && !isset($_POST['sync_now'])) {
      return;
    }

    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have permission to change these settings.', 'tec-sagely'), 403);
    }

    // Handle Sync Now button press separately
    if (isset($_POST['sync_now'])) {
      check_admin_referer('tec_sagely_sync_now');
      $this->sync_events(); // Trigger sync only if 'Sync Now' is clicked
      $this->message = __('Events synchronized successfully.', 'tec-sagely');
      return;
    }

    check_admin_referer('tec_sagely_save_settings');

    // Handle form submissions for API key and Sync Frequency
    if (isset($_POST['sagely_api_key'])) {
      $api_key = sanitize_text_field(wp_unslash($_POST['sagely_api_key']));
      update_option('sagely_api_key', $api_key);

      if ($this->test_api_key($api_key)) {
        $this->message = __('API Key is valid.', 'tec-sagely');
      } else {
        $this->message = __('API Key is invalid. Please check and try again.', 'tec-sagely');
        $this->message_type = 'error';
      }
    }

    if (isset($_POST['sagely_sync_frequency'])) {
      $sync_frequency = max(1, absint(wp_unslash($_POST['sagely_sync_frequency'])));
      update_option('sagely_sync_frequency', $sync_frequency);

      // Only reschedule the cron event without triggering a sync
      self::clear_scheduled_event_sync();
      self::schedule_event_sync();
      $this->message = __('Sync frequency updated successfully.', 'tec-sagely');
    }
  }

  // Render the settings page form
  public function render_settings_page() {
    $message = $this->message;
    $message_type = $this->message_type;
    $sync_frequency = get_option('sagely_sync_frequency', 24);
    $api_key = get_option('sagely_api_key', '');

?>
    <div class="wrap">
      <h1><?php esc_html_e('Sagely API Settings', 'tec-sagely'); ?></h1>
      <?php if ($message): ?>
        <div class="<?php echo esc_attr($message_type); ?>">
          <p><?php echo esc_html($message); ?></p>
        </div>
      <?php endif; ?>

      <p><?php echo wp_kses(__('The Sagely integration is designed to pull events from the Sagely API directly into The Events Calendar. Each API key provided by Sagely is specific to a single community. You will need to retrieve your API key from Sagely or contact Sagely support if you do not have it. For more details, please refer to <a href="https://prolificdigital.notion.site/The-Events-Calendar-Sagely-Addon-ce2eb70042734bcc9edb0dc8c4c6ec54?pvs=4" target="_blank">our support documentation</a>.', 'tec-sagely'), ['a' => ['href' => [], 'target' => [], 'rel' => []]]); ?></p>

      <form method="POST" action="">
        <?php wp_nonce_field('tec_sagely_save_settings'); ?>
        <table class="form-table">
          <tr valign="top">
            <th scope="row"><?php esc_html_e('Sagely API Key', 'tec-sagely'); ?></th>
            <td><input type="text" name="sagely_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php esc_html_e('Sync Frequency (in hours)', 'tec-sagely'); ?></th>
            <td><input type="number" name="sagely_sync_frequency" value="<?php echo esc_attr($sync_frequency); ?>" class="regular-text" min="1" /></td>
          </tr>

        </table>
        <?php submit_button(); ?>
      </form>

      <h2><?php esc_html_e('Manual Synchronization', 'tec-sagely'); ?></h2>
      <p><?php esc_html_e('Click the "Sync Now" button below to manually synchronize events with the Sagely API. This will immediately pull the latest events and update your calendar.', 'tec-sagely'); ?></p>

      <form method="POST" action="">
        <?php wp_nonce_field('tec_sagely_sync_now'); ?>
        <input type="hidden" name="sync_now" value="1" />
        <?php submit_button(__('Sync Now', 'tec-sagely'), 'secondary'); ?>
      </form>
    </div>
<?php
  }


  private function test_api_key($api_key) {
    $test_url = "https://api.sagelyweb.com/rest/v1/events/?limit=1";

    $args = [
      'headers' => [
        'Authorization' => $api_key,
      ],
    ];

    $response = wp_remote_get($test_url, $args);

    if (is_wp_error($response)) {
      return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['items']) && is_array($data['items'])) {
      return true;
    }

    return false;
  }

  public function sync_events() {
    if (!function_exists('tribe_events')) {
      return;
    }

    $api_key = get_option('sagely_api_key');
    $sagely_events = $this->fetch_sagely_events($api_key);

    if (!isset($sagely_events['items']) || !is_array($sagely_events['items'])) {
      error_log('Sagely events: Invalid structure or empty response');
      return;
    }

    foreach ($sagely_events['items'] as $event) {
      $sagely_event_id = $this->get_event_id_from_href($event['_href']);
      $existing_event_id = $this->get_existing_event_id_by_meta($sagely_event_id);

      // bs_log_error($sagely_event_id);
      // bs_log_error($existing_event);

      // return;

      // Sagely times carry their own UTC offset. gmdate() formats them in UTC,
      // which is exactly what date() produced (WordPress forces PHP's default
      // timezone to UTC) but no longer depends on that runtime setting.
      $name = isset($event['name']) ? $event['name'] : __('Unnamed Event', 'tec-sagely');
      $startTime = isset($event['startTime']) ? $event['startTime'] : null;
      $endTime = isset($event['endTime']) ? $event['endTime'] : gmdate('Y-m-d H:i:s', strtotime('+1 hours', strtotime($startTime)));
      $description = isset($event['description']) ? $event['description'] : '';
      $timezone = $this->extract_timezone_from_date($startTime);

      // Ensure category exists before assigning
      $category_name = isset($event['calendar']['name']) ? $event['calendar']['name'] : 'Uncategorized';
      $category_id = $this->ensure_category_exists($category_name);

      // Extract tags
      $tags = [];
      if (isset($event['tags']) && is_array($event['tags'])) {
        foreach ($event['tags'] as $tag) {
          $tags[] = $tag['name'];
        }
      }

      // Extract location and get or create the venue
      $location_name = isset($event['location']['name']) ? $event['location']['name'] : '';
      $venue_id = $location_name ? $this->get_or_create_venue($location_name) : null;

      if (!$startTime) {
        continue;
      }

      $args = [
        'status'            => 'publish',
        'title'             => $name,
        'start_date'        => gmdate('Y-m-d H:i:s', strtotime($startTime)),
        'end_date'          => gmdate('Y-m-d H:i:s', strtotime($endTime)),
        'description'       => $description,
        'venue'             => $venue_id,
        'category'          => [$category_id],
        'tag'               => $tags,
      ];

      if ($existing_event_id) {
        // Update the existing event
        tribe_events()->where('id', $existing_event_id)->set('title', $name)->save();
        tribe_events()->where('id', $existing_event_id)->set('start_date', gmdate('Y-m-d H:i:s', strtotime($startTime)))->save();
        tribe_events()->where('id', $existing_event_id)->set('end_date', gmdate('Y-m-d H:i:s', strtotime($endTime)))->save();
        tribe_events()->where('id', $existing_event_id)->set('description', $description)->save();
        tribe_events()->where('id', $existing_event_id)->set('venue', $venue_id)->save();
        tribe_events()->where('id', $existing_event_id)->set('category', [$category_id])->save();
        tribe_events()->where('id', $existing_event_id)->set('tag', $tags)->save();
      } else {
        // Create a new event
        $new_event_id = tribe_events()->set_args($args)->create();
        if ($new_event_id->ID) {
          update_post_meta($new_event_id->ID, '_sagely_event_id', $sagely_event_id);
        }
      }
    }
  }

  private function get_or_create_venue($location_name) {
    // Check if a venue with this name already exists
    $args = [
      'post_type'   => 'tribe_venue',
      'title'       => $location_name,
      'post_status' => 'publish',
      'numberposts' => 1,
      'fields'      => 'ids',
    ];

    $existing_venue = get_posts($args);

    if (!empty($existing_venue)) {
      return $existing_venue[0]; // Return the ID of the existing venue
    } else {
      // Create a new venue
      $venue_args = [
        'post_title'  => $location_name,
        'post_status' => 'publish',
        'post_type'   => 'tribe_venue',
      ];

      $new_venue_id = wp_insert_post($venue_args);
      return $new_venue_id;
    }
  }


  private function extract_timezone_from_date($datetime) {
    $datetime_obj = new \DateTime($datetime);
    $timezone = $datetime_obj->getTimezone();
    return $timezone->getName();
  }

  private function ensure_category_exists($category_name) {
    $term = term_exists($category_name, 'tribe_events_cat');
    if ($term) {
      return $term['term_id'];
    } else {
      $term = wp_insert_term($category_name, 'tribe_events_cat');
      if (!is_wp_error($term)) {
        return $term['term_id'];
      }
    }
    return null; // Fallback in case of error
  }


  // Extract the event ID from the _href URL
  private function get_event_id_from_href($href) {
    $parts = explode('/', rtrim($href, '/'));
    return end($parts); // The last part of the URL is the ID
  }

  // Check if an event with the given ID already exists
  private function get_existing_event_id_by_meta($event_id) {
    $args = [
      'post_type'  => 'tribe_events',
      'meta_key'   => '_sagely_event_id',
      'meta_value' => $event_id,
      'fields'     => 'ids',
      'posts_per_page' => 1,
    ];

    $posts = get_posts($args);

    if (!empty($posts)) {
      return $posts[0]; // Return the first (and only) post ID
    }

    return false;
  }



  private function fetch_sagely_events($api_key) {
    $start_date = gmdate('Y-m-d\TH:i:s\Z');
    $end_date = gmdate('Y-m-d\TH:i:s\Z', strtotime('+60 days'));

    $api_url = "https://api.sagelyweb.com/rest/v1/events/?between(startTime,date:{$start_date},date:{$end_date})";

    $args = [
      'headers' => [
        'Authorization' => $api_key,
      ],
    ];

    $response = wp_remote_get($api_url, $args);

    if (is_wp_error($response)) {
      return [];
    }

    $body = wp_remote_retrieve_body($response);
    $events = json_decode($body, true);

    return $events;
  }
}
