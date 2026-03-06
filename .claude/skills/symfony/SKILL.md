---
name: symfony
description: Symfony 8 expert patterns and best practices. Use when building backend APIs, entities, services, controllers, or database operations in adwords-budget/.
user-invocable: false
allowed-tools: Read, Glob, Grep
---

# Symfony 8 Expert Skills

## Project Context
- Backend location: `adwords-budget/`
- Symfony version: 8.0
- PHP version: 8.4+
- Stack: Doctrine ORM, Serializer, Validator

## Directory Structure
```
adwords-budget/
├── bin/console          # Symfony CLI
├── config/
│   ├── packages/        # Bundle configs
│   ├── routes.yaml      # Route imports
│   └── services.yaml    # Service definitions
├── public/index.php     # Entry point
├── src/
│   ├── Controller/      # HTTP layer
│   ├── Entity/          # Domain models
│   ├── Repository/      # Query logic
│   ├── DTO/             # Data transfer objects
│   ├── Service/         # Business logic
│   ├── EventListener/   # Event handlers
│   ├── Command/         # Console commands
│   └── Kernel.php
├── migrations/          # Database migrations
├── var/                 # Cache, logs
└── vendor/
```

## Commands

```bash
# Server
symfony serve                              # Start dev server
symfony serve -d                           # Daemon mode

# Cache
php bin/console cache:clear
php bin/console cache:warmup

# Database
php bin/console doctrine:database:create
php bin/console doctrine:schema:validate
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# Code generation
php bin/console make:controller Name
php bin/console make:entity Name

# Debug
php bin/console debug:router
php bin/console debug:container
```

## Controllers

### REST Controller
```php
<?php

namespace App\Controller;

use App\Service\ItemService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/items')]
class ItemController extends AbstractController
{
    public function __construct(
        private readonly ItemService $service,
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = min($request->query->getInt('limit', 20), 100);
        return $this->json($this->service->list($page, $limit));
    }

    #[Route('/{id<\d+>}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->service->find($id));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $item = $this->service->create($request->toArray());
        return $this->json($item, Response::HTTP_CREATED);
    }

    #[Route('/{id<\d+>}', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return $this->json($this->service->update($id, $request->toArray()));
    }

    #[Route('/{id<\d+>}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
```

### Route Parameters
```php
#[Route('/items/{id<\d+>}')]              // Numeric only
#[Route('/items/{slug}')]                  // Any string
#[Route('/items/{id}', defaults: ['id' => 1])]  // Default value
```

## Entities

### Basic Entity
```php
<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
#[ORM\Table(name: 'items')]
#[ORM\HasLifecycleCallbacks]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and setters...
}
```

### Relationships
```php
// Many-to-One
#[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'items')]
#[ORM\JoinColumn(nullable: false)]
private Category $category;

// One-to-Many
#[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'category', cascade: ['persist', 'remove'])]
private Collection $items;

// Many-to-Many
#[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'items')]
#[ORM\JoinTable(name: 'item_tags')]
private Collection $tags;
```

## Repositories

```php
<?php

namespace App\Repository;

use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    public function findByFilters(array $filters, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('i');

        if (isset($filters['status'])) {
            $qb->andWhere('i.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $qb->andWhere('i.name LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $qb->orderBy('i.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
```

## DTOs with Validation

```php
<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateItemDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Name is required')]
        #[Assert\Length(min: 2, max: 255)]
        public readonly string $name,

        #[Assert\Length(max: 5000)]
        public readonly ?string $description = null,

        #[Assert\PositiveOrZero]
        public readonly ?float $price = null,

        #[Assert\Choice(choices: ['draft', 'active', 'archived'])]
        public readonly string $status = 'draft',
    ) {}
}
```

### Common Validators
```php
#[Assert\NotBlank]
#[Assert\NotNull]
#[Assert\Email]
#[Assert\Url]
#[Assert\Length(min: 1, max: 255)]
#[Assert\Range(min: 0, max: 100)]
#[Assert\Positive]
#[Assert\PositiveOrZero]
#[Assert\Choice(choices: ['a', 'b', 'c'])]
#[Assert\Regex(pattern: '/^\d{4}-\d{2}-\d{2}$/')]
#[Assert\Valid]  // Validate nested objects
```

## Services

```php
<?php

namespace App\Service;

use App\DTO\CreateItemDto;
use App\Entity\Item;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ItemService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ItemRepository $repository,
        private readonly ValidatorInterface $validator,
    ) {}

    public function list(int $page, int $limit, array $filters = []): array
    {
        $items = $this->repository->findByFilters($filters, $page, $limit);
        return ['data' => $items, 'meta' => ['page' => $page, 'limit' => $limit]];
    }

    public function find(int $id): Item
    {
        $item = $this->repository->find($id);
        if (!$item) {
            throw new NotFoundHttpException("Item {$id} not found");
        }
        return $item;
    }

    public function create(array $data): Item
    {
        $dto = new CreateItemDto(...$data);
        $this->validate($dto);

        $item = new Item();
        $item->setName($dto->name);
        $item->setDescription($dto->description);
        $item->setPrice($dto->price);

        $this->em->persist($item);
        $this->em->flush();

        return $item;
    }

    public function update(int $id, array $data): Item
    {
        $item = $this->find($id);

        if (isset($data['name'])) $item->setName($data['name']);
        if (array_key_exists('description', $data)) $item->setDescription($data['description']);
        if (array_key_exists('price', $data)) $item->setPrice($data['price']);

        $this->em->flush();
        return $item;
    }

    public function delete(int $id): void
    {
        $item = $this->find($id);
        $this->em->remove($item);
        $this->em->flush();
    }

    private function validate(object $dto): void
    {
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }
    }
}
```

## Error Handling

```php
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

throw new NotFoundHttpException('Resource not found');        // 404
throw new BadRequestHttpException('Invalid request');         // 400
throw new AccessDeniedHttpException('Access denied');         // 403
```

## Events & Listeners

```php
// Event class
class ItemCreatedEvent
{
    public function __construct(public readonly Item $item) {}
}

// Listener
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ItemCreatedEvent::class)]
class ItemCreatedListener
{
    public function __invoke(ItemCreatedEvent $event): void
    {
        // Handle event
    }
}

// Dispatch
$this->eventDispatcher->dispatch(new ItemCreatedEvent($item));
```

## Console Commands

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:process-items', description: 'Process items')]
class ProcessItemsCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('type', InputArgument::REQUIRED, 'Item type');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->success('Done!');
        return Command::SUCCESS;
    }
}
```

## Best Practices

### PHP 8.4 Features
- Constructor property promotion
- Readonly properties and classes
- Typed properties everywhere
- Named arguments for clarity
- Attributes over annotations
- Match expressions
- Null-safe operator (`?->`)

### Architecture
- Keep controllers thin (validation, response)
- Business logic in services
- Database queries in repositories
- Use DTOs at boundaries
- Events for side effects
