# Simple sitemap extensions

## Overview:

Extends simple sitemap to add support for sitemap index files and configuring
variants per index file.

### Usage:

* install module
* goto /admin/config/search/simplesitemap/variants
* add one or more variants of type sitemap_index

  `index | sitemap_index | Sitemap Index`

  or multiple:

  `site-a_index | sitemap_index | Sitemap Index Site A`
  `site-b_index | sitemap_index | Sitemap Index Site B`
  `site-c_index | sitemap_index | Sitemap Index Site C`

* add more variants you would need & save configuration
* in /admin/config/search/simplesitemap/settings
  set the default sitemap variant to the sitemap index
* enable the variants which should be on a sitemap index on
  /admin/config/search/simplesitemap/sitemap-index
* export config, save & regenerate sitemaps
