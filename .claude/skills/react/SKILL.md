---
name: react
description: React 19 expert patterns and best practices. Use when building frontend components, hooks, forms, routing, or API integration in adwords-frontend/.
user-invocable: false
allowed-tools: Read, Glob, Grep
---

# React 19 Expert Skills

## Project Context
- Frontend location: `adwords-frontend/`
- React version: 19
- Build tool: Vite 7
- Router: React Router DOM 7

## Directory Structure
```
adwords-frontend/
├── public/              # Static assets
├── src/
│   ├── api/             # API service modules
│   ├── components/      # Reusable components
│   ├── hooks/           # Custom hooks
│   ├── pages/           # Page components
│   ├── context/         # React contexts
│   ├── utils/           # Utility functions
│   ├── App.jsx          # Root component
│   └── main.jsx         # Entry point
├── index.html
├── vite.config.js
├── eslint.config.js
└── package.json
```

## Commands

```bash
cd adwords-frontend

npm install              # Install dependencies
npm run dev              # Start dev server (port 5173)
npm run build            # Production build
npm run preview          # Preview production build
npm run lint             # Run ESLint
```

## Components

### Function Component Patterns
```jsx
// Basic component
export default function Button({ children, onClick, variant = 'primary' }) {
  return (
    <button className={`btn btn-${variant}`} onClick={onClick}>
      {children}
    </button>
  );
}

// With TypeScript-style prop destructuring
export default function Card({ title, children, footer = null }) {
  return (
    <div className="card">
      <h2>{title}</h2>
      <div className="card-body">{children}</div>
      {footer && <div className="card-footer">{footer}</div>}
    </div>
  );
}
```

### Component with State & Effects
```jsx
import { useState, useEffect, useCallback } from 'react';

export default function DataList({ endpoint, renderItem }) {
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchData = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await fetch(endpoint);
      if (!response.ok) throw new Error('Failed to fetch');
      const result = await response.json();
      setData(result.data || result);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, [endpoint]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  if (loading) return <div className="loading">Loading...</div>;
  if (error) return <div className="error">Error: {error}</div>;
  if (data.length === 0) return <div className="empty">No items found</div>;

  return (
    <ul>
      {data.map((item, index) => (
        <li key={item.id || index}>{renderItem(item)}</li>
      ))}
    </ul>
  );
}
```

### Controlled Form Component
```jsx
import { useState } from 'react';

export default function ItemForm({ initialData = {}, onSubmit, onCancel }) {
  const [formData, setFormData] = useState({
    name: initialData.name || '',
    description: initialData.description || '',
    price: initialData.price || '',
    status: initialData.status || 'draft',
  });
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  const handleChange = (e) => {
    const { name, value, type } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'number' ? (value === '' ? '' : Number(value)) : value,
    }));
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: null }));
    }
  };

  const validate = () => {
    const newErrors = {};
    if (!formData.name.trim()) newErrors.name = 'Name is required';
    if (formData.price && formData.price < 0) newErrors.price = 'Price must be positive';
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!validate()) return;

    setSubmitting(true);
    try {
      await onSubmit(formData);
    } catch (err) {
      setErrors({ submit: err.message });
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      {/* Form fields... */}
    </form>
  );
}
```

## React 19 Features

### use() Hook
```jsx
import { use, Suspense } from 'react';

function ItemDetails({ itemPromise }) {
  const item = use(itemPromise);
  return <div>{item.name}</div>;
}

// Usage with Suspense
<Suspense fallback={<Loading />}>
  <ItemDetails itemPromise={fetchItem(id)} />
</Suspense>
```

### useActionState (Form Actions)
```jsx
import { useActionState } from 'react';

async function createItem(prevState, formData) {
  const name = formData.get('name');
  try {
    const response = await fetch('/api/items', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name }),
    });
    if (!response.ok) {
      return { error: 'Failed to create' };
    }
    return { success: true, item: await response.json() };
  } catch (err) {
    return { error: err.message };
  }
}

function CreateItemForm() {
  const [state, formAction, isPending] = useActionState(createItem, null);

  return (
    <form action={formAction}>
      <input name="name" required />
      <button disabled={isPending}>
        {isPending ? 'Creating...' : 'Create'}
      </button>
      {state?.error && <p className="error">{state.error}</p>}
    </form>
  );
}
```

### useOptimistic
```jsx
import { useOptimistic } from 'react';

function TodoList({ todos, onToggle }) {
  const [optimisticTodos, addOptimistic] = useOptimistic(
    todos,
    (state, id) => state.map(t =>
      t.id === id ? { ...t, done: !t.done, pending: true } : t
    )
  );

  async function handleToggle(id) {
    addOptimistic(id);
    await onToggle(id);
  }

  return (
    <ul>
      {optimisticTodos.map(todo => (
        <li key={todo.id} style={{ opacity: todo.pending ? 0.5 : 1 }}>
          {todo.text}
        </li>
      ))}
    </ul>
  );
}
```

### useTransition
```jsx
import { useState, useTransition } from 'react';

function SearchResults() {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);
  const [isPending, startTransition] = useTransition();

  function handleSearch(e) {
    const value = e.target.value;
    setQuery(value);
    startTransition(async () => {
      const data = await searchAPI(value);
      setResults(data);
    });
  }

  return (
    <div>
      <input value={query} onChange={handleSearch} />
      {isPending && <span>Searching...</span>}
      <ul>{results.map(r => <li key={r.id}>{r.name}</li>)}</ul>
    </div>
  );
}
```

## Custom Hooks

### useFetch
```jsx
import { useState, useEffect, useCallback } from 'react';

export function useFetch(url, options = {}) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const refetch = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await fetch(url, options);
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      setData(await response.json());
    } catch (err) {
      setError(err);
    } finally {
      setLoading(false);
    }
  }, [url, JSON.stringify(options)]);

  useEffect(() => { refetch(); }, [refetch]);

  return { data, loading, error, refetch };
}
```

### useDebounce
```jsx
import { useState, useEffect } from 'react';

export function useDebounce(value, delay = 300) {
  const [debouncedValue, setDebouncedValue] = useState(value);

  useEffect(() => {
    const timer = setTimeout(() => setDebouncedValue(value), delay);
    return () => clearTimeout(timer);
  }, [value, delay]);

  return debouncedValue;
}
```

## API Service Module

```jsx
// src/api/client.js
const API_BASE = '/api';

async function request(endpoint, options = {}) {
  const url = `${API_BASE}${endpoint}`;
  const config = {
    headers: { 'Content-Type': 'application/json', ...options.headers },
    ...options,
  };

  if (config.body && typeof config.body === 'object') {
    config.body = JSON.stringify(config.body);
  }

  const response = await fetch(url, config);
  if (!response.ok) {
    const error = await response.json().catch(() => ({}));
    throw new Error(error.message || `HTTP ${response.status}`);
  }
  if (response.status === 204) return null;
  return response.json();
}

export const api = {
  get: (endpoint, params) => {
    const query = params ? '?' + new URLSearchParams(params) : '';
    return request(`${endpoint}${query}`);
  },
  post: (endpoint, body) => request(endpoint, { method: 'POST', body }),
  put: (endpoint, body) => request(endpoint, { method: 'PUT', body }),
  patch: (endpoint, body) => request(endpoint, { method: 'PATCH', body }),
  delete: (endpoint) => request(endpoint, { method: 'DELETE' }),
};
```

```jsx
// src/api/items.js
import { api } from './client';

export const itemsApi = {
  list: (params) => api.get('/items', params),
  get: (id) => api.get(`/items/${id}`),
  create: (data) => api.post('/items', data),
  update: (id, data) => api.put(`/items/${id}`, data),
  delete: (id) => api.delete(`/items/${id}`),
};
```

## React Router DOM 7

### Route Configuration
```jsx
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Layout />}>
          <Route index element={<Home />} />
          <Route path="items" element={<Items />} />
          <Route path="items/:id" element={<ItemDetail />} />
          <Route path="*" element={<NotFound />} />
        </Route>
      </Routes>
    </BrowserRouter>
  );
}
```

### Navigation & Params
```jsx
import { useParams, useSearchParams, useNavigate } from 'react-router-dom';

function ItemDetail() {
  const { id } = useParams();
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();

  function handleDelete() {
    navigate('/items', { replace: true });
  }

  return <div>Item {id}</div>;
}
```

## Context API

```jsx
import { createContext, useContext, useReducer } from 'react';

const AppContext = createContext(null);

export function AppProvider({ children }) {
  const [state, dispatch] = useReducer(reducer, initialState);
  return <AppContext.Provider value={{ state, dispatch }}>{children}</AppContext.Provider>;
}

export function useApp() {
  const context = useContext(AppContext);
  if (!context) throw new Error('useApp must be used within AppProvider');
  return context;
}
```

## Best Practices

### Component Design
- One component per file
- Keep components focused (single responsibility)
- Extract reusable logic to hooks

### State Management
- `useState` for local UI state
- `useReducer` for complex state logic
- Context for global state (auth, theme)

### Performance
- `React.memo()` for expensive pure components
- `useMemo()` for expensive calculations
- `useCallback()` for stable callbacks
- Lazy loading with `React.lazy()` and `Suspense`
