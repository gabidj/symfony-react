<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class GenerateCostsRequestDto
{
    /**
     * @var BudgetEntryDto[]
     */
    #[Assert\NotBlank]
    #[Assert\Count(min: 1, minMessage: 'At least one budget entry is required')]
    #[Assert\Valid]
    public array $entries = [];
}
