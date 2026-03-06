<?php

declare(strict_types=1);

namespace App\Service;

class BudgetService
{
    /**
     * Get the active budget at a specific time
     * @param array<array{dateTime: \DateTimeInterface, value: float}> $sortedBudgets
     */
    public function getBudgetAtTime(array $sortedBudgets, \DateTimeInterface $targetTime): float
    {
        $currentBudget = 0.0;

        foreach ($sortedBudgets as $entry) {
            if ($entry['dateTime'] <= $targetTime) {
                $currentBudget = $entry['value'];
            } else {
                break;
            }
        }

        return $currentBudget;
    }

    /**
     * Calculate monthly cap: previous days' cap + max(current budget, already spent today)
     */
    public function calculateMonthlyCap(float $dailyBudget, float $dailySpent, float $previousDaysCap): float
    {
        return $previousDaysCap + max($dailyBudget, $dailySpent);
    }

    /**
     * Get month key for tracking monthly costs/caps
     */
    public function getMonthKey(\DateTimeInterface $date): string
    {
        return $date->format('Y-m');
    }

    /**
     * Sort and deduplicate budget entries, add auto-end if needed
     * @param array<array{dateTime: \DateTimeInterface, value: float, note: ?string}> $budgetEntries
     * @return array<array{dateTime: \DateTimeInterface, value: float, note: ?string}>
     */
    public function parseBudgetEntries(array $budgetEntries): array
    {
        // Deduplicate by timestamp (keep last)
        $deduped = [];
        foreach ($budgetEntries as $entry) {
            $key = $entry['dateTime']->format('Y-m-d H:i');
            $deduped[$key] = $entry;
        }

        // Sort by dateTime
        $sorted = array_values($deduped);
        usort($sorted, fn($a, $b) => $a['dateTime'] <=> $b['dateTime']);

        // If last entry doesn't have 0 budget, add one on first day of next month
        if (!empty($sorted)) {
            $lastEntry = end($sorted);
            if ($lastEntry['value'] !== 0.0) {
                $nextMonth = \DateTime::createFromInterface($lastEntry['dateTime']);
                $nextMonth->modify('first day of next month');
                $nextMonth->setTime(0, 0, 0);

                $sorted[] = [
                    'dateTime' => $nextMonth,
                    'value' => 0.0,
                    'note' => 'Auto-added end',
                ];
            }
        }

        return $sorted;
    }
}
