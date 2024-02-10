SELECT
  e.earnings_name AS earnings_name,
  SUBSTRING_INDEX(group_concat(e.amount ORDER BY e.earnings_id), ',', -1) AS amount,
  SUBSTRING_INDEX(group_concat(e.inflation_rate ORDER BY e.inflation_rate), ',', -1) AS inflation_rate,
  SUBSTRING_INDEX(group_concat(e.begin_year ORDER BY e.earnings_id), ',', -1) AS begin_year,
  SUBSTRING_INDEX(group_concat(e.begin_month ORDER BY e.earnings_id), ',', -1) AS begin_month,
  SUBSTRING_INDEX(group_concat(e.end_year ORDER BY e.earnings_id), ',', -1) AS end_year,
  SUBSTRING_INDEX(group_concat(e.end_month ORDER BY e.earnings_id), ',', -1) AS end_month,
  SUBSTRING_INDEX(group_concat(e.repeat_every ORDER BY e.earnings_id), ',', -1) AS repeat_every
FROM (
  SELECT
    e.*
  FROM scenario s1
  JOIN earnings e ON e.scenario_id = s1.scenario_id
  WHERE s1.scenario_name = :scenario_name
    AND s1.account_type_id = 3
  ORDER BY earnings_id
) AS e
GROUP BY e.earnings_name
ORDER BY
  e.begin_year,
  e.begin_month,
  e.earnings_name
;
