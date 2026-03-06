<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class BudgetEntryDto
{
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^\d{2}\.\d{2}\.\d{4}$/',
        message: 'Date must be in MM.DD.YYYY format'
    )]
    public string $date;

    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^\d{2}:\d{2}$/',
        message: 'Time must be in HH:MM format'
    )]
    public string $time;

    #[Assert\NotNull]
    public mixed $value;

    public ?string $note = null;

    public function getDateTime(): \DateTimeInterface
    {
        [$month, $day, $year] = explode('.', $this->date);
        [$hours, $minutes] = explode(':', $this->time);

        return new \DateTime(sprintf(
            '%s-%s-%s %s:%s:00',
            $year, $month, $day, $hours, $minutes
        ));
    }

    public function getSanitizedValue(): float
    {
        $parsed = is_numeric($this->value) ? (float) $this->value : 0.0;
        return $parsed < 0 ? 0.0 : $parsed;
    }
}
