In this project you will work on a React application and a Symfony application.

## Project Structure
- **Frontend**: `adwords-frontend/` - React 19 with Vite
- **Backend**: `adwords-budget/` - Symfony 8 with PHP 8.4

## Agents (Contract-First Development)
For fullstack features, use the contract-first workflow:
1. Define contract in `.claude/contracts/{feature}.json`
2. Run frontend and backend agents in parallel

Agent files:
- `.claude/agents/fullstack.md` - Orchestration workflow
- `.claude/agents/contract.md` - Contract format
- `.claude/agents/frontend.md` - React implementation agent
- `.claude/agents/backend.md` - Symfony implementation agent

## Skills
Framework knowledge for agents:
- `.claude/skills/react.md` - React 19, Vite, React Router patterns
- `.claude/skills/symfony.md` - Symfony 8, Doctrine, PHP 8.4 patterns

## Guidelines
- Use React skills when working on the frontend (`adwords-frontend/`)
- Use Symfony skills when working on the backend (`adwords-budget/`)

## Rules
* Always end files with exactly one empty line.


