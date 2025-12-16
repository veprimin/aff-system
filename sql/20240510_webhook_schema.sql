-- Webhook observability and idempotency support

-- Raw payload storage
CREATE TABLE IF NOT EXISTS webhook_raw_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(255) NULL,
    payload LONGTEXT NOT NULL,
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Normalized payload storage
CREATE TABLE IF NOT EXISTS webhook_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(255) NOT NULL,
    request_id VARCHAR(32) NOT NULL,
    payload LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_webhook_logs_request (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure referral_users can track external customer identifiers
ALTER TABLE referral_users
    ADD COLUMN IF NOT EXISTS external_customer_id VARCHAR(255) NULL AFTER email;

-- Enforce idempotency on referral_users email/external_customer_id per client
ALTER TABLE referral_users
    ADD UNIQUE KEY IF NOT EXISTS uniq_referral_users_client_email (client_id, email),
    ADD UNIQUE KEY IF NOT EXISTS uniq_referral_users_client_external (client_id, external_customer_id);

-- Prevent duplicate order rows on retries
ALTER TABLE referral_orders
    ADD UNIQUE KEY IF NOT EXISTS uniq_referral_orders_order (samcart_order_id);

-- Product map helper for unknown product ids
CREATE TABLE IF NOT EXISTS product_map (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    samcart_product_id VARCHAR(255) NOT NULL,
    product_code VARCHAR(255) NOT NULL,
    payout_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    payout_type VARCHAR(50) NOT NULL DEFAULT 'onetime',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_product (samcart_product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
