---
name: fullstack
description: Implement frontend and backend in parallel from a contract.
argument-hint: <contract-name>
---

# Fullstack Implementation

Implement frontend and backend in parallel from a contract.

**Usage:** `/fullstack {contract-name}`

## Instructions

1. Read contract from `.claude/contracts/$ARGUMENTS.json`
2. If not found, list available contracts and suggest `/contract` first
3. Read `.claude/agents/fullstack.md` for orchestration workflow
4. Launch TWO parallel agents using Task tool:

**Backend Agent:**
- Reads: contract, `.claude/skills/symfony/SKILL.md`, `.claude/agents/backend.md`
- Implements in `adwords-budget/`: Entity, DTO, Service, Controller
- Creates migration

**Frontend Agent:**
- Reads: contract, `.claude/skills/react/SKILL.md`, `.claude/agents/frontend.md`
- Implements in `adwords-frontend/`: API service, hooks, components, pages

5. Run both agents in parallel
6. Summarize results from both

## Example
```
/fullstack budgets
```
Implements `.claude/contracts/budgets.json` with parallel frontend and backend agents.
