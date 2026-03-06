---
name: backend
description: Implement Symfony backend from a contract or requirements.
argument-hint: [contract-name]
---

# Backend Implementation

Implement Symfony backend from a contract or requirements.

**Usage:** `/backend {contract-name}` or `/backend`

## Instructions

1. If contract name provided ($ARGUMENTS):
   - Read `.claude/contracts/$ARGUMENTS.json`
2. If no contract:
   - Ask what feature to implement
   - Gather API requirements

3. Read these files:
   - `.claude/skills/symfony/SKILL.md` - Symfony 8 patterns
   - `.claude/agents/backend.md` - Implementation workflow

4. Implement in `adwords-budget/`:
   - Entity in `src/Entity/`
   - Repository in `src/Repository/` (if custom queries needed)
   - DTO in `src/DTO/`
   - Service in `src/Service/`
   - Controller in `src/Controller/`

5. Create migration:
   ```bash
   cd adwords-budget
   php bin/console make:migration
   php bin/console doctrine:migrations:migrate
   ```

6. Follow Symfony 8 and PHP 8.4 best practices from skills

## Checklist
- [ ] Entity with proper ORM mapping
- [ ] DTO with validation constraints
- [ ] Service with business logic
- [ ] Controller with REST endpoints
- [ ] Migration created
- [ ] Error handling with HTTP exceptions
