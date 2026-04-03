=== Flavor Block Visibility ===
Contributors: wpspacenerd
Donate link: https://www.spacenerd.space/
Tags: gutenberg, responsive, visibility, blocks, mobile
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds responsive visibility toggles to every Gutenberg block. Hide blocks on Desktop, Tablet, or Mobile right from the Advanced panel.

== Description ==

Flavor Block Visibility adds three simple toggle switches to the **Advanced** panel of every Gutenberg block:

* **Hide on Desktop** — hides the block on large screens
* **Hide on Tablet** — hides the block on medium screens
* **Hide on Mobile** — hides the block on small screens

= Features =

* Works with every core and third-party Gutenberg block
* Controls appear in the native Advanced panel — no extra sidebars
* Customizable breakpoints via **Settings → Block Visibility**
* Lightweight — CSS is only loaded on pages that actually use visibility toggles
* Visual indicators in the editor show which blocks have visibility rules
* No JavaScript on the frontend — pure CSS media queries
* No build step required
* Clean uninstall — removes all plugin data

= Default Breakpoints =

* Mobile: 0 – 767px
* Tablet: 768px – 1024px
* Desktop: 1025px and above

Breakpoints can be customized in **Settings → Block Visibility**.

== Installation ==

1. Upload the `flavor-block-visibility` folder to `/wp-content/plugins/`
2. Activate through the **Plugins** menu in WordPress
3. Select any block → open the **Advanced** panel in the sidebar → use the toggles
4. Optionally adjust breakpoints in **Settings → Block Visibility**

== Frequently Asked Questions ==

= Does this work with third-party blocks? =

Yes. The plugin adds responsive controls to every registered Gutenberg block, including blocks from other plugins and themes.

= How does the hiding work? =

The plugin uses CSS media queries with `display: none !important`. No JavaScript is used on the frontend. The CSS is only loaded on pages where at least one block uses a visibility toggle.

= Can I customize the breakpoints? =

Yes. Go to **Settings → Block Visibility** to change the pixel values for mobile, tablet, and desktop breakpoints.

= Will this affect SEO? =

CSS-based responsive hiding is a standard web practice. Search engines understand media queries and do not penalize content that is responsively hidden.

= Does it work with Full Site Editing (FSE)? =

Yes. The plugin supports both classic themes and block themes with Full Site Editing.

== Screenshots ==

1. Toggle controls in the Advanced panel of the block sidebar.
2. Settings page for customizing breakpoints.
3. Visual indicator in the editor when a block has visibility rules.

== Changelog ==

= 1.0.0 =
* Initial release.
* Hide on Desktop, Tablet, and Mobile toggles for all Gutenberg blocks.
* Customizable breakpoints via Settings page.
* Conditional CSS loading — styles only load when needed.
* Visual editor indicators for hidden blocks.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
