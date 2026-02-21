# 🚛 FleetFlow – Modular Fleet & Logistics Management System

FleetFlow is a hackathon-grade, industry-level web application designed to replace inefficient manual fleet logbooks with a centralized, rule-based digital management system.

It helps organizations manage vehicles, drivers, trips, maintenance, fuel costs, and operational analytics in one unified platform.



## 🎯 Objective

To build a smart fleet management system that:

- Optimizes vehicle lifecycle management
- Automates trip dispatching with validation rules
- Monitors driver compliance & safety
- Tracks fuel & maintenance expenses
- Calculates operational KPIs and ROI
- Provides analytics & exportable reports



## 👥 User Roles (RBAC - Role Based Access Control)

- Fleet Manager
- Dispatcher
- Safety Officer
- Financial Analyst

Each role has controlled access to specific modules.



## 🔑 Core Features

### ✅ 1. Secure Authentication
- Role-based login
- Session management
- Password security
- Protected routes

### 🚗 2. Vehicle Registry (CRUD)
- Add, edit, delete vehicles
- Unique License Plate validation
- Capacity & odometer tracking
- Status: Available / On Trip / In Shop / Retired

### 👨‍✈️ 3. Driver Management
- License category & expiry tracking
- Safety score system
- Status control (On Duty / Off Duty / Suspended)
- Automatic assignment block if license expired

### 📦 4. Smart Trip Dispatcher
- Assign vehicle + driver
- Cargo weight validation
- Prevent over-capacity trips
- Lifecycle:
  - Draft
  - Dispatched
  - Completed
  - Cancelled

Automatic Status Updates:
- On Dispatch → Vehicle & Driver = On Trip
- On Completion → Vehicle & Driver = Available

### 🔧 5. Maintenance & Service Logs
- Record service details & cost
- Auto-switch vehicle to "In Shop"
- Vehicles in shop hidden from dispatcher

### ⛽ 6. Fuel & Expense Logging
- Track fuel usage (liters & cost)
- Link expenses per vehicle
- Automatic operational cost calculation

### 📊 7. Analytics & Financial Reports
- Fuel Efficiency (km/L)
- Total Operational Cost
- Vehicle ROI calculation
- Monthly expense charts
- CSV/PDF export



## 📈 Key Calculations

- Utilization Rate = (Active Vehicles / Total Vehicles) × 100
- Fuel Efficiency = Distance / Liters
- Total Operational Cost = Fuel + Maintenance
- Vehicle ROI = (Revenue - (Fuel + Maintenance)) / Acquisition Cost



## 🛠️ Technology Stack

- Frontend: HTML5, CSS3, Bootstrap 5
- Backend: Core PHP
- Database: MySQL (Relational Schema)
- Charts & Analytics: Chart.js
- Server: XAMPP / Apache



## 🗄️ Database Structure

Main Tables:
- users
- vehicles
- drivers
- trips
- maintenance_logs
- fuel_logs

All relationships implemented using foreign keys.



## 🔄 System Workflow Demo

1. Add Vehicle (e.g., 500kg capacity)
2. Add Driver (valid license)
3. Create Trip (450kg load)
   - Capacity validation
   - Status update to On Trip
4. Complete Trip
   - Update odometer
   - Status reset to Available
5. Add Maintenance
   - Auto status → In Shop
6. Add Fuel Log
7. Analytics auto-updated



## 💻 Installation Guide

1. Clone the repository
2. Import `fleetflow_database.sql` into MySQL
3. Configure database connection in `/config`
4. Start Apache & MySQL (XAMPP)
5. Open in browser:
