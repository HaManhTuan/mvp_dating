#!/bin/bash

# init-db.sql - Database initialization script

# Create database if not exists
CREATE DATABASE IF NOT EXISTS `dating` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user if not exists
CREATE USER IF NOT EXISTS 'dating'@'%' IDENTIFIED BY 'secret';

# Grant privileges
GRANT ALL PRIVILEGES ON `dating`.* TO 'dating'@'%';

# Flush privileges
FLUSH PRIVILEGES;
