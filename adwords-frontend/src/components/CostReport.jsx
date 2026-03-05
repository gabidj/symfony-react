import './CostReport.css'

function CostReport({ generatedCosts }) {
  if (!generatedCosts || generatedCosts.length === 0) {
    return null
  }

  // Aggregate costs by date
  const dailySummary = {}

  for (const row of generatedCosts) {
    if (!dailySummary[row.date]) {
      dailySummary[row.date] = {
        date: row.date,
        budget: row.budget,
        totalCosts: 0
      }
    }
    dailySummary[row.date].totalCosts += parseFloat(row.actualCost)
  }

  const sortedDays = Object.values(dailySummary).sort((a, b) => {
    const [monthA, dayA, yearA] = a.date.split('.')
    const [monthB, dayB, yearB] = b.date.split('.')
    return new Date(yearA, monthA - 1, dayA) - new Date(yearB, monthB - 1, dayB)
  })

  const handleExport = () => {
    const headers = ['Date', 'Budget', 'Costs']
    const csvRows = [
      headers.join(','),
      ...sortedDays.map(row =>
        [row.date, row.budget, row.totalCosts.toFixed(2)].join(',')
      )
    ]
    const csvContent = csvRows.join('\n')

    const blob = new Blob([csvContent], { type: 'text/csv' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'daily_cost_report.csv'
    a.click()
    URL.revokeObjectURL(url)
  }

  return (
    <div className="cost-report">
      <div className="report-header">
        <h3>Daily Cost Report</h3>
        <button onClick={handleExport} className="export-btn">
          Export CSV
        </button>
      </div>

      <div className="table-container">
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Budget</th>
              <th>Costs</th>
            </tr>
          </thead>
          <tbody>
            {sortedDays.map((row) => (
              <tr key={row.date} className={row.totalCosts === 0 ? 'zero-row' : ''}>
                <td>{row.date}</td>
                <td>{row.budget}</td>
                <td>{row.totalCosts.toFixed(2)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}

export default CostReport