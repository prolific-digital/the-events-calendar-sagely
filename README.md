# The Events Calendar - Sagely Integration

## Overview

The Events Calendar - Sagely Integration plugin seamlessly connects your Sagely API with The Events Calendar plugin on your WordPress site. This integration allows you to automatically pull events from the Sagely platform directly into your calendar, ensuring that your community's event information is always up-to-date.

> **Important:** The Events Calendar plugin must be installed and activated prior to activating The Events Calendar - Sagely Integration plugin. Without it, this plugin will not function correctly.

For a detailed guide on setup and troubleshooting, please refer to [our support documentation](https://prolificdigital.notion.site/The-Events-Calendar-Sagely-Addon-ce2eb70042734bcc9edb0dc8c4c6ec54).

## Key Features

- **Automatic Event Syncing:** Automatically synchronize events from Sagely into The Events Calendar on your WordPress site.
- **Manual Sync Option:** Allows manual synchronization of events whenever needed.
- **Custom Sync Frequency:** Configure how often the plugin should check for new events from the Sagely API.
- **Location (Venue) Management:** Automatically create or assign venues based on event location data.
- **Tag and Category Management:** Automatically create and assign tags and categories based on event data.

## Getting Started

### Installation

1. **Download the Plugin:** Ensure you have the latest version of [The Events Calendar - Sagely Integration Plugin](https://github.com/prolific-digital/the-events-calendar-sagely/releases).
2. **Upload the Plugin:**
   - Go to your WordPress Dashboard.
   - Navigate to **Plugins > Add New**.
   - Click on **Upload Plugin**.
   - Choose the downloaded plugin file and click **Install Now**.
3. **Activate the Plugin:**
   - After installation, click on **Activate** to enable the plugin on your site.

### Setting Up the Plugin

Once activated, follow these steps to configure the plugin and start syncing events:

1. **Access the Settings:**

   - Navigate to **Events > Sagely API** in your WordPress admin menu.

2. **Enter Your Sagely API Key:**

   - **What It Is:** The API key connects your WordPress site to Sagely, allowing events to be pulled in.
   - **How to Get It:** Retrieve your API key from Sagely or contact their support team.
   - **How to Enter It:** Input the key into the **Sagely API Key** field and click **Save Changes**.

3. **Set Sync Frequency:**

   - **Purpose:** This determines how often the plugin checks for new events.
   - **How to Set It:** Input the number of hours for the sync interval (e.g., `24` for daily syncing) and click **Save Changes**.

4. **Manual Synchronization:**
   - **What It Does:** Allows you to manually sync events at any time.
   - **How to Use It:** Click the **Sync Now** button on the settings page to pull the latest events.

## How the Plugin Handles Data

### Event Duration

- **Default Event Duration:** Since the Sagely API does not provide an end time for events, the plugin defaults to a 1-hour duration for each event. You may need to adjust the end times manually if your events typically last longer or shorter.

### Location (Venue) Management

- **Automatic Venue Assignment:** The plugin checks for existing venues in The Events Calendar based on the location name provided by Sagely. If a match is found, it assigns the existing venue; if not, it creates a new one.
- **Venue Management:** You can manage venues created or assigned by the plugin under **Events > Venues** in your WordPress dashboard.

### Category and Tag Management

- **Automatic Category Creation:** The plugin maps event categories from Sagely to categories in The Events Calendar, creating new categories if necessary.
- **Tag Management:** Tags from Sagely are directly mapped to The Events Calendar, maintaining consistency and aiding in event filtering.

## Known Issues and Limitations

### No End Date Provided by Sagely API

- **Issue:** The Sagely API only provides a start date, so the plugin defaults to a 1-hour event duration.
- **Workaround:** Adjust event end times manually if necessary.

### Syncing Limited to 60 Days

- **Issue:** The plugin syncs only the next 60 days' worth of events to maintain optimal performance.
- **Workaround:** For events beyond this period, adjust the plugin settings or perform a manual sync closer to the event dates.

## Troubleshooting

### API Key Issues

- **Invalid API Key:** Double-check the key for accuracy, ensuring no extra spaces are included.
- **No Events Syncing:** Verify the API key and sync frequency settings, and try using the "Sync Now" button.

### Event Sync Issues

- **Events Not Showing Up:** Ensure the event data in Sagely is correct, and recheck your API key.

### Sync Frequency Issues

- **Sync Not Happening:** Confirm the sync frequency setting is correct. Lower the sync interval if more frequent syncing is required.

## Frequently Asked Questions (FAQs)

- **Where do I find my Sagely API key?** Retrieve it from the Sagely platform or contact their support.
- **How often should I set the sync frequency?** A daily sync (every 24 hours) is generally sufficient.
- **Can I manually trigger a sync?** Yes, use the "Sync Now" button on the settings page.
- **What happens if I update an event in Sagely?** The plugin will update the corresponding event in The Events Calendar.
- **Why aren't venues appearing for some events?** Venue information may be missing in the Sagely data; you'll need to manually assign it.

## Support and Documentation

For more detailed information, including advanced configuration options and troubleshooting tips, please visit [our support documentation](https://prolificdigital.notion.site/The-Events-Calendar-Sagely-Addon-ce2eb70042734bcc9edb0dc8c4c6ec54).

For additional support, contact us at [support@prolificdigital.com](mailto:support@prolificdigital.com).

---

Thank you for using The Events Calendar - Sagely Integration plugin. We hope it enhances your event management process. For suggestions or feedback, please reach out to us!
