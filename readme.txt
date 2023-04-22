=== Really Simple Featured Video - Featured video support for Posts, Pages & WooCommerce Products ===
Contributors: smalltowndev, lushkant
Requires at least: 5.9
Requires PHP: 7.4
Tested up to: 6.2
Stable tag: 0.6.0
Tags: featured, video, featured video, woocommerce, product-video, video embed, youtube, dailymotion, vimeo
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Really Simple Featured Video enables featured video support for WordPress posts, pages & WooCommerce Products.

== Description ==

Really Simple Featured Video plugin provides a really straightforward way of adding featured video support to your custom post types. Adding your own videos to the site is a breeze and you get an easy to use settings panel with the options you really need.

https://www.youtube.com/watch?v=vbLgiRQ0Moo

With Really Simple Featured Video you get a metabox at posts, pages & products edit screen at the very bottom and a very similar interface as you're used to with featured image.

You get a really simple settings page which has all the controls you need for managing featured videos throughout your site. Explore it yourself for a better look or take a look at screenshots below.

#### *NEW* Youtube, Dailymotion and Vimeo Embed Support

You can now embed videos from Youtube, Dailymotion and Vimeo directly at each post/page/product or any custom type that supports featured images.

#### WooCommerce Support

This plugin came out of a real need with many plugins available out there yet none provides a good integration with WooCommerce.
Really Simple Featured Video plugin provides a straightforward implementation for WooCommerce Product Featured Video, with which your set featured video loads directly into product thumbnails along with any other product images.

#### Enable/Disable Post Types Support

You can enable/disable support for post types via the settings page though by default featured videos are enabled for blog posts.

#### Enable/Disable Video Controls, Autoplay, Loop & Picture-In-Picture

You can manage everything for the featured videos, by setting them accordingly, options for these are available at settings page.

#### Mute Video Sound

You also have an option to control video sound, set this at the settings page.

#### Shortcodes

There are also shortcodes to embed featured video at any post, page or product you want.

[rsfv] shortcode is for displaying set featured video of the individual post anywhere in the post.

[rsfv_by_postid] shortcode is for displaying featured video of any post anywhere you want, you just need to pass a vaild post id to it e.g. [rsfv_by_postid post_id="281"]


You can send a feedback or a feature request at https://github.com/smalltowndev/really-simple-feature-video
Or create a thread at forums here, in any case I'll make sure to resolve if any issues asap.

== Screenshots ==

1. Single post/page featured video on Twenty Twenty-Three theme.
2. Featured video posts on Twenty Twenty-Three theme.
3. Settings page view.
4. Video playback controls view at Really Simple Featured Video settings.
5. WooCommerce settings page view.
6. Single Page view with Featured video on Twenty nineteen theme.
7. Showing Featured video by post id shortcode via Block editor.


== Frequently Asked Questions ==

= Will this plugin work with any theme? =
Yes, as long as the theme you use follows standard WordPress/WooCommerce way of handling post thumbnails, this plugin should work without any problems.

= Where can I get help? =
You can get help by reaching out to me at https://github.com/smalltowndev/really-simple-featured-video or support forums here.

== Changelog ==

= 0.6.0 - April 23, 2023 =
- New: Theme Compatibility Engine and controls at settings
- New: Integrations and controls at settings
- New: WooCommerce shop archives now supported
- New: Directly copy and paste Youtube, Vimeo and Dailymotion video urls at embeds
- Fix: Video autoplay in iOS devices
- Fix: Settings page throwing error in some cases
- Fix: Other minor fixes
- Fix: Missing textdomain in strings
- Improvement: Update Freemius SDK to v2.5.6
- Improvement: Remove settings sidebar
- Improvement: Core FSE themes support and fallback for classic themes
- Improvement: Compatibility with WordPress v6.2.x

= 0.5.5 - July 15, 2022 =
- Compatibility up to WordPress 6.0.1
- Minor code improvements
- Update settings sidebar

= 0.5.4 - June 18, 2022 =
- Fix styling issue at edit screen
- Update translation .pot file

= 0.5.3 - June 18, 2022 =
- Fix shortcode issues
- Improve security issues as per WPCS standards
- other code improvements

= 0.5.2 - June 16, 2022 =
- Update Freemius version to v2.5.0rc-2 for security issue
- Improve code as per WordPress Coding Standards
- Fix metabox not showing as per Enabled CPT types at settings
- Other code improvements

= 0.5.1 - June 13, 2021 =
- Fix missing updater file

= 0.5.0 - June 12, 2021 =
- Adds Youtube, Dailymotion & Vimeo support
- Update settings page
- Other code improvements

= 0.0.3 - January 11, 2021 =
- Add option for Video controls, loop, mute & picture-in-picture at Settings
- Added Freemius for better customer feedback & support
- Other minor improvements

= 0.0.2 - September 15, 2020 =
- Fix array_keys throwing error when post_types option empty
- Add Settings action link at plugins page

= 0.0.1 - September 13, 2020 =
- Initial release