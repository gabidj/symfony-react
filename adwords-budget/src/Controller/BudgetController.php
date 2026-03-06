<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\BudgetEntryDto;
use App\DTO\GenerateCostsRequestDto;
use App\Service\CostGeneratorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class BudgetController extends AbstractController
{
    public function __construct(
        private readonly CostGeneratorService $costGeneratorService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('/costs/generate', name: 'api_costs_generate', methods: ['POST'])]
    public function generateCosts(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['entries']) || !is_array($data['entries'])) {
            return $this->json([
                'error' => 'Invalid request format. Expected {"entries": [...]}',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate entries
        $requestDto = new GenerateCostsRequestDto();
        $errors = [];

        foreach ($data['entries'] as $index => $entryData) {
            $entry = new BudgetEntryDto();
            $entry->date = $entryData['date'] ?? '';
            $entry->time = $entryData['time'] ?? '';
            $entry->value = $entryData['value'] ?? null;
            $entry->note = $entryData['note'] ?? null;

            $violations = $this->validator->validate($entry);
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $errors[] = sprintf(
                        'Entry %d: %s - %s',
                        $index,
                        $violation->getPropertyPath(),
                        $violation->getMessage()
                    );
                }
            } else {
                $requestDto->entries[] = $entry;
            }
        }

        if (!empty($errors)) {
            return $this->json([
                'error' => 'Validation failed',
                'details' => $errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        if (empty($requestDto->entries)) {
            return $this->json([
                'error' => 'No valid budget entries provided',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Convert DTOs to budget entries array
        $budgetEntries = array_map(fn(BudgetEntryDto $dto) => [
            'dateTime' => $dto->getDateTime(),
            'value' => $dto->getSanitizedValue(),
            'note' => $dto->note,
        ], $requestDto->entries);

        // Generate and persist costs
        $costEntities = $this->costGeneratorService->generateAndPersistCosts($budgetEntries);

        // Return generated costs
        $costs = array_map(fn($entity) => [
            'date' => $entity->getDateTime()->format('m.d.Y'),
            'time' => $entity->getDateTime()->format('H:i'),
            'budget' => $entity->getBudget(),
            'cost' => $entity->getCost(),
        ], $costEntities);

        return $this->json([
            'success' => true,
            'count' => count($costs),
            'costs' => $costs,
        ]);
    }

    #[Route('/report/daily', name: 'api_report_daily', methods: ['GET'])]
    public function dailyReport(): JsonResponse
    {
        $report = $this->costGeneratorService->generateDailyReport();

        if (empty($report)) {
            return $this->json([
                'error' => 'No cost data available. Generate costs first.',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'count' => count($report),
            'report' => $report,
        ]);
    }

    #[Route('/costs', name: 'api_costs_list', methods: ['GET'])]
    public function listCosts(): JsonResponse
    {
        $costs = $this->costGeneratorService->getStoredCosts();

        if (empty($costs)) {
            return $this->json([
                'error' => 'No cost data available. Generate costs first.',
            ], Response::HTTP_NOT_FOUND);
        }

        $data = array_map(fn($entity) => [
            'date' => $entity->getDateTime()->format('m.d.Y'),
            'time' => $entity->getDateTime()->format('H:i'),
            'budget' => $entity->getBudget(),
            'cost' => $entity->getCost(),
        ], $costs);

        return $this->json([
            'success' => true,
            'count' => count($data),
            'costs' => $data,
        ]);
    }
}
