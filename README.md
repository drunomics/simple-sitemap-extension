# Simple sitemap extensions

## Overview:

Extends simple sitemap to add support for sitemap index files and configuring
variants per index file.

## Usage:

* install module

### Sitemap index
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

### extended_entity (Extended entity) sitemap type
To get extra data for entities, as images.

For this a config file will be needed to be added.
`simple_sitemap_extensions.extended_entity.image_paths.yml`

Which should define the mapping to the images for entity.

Example for a node article with different fields that contain images

```
node:
  article:
    fields:
      field_hero:
        -
          bundles:
            - gallery
          fields:
            field_media:
              -
                bundles:
                  - image
                fields:
                  field_image: true
        -
          bundles:
            - image
          fields:
            field_image:
              -
                bundles:
                  - image
                fields:
                  field_image: true
      field_paragraphs:
        -
          bundles:
            - gallery
          fields:
            field_media:
              -
                bundles:
                  - image
                fields:
                  field_image: true
        -
          bundles:
            - image
          fields:
            field_image:
              -
                bundles:
                  - image
                fields:
                  field_image: true
      field_teaser_media:
        -
          bundles:
            - image
          fields:
            field_image: true
```

Then add an `extended_entity` sitemap variant:
* goto /admin/config/search/simplesitemap/variants
* add the new variant of type `extended_entity`

Example:
  `variant_machine_name | extended_entity | Variant label`
