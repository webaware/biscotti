# Biscotti

## Description

Biscotti is a plugin that modifies the expiration of the logged in user cookie in WordPress. Choose from the default WordPress expiration (14 days), three months (90 days), six months (180 days), or one year (365 days). Because some folks hate to have to keep entering their passwords.

## Installation

To install this plugin, drop `biscotti.php` into your site's `wp-content/plugins` directory and activate it.

## Usage

Once the plugin has been activated, a new option will be available in the WordPress dashboard under "User -> Profile" called "Login Cookie Expiration". There, you can select the cookie expiration from the following options on a per-account basis:

- **Default (14 days)** - WordPress's standard expiration
- **3 months (90 days)** - Extended expiration for frequent users
- **6 months (180 days)** - Longer expiration for regular users
- **1 year (365 days)** - Maximum expiration for power users

After updating this setting, you *will* need to log out and back into WordPress for your new cookie expiration value to take effect.

Enjoy your long cookie!

## WP-CLI Commands

As of version 2.1.0, Biscotti includes WP-CLI commands for managing a user's logged in session cookie expiration.

### `wp biscotti get <user_id>`

Retrieves the current cookie expiration setting for a user.

#### Parameters

- `<user_id>` — The ID of the user.

#### Examples

Get the logged in session cookie expiration for user ID 123:

```bash
wp biscotti get 123
```

Example output:
```
Cookie expiration for johndoe (ID: 123): 1 year (365 days)
```

### `wp biscotti set <user_id> <expiration>`

Sets the logged in session cookie expiration for a user.

#### Parameters

- `<user_id>` — The ID of the user.
- `<expiration>` — The expiration duration. Must be one of:
  - `default` — WordPress default (14 days)
  - `3 months` — 90 days
  - `6 months` — 180 days
  - `1 year` — 365 days

#### Examples

Set a user's cookie expiration to 1 year:

```bash
wp biscotti set 123 '1 year'
```

Reset a user to the default expiration:

```bash
wp biscotti set 123 default
```

## Changelog

### 3.0.0

**Major Security & Feature Update**

Security Improvements:
- **CRITICAL:** Added CSRF protection with nonce verification on profile form submissions
- Added input validation with whitelist checking for all user inputs
- Added user existence validation in WP-CLI commands
- Changed all loose comparisons (`==`) to strict comparisons (`===`)

New Features:
- Added "Default (14 days)" option to allow users to revert to WordPress standard expiration
- Added full internationalization (i18n) support with text domain and translation functions
- Enhanced form descriptions with clearer explanations of each expiration option
- Improved WP-CLI commands with better output formatting and validation
- Added PHP 8.0+ type hints (parameter and return types) throughout

Code Quality:
- Added plugin constants for all magic strings and values
- Improved PHPDoc blocks with complete parameter and return documentation
- Switched from if/elseif chains to switch statements for better readability
- Enhanced error messages in WP-CLI commands
- Added `uninstall.php` to properly clean up user meta on plugin deletion
- Updated form markup with ARIA roles for better accessibility

### 2.1.0

Added WP-CLI command. Bumped required PHP version to 8.0.

### 2.0.3

@webaware has decided to help make this code less awful and submitted a pull request. This release implements their improvements.

### 2.0.2

Sanitize. Not escape. Ack!

### 2.0.1

Forgot to escape the lone `$_POST` in my code. Feel dumb about it. Fixed now tho.

### 2.0.0

Rewrite! Now, instead of forcing *everyone* to use the same login cookie expiration, Biscotti allows users to individually select their login cookie expiration on their profile page.

### 1.0.0

Initial release. Simple plugin that forced login cookie expiration for every user to 1 year.

## Credits

All plugin code is (currently) Jason Cosper's fault.
Plugin header image courtesy of Terri Bateman.
Plugin icon courtesy of Toora Khan from Noun Project.
