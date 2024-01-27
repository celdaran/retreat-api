-- Create unit test scenarios
INSERT INTO `scenario` (`scenario_name`, `scenario_descr`, `account_type_id`, `created_at`, `modified_at`) VALUES
  ('UNIT TEST 01', 'Asset Scenario Unit Test 01', 1, '2024-01-23 23:00:00', '2024-01-23 23:00:00'),
  ('UNIT TEST 01', 'Expense Scenario Unit Test 01', 2, '2024-01-23 23:00:00', '2024-01-23 23:00:00')
;

INSERT INTO `asset` (`scenario_id`, `asset_name`, `opening_balance`, `max_withdrawal`, `apr`, `begin_after`, `begin_year`, `begin_month`, `created_at`, `modified_at`) VALUES
  (4, 'Savings', 5000.00, 500.00, 0.000, NULL, 2025, 1, '2024-01-23 23:00:00', '2024-01-23 23:00:00')
;

INSERT INTO `expense` (`scenario_id`, `expense_name`, `amount`, `inflation_rate`, `begin_year`, `begin_month`, `end_year`, `end_month`, `repeat_every`, `created_at`, `modified_at`) VALUES
  (5, 'House', 50.00, 0.000, 2025, 1, 2025, 12, NULL, '2024-01-23 23:00:00', '2024-01-23 23:00:00')
;
