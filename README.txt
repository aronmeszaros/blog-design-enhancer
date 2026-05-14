=== Blog Design Enhancer ===
Contributors: your-name
Tags: blog, excerpt, cta, toc, breadcrumbs, related-posts, css, js
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds conversion-focused enhancements for blog listing and single article pages.

== Installation ==
1. Upload the `blog-design-enhancer` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Go to Settings > Blog Design Enhancer.
4. Configure excerpt length, social URLs, CTA content, and optional custom CSS/JS.

== Features ==
- Short excerpt length control for blog/archive/search pages.
- Auto-generated excerpt when a post has no manual excerpt.
- Visual redesign for archive cards and stronger readability.
- Breadcrumb output for category and single pages.
- Inline CTA modules after intro and mid-article, plus final CTA at end.
- Mini table of contents generated from H2/H3 headings.
- Share buttons at the end of single posts.
- Related topics and related posts based on post tags.
- Admin fields for custom CSS and JS.
- Optional GitHub release-based updates through a built-in updater class.

== Notes ==
- Social profile links are global and appear for each post listing item.
- Plugin targets posts on blog-like listings (home, archive, search) and single post pages.
- Main logic is split into modular files under `/includes/`.
- To enable updates, replace `your-owner/blog-design-enhancer` and optionally set `BDE_GITHUB_TOKEN` in `blog-design-enhancer.php`.
- GitHub commit-based updates should be implemented through automated releases in GitHub Actions; the updater is already ready for release ZIPs.
