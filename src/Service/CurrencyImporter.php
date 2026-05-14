<?php

namespace App\Service;

use App\Entity\Currency;
use App\Entity\ExchangeRate;
use App\Entity\Setting;
use App\Repository\CurrencyRepository;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CurrencyImporter
{
    public function __construct(
        private EntityManagerInterface $em,
        private SettingRepository $settingRepository,
        private CurrencyRepository $currencyRepository,
        private HttpClientInterface $httpClient
    ) {}

    public function import(): ?\DateTimeInterface
    {
        $setting = $this->settingRepository->findOneByName('fetch_currencies');
        $codes = $setting ? json_decode($setting->getValue(), true) : ['USD', 'EUR', 'AUD'];
        if (!is_array($codes)) $codes = ['USD', 'EUR'];

        $response = $this->httpClient->request('GET', 'http://www.cbr.ru/scripts/XML_daily.asp');
        $xml = simplexml_load_string($response->getContent());
        if (!$xml) return null;

        $xmlDate = \DateTime::createFromFormat('d.m.Y', (string)$xml['Date']) ?: new \DateTime('today');

        foreach ($xml->Valute as $valute) {
            $charCode = (string)$valute->CharCode;
            if (!in_array($charCode, $codes, true)) continue;

            $currency = $this->currencyRepository->findOneBy(['code' => $charCode]);
            if (!$currency) {
                $currency = new Currency();
                $currency->setCode($charCode);
                $currency->setName((string)$valute->Name);
                $currency->setNominal((int)$valute->Nominal);
                $this->em->persist($currency);
            }

            $value = str_replace(',', '.', (string)$valute->Value);
            $vunitRate = str_replace(',', '.', (string)$valute->VunitRate);

            $existing = $this->em->getRepository(ExchangeRate::class)->findOneBy([
                'currency' => $currency,
                'date' => $xmlDate,
            ]);

            if (!$existing) {
                $rate = new ExchangeRate();
                $rate->setCurrency($currency);
                $rate->setDate($xmlDate);
                $rate->setValue($value);
                $rate->setVunitRate($vunitRate);
                $this->em->persist($rate);
            } 
        }

        $this->em->flush();

        $lastFetch = $this->settingRepository->findOneByName('last_fetch_time') ?? new Setting();
        $lastFetch->setName('last_fetch_time');
        $lastFetch->setValue((string)time());
        $this->em->persist($lastFetch);
        $this->em->flush();

        return $xmlDate;
    }
}