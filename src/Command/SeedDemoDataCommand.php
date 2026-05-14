<?php

namespace App\Command;

use App\Entity\Currency;
use App\Entity\ExchangeRate;
use App\Repository\CurrencyRepository;
use App\Repository\ExchangeRateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:seed-demo-data')]
class SeedDemoDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private CurrencyRepository $currencyRepository,
        private ExchangeRateRepository $exchangeRateRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Inserts demo exchange rates for yesterday (USD, EUR, AUD) if missing.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $yesterday = new \DateTime('yesterday');
        $yesterday->setTime(0, 0);

        $currenciesData = [
            ['code' => 'USD', 'name' => 'Доллар США', 'nominal' => 1, 'value' => '74.5000', 'vunitRate' => '74.5000'],
            ['code' => 'EUR', 'name' => 'Евро', 'nominal' => 1, 'value' => '88.2000', 'vunitRate' => '88.2000'],
            ['code' => 'AUD', 'name' => 'Австралийский доллар', 'nominal' => 1, 'value' => '53.0996', 'vunitRate' => '53.0996'],
        ];

        foreach ($currenciesData as $data) {
            $currency = $this->currencyRepository->findOneBy(['code' => $data['code']]);
            if (!$currency) {
                $currency = new Currency();
                $currency->setCode($data['code']);
                $currency->setName($data['name']);
                $currency->setNominal($data['nominal']);
                $this->em->persist($currency);
            }

            // Проверяем, существует ли уже запись за вчера
            $existing = $this->exchangeRateRepository->findOneBy([
                'currency' => $currency,
                'date' => $yesterday,
            ]);

            if (!$existing) {
                $rate = new ExchangeRate();
                $rate->setCurrency($currency);
                $rate->setDate($yesterday);
                $rate->setValue($data['value']);
                $rate->setVunitRate($data['vunitRate']);
                $this->em->persist($rate);
                $output->writeln("Added demo rate for {$data['code']} on {$yesterday->format('Y-m-d')}");
            } else {
                $output->writeln("Demo rate for {$data['code']} already exists, skipped.");
            }
        }

        $this->em->flush();
        $output->writeln('Demo data seeding completed.');

        return Command::SUCCESS;
    }
}