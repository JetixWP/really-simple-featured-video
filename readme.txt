=== Really Simple Featured Video - Featured video support for Posts, Pages & WooCommerce Products ===
Contributors: smalltowndev, lushkant
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 6.4.2
Stable tag: 0.8.0
Tags: featured, video, featured video, woocommerce, product-video, video embed, youtube, dailymotion, vimeo, woo, cpt
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Really Simple Featured Video enables featured video support for WordPress posts, pages, CPTs (with featured images) & WooCommerce Products.

== Description ==

Really Simple Featured Video plugin provides a really straightforward way of adding featured video support to your custom post types. Adding your own videos to the site is a breeze and you get an easy to use settings panel with the options you really need.

https://www.youtube.com/watch?v=xHrj2lcNS5Q

With Really Simple Featured Video you get a metabox at posts, pages, CPTs & Woo products edit screen at the very bottom and a very similar interface as you're used to with featured image.

You get a really simple settings page which has all the controls you need for managing featured videos throughout your site. Explore it yourself for a better look or take a look at screenshots below.

#### *NEW* Youtube, Dailymotion and Vimeo Support

You can now embed videos from Youtube, Dailymotion and Vimeo directly at each post/page/product or any custom type that supports featured images.

#### WooCommerce Single and Shop Archives Support

This plugin came out of a real need with many plugins available out there yet none provides a good integration with WooCommerce.
Really Simple Featured Video plugin provides a straightforward implementation for WooCommerce Product Featured Video, with which your set featured video loads directly into product thumbnails along with any other product images.

#### Supports for Core themes

We now support all the major newer core themes such as TwentyTwenty Four to Classic themes.

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

You can send a feedback or a feature request at [github.com/smalltowndev/really-simple-featured-video](https://github.com/smalltowndev/really-simple-featured-video)
Or create a thread at forums here, in any case 

= OUR PRO VERSION IS COMING SOON, READ MORE BELOW =

We're currently working on a Pro plugin to support additional features, where we can continue to keep them maintained and updated.
We have quite a few requests from you guys, and the plugin is scheduled to be released by December 2023.

If you wish to grab on the early bird deal which will be available as a lifetime version of the plugin to first few customers.

Initially our Pro plugin will include these features -

- **Change Video Aspect Ratio** - Apply sitewide featured video aspect ratio such as 16:9, 3:2 and more.
- **Change Video Order at Woo Product CPT** - Set video order of at single product pages of WooCommerce Product CPT
- **Requests for Theme Compatibility** - We will take requests for additional theme compatibility.
- **Priority Support** - Finally we will be able to additionally solve your issues at priority and faster response times.

More to come in the future such as -

- **Elementor support**
- **Divi Support**
- **Featured Video Blocks for Full Site Editing**
- And much more.

If you wish to lock your early bird spot beforehand please send us an email at [hello@smalltowndev.com](mailto:hello@smalltowndev.com), and you will be notified at the very moment we launch.

== Screenshots ==

1. Single post/page featured video on Twenty Twenty-Three theme.
2. Featured video posts on Twenty Twenty-Three theme.
3. Settings page view.
4. Video playback controls view at Really Simple Featured Video settings.
5. WooCommerce settings page view.
6. Single Page view with Featured video on Twenty nineteen theme.
7. Showing Featured video by post id shortcode via Block editor.


== Frequently Asked Questions ==

= Is there a Pro version coming for this? =
Yes, and is due for launch by this December 2023. If you wish to get the early bird lifetime deal, please lock your spot by emailing us at [hello@smalltowndev.com](mailto:hello@smalltowndev.com). And we will make sure you get notified at the very moment we launch.

= Will this plugin work with any theme? =
Yes, as long as the theme you use follows standard WordPress/WooCommerce way of handling post thumbnails, this plugin should work without any problems.

= Where can I get help? =
You can get help by reaching out to us at [github.com/smalltowndev/really-simple-featured-video](https://github.com/smalltowndev/really-simple-featured-video) or support forums here.

== Changelog ==

= 0.8.0 - December 14, 2023 =
- New: Add TwentyTwenty Four Theme support
- New: Support for PHP 8.1
- New: Bumped min required WordPress version to v6.0
- Fix: Settings checkbox not being respected when saved
- Fix: Duplicate thumbnails being shown at Woo product single pages
- Fix: Embed URLs not being shown when directly copy/pasted
- Fix: Core themes support
- Improvement: Update Freemius SDK to v2.6.1
- Improvement: Minor code changes
- Improvement: Compatibility with WordPress v6.4.x

= 0.7.2 - August 01, 2023 =
- Improvement: Update Freemius SDK to v2.5.10

= 0.7.1 - June 20, 2023 =
- Fix: Default self hosted video controls
- Fix: WooCommerce placeholder images appearing below featured video
- Improvements: Other minor code improvements

= 0.7.0 - May 22, 2023 =
- New: Custom post types support (Available via settings)
- Fix: Default video playback controls
- Fix: Default Theme Compatibility engine throwing error at frontend
- Improvement: Update Freemius SDK to v2.5.8
- Improvements: Code improvements

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