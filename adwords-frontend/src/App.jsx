import { BrowserRouter, Routes, Route, Link } from 'react-router-dom'
import Home from './pages/Home.jsx'
import Budgets from './pages/Budgets.jsx'
import './App.css'

function App() {
  return (
    <BrowserRouter>
      <nav className="main-nav">
        <Link to="/">Home</Link>
        <Link to="/budgets">Budgets</Link>
      </nav>
      <Routes>
        <Route path="/" element={<Home />} />
        <Route path="/budgets" element={<Budgets />} />
      </Routes>
    </BrowserRouter>
  )
}

export default App
