import { useState } from 'react'
import './CostGenerator.css'
import CostReport from './CostReport'

function parseDateTime(dateStr, timeStr) {
  const [month, day, year] = dateStr.split('.')
  const [hours, minutes] = timeStr.split(':')
  return new Date(year, month - 1, day, hours, minutes)
}

function formatDate(date) {
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const year = date.getFullYear()
  return `${month}.${day}.${year}`
}

function formatTime(date) {
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${hours}:${minutes}`
}

function getBudgetAtTime(budgetEntries, targetTime) {
  let currentBudget = 0
  for (const entry of budgetEntries) {
    if (entry.dateTime <= targetTime) {
      currentBudget = entry.value
    } else {
      break
    }
  }
  return currentBudget
}

function getMonthKey(date) {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
}

function calculateMonthlyCap(date, sortedBudgets) {
  // Sum daily budgets from first day of month up to and including current day
  const year = date.getFullYear()
  const month = date.getMonth()
  const firstDay = new Date(year, month, 1)

  let monthlyCap = 0

  const iterDay = new Date(firstDay)
  while (iterDay <= date) {
    const dayStart = new Date(iterDay)
    dayStart.setHours(0, 0, 0, 0)
    const dailyBudget = getBudgetAtTime(sortedBudgets, dayStart)
    monthlyCap += dailyBudget
    iterDay.setDate(iterDay.getDate() + 1)
  }

  return monthlyCap
}

function generateRandomTimes(count) {
  // Generate random times in 5-minute intervals (0-287 slots per day)
  const slots = []
  const totalSlots = 288 // 24 hours * 12 slots per hour (5 min each)

  while (slots.length < count) {
    const slot = Math.floor(Math.random() * totalSlots)
    if (!slots.includes(slot)) {
      slots.push(slot)
    }
  }

  return slots.sort((a, b) => a - b).map(slot => {
    const hours = Math.floor(slot / 12)
    const minutes = (slot % 12) * 5
    return { hours, minutes }
  })
}

function parseBudgetEntries(budgetEntries) {
  const sortedBudgets = [...budgetEntries].sort((a, b) => a.dateTime - b.dateTime)

  // If last entry doesn't have 0 budget, add one on first day of next month
  const lastEntry = sortedBudgets[sortedBudgets.length - 1]
  if (lastEntry && lastEntry.value !== 0) {
    const nextMonth = new Date(lastEntry.dateTime)
    nextMonth.setMonth(nextMonth.getMonth() + 1)
    nextMonth.setDate(1)
    nextMonth.setHours(0, 0, 0, 0)
    sortedBudgets.push({
      dateTime: nextMonth,
      value: 0,
      note: 'Auto-added end'
    })
  }

  return sortedBudgets
}

function generateCostEvent(costTime, dailyBudget, dailyLimit, dailyCumulative, monthlyCap, monthlyCumulative) {
  // Generate random cost between 0.10 and 20.00
  const proposedCost = Math.round(10 + Math.random() * 1990) / 100
  const remainingDaily = Math.max(0, dailyLimit - dailyCumulative)
  const remainingMonthly = Math.max(0, monthlyCap - monthlyCumulative)
  const remainingLimit = Math.min(remainingDaily, remainingMonthly)

  let status = 'rejected'
  let actualCost = 0

  if (dailyBudget > 0 && proposedCost <= remainingLimit) {
    status = 'accepted'
    actualCost = proposedCost
  }

  return {
    date: formatDate(costTime),
    time: formatTime(costTime),
    budget: dailyBudget,
    proposedCost: proposedCost.toFixed(2),
    actualCost: actualCost.toFixed(2),
    status,
    actualCostNum: actualCost
  }
}

function generateCostsForDay(date, sortedBudgets, endDate, monthlyCap, monthlyStartCost) {
  const dayStart = new Date(date)
  dayStart.setHours(0, 0, 0, 0)

  const dailyBudget = getBudgetAtTime(sortedBudgets, dayStart)
  const dailyLimit = dailyBudget * 2

  // Generate 1-10 random cost events for this day
  const numCosts = Math.floor(Math.random() * 10) + 1
  const randomTimes = generateRandomTimes(numCosts)

  const dayCosts = []
  let dailyCumulative = 0
  let monthlyCumulative = monthlyStartCost

  for (const timeSlot of randomTimes) {
    const costTime = new Date(date)
    costTime.setHours(timeSlot.hours, timeSlot.minutes, 0, 0)

    if (costTime > endDate) break

    const event = generateCostEvent(costTime, dailyBudget, dailyLimit, dailyCumulative, monthlyCap, monthlyCumulative)
    dailyCumulative += event.actualCostNum
    monthlyCumulative += event.actualCostNum

    dayCosts.push({
      ...event,
      dailyCumulative: dailyCumulative.toFixed(2),
      dailyLimit: dailyLimit.toFixed(2),
      remainingLimit: Math.max(0, dailyLimit - dailyCumulative).toFixed(2),
      monthlyCap: monthlyCap.toFixed(2),
      monthlyCost: monthlyCumulative.toFixed(2)
    })
  }

  return { dayCosts, monthlyEndCost: monthlyCumulative }
}

function generateCosts(budgetEntries) {
  if (budgetEntries.length === 0) return []

  const sortedBudgets = parseBudgetEntries(budgetEntries)

  const startDate = new Date(sortedBudgets[0].dateTime)
  startDate.setHours(0, 0, 0, 0)

  const endDate = new Date(sortedBudgets.at(-1).dateTime)
  endDate.setHours(23, 59, 0, 0)

  const costs = []
  const iterDate = new Date(startDate)
  const monthlyCosts = {} // Track cumulative cost per month

  while (iterDate <= endDate) {
    const monthKey = getMonthKey(iterDate)
    const monthlyCap = calculateMonthlyCap(iterDate, sortedBudgets)
    const monthlyStartCost = monthlyCosts[monthKey] || 0

    const { dayCosts, monthlyEndCost } = generateCostsForDay(
      iterDate, sortedBudgets, endDate, monthlyCap, monthlyStartCost
    )

    monthlyCosts[monthKey] = monthlyEndCost
    costs.push(...dayCosts)
    iterDate.setDate(iterDate.getDate() + 1)
  }

  return costs
}

function CostGenerator({ budgetData }) {
  const [generatedCosts, setGeneratedCosts] = useState(null)
  const [error, setError] = useState('')

  const handleGenerate = () => {
    try {
      const budgetEntries = budgetData.rows
        .filter(row => row.date && row.time && row.value !== '')
        .map(row => ({
          dateTime: parseDateTime(row.date, row.time),
          value: parseFloat(row.value) || 0,
          note: row.note || ''
        }))
        .sort((a, b) => a.dateTime - b.dateTime)

      if (budgetEntries.length === 0) {
        setError('No valid budget entries found')
        return
      }

      const costs = generateCosts(budgetEntries)
      setGeneratedCosts(costs)
      setError('')
    } catch (err) {
      setError('Failed to generate costs: ' + err.message)
    }
  }

  const handleExport = () => {
    if (!generatedCosts) return

    const headers = ['date', 'time', 'budget', 'proposed_cost', 'actual_cost', 'status', 'daily_cumulative', 'daily_limit', 'remaining_limit', 'monthly_cap', 'monthly_cost']
    const csvRows = [
      headers.join(','),
      ...generatedCosts.map(row =>
        [row.date, row.time, row.budget, row.proposedCost, row.actualCost, row.status, row.dailyCumulative, row.dailyLimit, row.remainingLimit, row.monthlyCap, row.monthlyCost].join(',')
      )
    ]
    const csvContent = csvRows.join('\n')

    const blob = new Blob([csvContent], { type: 'text/csv' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'generated_costs.csv'
    a.click()
    URL.revokeObjectURL(url)
  }

  return (
    <div className="cost-generator">
      <h3>Generate Random Costs</h3>
      <p className="hint">
        Generates 1-10 random costs per day at 5-minute intervals.
        Costs range from $0.00 to $20.00. Daily limit is 2x budget.
        Monthly cap is sum of daily budgets for the month.
        Costs exceeding daily limit or monthly cap are rejected.
      </p>

      <button onClick={handleGenerate} className="generate-btn">
        Generate Random Costs
      </button>

      {error && <div className="error-message">{error}</div>}

      <CostReport generatedCosts={generatedCosts} />

      {generatedCosts && (
        <div className="costs-results">
          <div className="costs-header">
            <h4>Detailed Costs ({generatedCosts.length} events)</h4>
            <button onClick={handleExport} className="export-btn">
              Export CSV
            </button>
          </div>
          <div className="table-container">
            <table>
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Time</th>
                  <th>Budget</th>
                  <th>Proposed</th>
                  <th>Actual</th>
                  <th>Status</th>
                  <th>Daily Cum.</th>
                  <th>Daily Limit</th>
                  <th>Remaining</th>
                  <th>Monthly Cap</th>
                  <th>Monthly Cost</th>
                </tr>
              </thead>
              <tbody>
                {generatedCosts.map((row, i) => (
                  <tr key={i} className={row.budget === 0 ? 'zero-budget' : (row.status === 'rejected' ? 'rejected-cost' : 'accepted-cost')}>
                    <td>{row.date}</td>
                    <td>{row.time}</td>
                    <td>{row.budget}</td>
                    <td>{row.proposedCost}</td>
                    <td>{row.actualCost}</td>
                    <td className={`status-${row.status}`}>{row.status}</td>
                    <td>{row.dailyCumulative}</td>
                    <td>{row.dailyLimit}</td>
                    <td>{row.remainingLimit}</td>
                    <td>{row.monthlyCap}</td>
                    <td>{row.monthlyCost}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  )
}

export default CostGenerator
