# WP Business Reviews Server

This server plugin provides authentication for certain platforms in the [WP Business Reviews plugin](https://github.com/wordimpress/wp-business-reviews).

At this time, Facebook is the only platform that requires server-side functionality through this plugin in order to authenticate.

## Facebook OAuth2 Setup

In order for this plugin to authenticate users via Facebook, a valid app ID and app secret must be provided. For security purposes, these credentials have been omitted from version control and must be defined on the site in which this plugin is installed.

1. Visit https://developers.facebook.com/apps/ and select an app to access the app ID and app secret.
2. Define the following constants in `wp-config.php`:

```
define( 'WPBRS_FACEBOOK_APP_ID', '{INSERT_APP_ID_HERE}' );
define( 'WPBRS_FACEBOOK_APP_SECRET', '{INSERT_APP_SECRET_HERE}' );
```

3. Activate the plugin.

The plugin is now ready to authenticate users who wish to connect to Facebook through the WP Business Reviews plugin.
