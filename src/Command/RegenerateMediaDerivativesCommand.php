<?php

declare(strict_types=1);

namespace Mediarama\Command;

use Mediarama\Media\Application\RegenerateMediaDerivatives;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'mediarama:media:regenerate',
    description: 'Generate missing derivatives for one media asset.',
)]
final class RegenerateMediaDerivativesCommand extends Command
{
    public function __construct(private readonly RegenerateMediaDerivatives $regenerate)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('media-id', InputArgument::REQUIRED, 'Media UUID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ($this->regenerate)(Uuid::fromString((string) $input->getArgument('media-id')));
        $output->writeln('Derivative generation completed.');

        return Command::SUCCESS;
    }
}
