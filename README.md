# symfony-react

> Only the frontend is needed
> The goal of this project is to create a React application that reads a CSV file containing daily budgets and costs, and calculates the monthly ceiling for each month based on the provided data. The application will display the results in a user-friendly format.

```
cd adwords-frontend
npm install
npm run dev
open http://localhost:5173/budgets
```

Use either of the files or create your own CSV file with the same format.
* example.csv - the given input
* example2.csv - the given input, but unordered some days and duplicate some entries



Assumptions I took:

- Since the values are random, there's no real need for backend or db right now unless we want to do something else with the data. Using only react + vite.
- The problem states "over a period of 3 months", however since the data is from a CSV file, I assume last date as being the same for the rest of the month.
  - Therefore if last date is 03.xx.2019. The final date will be 04.01.2019 with 0 budget.
- I have used CSV as the input for an easier input experience and easier testing.
- Assumed the not not was a typo: The cumulated cost per month can *not not* be greater than the sum of the maximum budget for each days within the month
- Budgets are know until present day. I.e: if today is 21.01.2019, we know the budget for 21.01.2019, monthly ceiling is calculated until 21.01.2019.
- Costs are random between $0.00 and $20.00

Example for budget monthly parsing:
* 01 Jan  6$ 
* 21 Jan 10$

Results for January monthly cap:
* 01 Jan - 6$
* 02 Jan - 6$ + 6$ = 12$
* 20 Jan - 6$ * 20 = 120$
* 21 Jan - 6$ * 20 + 10$ * 11 = 230$
* 31 Jan - 6$ * 20 + 10$ * 11 = 230$

Example 2
* 01 Jan  6$
* 01 Jan  2$
* 03 Jan 10$
* 04 Jan  0$

Results for January:
* 01 Jan - 6$ then, 2$ = 2$ (since initial budget is higher than $2, cost might be bigger and already consumed)
* 02 Jan - 2$ + 2$ = 4$ (will only accept costs if they are lower than 2$ and monthly lower than 4$)
* 03 Jan - 2$ * 2 + 10$ * 1 = 14$ (since the cost limit is higher than the initial budget, we can consume more, but only for the remaining days)
* 04 Jan - 2$ * 2 + 10$ * 1 + 0$ * 28 = 14$ (since the cost limit is higher than the initial

⚠️ Example 3  
* 01 Jan  6$
* 01 Jan  2$
* 03 Jan  0$

Caveat: Cost can still be higher than monthly ceiling in one situation:
01 Jan - 6$ spent on 6$ budget.

