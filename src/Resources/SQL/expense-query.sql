SELECT e.*
FROM expense e
JOIN scenario s ON s.scenario_id = e.scenario_id
WHERE s.account_type_id = 1
  AND s.scenario_name = :scenario_name
ORDER BY e.begin_year, e.begin_month, e.expense_name;
