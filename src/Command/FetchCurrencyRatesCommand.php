<?php

namespace App\Command;

use App\Service\CurrencyImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsCommand(name: 'app:fetch-currency-rates')]
class FetchCurrencyRatesCommand extends Command
{
    public function __construct(
        private CurrencyImporter $importer,
        private TagAwareCacheInterface $cache
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = $this->importer->import();
        if ($date === null) {
            $output->writeln('<error>Import failed</error>');
            return Command::FAILURE;
        }

        $this->cache->invalidateTags(['currencies']);
        $output->writeln('Imported rates for ' . $date->format('Y-m-d'));
        return Command::SUCCESS;
    }
}