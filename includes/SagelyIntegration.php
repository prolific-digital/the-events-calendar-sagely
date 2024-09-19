<?php

namespace ProlificDigital\SagelyIntegration\Includes;

class SagelyIntegration {

  public function __construct() {
    add_action('admin_menu', [$this, 'add_settings_page']);
    add_filter('cron_schedules', [$this, 'custom_cron_schedule']);
    register_activation_hook(__FILE__, [$this, 'schedule_event_sync']);
    register_deactivation_hook(__FILE__, [$this, 'clear_scheduled_event_sync']);
    add_action('sagely_event_sync', [$this, 'run_daily_sync']);

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
    echo '<div class="notice notice-error"><p>' . esc_html__('Sagely Integration for The Events Calendar requires The Events Calendar Plugin to be installed and active.', 'the-events-calendar-sagely') . '</p></div>';
  }

  // Schedule the cron event on plugin activation
  public function schedule_event_sync() {
    $api_key = get_option('sagely_api_key');
    $sync_frequency = get_option('sagely_sync_frequency', 24); // Default to 24 hours if not set

    if ($api_key && !wp_next_scheduled('sagely_event_sync')) {
      // Calculate the next interval based on the sync frequency
      $next_run_time = strtotime('+' . $sync_frequency . ' hours', time());
      wp_schedule_event($next_run_time, 'custom_sagely_interval', 'sagely_event_sync');
    }
  }



  // Clear the scheduled cron event on plugin deactivation
  public function clear_scheduled_event_sync() {
    $timestamp = wp_next_scheduled('sagely_event_sync');
    if ($timestamp) {
      wp_unschedule_event($timestamp, 'sagely_event_sync');
    }
  }


  // Hook the sync method to the daily cron event
  public function run_daily_sync() {
    if ($this->check_events_calendar_active()) {
      $this->sync_events();
    }
  }

  // Add a custom interval based on the sync frequency in hours
  public function custom_cron_schedule($schedules) {
    $sync_frequency = get_option('sagely_sync_frequency', 24); // Default to 24 hours if not set
    $interval = $sync_frequency * 3600; // Convert hours to seconds

    $schedules['custom_sagely_interval'] = [
      'interval' => $interval,
      'display'  => sprintf(__('Every %d Hours', 'the-events-calendar-sagely'), $sync_frequency),
    ];

    return $schedules;
  }


  // Add the settings page under The Events Calendar menu
  public function add_settings_page() {
    if ($this->check_events_calendar_active()) {
      add_submenu_page(
        'edit.php?post_type=tribe_events',
        __('Sagely API Settings', 'the-events-calendar-sagely'),
        __('Sagely API', 'the-events-calendar-sagely'),
        'manage_options',
        'sagely-api-settings',
        [$this, 'render_settings_page']
      );
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


  // Render the settings page form
  public function render_settings_page() {
    $message = '';
    $message_type = 'updated';

    // Handle form submissions for API key and Sync Frequency
    if (isset($_POST['sagely_api_key'])) {
      $api_key = sanitize_text_field($_POST['sagely_api_key']);
      update_option('sagely_api_key', $api_key);

      if ($this->test_api_key($api_key)) {
        $message = __('API Key is valid.', 'the-events-calendar-sagely');
      } else {
        $message = __('API Key is invalid. Please check and try again.', 'the-events-calendar-sagely');
        $message_type = 'error';
      }
    }

    if (isset($_POST['sagely_sync_frequency'])) {
      $sync_frequency = intval($_POST['sagely_sync_frequency']);
      update_option('sagely_sync_frequency', $sync_frequency);

      // Only reschedule the cron event without triggering a sync
      $this->clear_scheduled_event_sync();
      $this->schedule_event_sync();
      $message = __('Sync frequency updated successfully.', 'the-events-calendar-sagely');
    }

    $sync_frequency = get_option('sagely_sync_frequency', 24);

    // Handle Sync Now button press separately
    if (isset($_POST['sync_now'])) {
      $site_url = get_site_url();
      $events_url = $site_url . '/wp-admin/edit.php?post_type=tribe_events';

      // $this->sync_events(); // Trigger sync only if 'Sync Now' is clicked
      wp_schedule_single_event(time(), 'sagely_event_sync'); // Schedule to run immediately
      $message = __('Event sync in progress. <a href="' . esc_url($events_url) . '">View Events</a>', 'the-events-calendar-sagely');
    }

    $api_key = get_option('sagely_api_key', '');

?>
    <div class="wrap">
      <h1><?php esc_html_e('Sagely API Settings', 'the-events-calendar-sagely'); ?></h1>
      <?php if ($message): ?>
        <div class="<?php echo esc_attr($message_type); ?>">
          <p><?php echo $message; ?></p>
        </div>
      <?php endif; ?>

      <p><?php echo __('The Sagely integration is designed to pull events from the Sagely API directly into The Events Calendar. Each API key provided by Sagely is specific to a single community. You will need to retrieve your API key from Sagely or contact Sagely support if you do not have it. For more details, please refer to <a href="https://prolificdigital.notion.site/The-Events-Calendar-Sagely-Addon-ce2eb70042734bcc9edb0dc8c4c6ec54?pvs=4" target="_blank">our support documentation</a>.', 'the-events-calendar-sagely'); ?></p>

      <form method="POST" action="">
        <table class="form-table">
          <tr valign="top">
            <th scope="row"><?php esc_html_e('Sagely API Key', 'the-events-calendar-sagely'); ?></th>
            <td><input type="text" name="sagely_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php esc_html_e('Sync Frequency (in hours)', 'the-events-calendar-sagely'); ?></th>
            <td><input type="number" name="sagely_sync_frequency" value="<?php echo esc_attr($sync_frequency); ?>" class="regular-text" min="1" /></td>
          </tr>

        </table>
        <?php submit_button(); ?>
      </form>

      <h2><?php esc_html_e('Manual Synchronization', 'the-events-calendar-sagely'); ?></h2>
      <p><?php esc_html_e('Click the "Sync Now" button below to manually synchronize events with the Sagely API. This will immediately pull the latest events and update your calendar.', 'the-events-calendar-sagely'); ?></p>

      <form method="POST" action="">
        <input type="hidden" name="sync_now" value="1" />
        <?php submit_button(__('Sync Now', 'the-events-calendar-sagely'), 'secondary'); ?>
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

    // Fetch the WordPress timezone
    $timezone = wp_timezone();

    foreach ($sagely_events['items'] as $event) {
      $sagely_event_id = $this->get_event_id_from_href($event['_href']);
      $existing_event_id = $this->get_existing_event_id_by_meta($sagely_event_id);

      $name = isset($event['name']) ? $event['name'] : __('Unnamed Event', 'the-events-calendar-sagely');
      $startTime = isset($event['startTime']) ? $event['startTime'] : null;
      $endTime = isset($event['endTime']) ? $event['endTime'] : date('Y-m-d H:i:s', strtotime('+1 hours', strtotime($startTime)));
      $description = isset($event['description']) ? $event['description'] : '';

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

      // Apply WordPress timezone to start and end times
      $start_time_obj = new \DateTime($startTime, $timezone);
      $end_time_obj = new \DateTime($endTime, $timezone);

      $args = [
        'status'            => 'publish',
        'title'             => $name,
        'start_date'        => $start_time_obj->format('Y-m-d H:i:s'),
        'end_date'          => $end_time_obj->format('Y-m-d H:i:s'),
        'description'       => $description,
        'venue'             => $venue_id,
        'category'          => [$category_id],
        'tag'               => $tags,
      ];

      if ($existing_event_id) {
        // Update the existing event
        tribe_events()->where('id', $existing_event_id)->set('title', $name)->save();
        tribe_events()->where('id', $existing_event_id)->set('start_date', date('Y-m-d H:i:s', strtotime($startTime)))->save();
        tribe_events()->where('id', $existing_event_id)->set('end_date', date('Y-m-d H:i:s', strtotime($endTime)))->save();
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
