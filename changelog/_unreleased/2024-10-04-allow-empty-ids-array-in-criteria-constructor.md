---
title: Allow empty ids array in criteria constructor
issue: NEXT-0000
author: Florian Kasper
author_email: fk@phoenix-corporation.biz
author_github: @flkasper
---

# Core

* Changed constructor `\Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria` to allow empty ids array. Empty ids array is is treated like null.
* Changed `\Shopware\Tests\Unit\Core\Framework\DataAbstractionLayer\Search\CriteriaTest` to adjust to this change.
