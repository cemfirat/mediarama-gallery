<?php

declare(strict_types=1);

namespace Mediarama\Command;

use Mediarama\Upload\Application\CleanupExpiredUploads;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mediarama:uploads:cleanup',
    description: 'Remove expired upload sessions and temporary upload data.',
)]
final class CleanupExpiredUploadsCommand extends Command
{
    public function __construct(private readonly CleanupExpiredUploads $cleanup)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = ($this->cleanup)();
        $output->writeln(sprintf('Cleaned %d expired upload session(s).', $count));

        return Command::SUCCESS;
    }
}
