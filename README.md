# Back In Stock Notifications for WooCommerce®

Automatically notify customers when products they're interested in are back in stock, and track demand for your most popular items. The plugin integrates seamlessly with WooCommerce® to capture customer interest and manage notifications efficiently.

* * *

## Features

- **Automated Notifications**: Sends customizable emails to customers when a product they signed up for is back in stock.
- **Demand Tracking**: Track and display demand trends through waitlist statistics and analytics.
- **WooCommerce® Integration**: Built for WooCommerce® to manage back-in-stock notifications.
- **Customizable Emails**: Email templates for customer notifications can be customized to match your brand.
- **Dashboard Insights**: Gain insights into product demand, customer interest, and track notification history within your WooCommerce® admin.
- **Automatic Updates**: Receive updates through the WordPress® dashboard using [PluginUpdateChecker](https://github.com/YahnisElsts/plugin-update-checker).
* * *

## Installation

### Option 1: Install via WordPress® Dashboard (Recommended)

1. Download the latest `.zip` file from the [Releases section on GitHub](https://github.com/robertdevore/back-in-stock-notifications/releases).
2. In your WordPress® Dashboard, navigate to **Plugins > Add New**.
3. Click the **Upload Plugin** button at the top of the page.
4. Select the `.zip` file you downloaded and click **Install Now**.
5. Once installation is complete, click **Activate Plugin**.

* * *

### Option 2: Install via SFTP

1. Download the latest `.zip` file from the [Releases section on GitHub](https://github.com/robertdevore/back-in-stock-notifications/releases).
2. Unzip the file to create a folder named `back-in-stock-notifications`.
3. Use an SFTP client (e.g., FileZilla, Cyberduck) to connect to your WordPress® server.
4. Upload the `back-in-stock-notifications` folder to the `/wp-content/plugins/` directory.
5. Go to your WordPress® Dashboard, navigate to **Plugins**, and click **Activate** under _Back In Stock Notifications for WooCommerce®.

### Requirements

- WooCommerce® (must be active for the plugin to work)
- WordPress® 5.0+
- PHP 7.2+ (compatible with PHP 8.0+)
* * *

## Configuration and Setup

### Step 1: Enable WooCommerce® Compatibility

Ensure WooCommerce® is installed and activated before using this plugin. The plugin will automatically deactivate if WooCommerce® is not active.

### Step 2: Configure the Plugin

Upon activation, the plugin creates the following custom database tables:

- **Waitlist**: Tracks product waitlists by customer email.
- **Waitlist History**: Records historical waitlist data.
- **Notifications**: Stores sent notifications for back-in-stock alerts.

These tables are created and maintained automatically.

### Step 3: Email Customization

The plugin includes customizable email templates located in:

- `templates/emails/back-in-stock-notification.php`: HTML version of the back-in-stock email.
- `templates/emails/plain/back-in-stock-notification.php`: Plain text version of the email.

You can further customize these templates by copying them to your theme folder under `woocommerce/emails/` and editing them to match your brand style.

* * *

## Usage

### 1. Adding Customers to the Waitlist

- Customers can join a waitlist on a product's single page.
- The waitlist form captures the customer's email, saving it to the waitlist database table.
- The plugin enqueues JavaScript only on out-of-stock single product pages, optimizing performance.

### 2. Notifying Customers

When stock levels are updated (via WooCommerce®'s product update), the plugin will:

- Check if the product's stock is now above zero.
- Send a back-in-stock email notification to all users on that product's waitlist.
- Log notifications and remove customers from the waitlist after notifying them.

### 3. Managing Waitlists and Analytics

Admins can access the **Back In Stock** submenu under **WooCommerce®**. The dashboard provides insights, including:
- **Most Wanted Products**: Products with the highest waitlist counts.
- **Most Overdue Products**: Products that have been out of stock the longest.
- **Most Signed-Up Products**: Top products based on waitlist sign-ups over time.
- **Sign-Ups and Notifications**: Daily and monthly sign-up and notification statistics are tracked, with values stored for easy access via a helper class.

### 4. CSV Exporting

Two CSV export options are available on the **Back In Stock** dashboard:
- **Export Emails**: Exports all unique emails from the waitlist history table.
- **Export Data**: Exports demand insights such as the most wanted, most overdue, and most signed-up products.

### Helper Functionality

The plugin introduces a `BISN_Data_Helper` class to simplify database queries for various waitlist and notification statistics, such as:

- `get_most_wanted_products()`: Retrieves the top 10 most wanted products based on waitlist count.
- `get_most_overdue_products()`: Retrieves the top 10 products out of stock the longest.
- `get_signups_today()`: Counts the sign-ups from today.
- `get_sent_today()`: Counts notifications sent today.

The helper class enables efficient data retrieval for a streamlined and modular plugin structure.

### Customizable Back In Stock Email

The **BISN_Back_In_Stock_Email** class controls the back-in-stock email notifications, which are sent when products are restocked. Emails are triggered by the `bisn_send_back_in_stock_email` action, providing seamless integration into WooCommerce's email management system.