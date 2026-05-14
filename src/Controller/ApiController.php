<?php

namespace App\Controller;

use App\Entity\Setting;
use App\Repository\ExchangeRateRepository;
use App\Repository\SettingRepository;
use App\Service\CurrencyImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use App\Entity\ExchangeRate;

class ApiController extends AbstractController
{
    public function __construct(
        private ExchangeRateRepository $rateRepo,
        private SettingRepository $settingRepo,
        private EntityManagerInterface $em,
        private TagAwareCacheInterface $cache,
        private CurrencyImporter $importer
    ) {}

    #[Route('/api/currencies', methods: ['GET'])]
    public function currencies(): JsonResponse
    {
        $this->maybeUpdateRates();

        return $this->json(
            $this->cache->get('widget_data', function (\Symfony\Contracts\Cache\ItemInterface $item): array {
                $item->tag('currencies');
                return $this->buildWidgetData();
            })
        );
    }

    #[Route('/api/settings', methods: ['GET'])]
    public function getSettings(): JsonResponse
    {
        return $this->json([
            'fetch_currencies' => $this->getSettingArray('fetch_currencies', ['USD', 'EUR', 'AUD']),
            'display_currencies' => $this->getSettingArray('display_currencies', ['USD', 'EUR', 'AUD']),
            'update_interval' => (int)($this->getSettingValue('update_interval', '60')),
        ]);
    }

    #[Route('/api/settings', methods: ['POST'])]
    public function saveSettings(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['fetch_currencies'])) {
            $this->updateSetting('fetch_currencies', json_encode($data['fetch_currencies']));
        }
        if (isset($data['display_currencies'])) {
            $this->updateSetting('display_currencies', json_encode($data['display_currencies']));
        }
        if (isset($data['update_interval'])) {
            $this->updateSetting('update_interval', (string)$data['update_interval']);
        }

        $this->em->flush();
        $this->cache->invalidateTags(['currencies']);

        return $this->json(['status' => 'ok']);
    }

    private function maybeUpdateRates(): void
    {
        $interval = (int)($this->getSettingValue('update_interval') ?: '60');
        $lastFetch = (int)($this->getSettingValue('last_fetch_time') ?: '0');

        if (time() - $lastFetch >= $interval) {
            $this->importer->import();
            $this->cache->invalidateTags(['currencies']);
        }
    }

    private function buildWidgetData(): array
    {
        $displayCodes = $this->getSettingArray('display_currencies', ['USD', 'EUR', 'AUD']);
        $result = [];

        foreach ($displayCodes as $code) {
            $latestRate = $this->rateRepo->createQueryBuilder('r')
                ->join('r.currency', 'c')
                ->where('c.code = :code')
                ->orderBy('r.date', 'DESC')
                ->setMaxResults(1)
                ->setParameter('code', $code)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$latestRate) {
                $result[] = $this->emptyCurrencyRow($code);
                continue;
            }

            $currency = $latestRate->getCurrency();
            $current = $this->unitRate($latestRate);

            $previousRate = $this->rateRepo->createQueryBuilder('r')
                ->join('r.currency', 'c')
                ->where('c.code = :code')
                ->andWhere('r.date < :date')
                ->orderBy('r.date', 'DESC')
                ->setMaxResults(1)
                ->setParameter('code', $code)
                ->setParameter('date', $latestRate->getDate())
                ->getQuery()
                ->getOneOrNullResult();

            $prev = $previousRate ? $this->unitRate($previousRate) : null;
            $change = ($prev !== null) ? round($current - $prev, 4) : null;

            $result[] = [
                'code' => $currency->getCode(),
                'name' => $currency->getName(),
                'nominal' => $currency->getNominal(),
                'value' => round($current, 4),
                'previousValue' => $prev !== null ? round($prev, 4) : null,
                'change' => $change,
                'date' => $latestRate->getDate()->format('Y-m-d'),
            ];
        }
        return $result;
    }

    private function unitRate(ExchangeRate $rate): float
    {
        $nominal = $rate->getCurrency()->getNominal() ?: 1;
        $val = $rate->getVunitRate() ?? bcdiv($rate->getValue(), (string)$nominal, 4);
        return (float)$val;
    }

    private function emptyCurrencyRow(string $code): array
    {
        return [
            'code' => $code, 'name' => $code, 'nominal' => 1,
            'value' => null, 'previousValue' => null, 'change' => null, 'date' => null,
        ];
    }

    private function getSettingArray(string $name, array $default = []): array
    {
        $s = $this->settingRepo->findOneByName($name);
        if (!$s) return $default;
        $decoded = json_decode($s->getValue(), true);
        return is_array($decoded) ? $decoded : $default;
    }

    private function getSettingValue(string $name, string $default = ''): string
    {
        $s = $this->settingRepo->findOneByName($name);
        return $s ? $s->getValue() : $default;
    }

    private function updateSetting(string $name, string $value): void
    {
        $setting = $this->settingRepo->findOneByName($name) ?? new Setting();
        $setting->setName($name);
        $setting->setValue($value);
        $this->em->persist($setting);
    }
}