import { useState } from 'react'
import CostGenerator from '../components/CostGenerator.jsx'
import './Budgets.css'

function parseCSV(text) {
  const lines = text.trim().split('\n')
  if (lines.length === 0) return { headers: [], rows: [] }

  const headers = lines[0].split(',').map(h => h.trim())
  const rows = lines.slice(1).map(line => {
    const values = line.split(',').map(v => v.trim())
    return headers.reduce((obj, header, index) => {
      obj[header] = values[index] || ''
      return obj
    }, {})
  })

  return { headers, rows }
}

function Budgets() {
  const [csvText, setCsvText] = useState('')
  const [parsedData, setParsedData] = useState(null)
  const [error, setError] = useState('')

  const handleParse = () => {
    try {
      if (!csvText.trim()) {
        setError('Please enter CSV data')
        setParsedData(null)
        return
      }

      const result = parseCSV(csvText)
      if (result.headers.length === 0) {
        setError('No valid CSV data found')
        setParsedData(null)
        return
      }

      // Sort rows by date and time
      result.rows.sort((a, b) => {
        const [monthA, dayA, yearA] = (a.date || '').split('.')
        const [monthB, dayB, yearB] = (b.date || '').split('.')
        const [hoursA, minsA] = (a.time || '00:00').split(':')
        const [hoursB, minsB] = (b.time || '00:00').split(':')
        return new Date(yearA, monthA - 1, dayA, hoursA, minsA) - new Date(yearB, monthB - 1, dayB, hoursB, minsB)
      })

      // If last entry doesn't have 0 budget, add auto-added end row
      const lastRow = result.rows[result.rows.length - 1]
      if (lastRow && parseFloat(lastRow.value) !== 0) {
        const [month, day, year] = lastRow.date.split('.')
        const nextMonth = new Date(year, month, 1)
        const newDate = `${String(nextMonth.getMonth() + 1).padStart(2, '0')}.01.${nextMonth.getFullYear()}`

        result.rows.push({
          date: newDate,
          time: '00:00',
          value: '0',
          note: 'Auto-added end'
        })
      }

      setParsedData(result)
      setError('')
    } catch (err) {
      setError('Failed to parse CSV: ' + err.message)
      setParsedData(null)
    }
  }

  const handleFileUpload = (event) => {
    const file = event.target.files[0]
    if (!file) return

    const reader = new FileReader()
    reader.onload = (e) => {
      setCsvText(e.target.result)
    }
    reader.onerror = () => {
      setError('Failed to read file')
    }
    reader.readAsText(file)
  }

  const handleClear = () => {
    setCsvText('')
    setParsedData(null)
    setError('')
  }

  return (
    <div className="budgets-page">
      <h1>Budgets</h1>

      <div className="csv-input-section">
        <div className="file-upload">
          <label htmlFor="csv-file">Upload CSV file:</label>
          <input
            type="file"
            id="csv-file"
            accept=".csv"
            onChange={handleFileUpload}
          />
        </div>

        <div className="csv-textarea">
          <label htmlFor="csv-text">Or paste CSV data:</label>
          <textarea
            id="csv-text"
            value={csvText}
            onChange={(e) => setCsvText(e.target.value)}
            placeholder="date,time,value,note&#10;01.01.2019,10:00,7,First budget&#10;01.01.2019,11:00,0,"
            rows={10}
          />
        </div>

        <div className="actions">
          <button onClick={handleParse}>Parse CSV</button>
          <button onClick={handleClear} className="secondary">Clear</button>
        </div>
      </div>

      {error && <div className="error-message">{error}</div>}

      {parsedData && (
        <div className="results-section">
          <h2>Parsed Data ({parsedData.rows.length} rows)</h2>
          <div className="table-container">
            <table>
              <thead>
                <tr>
                  {parsedData.headers.map((header, i) => (
                    <th key={i}>{header}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {parsedData.rows.map((row, rowIndex) => (
                  <tr key={rowIndex}>
                    {parsedData.headers.map((header, colIndex) => (
                      <td key={colIndex}>{row[header]}</td>
                    ))}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <CostGenerator budgetData={parsedData} />
        </div>
      )}
    </div>
  )
}

export default Budgets
