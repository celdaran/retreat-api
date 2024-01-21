SELECT
  i.income_name AS income_name,
  SUBSTRING_INDEX(group_concat(i.amount ORDER BY i.income_id), ',', -1) AS amount,
  SUBSTRING_INDEX(group_concat(i.inflation_rate ORDER BY i.inflation_rate), ',', -1) AS inflation_rate,
  SUBSTRING_INDEX(group_concat(i.begin_year ORDER BY i.income_id), ',', -1) AS begin_year,
  SUBSTRING_INDEX(group_concat(i.begin_month ORDER BY i.income_id), ',', -1) AS begin_month,
  SUBSTRING_INDEX(group_concat(i.end_year ORDER BY i.income_id), ',', -1) AS end_year,
  SUBSTRING_INDEX(group_concat(i.end_month ORDER BY i.income_id), ',', -1) AS end_month,
  SUBSTRING_INDEX(group_concat(e.repeat_every ORDER BY e.expense_id), ',', -1) AS repeat_every
FROM (
  SELECT
    i.*
  FROM scenario s1
  JOIN income i ON i.scenario_id = s1.scenario_id
  WHERE s1.scenario_name = :scenario_name
    AND s1.account_type_id = 3
  ORDER BY income_id
) AS i
GROUP BY i.income_name
ORDER BY i.income_name
;
