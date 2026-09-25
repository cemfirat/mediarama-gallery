<?php

declare(strict_types=1);

namespace Mediarama\Command;

use Mediarama\Import\Coppermine\CoppermineCollectionImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mediarama:import:coppermine:collections',
    description: 'Import Coppermine categories and albums into Mediarama collections.',
)]
final class CoppermineImportCollectionsCommand extends Command
{
    public function __construct(private readonly CoppermineCollectionImporter $importer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        do {
            $count = $this->importer->importCategories();
            $output->writeln(sprintf('Imported %d category row(s).', $count));
        } while ($count > 0);

        do {
            $count = $this->importer->importAlbums();
            $output->writeln(sprintf('Imported %d album row(s).', $count));
        } while ($count > 0);

        return Command::SUCCESS;
    }
}
