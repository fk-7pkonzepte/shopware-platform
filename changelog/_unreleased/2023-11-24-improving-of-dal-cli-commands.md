---
title: Improving of DAL cli commands
issue: NEXT-00000
author: Florian Kasper
author_email: flkasper@web.de
author_github: flkasper
---

# Core

* Changed `\Shopware\Core\Framework\DataAbstractionLayer\Command\CreateEntitiesCommand`
  to improve configurability and use in plugin development
    * Added argument `whitelist` to filter entities by full entity name or prefix.
    * Added option `dir` to make the target directory configurable, old path is used as default
    * Moved success output to the end of execute method
* Changed `\Shopware\Core\Framework\DataAbstractionLayer\Command\CreateSchemaCommand`
  to improve configurability and use in plugin development
    * Added argument `whitelist` to filter entities by full entity name or prefix.
    * Added option `dir` to make the target directory configurable, old path is used as default
    * Added option `split` to write single schema file for every entity name
    * Moved success output to the end of execute method
* Changed `\Shopware\Core\Framework\DataAbstractionLayer\EntityGenerator` to improve entity generation
    * Added generic EntityCollection type to `$collectionTemplate` and removed obsolete methods in phpdoc
    * Changed `$propertyTemplate` to typed properties and removed phpdoc
    * Changed `$propertyTemplate` and `generateProperty` to add null values to not required properties
    * Added trait generation like imports
    * Changed `generateEntity` to sort lines of imports and traits
    * Changed `generate`, `generateEntity` and `generateCollection`to only generate files,
      if entity or collection are defined in Definition classes
    * Changed `generateProperty` to skip fields, which are defined in `Entity` class or in traits
    * Changed placeholder and variable `domain` to `namespace`
    * Moved generation of class name prefix and namespace to `generate` to remove duplicate code
* Changed `\Shopware\Core\Framework\DataAbstractionLayer\SchemaGenerator` to improve schema generation
    * Changed `generateFieldColumn` to move exclude of `TranslatedField` to the other excludes

