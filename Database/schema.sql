-- Create Database
CREATE DATABASE SmartMeterDB;
GO

USE SmartMeterDB;
GO

-- Meters Table
CREATE TABLE Meters (
    MeterID VARCHAR(50) PRIMARY KEY,
    Location VARCHAR(100) NOT NULL,
    ClientID INT,
    ClientName VARCHAR(100),
    Status VARCHAR(20) DEFAULT 'Active',
    LastReading DECIMAL(10,2) DEFAULT 0,
    InstallationDate DATE,
    MeterType VARCHAR(50),
    CreatedAt DATETIME DEFAULT GETDATE()
);

-- Meter Readings Table
CREATE TABLE MeterReadings (
    ReadingID INT IDENTITY(1,1) PRIMARY KEY,
    MeterID VARCHAR(50),
    Units DECIMAL(10,2),
    ReadingDate DATETIME,
    Status VARCHAR(20),
    FOREIGN KEY (MeterID) REFERENCES Meters(MeterID)
);

-- Bills Table
CREATE TABLE Bills (
    BillID INT IDENTITY(1,1) PRIMARY KEY,
    MeterID VARCHAR(50),
    BillAmount DECIMAL(10,2),
    Units DECIMAL(10,2),
    BillDate DATE,
    DueDate DATE,
    PaymentStatus VARCHAR(20),
    FOREIGN KEY (MeterID) REFERENCES Meters(MeterID)
);

-- Clients Table
CREATE TABLE Clients (
    ClientID INT IDENTITY(1,1) PRIMARY KEY,
    ClientName VARCHAR(100),
    ContactPerson VARCHAR(100),
    Phone VARCHAR(15),
    Email VARCHAR(100),
    Address TEXT
);

-- Sample Data
INSERT INTO Meters VALUES 
('MTR001', 'Dehradun', 1, 'Municipal Corporation', 'Active', 1250.50, '2024-01-15', 'Smart Meter', GETDATE()),
('MTR002', 'Ranchi', 2, 'State Electricity Board', 'Active', 2340.75, '2024-02-20', 'Smart Meter', GETDATE()),
('MTR003', 'Delhi', 3, 'BSES', 'Maintenance', 890.25, '2024-03-10', 'Smart Meter', GETDATE());

INSERT INTO Clients VALUES
('Municipal Corporation', 'Rajesh Kumar', '9876543210', 'rajesh@mcd.gov.in', 'Dehradun'),
('State Electricity Board', 'Priya Sharma', '9876543211', 'priya@seb.gov.in', 'Ranchi'),
('BSES', 'Amit Verma', '9876543212', 'amit@bses.com', 'Delhi');
