# Newsletters for WordPress®

A lightweight and powerful newsletter subscription plugin for WordPress®. Manage newsletter signups, store subscriber data in a custom database table, and send emails directly from the WordPress® admin.

* * *

## Features

✅ Create and manage newsletter signups via shortcode  
✅ Store subscribers in a custom, optimized database table  
✅ Admin dashboard with sortable & searchable subscribers list  
✅ AJAX-powered subscription form  
✅ Manual subscriber management (add/remove)  
✅ Simple bulk email sender  
✅ Developer-friendly structure  
✅ WordPress-native design, fully translatable

## Installation

1. Upload the plugin files to the `/wp-content/plugins/newsletters-for-wordpress/` directory, or install directly through the WordPress admin.
2. Activate the plugin via the **Plugins** menu.
3. A new **Newsletters** menu will appear in the WordPress® admin.
* * *

## Usage

### Embed the Signup Form

Use the following shortcode anywhere on your site:    

    ```php
    [newsletter_form]
    ```

This will render a simple AJAX-enabled form:

- Name (Required)
- Email (Required)
- Subscribe Button

### View & Manage Subscribers

Go to **Newsletters → Signups** in the WordPress admin to:
- View all signups
- Search subscribers
- Delete subscribers
- Manually add subscribers via modal

### Send Emails

1. Navigate to **Newsletters → Send Emails**.
2. Compose your email subject and body.
3. Use `{name}` in the body to automatically personalize emails.
4. Click **Send Email** to deliver the email to all subscribers.
* * *

## Admin Pages

| Page | Description | 
| ---- | ----  |
| Newsletters → Signups | View, add, or delete subscribers | 
| Newsletters → Send Emails | Compose and send emails to all subscribers |

## FAQ

### Can I export subscribers?

Currently, there is no export feature, but you can access the custom table directly or extend the plugin.

### Does it work with SMTP plugins?

Yes! This plugin uses `wp_mail()`, which automatically integrates with any properly configured SMTP plugin.

### Can I personalize emails?

Yes! Use `{name}` inside the email body to automatically replace it with the subscriber's name.

* * *

## Contributing

Contributions, pull requests, and feedback are welcome via [GitHub Issues](https://github.com/robertdevore/newsletters-for-wordpress/issues).

* * *

## License

Licensed under the [GPL v3](https://www.gnu.org/licenses/gpl-3.0.txt)

* * *