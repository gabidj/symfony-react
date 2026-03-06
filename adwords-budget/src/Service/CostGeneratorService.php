<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CostEvent;
use Doctrine\ORM\EntityManagerInterface;

class CostGeneratorService
{
    public function __construct(
        private readonly BudgetService $budgetService,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * Generate random times in 5-minute intervals
     * @return array<array{hours: int, minutes: int}>
     */
    private function generateRandomTimes(int $count): array
    {
        $slots = [];
        $totalSlots = 288; // 24 hours * 12 slots per hour (5 min each)

        while (count($slots) < $count) {
            $slot = random_int(0, $totalSlots - 1);
            if (!in_array($slot, $slots, true)) {
                $slots[] = $slot;
            }
        }

        sort($slots);

        return array_map(fn($slot) => [
            'hours' => intdiv($slot, 12),
            'minutes' => ($slot % 12) * 5,
        ], $slots);
    }

    /**
     * Generate a single cost event
     */
    private function generateCostEvent(
        \DateTimeInterface $costTime,
        float $dailyBudget,
        float $dailyLimit,
        float $dailyCumulative,
        float $monthlyCap,
        float $monthlyCumulative,
    ): array {
        // Generate random cost between 0.10 and 20.00
        $proposedCost = (random_int(10, 2000)) / 100;

        $remainingDaily = max(0, $dailyLimit - $dailyCumulative);
        $remainingMonthly = max(0, $monthlyCap - $monthlyCumulative);
        $remainingLimit = min($remainingDaily, $remainingMonthly);

        $status = 'rejected';
        $actualCost = 0.0;

        if ($dailyBudget > 0 && $proposedCost <= $remainingLimit) {
            $status = 'accepted';
            $actualCost = $proposedCost;
        }

        return [
            'dateTime' => $costTime,
            'budget' => $dailyBudget,
            'proposedCost' => $proposedCost,
            'actualCost' => $actualCost,
            'status' => $status,
        ];
    }

    /**
     * Generate costs for a single day
     */
    private function generateCostsForDay(
        \DateTimeInterface $date,
        array $sortedBudgets,
        \DateTimeInterface $endDate,
        float $previousDaysCap,
        float $monthlyStartCost,
    ): array {
        // Generate 1-10 random cost events for this day
        $numCosts = random_int(1, 10);
        $randomTimes = $this->generateRandomTimes($numCosts);

        $dayCosts = [];
        $dailyCumulative = 0.0;
        $monthlyCumulative = $monthlyStartCost;

        foreach ($randomTimes as $timeSlot) {
            $costTime = \DateTime::createFromInterface($date);
            $costTime->setTime($timeSlot['hours'], $timeSlot['minutes'], 0);

            if ($costTime > $endDate) {
                break;
            }

            // Get budget at the actual time of this cost event
            $dailyBudget = $this->budgetService->getBudgetAtTime($sortedBudgets, $costTime);
            $dailyLimit = $dailyBudget * 2;

            // Monthly cap = previous days' cap + max(current budget, already spent today)
            $monthlyCap = $this->budgetService->calculateMonthlyCap(
                $dailyBudget,
                $dailyCumulative,
                $previousDaysCap
            );

            $event = $this->generateCostEvent(
                $costTime,
                $dailyBudget,
                $dailyLimit,
                $dailyCumulative,
                $monthlyCap,
                $monthlyCumulative
            );

            $dailyCumulative += $event['actualCost'];
            $monthlyCumulative += $event['actualCost'];

            $dayCosts[] = array_merge($event, [
                'dailyCumulative' => $dailyCumulative,
                'dailyLimit' => $dailyLimit,
                'remainingLimit' => max(0, $dailyLimit - $dailyCumulative),
                'monthlyCap' => $monthlyCap,
                'monthlyCost' => $monthlyCumulative,
            ]);
        }

        // Final daily cap contribution = max(last budget of day, total spent)
        $dayEnd = \DateTime::createFromInterface($date);
        $dayEnd->setTime(23, 59, 59);
        $endOfDayBudget = $this->budgetService->getBudgetAtTime($sortedBudgets, $dayEnd);
        $dailyCapContribution = max($endOfDayBudget, $dailyCumulative);

        return [
            'dayCosts' => $dayCosts,
            'monthlyEndCost' => $monthlyCumulative,
            'dailyCapContribution' => $dailyCapContribution,
        ];
    }

    /**
     * Generate all costs from budget entries
     * @param array<array{dateTime: \DateTimeInterface, value: float, note: ?string}> $budgetEntries
     */
    public function generateCosts(array $budgetEntries): array
    {
        if (empty($budgetEntries)) {
            return [];
        }

        $sortedBudgets = $this->budgetService->parseBudgetEntries($budgetEntries);

        $startDate = \DateTime::createFromInterface($sortedBudgets[0]['dateTime']);
        $startDate->setTime(0, 0, 0);

        $endDate = \DateTime::createFromInterface(end($sortedBudgets)['dateTime']);
        $endDate->setTime(23, 59, 0);

        $costs = [];
        $iterDate = clone $startDate;
        $monthlyCosts = [];
        $monthlyCaps = [];

        while ($iterDate <= $endDate) {
            $monthKey = $this->budgetService->getMonthKey($iterDate);
            $previousDaysCap = $monthlyCaps[$monthKey] ?? 0.0;
            $monthlyStartCost = $monthlyCosts[$monthKey] ?? 0.0;

            $result = $this->generateCostsForDay(
                $iterDate,
                $sortedBudgets,
                $endDate,
                $previousDaysCap,
                $monthlyStartCost
            );

            $monthlyCosts[$monthKey] = $result['monthlyEndCost'];
            $monthlyCaps[$monthKey] = $previousDaysCap + $result['dailyCapContribution'];

            $costs = array_merge($costs, $result['dayCosts']);
            $iterDate->modify('+1 day');
        }

        return $costs;
    }

    /**
     * Generate costs and persist to database
     * @param array<array{dateTime: \DateTimeInterface, value: float, note: ?string}> $budgetEntries
     * @return CostEvent[]
     */
    public function generateAndPersistCosts(array $budgetEntries): array
    {
        // Delete old cost events
        $this->entityManager->createQuery('DELETE FROM App\Entity\CostEvent')->execute();

        $costsData = $this->generateCosts($budgetEntries);
        $entities = [];

        foreach ($costsData as $costData) {
            $entity = new CostEvent();
            $entity->setDateTime($costData['dateTime']);
            $entity->setBudget(number_format($costData['budget'], 2, '.', ''));
            $entity->setCost(number_format($costData['actualCost'], 2, '.', ''));

            $this->entityManager->persist($entity);
            $entities[] = $entity;
        }

        $this->entityManager->flush();

        return $entities;
    }

    /**
     * Get stored cost events
     * @return CostEvent[]
     */
    public function getStoredCosts(): array
    {
        return $this->entityManager
            ->getRepository(CostEvent::class)
            ->findBy([], ['dateTime' => 'ASC']);
    }

    /**
     * Generate daily report from stored costs
     */
    public function generateDailyReport(): array
    {
        $costs = $this->getStoredCosts();
        $dailySummary = [];

        foreach ($costs as $cost) {
            $dateKey = $cost->getDateTime()->format('m.d.Y');

            if (!isset($dailySummary[$dateKey])) {
                $dailySummary[$dateKey] = [
                    'date' => $dateKey,
                    'budget' => $cost->getBudget(),
                    'totalCosts' => 0.0,
                ];
            }

            $dailySummary[$dateKey]['totalCosts'] += $cost->getCostAsFloat();
        }

        // Format totals
        foreach ($dailySummary as &$day) {
            $day['totalCosts'] = number_format($day['totalCosts'], 2, '.', '');
        }

        return array_values($dailySummary);
    }
}
