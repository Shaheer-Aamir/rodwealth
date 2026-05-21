-- ============================================
--  Construction Leads Database Schema
--  Run this in phpMyAdmin > SQL tab
-- ============================================

CREATE DATABASE IF NOT EXISTS construction_leads CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE construction_leads;

CREATE TABLE IF NOT EXISTS leads (
    id              INT AUTO_INCREMENT PRIMARY KEY,

    -- Shared fields (both forms)
    name            VARCHAR(100)  NOT NULL,
    email           VARCHAR(150)  NOT NULL,
    phone           VARCHAR(30)   NOT NULL,
    message         TEXT          DEFAULT NULL,
    source          ENUM('homepage','contact') NOT NULL DEFAULT 'homepage',

    -- Project type (both forms send this, stored in one column)
    project_type    VARCHAR(100)  DEFAULT NULL,

    -- Contact page extra fields
    project_address VARCHAR(255)  DEFAULT NULL,
    budget          VARCHAR(100)  DEFAULT NULL,
    timeline        VARCHAR(100)  DEFAULT NULL,

    -- Admin tracking
    status          ENUM('new','contacted','in_progress','closed','spam') NOT NULL DEFAULT 'new',
    notes           TEXT          DEFAULT NULL,
    ip_address      VARCHAR(45)   DEFAULT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_status  ON leads(status);
CREATE INDEX idx_source  ON leads(source);
CREATE INDEX idx_created ON leads(created_at);
