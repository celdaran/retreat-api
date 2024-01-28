SELECT
  i.earnings_name AS earnings_name,
  SUBSTRING_INDEX(group_concat(i.amount ORDER BY i.earnings_id), ',', -1) AS amount,
  SUBSTRING_INDEX(group_concat(i.inflation_rate ORDER BY i.inflation_rate), ',', -1) AS inflation_rate,
  SUBSTRING_INDEX(group_concat(i.begin_year ORDER BY i.earnings_id), ',', -1) AS begin_year,
  SUBSTRING_INDEX(group_concat(i.begin_month ORDER BY i.earnings_id), ',', -1) AS begin_month,
  SUBSTRING_INDEX(group_concat(i.end_year ORDER BY i.earnings_id), ',', -1) AS end_year,
  SUBSTRING_INDEX(group_concat(i.end_month ORDER BY i.earnings_id), ',', -1) AS end_month,
  SUBSTRING_INDEX(group_concat(i.repeat_every ORDER BY i.earnings_id), ',', -1) AS repeat_every
FROM (
  SELECT
    i.*
  FROM scenario s1
  JOIN earnings i ON i.scenario_id = s1.scenario_id
  WHERE s1.scenario_name = :scenario_name
    AND s1.account_type_id = 3
  ORDER BY earnings_id
) AS i
GROUP BY i.earnings_name
ORDER BY i.earnings_name
;
