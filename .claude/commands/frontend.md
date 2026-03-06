---
name: frontend
description: Implement React frontend from a contract or requirements.
argument-hint: [contract-name]
---

# Frontend Implementation

Implement React frontend from a contract or requirements.

**Usage:** `/frontend {contract-name}` or `/frontend`

## Instructions

1. If contract name provided ($ARGUMENTS):
   - Read `.claude/contracts/$ARGUMENTS.json`
2. If no contract:
   - Ask what feature to implement
   - Gather UI requirements

3. Read these files:
   - `.claude/skills/react/SKILL.md` - React 19 patterns
   - `.claude/agents/frontend.md` - Implementation workflow

4. Implement in `adwords-frontend/`:
   - API service module in `src/api/`
   - Custom hooks in `src/hooks/` (if needed)
   - Components in `src/components/`
   - Pages in `src/pages/`
   - Update routes in `App.jsx`

5. Follow React 19 best practices from skills

## Checklist
- [ ] API service matches contract/requirements
- [ ] Components handle loading/error/empty states
- [ ] Forms have validation
- [ ] Routes configured
- [ ] Proper error handling
