<?php

declare(strict_types=1);

namespace Mediarama\Command;

use Mediarama\Import\Coppermine\CoppermineReconciler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mediarama:import:coppermine:reconcile',
    description: 'Compare Coppermine pictures/files with imported Mediarama media.',
)]
final class CoppermineReconcileCommand extends Command
{
    public function __construct(private readonly CoppermineReconciler $reconciler)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $report = $this->reconciler->reconcile();

        $output->writeln('Coppermine pictures: '.$report->sourcePictures);
        $output->writeln('Mapped pictures: '.$report->mappedPictures);
        $output->writeln('Target media: '.$report->targetMedia);
        $output->writeln('Collection links: '.$report->collectionLinks);
        $output->writeln('Missing source files: '.count($report->missingFiles));
        $output->writeln('Unmapped source pictures: '.count($report->unmappedPictureIds));

        foreach ($report->missingFiles as $missing) {
            $output->writeln(sprintf('<error>Missing pid=%s: %s</error>', $missing['pid'], $missing['path']));
        }
        foreach ($report->unmappedPictureIds as $pid) {
            $output->writeln('<comment>Unmapped pid='.$pid.'</comment>');
        }

        return $report->isClean() ? Command::SUCCESS : Command::FAILURE;
    }
}
