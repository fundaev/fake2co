CREATE TABLE orders
(
  id int NOT NULL AUTO_INCREMENT,
  create_date int NOT NULL,
  fraud_status char(64),
  email char(128),
  cardholder_name char(128),
  duration char(128),
  recurrence char(128),
  product char(128),
  product_desc char(128),
  price char(128),
  startup_fee char(128),
  merchant_order_id char(128),
  return_url char(255),
  PRIMARY KEY (id)
) ENGINE MyISAM;

CREATE TABLE order_transactions
(
  id int NOT NULL AUTO_INCREMENT,
  order_id int NOT NULL,
  price char(16),
  type char(64) NOT NULL,
  create_date int NOT NULL,
  status char(64),
  PRIMARY KEY (id)
) ENGINE MyISAM;

CREATE TABLE callbacks
(
  id int NOT NULL AUTO_INCREMENT,
  order_id int NOT NULL,
  transaction_id int NOT NULL DEFAULT 0,
  message_type char(128) NOT NULL,
  callback_date int NOT NULL,
  url char(255),
  request text,
  headers text,
  response text,
  PRIMARY KEY (id)
) ENGINE MyISAM;