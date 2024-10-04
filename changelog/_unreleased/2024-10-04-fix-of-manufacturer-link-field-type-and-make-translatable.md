---
title: Fix of manufacturer link field type and make translatable
issue: NEXT-38185
author: Florian Kasper
author_email: fk@phoenix-corporation.biz
author_github: @flkasper
---

# Core

* Make manufactuer.link translatable. Changed files:
    * `\Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition`
    * `\Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity`
    * `\Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerHydrator`
    * `\Shopware\Core\Content\Product\Aggregate\ProductManufacturerTranslation\ProductManufacturerTranslationDefinition`
    * `\Shopware\Core\Content\Product\Aggregate\ProductManufacturerTranslation\ProductManufacturerTranslationEntity`
* Added migration `\Shopware\Core\Migration\V6_6\Migration1727863533ChangeProductManufacturerLink` to change database
  and move data to translation table.
* Changed field type of link from StringField to LongTextField

___

# Administration

* Changed `sw-manufacturer-detail.html.twig` to show inherited link translation as placeholder.

___

# Storefront

* Changed `src/Storefront/Resources/views/storefront/component/product/quickview/minimal.html.twig`, `src/Storefront/Resources/views/storefront/element/cms-element-manufacturer-logo.html.twig`
and `src/Storefront/Resources/views/storefront/page/product-detail/headline.html.twig` to use translated.link.
