INSERT INTO `account_type` (`account_type_id`, `account_type_name`, `created_at`, `modified_at`) VALUES
  (1, 'Expense', '2023-04-24 10:40:57', '2023-04-24 10:40:57'),
  (2, 'Asset', '2023-04-24 10:40:57', '2023-04-24 10:40:57'),
  (3, 'Earnings', '2024-01-15 20:14:59', '2024-01-15 20:14:59')
;

INSERT INTO `scenario` (`scenario_name`, `scenario_descr`, `account_type_id`, `created_at`, `modified_at`) VALUES
  ('Default', 'Default Expense Scenario', 1, '2023-04-24 10:40:57', '2023-04-24 10:40:57'),
  ('Default', 'Default Asset Scenario', 2, '2023-04-24 10:40:57', '2023-04-24 10:40:57'),
  ('Default', 'Default Earnings Scenario', 3, '2024-01-15 20:15:41', '2024-01-15 20:15:41')
;

INSERT INTO `simulation` (`simulation_name`, `simulation_descr`, `scenario_id__expense`, `scenario_id__asset`, `scenario_id__earnings`, `periods`, `start_year`, `start_month`, `created_at`, `modified_at`) VALUES
  ('First Simulation', NULL, '1', '2', '3', '300', '2026', '1', '2024-01-20 05:12:44', '2024-01-20 05:12:44')
;
