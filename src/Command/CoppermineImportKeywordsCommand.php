<?php

declare(strict_types=1);

namespace Mediarama\Command;

use Mediarama\Import\Coppermine\CoppermineKeywordImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mediarama:import:coppermine:keywords',
    description: 'Import Coppermine picture keywords as Mediarama tags.',
)]
final class CoppermineImportKeywordsCommand extends Command
{
    public function __construct(private readonly CoppermineKeywordImporter $importer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $report = $this->importer->import();

        $output->writeln('Keyword separator: '.json_encode($report->separator));
        $output->writeln('Created tags: '.$report->createdTags);
        $output->writeln('Created media/tag links: '.$report->createdLinks);
        $output->writeln('Pictures without media mapping: '.count($report->unmappedPictureIds));

        return $report->unmappedPictureIds === [] ? Command::SUCCESS : Command::FAILURE;
    }
}
