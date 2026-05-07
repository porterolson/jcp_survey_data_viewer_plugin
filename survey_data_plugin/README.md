# JCP Survey Data Viewer

## Installation

1. Copy the `survey_data_plugin` folder into your WordPress `wp-content/plugins/` directory.
2. Activate **JCP Survey Data Viewer** from **Plugins** in wp-admin.
3. Open **Users -> Survey Data** to view rows from the `{$wpdb->prefix}job_survey` table.

## What It Does

- Adds a new **Users -> Survey Data** admin screen.
- Reads the live `job_survey` table using the current WordPress table prefix.
- Shows every discovered column from that table in the admin table.
- Supports full-table search, column-specific filtering, sorting, pagination, and JSON export of the current view.

## Notes

- This plugin is read-only and does not modify survey rows.
- If the `job_survey` table does not exist for the current site prefix, the admin page will show a missing-table message.
