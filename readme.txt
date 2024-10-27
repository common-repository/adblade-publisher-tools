=== Adblade Publisher Tools ===
Tags: ad networks, ads, adblade, adiant, advertising, affiliate, industry brains, content ads, content recommendation, contextual ads, contextual advertising., engagement, income, monetization, montize, monitise, pay per click, popular posts, posts, ppc, related, related content, relevant ads, revenue, similar posts, text ads, traffic, traffic, widget, widgets
marketing, adblade
Requires at least: 4.3.1
Tested up to: 5.8
Stable tag: 1.8.9 
License: GPLv3

Display high quality native ads to your audience and increase visitor
engagement and revenue with Adblade.

== Description ==
The Adblade plugin provides WordPress publishers the ability to implement Adblade native ad units using short codes and forms. The Adblade plugin allows publishers to monetize their web traffic with high quality native ads from Adblade advertisers as well as 3rd party RTB demand sources that bid for Adblade’s inventory.  If you do not yet have an Adblade account, no problem, simply visit Adblade.com and fill out an application to be a "Publisher". Once your registered on Adblade, simply install the plugin on the WP plugins page and the plugin will start working with all existing Adblade ad units. Place the Adblade unit in your content, below your content or on the right rail.

== Installation ==
1. Upload 'adblade' to the '/wp-content/plugins/' directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. If you don’t have an Adblade account, create one here https://adblade.com/registration/publisher-signup
4. Create the Adblade Ad units you want by choosing from several templates.
5. Paste the Adblade Ad code into the Adblade plugin using the settings feature.

== Frequently Asked Questions ==
= How do I install the plugin? =
Install and activate the plugin on the plugins page then visit the settings page and click Save Changes. Nothing else is required to use the Adblade Publisher Tools plugin.

= How do I use the plugin? =
Once the plugin is installed it will start working for all existing Adblade tags on a publisher's site. Simply place your Adblade tags in your WordPress site where you want the ads to render. Optionally, publishers can use the short codes instead of placing Adblade tags directly.

= How do I use the short codes? =
Short code for dynamic responsive tags: [adblade container_id="1234-1234567890"]. Short code for fixed sized tags: [adblade container_id="1234-1234567890" width=160 height=600 type=1]. Replace "1234-1234567890" with the actual data-cid value from your ad tags. If the tag is fixed sized replace the width and height values accordingly.

= How do I use the Before Post Ad Tag? =
The Before Post Ad Tag field can be used to automatically append an ad tag to the top of every post. Just save the ad tag in the Before Post Ad Tag field on the options page.

= How do I use the [AdsWithin] short code? =
The [AdsWithin] short code can be used to place an ad tag inside and article.  Just put the [AdsWithin] short code in your article where you'd like the ad tag to appear and save the ad tag in the [AdsWithin] Short Code Ad Tag field on the options page.

= How do I use the After Post Ad Tag? =
The After Post Ad Tag field can be used to automatically append an ad tag to the bottom of every post. Just save the ad tag in the After Post Ad Tag field on the options page.

= Why doesn't the bypass appear to work after I install/upgrade? =
Sometimes the settings just need to be saved in the admin section. Go to the admin page, make sure that "Attempt to bypass..." is checked, and save the changes. You should save the changes even if no changes were made.

= Why do I occasionally see "404: Not Found" errors when using the bypass? =
The bypass URLs will change daily. If you cache your pages longer, the URLs might hit the expired bypass URLs causing these errors. If possible, reduce the cache expiration on your site.

= Are there any other requirements for this plugin? =
PHP 5.3 or newer is required for this plugin to work. You will also need an Adblade Publisher account. 

= What do the advanced ad blocker bypass settings do? =
The advanced ad blocker bypass settings are optional. They can be used to only display Adblade ads when an ad blocker is detected. If no ad blocker is detected your regular ad tags will be displayed like normal. When an ad blocker is detected, the plugin will insert the Adblade ad tag into the document using the selector specified for that tag. If you change the advanced ad blocker bypass settings it is recommended you clear your WordPress cache after saving the changes.

= How can I become an Adblade publisher and get ad tags? =
Visit https://www.adblade.com/registration/publisher-signup and register for an account.

== Changelog ==
= 1.8.9 =
* Improve security, code formatting

= 1.8.8 = 
* Fixed broken http headers for assets

= 1.8.7 =
* Adblade shortcode secure by default. 

= 1.8.6 =
* Update static CDN hostname

= 1.8.5 =
* Add new subsitution for zone.

= 1.8.4 =
* Fix bug where the plugin added a double-slash, allowing it to be easily blocked. 

= 1.8.3 =
* Switch to an older "random" function to support more versions of PHP and WordPress

= 1.8.2 =
* Improve multisite options
* Better support for CSS-specific bypassing
* Make sure clicks are bypassed properly

= 1.8.1 =
* Bypass improvements

= 1.8 =
* Add Advanced Ad Blocker Bypass Settings

= 1.7 =
* Remove rewrite rules in favor of direct links.
* Add settings link on main plugin page.
* Update the bypass URLs daily.

= 1.6.1, 1.6.2, 1.6.3 =
* fix typos
* fix MIME types for JavaScript requests

= 1.6 =
* Make bypass more difficult to block.

= 1.5 =
* Add hooks to add ads before and after posts. 
* Add new [AdsWithin] short code.
* Add limits to what URLs can be bypassed.
* Bring code up to WordPress standards (https://codex.wordpress.org/WordPress_Coding_Standards)
* Refactor admin page. 

= 1.4 =
* Tighter controls over which hostnames can be used in the bypass.

= 1.3 =
* use transients to speed up server to server calls
* add caching headers to static Adblade assets
* fix CSS with older dynazones

= 1.2 =
* Make sure plugin JavaScript loads after jQuery

= 1.1 =
* use wp_safe_remote_get
* catch HTTP errors in proxy
* add default values to plugin options
* change disclosure on proxy
* add changelog

= 1.0 =
* Initial plugin
