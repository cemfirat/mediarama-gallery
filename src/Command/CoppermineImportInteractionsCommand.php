<?php

declare(strict_types=1);

namespace Mediarama\Command;

use Mediarama\Import\Coppermine\CoppermineInteractionImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mediarama:import:coppermine:interactions',
    description: 'Import Coppermine comments and recoverable ratings.',
)]
final class CoppermineImportInteractionsCommand extends Command
{
    public function __construct(private readonly CoppermineInteractionImporter $importer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $report = $this->importer->import();

        $output->writeln('Comments imported: '.$report->commentsImported);
        $output->writeln('Individual ratings imported: '.$report->ratingsImported);
        $output->writeln('Legacy rating aggregates preserved: '.$report->ratingAggregatesPreserved);
        $output->writeln('Aggregate votes without recoverable individual values: '.$report->aggregateVotesWithoutRecoverableIndividualRatings);

        foreach ($report->warnings as $warning) {
            $output->writeln('<comment>'.$warning.'</comment>');
        }

        return Command::SUCCESS;
    }
}
