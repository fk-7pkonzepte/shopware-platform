<?php
declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer\Command;

use Shopware\Core\Framework\Adapter\Console\ShopwareStyle;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityGenerator;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

#[AsCommand(
    name: 'dal:create:entities',
    description: 'Creates the entity classes',
)]
#[Package('core')]
class CreateEntitiesCommand extends Command
{
    private readonly string $dir;

    /**
     * @internal
     */
    public function __construct(
        private readonly EntityGenerator $entityGenerator,
        private readonly DefinitionInstanceRegistry $registry,
        private string $rootDir
    ) {
        $this->dir = $rootDir . '/../schema/';
        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument('whitelist', InputArgument::IS_ARRAY, 'Filter entities by full entity name or prefix.', []);
        $this->addOption('dir', null, InputOption::VALUE_REQUIRED, 'Target directory for schema files', $this->dir);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new ShopwareStyle($input, $output);
        $io->title('DAL generate schema');

        $whitelist = $input->getArgument('whitelist');
        $hasWhitelist = !empty($whitelist);

        $entities = $this->registry->getDefinitions();
        $classes = [];

        foreach ($entities as $entity) {
            $entityName = $entity->getEntityName();

            if ($hasWhitelist) {
                if (!in_array($entityName, $whitelist) && empty(array_filter($whitelist, fn($item) => str_starts_with($entityName, $item)))) {
                    continue;
                }
            }

            $domain = explode('_', $entityName);
            $domain = array_shift($domain);

            $classes[$domain] ??= [];
            $classes[$domain][] = $this->entityGenerator->generate($entity);
        }

        $dir = $input->getOption('dir');
        if (path::isRelative($dir)) {
            $dir= Path::makeAbsolute($dir, $this->rootDir);
        }
        if (!file_exists($dir)) {
            mkdir($dir);
        }

        foreach ($classes as $domain => $groupClasses) {
            foreach ($groupClasses as $entityClasses) {
                if (empty($entityClasses)) {
                    continue;
                }

                if (!file_exists($dir . '/' . $domain)) {
                    mkdir($dir . '/' . $domain);
                }

                foreach ($entityClasses as $file => $content) {
                    file_put_contents($dir . '/' . $domain . '/' . $file, $content);
                }
            }
        }

        $io->success('Created schema in ' . $dir);

        return self::SUCCESS;
    }
}
