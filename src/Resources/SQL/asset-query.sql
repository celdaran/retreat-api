SELECT
  a.asset_id,
  a.asset_name,
  SUBSTRING_INDEX(group_concat(a.opening_balance ORDER BY a.asset_id), ',', -1) AS opening_balance,
  SUBSTRING_INDEX(group_concat(a.opening_balance ORDER BY a.asset_id), ',', -1) AS current_balance,
  SUBSTRING_INDEX(group_concat(a.max_withdrawal ORDER BY a.asset_id), ',', -1) AS max_withdrawal,
  SUBSTRING_INDEX(group_concat(a.apr ORDER BY a.asset_id), ',', -1) AS apr,
  SUBSTRING_INDEX(group_concat(a.taxable ORDER BY a.asset_id), ',', -1) AS taxable,
  SUBSTRING_INDEX(group_concat(a.begin_after ORDER BY a.asset_id), ',', -1) AS begin_after,
  SUBSTRING_INDEX(group_concat(a.begin_year ORDER BY a.asset_id), ',', -1) AS begin_year,
  SUBSTRING_INDEX(group_concat(a.begin_month ORDER BY a.asset_id), ',', -1) AS begin_month
FROM (
       SELECT
         a.*
       FROM scenario s1
              JOIN asset a ON a.scenario_id = s1.scenario_id
       WHERE s1.scenario_name = :scenario_name
         AND s1.account_type_id = 2
       ORDER BY asset_id
     ) AS a
GROUP BY a.asset_name
ORDER BY a.begin_year, a.begin_month
;
