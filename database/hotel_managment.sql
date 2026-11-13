-- Quick Reserve - database schema (v3)
-- Import with:  mysql -u root -p < database/hotel_managment.sql
-- (or phpMyAdmin > Import). Then optionally import database/seed_demo.sql for demo data.

CREATE DATABASE IF NOT EXISTS `hotel_managment` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hotel_managment`;

CREATE TABLE IF NOT EXISTS `user` (
  `User_id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `User_name`     VARCHAR(100) NOT NULL,
  `User_phoneno`  VARCHAR(20)  NOT NULL,
  `User_email`    VARCHAR(150) NOT NULL,
  `User_password` VARCHAR(255) NOT NULL,           -- bcrypt hash (password_hash)
  `User_address`  VARCHAR(255) NOT NULL,
  `is_admin`      TINYINT(1)   NOT NULL DEFAULT 0, -- 1 = may sign in to the Admin panel
  `otp`           VARCHAR(255) NULL,
  `otpused`       TINYINT(1)   NOT NULL DEFAULT 1,
  `otp_expires`   DATETIME     NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`User_id`),
  UNIQUE KEY `uq_user_email` (`User_email`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `hotel` (
  `Hotel_id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Hotel_name`        VARCHAR(150) NOT NULL,
  `Hotel_city`        VARCHAR(80)  NOT NULL DEFAULT '',
  `Hotel_description` TEXT         NOT NULL,
  `Hotel_address`     VARCHAR(255) NOT NULL,
  `Hotel_phone_no`    VARCHAR(20)  NOT NULL,
  `Hotel_email`       VARCHAR(150) NOT NULL,
  `Hotel_rating`      TINYINT UNSIGNED NOT NULL DEFAULT 3,   -- star class 1-5 (set by the hotel)
  `Hotel_amenities`   VARCHAR(500) NOT NULL DEFAULT '',      -- comma separated: WiFi, Pool, Spa ...
  `Hotel_image`       VARCHAR(255) NULL,
  PRIMARY KEY (`Hotel_id`),
  KEY `idx_hotel_city` (`Hotel_city`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `room_type` (
  `Room_type_id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Room_type_name`            VARCHAR(100) NOT NULL,
  `Room_type_description`     TEXT         NOT NULL,
  `Room_type_capacity`        TINYINT UNSIGNED NOT NULL,
  `Room_type_price_per_night` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`Room_type_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `room` (
  `Room_id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Hotel_id`          INT UNSIGNED NOT NULL,
  `Room_type_id`      INT UNSIGNED NOT NULL,
  `Room_no`           VARCHAR(20)  NOT NULL,
  `Room_availability` ENUM('yes','no') NOT NULL DEFAULT 'yes',   -- 'no' = out of service
  `Room_image`        VARCHAR(255) NULL,
  PRIMARY KEY (`Room_id`),
  UNIQUE KEY `uq_room_per_hotel` (`Hotel_id`,`Room_no`),
  CONSTRAINT `fk_room_hotel` FOREIGN KEY (`Hotel_id`) REFERENCES `hotel` (`Hotel_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_room_type`  FOREIGN KEY (`Room_type_id`) REFERENCES `room_type` (`Room_type_id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `coupon` (
  `Coupon_id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Code`         VARCHAR(30)  NOT NULL,
  `Description`  VARCHAR(150) NOT NULL DEFAULT '',
  `Type`         ENUM('percent','flat') NOT NULL DEFAULT 'percent',
  `Value`        DECIMAL(10,2) NOT NULL,
  `Min_amount`   DECIMAL(10,2) NOT NULL DEFAULT 0,
  `Max_discount` DECIMAL(10,2) NULL,
  `Valid_from`   DATE NULL,
  `Valid_to`     DATE NULL,
  `Usage_limit`  INT UNSIGNED NULL,
  `Used_count`   INT UNSIGNED NOT NULL DEFAULT 0,
  `Active`       TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`Coupon_id`),
  UNIQUE KEY `uq_coupon_code` (`Code`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `booking` (
  `Booking_id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Booking_ref`    VARCHAR(24)  NOT NULL,                      -- public reference, e.g. QR261111-AB12CD
  `User_id`        INT UNSIGNED NOT NULL,
  `Room_id`        INT UNSIGNED NOT NULL,
  `Guests`         TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `Check_in_date`  DATE NOT NULL,
  `Check_out_date` DATE NOT NULL,
  `Subtotal`       DECIMAL(10,2) NOT NULL DEFAULT 0,           -- nights x price
  `Coupon_code`    VARCHAR(30) NULL,
  `Discount`       DECIMAL(10,2) NOT NULL DEFAULT 0,
  `Tax`            DECIMAL(10,2) NOT NULL DEFAULT 0,           -- GST
  `Total_price`    DECIMAL(10,2) NOT NULL DEFAULT 0,           -- subtotal - discount + tax
  `Booking_date`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Booking_status` ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `Payment_status` ENUM('unpaid','paid','refund_pending','refunded') NOT NULL DEFAULT 'unpaid',
  `Hold_expires`   DATETIME NULL,                              -- unpaid online bookings release the room after this
  `Invoice_no`     VARCHAR(30) NULL,
  PRIMARY KEY (`Booking_id`),
  UNIQUE KEY `uq_booking_ref` (`Booking_ref`),
  UNIQUE KEY `uq_invoice_no` (`Invoice_no`),
  KEY `idx_booking_room_dates` (`Room_id`,`Check_in_date`,`Check_out_date`),
  CONSTRAINT `fk_booking_user` FOREIGN KEY (`User_id`) REFERENCES `user` (`User_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_room` FOREIGN KEY (`Room_id`) REFERENCES `room` (`Room_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `payment` (
  `Payment_id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Booking_id`         INT UNSIGNED NOT NULL,
  `Amount`             DECIMAL(10,2) NOT NULL,
  `Payment_date`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Payment_method`     ENUM('cash','card','upi','netbanking','razorpay') NOT NULL DEFAULT 'cash',
  `Status`             ENUM('created','paid','failed','refunded') NOT NULL DEFAULT 'paid',
  `Gateway_order_id`   VARCHAR(64) NULL,
  `Gateway_payment_id` VARCHAR(64) NULL,
  `Gateway_signature`  VARCHAR(128) NULL,
  PRIMARY KEY (`Payment_id`),
  UNIQUE KEY `uq_gateway_order` (`Gateway_order_id`),
  CONSTRAINT `fk_payment_booking` FOREIGN KEY (`Booking_id`) REFERENCES `booking` (`Booking_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `review` (
  `Review_id`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Hotel_id`   INT UNSIGNED NOT NULL,
  `User_id`    INT UNSIGNED NOT NULL,
  `Booking_id` INT UNSIGNED NOT NULL,
  `Rating`     TINYINT UNSIGNED NOT NULL,
  `Comment`    VARCHAR(1000) NOT NULL DEFAULT '',
  `Created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Review_id`),
  UNIQUE KEY `uq_review_booking` (`Booking_id`),
  KEY `idx_review_hotel` (`Hotel_id`),
  CONSTRAINT `fk_review_hotel`   FOREIGN KEY (`Hotel_id`)   REFERENCES `hotel` (`Hotel_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_user`    FOREIGN KEY (`User_id`)    REFERENCES `user` (`User_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_booking` FOREIGN KEY (`Booking_id`) REFERENCES `booking` (`Booking_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- No default admin account is created here on purpose.
-- Open Admin/register.php once after import to create the first admin
-- (or import database/seed_demo.sql, which includes a DEMO admin - change its password).
