---
name: contract
description: Design an API contract for a new feature. Creates contract JSON in .claude/contracts/ for use by /fullstack, /frontend, or /backend.
---

# Create API Contract

Design an API contract for a new feature.

## Instructions

1. Ask the user what feature they want to build
2. Gather requirements:
   - What resources/entities are involved?
   - What operations (list, create, update, delete, custom)?
   - What fields and validation rules?
   - Any relationships between resources?
3. Read `.claude/agents/contract.md` for contract schema
4. Create contract in `.claude/contracts/{feature-name}.json`
5. Show contract to user for review
6. **STOP** - do not implement

## After Creating

Tell the user:
```
Contract created at .claude/contracts/{feature-name}.json

To implement, run:
  /fullstack {feature-name}

Or implement separately:
  /frontend {feature-name}
  /backend {feature-name}
```
