---
title: Prevent context modification in document renderers by cloning context
issue: NEXT-37924
author: Florian Kasper
author_email: fk@phoenix-corporation.biz
author_github: @flkasper
---

# Core

* Changed document generators to prevent context modification by cloning context before assign call. Changed classes
    * `\Shopware\Core\Checkout\Document\Renderer\CreditNoteRenderer`
    * `\Shopware\Core\Checkout\Document\Renderer\DeliveryNoteRenderer`
    * `\Shopware\Core\Checkout\Document\Renderer\InvoiceRenderer`
    * `\Shopware\Core\Checkout\Document\Renderer\StornoRenderer`
