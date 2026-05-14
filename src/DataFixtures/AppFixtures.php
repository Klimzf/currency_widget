<?php

namespace App\DataFixtures;

use App\Entity\Currency;
use App\Entity\ExchangeRate;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $yesterday = new \DateTime('yesterday');
        $yesterday->setTime(0, 0);

        $fakeRates = [
            'USD' => ['value' => '74.5000', 'vunitRate' => '74.5000', 'nominal' => 1, 'name' => 'Доллар США'],
            'EUR' => ['value' => '88.2000', 'vunitRate' => '88.2000', 'nominal' => 1, 'name' => 'Евро'],
            'AUD' => ['value' => '53.0996', 'vunitRate' => '53.0996', 'nominal' => 1, 'name' => 'Австралийский доллар'],
        ];

        foreach ($fakeRates as $code => $data) {
            $currency = $manager->getRepository(Currency::class)->findOneBy(['code' => $code]);
            if (!$currency) {
                $currency = new Currency();
                $currency->setCode($code);
                $currency->setName($data['name']);
                $currency->setNominal($data['nominal']);
                $manager->persist($currency);

                $manager->flush();
            }

            $existing = $manager->getRepository(ExchangeRate::class)->findOneBy([
                'currency' => $currency,
                'date' => $yesterday,
            ]);

            if (!$existing) {
                $rate = new ExchangeRate();
                $rate->setCurrency($currency);
                $rate->setDate($yesterday);
                $rate->setValue($data['value']);
                $rate->setVunitRate($data['vunitRate']);
                $manager->persist($rate);
            }
        }

        $manager->flush();
    }
}