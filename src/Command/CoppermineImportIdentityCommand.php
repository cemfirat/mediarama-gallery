<?php

declare(strict_types=1);

namespace Mediarama\Command;

use Mediarama\Import\Coppermine\CoppermineIdentityImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mediarama:import:coppermine:identity',
    description: 'Import Coppermine groups and users without reusing legacy password hashes.',
)]
final class CoppermineImportIdentityCommand extends Command
{
    public function __construct(private readonly CoppermineIdentityImporter $importer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        do {
            $count = $this->importer->importGroups();
            $output->writeln(sprintf('Imported %d group row(s).', $count));
        } while ($count > 0);

        do {
            $count = $this->importer->importUsers();
            $output->writeln(sprintf('Imported %d user row(s).', $count));
        } while ($count > 0);

        return Command::SUCCESS;
    }
}
