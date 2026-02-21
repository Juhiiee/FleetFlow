-- FleetFlow Database Schema
-- MySQL Relational Structure

CREATE DATABASE IF NOT EXISTS fleetflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fleetflow;

-- =============================================
-- USERS TABLE (RBAC)
-- =============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('Fleet Manager','Dispatcher','Safety Officer','Financial Analyst') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- VEHICLES TABLE
-- =============================================
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_name VARCHAR(100) NOT NULL,
    license_plate VARCHAR(20) NOT NULL UNIQUE,
    vehicle_type ENUM('Truck','Van','Sedan','SUV','Bus') NOT NULL DEFAULT 'Truck',
    max_load_capacity DECIMAL(10,2) NOT NULL DEFAULT 0,
    odometer DECIMAL(12,2) NOT NULL DEFAULT 0,
    acquisition_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    region VARCHAR(50) DEFAULT 'Default',
    status ENUM('Available','On Trip','In Shop','Retired') NOT NULL DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- DRIVERS TABLE
-- =============================================
CREATE TABLE drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    license_category ENUM('A','B','C','D','E') NOT NULL DEFAULT 'B',
    license_expiry DATE NOT NULL,
    safety_score DECIMAL(4,1) NOT NULL DEFAULT 100.0,
    phone VARCHAR(20) DEFAULT NULL,
    status ENUM('Off Duty','On Duty','Suspended') NOT NULL DEFAULT 'Off Duty',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- TRIPS TABLE
-- =============================================
CREATE TABLE trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    driver_id INT NOT NULL,
    cargo_description VARCHAR(255) DEFAULT NULL,
    cargo_weight DECIMAL(10,2) NOT NULL DEFAULT 0,
    origin VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    start_odometer DECIMAL(12,2) DEFAULT NULL,
    end_odometer DECIMAL(12,2) DEFAULT NULL,
    distance DECIMAL(12,2) DEFAULT NULL,
    status ENUM('Draft','Dispatched','Completed','Cancelled') NOT NULL DEFAULT 'Draft',
    dispatched_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE RESTRICT,
    INDEX idx_trip_status (status),
    INDEX idx_trip_vehicle (vehicle_id),
    INDEX idx_trip_driver (driver_id)
) ENGINE=InnoDB;

-- =============================================
-- MAINTENANCE LOGS TABLE
-- =============================================
CREATE TABLE maintenance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    service_description VARCHAR(255) NOT NULL,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    service_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    INDEX idx_maint_vehicle (vehicle_id)
) ENGINE=InnoDB;

-- =============================================
-- FUEL LOGS TABLE
-- =============================================
CREATE TABLE fuel_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    liters DECIMAL(10,2) NOT NULL DEFAULT 0,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    fuel_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    INDEX idx_fuel_vehicle (vehicle_id)
) ENGINE=InnoDB;


-- =============================================
-- SAMPLE DATA
-- =============================================

-- Users (password = 'password123' for all)
INSERT INTO users (username, password, full_name, email, role) VALUES
('manager1', '$2y$10$GnDTPljWsKyKTumv8wDugeL571q4tVw5uoyUrRfbG8WeRpe84V8iS', 'Alice Carter', 'alice@fleetflow.com', 'Fleet Manager'),
('dispatcher1', '$2y$10$GnDTPljWsKyKTumv8wDugeL571q4tVw5uoyUrRfbG8WeRpe84V8iS', 'Bob Martinez', 'bob@fleetflow.com', 'Dispatcher'),
('safety1', '$2y$10$GnDTPljWsKyKTumv8wDugeL571q4tVw5uoyUrRfbG8WeRpe84V8iS', 'Carol White', 'carol@fleetflow.com', 'Safety Officer'),
('analyst1', '$2y$10$GnDTPljWsKyKTumv8wDugeL571q4tVw5uoyUrRfbG8WeRpe84V8iS', 'Dan Lee', 'dan@fleetflow.com', 'Financial Analyst');

-- Vehicles
INSERT INTO vehicles (vehicle_name, license_plate, vehicle_type, max_load_capacity, odometer, acquisition_cost, region, status) VALUES
('Volvo FH16', 'FL-1001', 'Truck', 12000.00, 45000.00, 85000.00, 'North', 'Available'),
('Mercedes Sprinter', 'FL-1002', 'Van', 1500.00, 32000.00, 42000.00, 'East', 'Available'),
('Ford Transit', 'FL-1003', 'Van', 1200.00, 58000.00, 35000.00, 'West', 'On Trip'),
('Toyota Hilux', 'FL-1004', 'SUV', 800.00, 21000.00, 28000.00, 'South', 'In Shop'),
('Scania R500', 'FL-1005', 'Truck', 15000.00, 120000.00, 110000.00, 'North', 'Available');

-- Drivers
INSERT INTO drivers (full_name, license_category, license_expiry, safety_score, phone, status) VALUES
('James Wilson', 'C', '2027-06-15', 95.0, '555-0101', 'Off Duty'),
('Maria Garcia', 'D', '2026-12-31', 88.5, '555-0102', 'Off Duty'),
('Robert Kim', 'C', '2025-01-01', 72.0, '555-0103', 'Off Duty'),
('Sarah Brown', 'B', '2027-09-20', 97.5, '555-0104', 'On Duty'),
('Michael Davis', 'E', '2026-08-10', 60.0, '555-0105', 'Suspended');

-- Trips
INSERT INTO trips (vehicle_id, driver_id, cargo_description, cargo_weight, origin, destination, start_odometer, end_odometer, distance, status, dispatched_at, completed_at) VALUES
(3, 4, 'Electronic Components', 800.00, 'Chicago', 'Detroit', 57000.00, 58000.00, 1000.00, 'Dispatched', '2026-02-15 08:00:00', NULL),
(1, 1, 'Steel Beams', 10000.00, 'New York', 'Boston', 44000.00, 45000.00, 1000.00, 'Completed', '2026-02-10 06:00:00', '2026-02-11 18:00:00'),
(2, 2, 'Medical Supplies', 500.00, 'Los Angeles', 'San Diego', 31500.00, 32000.00, 500.00, 'Completed', '2026-02-08 09:00:00', '2026-02-08 15:00:00');

-- Maintenance Logs
INSERT INTO maintenance_logs (vehicle_id, service_description, cost, service_date) VALUES
(4, 'Brake pad replacement', 450.00, '2026-02-18'),
(4, 'Oil change + filter', 120.00, '2026-02-18'),
(1, 'Tire rotation', 200.00, '2026-02-05'),
(2, 'Transmission fluid flush', 350.00, '2026-01-20');

-- Fuel Logs
INSERT INTO fuel_logs (vehicle_id, liters, cost, fuel_date) VALUES
(1, 120.00, 180.00, '2026-02-10'),
(2, 45.00, 67.50, '2026-02-08'),
(3, 60.00, 90.00, '2026-02-15'),
(1, 130.00, 195.00, '2026-02-01'),
(5, 200.00, 300.00, '2026-01-25');
