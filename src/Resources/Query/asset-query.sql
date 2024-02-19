SELECT a.*
FROM asset a
       JOIN scenario s ON s.scenario_id = a.scenario_id
WHERE s.account_type_id = 2
  AND s.scenario_name = :scenario_name
ORDER BY a.priority, a.begin_year, a.begin_month, a.apr, a.max_withdrawal, a.asset_name;
