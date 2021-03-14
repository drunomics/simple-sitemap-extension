# Dynamic Monthly Simple Sitemap

Extension of a [Simple XML Sitemap module](https://drupal.org/project/simple_sitemap)
and a submodule of [Simple Sitemap Extensions](https://github.com/drunomics/simple-sitemap-extensions).
Extends a default `default_href` sitemap type. This sitemap type creates a
sitemap index for each month from now to the oldest entity configured to be indexed.

This module creates a sitemap (of configured entities) for each month since the
first entity was created.

## Usage

Add monthly sitemap variant to the sitemap variants at `/admin/config/search/simplesitemap/variants`.
For example:

    monthly | monthly_sitemap_type | Monthly

