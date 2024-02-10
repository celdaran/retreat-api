-- Create unit test scenarios
INSERT INTO scenario (scenario_id, scenario_name, account_type_id) VALUES
  ( 4, 'ut01-expenses', 1),
  ( 5, 'ut01-assets',   2),
  ( 6, 'ut01-earnings', 3),

  ( 7, 'ut02-expenses', 1),
  ( 8, 'ut02-assets',   2),
  ( 9, 'ut02-earnings', 3),

  (10, 'ut03-expenses', 1),
  (11, 'ut03-assets',   2),
  (12, 'ut03-earnings', 3),

  (13, 'ut04-expenses', 1),
  (14, 'ut04-assets',   2),
  (15, 'ut04-earnings', 3),

  (16, 'ut05-expenses', 1),
  (17, 'ut05-assets',   2),
  (18, 'ut05-earnings', 3)
;

-- -----------------------------------------------------------------------------
-- Unit Test: 01
-- Purpose..: Very basic single expense and single asset, linear depletion
-- -----------------------------------------------------------------------------

INSERT INTO expense (scenario_id, expense_name, amount, inflation_rate, begin_year, begin_month) VALUES
  (4, 'Expense 1', 100.00, 0.000, 2025, 1)
;

INSERT INTO asset (scenario_id, asset_name, opening_balance, max_withdrawal, apr, begin_after, begin_year, begin_month) VALUES
  (5, 'Asset 1', 1000.00, 100.00, 0.000, NULL, 2025, 1)
;

-- -----------------------------------------------------------------------------
-- Unit Test: 02
-- Purpose..: Add a chained asset (Asset 2 only starts when Asset 1 depletes)
-- -----------------------------------------------------------------------------

INSERT INTO expense (scenario_id, expense_name, amount, inflation_rate, begin_year, begin_month) VALUES
  (7, 'Expense 1', 100.00, 0.000, 2025, 1)
;

INSERT INTO asset (scenario_id, asset_name, opening_balance, max_withdrawal, apr, begin_after, begin_year, begin_month) VALUES
  (8, 'Asset 1', 500.00, 100.00, 0.000, NULL, 2025, 1)
;

INSERT INTO asset (scenario_id, asset_name, opening_balance, max_withdrawal, apr, begin_after, begin_year, begin_month) VALUES
  (8, 'Asset 2', 1000.00, 100.00, 0.000, (SELECT a2.asset_id FROM asset a2 WHERE a2.scenario_id = 8 AND a2.asset_name = 'Asset 1'), 2025, 1)
;

-- -----------------------------------------------------------------------------
-- Unit Test: 03
-- Purpose..: Chained asset (Asset 1 -> Asset 2 -> Asset 3)
-- -----------------------------------------------------------------------------

INSERT INTO expense (scenario_id, expense_name, amount, inflation_rate, begin_year, begin_month) VALUES
  (10, 'Expense 1', 100.00, 0.000, 2025, 1)
;

INSERT INTO asset (scenario_id, asset_name, opening_balance, max_withdrawal, apr, begin_after, begin_year, begin_month) VALUES
  (11, 'Asset 1', 300.00, 100.00, 0.000, NULL, 2025, 1)
;
INSERT INTO asset (scenario_id, asset_name, opening_balance, max_withdrawal, apr, begin_after, begin_year, begin_month) VALUES
  (11, 'Asset 2', 300.00, 100.00, 0.000, (SELECT a2.asset_id FROM asset a2 WHERE a2.scenario_id = 11 AND a2.asset_name = 'Asset 1'), 2025, 1)
;
INSERT INTO asset (scenario_id, asset_name, opening_balance, max_withdrawal, apr, begin_after, begin_year, begin_month) VALUES
  (11, 'Asset 3', 600.00, 100.00, 0.000, (SELECT a2.asset_id FROM asset a2 WHERE a2.scenario_id = 11 AND a2.asset_name = 'Asset 2'), 2025, 1)
;

-- -----------------------------------------------------------------------------
-- Unit Test: 04
-- Purpose..: Pull from multiple assets simultaneously, introduce interest
-- -----------------------------------------------------------------------------

INSERT INTO expense (scenario_id, expense_name, amount, inflation_rate, begin_year, begin_month) VALUES
  (13, 'Expense 1', 500.00, 0.000, 2025, 1)
;
INSERT INTO expense (scenario_id, expense_name, amount, inflation_rate, begin_year, begin_month) VALUES
  (13, 'Expense 2', 600.00, 1.000, 2030, 1)
;
INSERT INTO expense (scenario_id, expense_name, amount, inflation_rate, begin_year, begin_month) VALUES
  (13, 'Expense 3', 700.00, 5.000, 2035, 1)
;

INSERT INTO asset (scenario_id, asset_name, opening_balance, max_withdrawal, apr, begin_after, begin_year, begin_month) VALUES
  (14, 'Asset 1', 1000.00, 100.00, 2.000, NULL, 2025, 1)
;
INSERT INTO asset (scenario_id, asset_name, opening_balance, max_withdrawal, apr, begin_after, begin_year, begin_month) VALUES
  (14, 'Asset 2', 2000.00, 200.00, 5.000, NULL, 2025, 1)
;
INSERT INTO asset (scenario_id, asset_name, opening_balance, max_withdrawal, apr, begin_after, begin_year, begin_month) VALUES
  (14, 'Asset 3', 3000.00, 300.00, 10.000, NULL, 2025, 1)
;

-- -----------------------------------------------------------------------------
-- Unit Test: 05
-- Purpose..: This is the Everything Bagel test set
-- -----------------------------------------------------------------------------

INSERT INTO expense (scenario_id, expense_name, amount, inflation_rate, begin_year, begin_month, end_year, end_month, repeat_every)
VALUES
  (16, 'Mortgage',         1500.00, 0.000, 2025, 8, 2031,    5, NULL),
  (16, 'Property Tax',    12000.00, 2.000, 2026, 1, NULL, NULL,   12),
  (16, 'Home Insurance',   1500.00, 2.500, 2026, 3, NULL, NULL,   12),
  (16, 'Utilities',         300.00, 3.000, 2025, 8, NULL, NULL, NULL),
  (16, 'Home Maint',        500.00, 2.000, 2027, 1, 2037,    3, NULL),
  (16, 'Healthcare',       1000.00, 5.000, 2025, 8, NULL, NULL, NULL),
  (16, 'Car Expenses',      250.00, 2.500, 2025, 8, NULL, NULL, NULL),
  (16, 'Food',              600.00, 7.500, 2025, 8, NULL, NULL, NULL),
  (16, 'Wildcard',         7000.00, 0.000, 2030, 7, 2050,   12,   36),
  (16, 'Travel',           1000.00, 3.000, 2025, 9, 2045,   10,    4)
;

INSERT INTO asset (scenario_id, asset_name, opening_balance, max_withdrawal, apr, taxable, begin_after, begin_year, begin_month)
VALUES
  (17, 'Old 401k',        50000.00, 1000.00, 3.000, 1, NULL, 2025, 8),
  (17, 'New 401k',        25000.00, 2500.00, 3.000, 1, NULL, 2025, 8),
  (17, 'HYSA 1',          50000.00, 5000.00, 4.500, 1, NULL, 2025, 8),
  (17, 'HYSA 2',          35000.00, 1500.00, 4.500, 1, NULL, 2025, 8),
  (17, 'Stonks',         100000.00, 1500.00, 4.500, 1, (SELECT a2.asset_id FROM asset a2 WHERE a2.asset_name = 'HYSA 2'), 2025, 8)
;

INSERT INTO earnings (scenario_id, earnings_name, amount, inflation_rate, begin_year, begin_month, end_year, end_month, repeat_every)
VALUES
  (18, 'Social Security 1',  500.00, 1.500, 2025, 1, 2055, 12, NULL),
  (18, 'Social Security 2',  500.00, 1.500, 2025, 1, 2055, 12, NULL),
  (18, 'Some Side Gig',      250.00, 0.000, 2027, 1, 2032,  9, NULL)
;
