CREATE TABLE `scenario` (
  `scenario_id` integer PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `scenario_name` varchar(255) NOT NULL,
  `scenario_descr` varchar(255),
  `account_type_id` integer NOT NULL,
  `created_at` timestamp DEFAULT (now()),
  `modified_at` timestamp DEFAULT (now())
);

CREATE TABLE `account_type` (
  `account_type_id` integer PRIMARY KEY NOT NULL,
  `account_type_name` varchar(255) UNIQUE NOT NULL,
  `account_type_descr` varchar(255),
  `created_at` timestamp DEFAULT (now()),
  `modified_at` timestamp DEFAULT (now())
);

CREATE TABLE `expense` (
  `expense_id` integer PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `scenario_id` integer NOT NULL,
  `expense_name` varchar(255) NOT NULL,
  `expense_descr` varchar(255),
  `amount` DECIMAL(13,2) NOT NULL,
  `inflation_rate` DECIMAL(5,3) NOT NULL,
  `begin_year` integer,
  `begin_month` integer,
  `end_year` integer,
  `end_month` integer,
  `repeat_every` integer,
  `created_at` timestamp DEFAULT (now()),
  `modified_at` timestamp DEFAULT (now())
);

CREATE TABLE `asset` (
  `asset_id` integer PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `scenario_id` integer NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `asset_descr` varchar(255),
  `opening_balance` DECIMAL(13,2) NOT NULL,
  `max_withdrawal` DECIMAL(13,2),
  `apr` DECIMAL(5,3),
  `taxable` bool,
  `begin_after` integer,
  `begin_year` integer,
  `begin_month` integer,
  `created_at` timestamp DEFAULT (now()),
  `modified_at` timestamp DEFAULT (now())
);

CREATE TABLE `earnings` (
  `earnings_id` integer PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `scenario_id` integer NOT NULL,
  `earnings_name` varchar(255) NOT NULL,
  `earnings_descr` varchar(255),
  `amount` DECIMAL(13,2) NOT NULL,
  `inflation_rate` DECIMAL(5,3),
  `begin_year` integer,
  `begin_month` integer,
  `end_year` integer,
  `end_month` integer,
  `repeat_every` integer,
  `created_at` timestamp DEFAULT (now()),
  `modified_at` timestamp DEFAULT (now())
);

CREATE TABLE `simulation` (
  `simulation_id` integer PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `simulation_name` varchar(255) UNIQUE NOT NULL,
  `simulation_descr` varchar(255),
  `scenario_id__expense` integer NOT NULL,
  `scenario_id__asset` integer NOT NULL,
  `scenario_id__earnings` integer NOT NULL,
  `periods` integer,
  `start_year` integer,
  `start_month` integer,
  `created_at` timestamp DEFAULT (now()),
  `modified_at` timestamp DEFAULT (now())
);

CREATE UNIQUE INDEX `scenario_index_0` ON `scenario` (`scenario_name`, `account_type_id`);

CREATE UNIQUE INDEX `expense_index_1` ON `expense` (`expense_name`, `scenario_id`);

CREATE UNIQUE INDEX `asset_index_2` ON `asset` (`asset_name`, `scenario_id`);

CREATE UNIQUE INDEX `earnings_index_3` ON `earnings` (`earnings_name`, `scenario_id`);

ALTER TABLE `scenario` ADD FOREIGN KEY (`account_type_id`) REFERENCES `account_type` (`account_type_id`);

ALTER TABLE `expense` ADD FOREIGN KEY (`scenario_id`) REFERENCES `scenario` (`scenario_id`);

ALTER TABLE `asset` ADD FOREIGN KEY (`scenario_id`) REFERENCES `scenario` (`scenario_id`);

ALTER TABLE `asset` ADD FOREIGN KEY (`begin_after`) REFERENCES `asset` (`asset_id`);

ALTER TABLE `earnings` ADD FOREIGN KEY (`scenario_id`) REFERENCES `scenario` (`scenario_id`);

ALTER TABLE `simulation` ADD FOREIGN KEY (`scenario_id__expense`) REFERENCES `scenario` (`scenario_id`);

ALTER TABLE `simulation` ADD FOREIGN KEY (`scenario_id__asset`) REFERENCES `scenario` (`scenario_id`);

ALTER TABLE `simulation` ADD FOREIGN KEY (`scenario_id__earnings`) REFERENCES `scenario` (`scenario_id`);
