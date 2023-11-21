<?php declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer\Command;

use Shopware\Core\Framework\Adapter\Console\ShopwareStyle;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\SchemaGenerator;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

#[AsCommand(
    name: 'dal:create:schema',
    description: 'Creates the database schema',
)]
#[Package('core')]
class CreateSchemaCommand extends Command
{
    private readonly string $dir;

    /**
     * @internal
     */
    public function __construct(
        private readonly SchemaGenerator $schemaGenerator,
        private readonly DefinitionInstanceRegistry $registry,
        private string $rootDir
    ) {
        $this->dir = $rootDir . '/schema/';
        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument('whitelist', InputArgument::IS_ARRAY, 'Filter entities by full entity name or prefix.', []);
        $this->addOption('dir', null, InputOption::VALUE_REQUIRED, 'Target directory for schema files', $this->dir);
        $this->addOption('split', null, InputOption::VALUE_NONE, 'Write single schema file for every entity name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new ShopwareStyle($input, $output);
        $io->title('DAL generate schema');

        $whitelist = $input->getArgument('whitelist');
        $hasWhitelist = !empty($whitelist);
        $splitFile = $input->getOption('split');

        $entities = $this->registry->getDefinitions();
        $schema = [];

        foreach ($entities as $entity) {
            $entityName = $entity->getEntityName();

            if ($hasWhitelist) {
                if (!in_array($entityName, $whitelist) && empty(array_filter($whitelist, fn($item) => str_starts_with($entityName, $item)))) {
                    continue;
                }
            }

            if ($splitFile) {
                $group = $entityName;
            } else {
                $domain = explode('_', $entityName);
                $domain = array_shift($domain);
                $group = $domain;
            }
            $schema[$group] ??= [];
            $schema[$group][] = $this->schemaGenerator->generate($entity);
        }

        $dir = $input->getOption('dir');
        if (path::isRelative($dir)) {
            $dir= Path::makeAbsolute($dir, $this->rootDir);
        }
        if (!file_exists($dir)) {
            mkdir($dir);
        }

        foreach ($schema as $group => $sql) {
            file_put_contents($dir . '/' . $group . '.sql', implode("\n\n", $sql));
        }

        $io->success('Created schema in ' . $dir);

        return self::SUCCESS;
    }
}
