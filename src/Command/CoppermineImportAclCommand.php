<?php

declare(strict_types=1);

namespace Mediarama\Command;

use Mediarama\Import\Coppermine\CoppermineAclImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mediarama:import:coppermine:acl',
    description: 'Import Coppermine album visibility and password-protection intent safely.',
)]
final class CoppermineImportAclCommand extends Command
{
    public function __construct(private readonly CoppermineAclImporter $importer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $report = $this->importer->import();

        $output->writeln('Processed albums: '.$report->processedAlbums);
        $output->writeln('Created collection access rules: '.$report->createdAccessRules);
        $output->writeln('Password-protected albums requiring a new password: '.count($report->passwordResetAlbums));

        foreach ($report->unmappedPrincipals as $warning) {
            $output->writeln('<error>Unmapped ACL principal: '.$warning.'</error>');
        }

        foreach ($report->passwordResetAlbums as $aid) {
            $output->writeln('<comment>Album '.$aid.' requires a new Mediarama collection password.</comment>');
        }

        return $report->unmappedPrincipals === [] ? Command::SUCCESS : Command::FAILURE;
    }
}
