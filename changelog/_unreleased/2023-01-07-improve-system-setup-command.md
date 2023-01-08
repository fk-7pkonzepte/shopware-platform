---
title: Improve SystemSetupCommand
issue:
author: Florian Kasper
author_email: fk@bitsandlikes.de
author_github: flkasper
---
# Core
* Improve `Shopware\Core\Maintenance\System\Command\SystemSetupCommand`:
  * Replace `return 0` in `SystemSetupCommand::execute` with `self::SUCCESS`.  
  * Add options for `database-user`, `database-password`, `database-host`, `database-port`, `database-name`.
  * Don't ask for database information if option `database-url` is used.
  * Don't ask for database information if it's passed as an option.
  * Add unit test for custom database config options
