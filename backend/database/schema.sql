-- Analytics Platform MySQL Schema
CREATE DATABASE IF NOT EXISTS analytics_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE analytics_db;

CREATE TABLE IF NOT EXISTS events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id VARCHAR(64) NOT NULL UNIQUE,
    event_name VARCHAR(128) NOT NULL,
    timestamp VARCHAR(32) NOT NULL,
    timestamp_epoch BIGINT NOT NULL,
    source VARCHAR(16) NOT NULL,
    anonymous_id VARCHAR(64) NOT NULL,
    session_id VARCHAR(64) NULL,
    user_id VARCHAR(64) NULL,
    properties JSON NULL,
    context JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_events_timestamp_epoch (timestamp_epoch),
    INDEX idx_events_name (event_name),
    INDEX idx_events_anon (anonymous_id),
    INDEX idx_events_session (session_id),
    INDEX idx_events_source (source),
    INDEX idx_events_name_time (event_name, timestamp_epoch)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(64) NOT NULL UNIQUE,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    table_or_delivery VARCHAR(255) NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(8) NOT NULL DEFAULT 'USD',
    items JSON NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'completed',
    anonymous_id VARCHAR(64) NULL,
    session_id VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_orders_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
