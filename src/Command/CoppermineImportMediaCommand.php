<?php

declare(strict_types=1);

namespace Mediarama\Command;

use Mediarama\Import\Coppermine\CoppermineMediaImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mediarama:import:coppermine:media',
    description: 'Import Coppermine pictures and immutable originals into Mediarama.',
)]
final class CoppermineImportMediaCommand extends Command
{
    public function __construct(private readonly CoppermineMediaImporter $importer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        do {
            $report = $this->importer->importBatch();

            $output->writeln(sprintf(
                'Imported %d media; skipped %d.',
                $report->imported,
                $report->skipped,
            ));

            foreach ($report->warnings as $warning) {
                $output->writeln('<error>'.$warning.'</error>');
            }

            if ($report->warnings !== []) {
                return Command::FAILURE;
            }
        } while (!$report->sourceExhausted);

        return Command::SUCCESS;
    }
}
