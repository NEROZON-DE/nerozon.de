-- NEROZON DEV2
-- Adds the operational Work Order registry used by the Dispatcher PoC.
-- Non-destructive and idempotent.

CREATE TABLE IF NOT EXISTS dispatcher_workorders (
    wo_id VARCHAR(40) PRIMARY KEY,
    target VARCHAR(40) NOT NULL,
    repository VARCHAR(200) NOT NULL,
    branch_name VARCHAR(200) NOT NULL,
    wo_path VARCHAR(500) NOT NULL,
    commit_sha CHAR(40) NOT NULL,
    authority_repository VARCHAR(200) NOT NULL,
    authority_branch VARCHAR(200) NOT NULL DEFAULT 'main',
    authority_path VARCHAR(500) NOT NULL DEFAULT 'ROLE.md',
    status VARCHAR(30) NOT NULL DEFAULT 'registered',
    openai_response_id VARCHAR(100) NULL,
    openai_status VARCHAR(30) NULL,
    error_text TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_workorders_status (status, updated_at),
    INDEX idx_workorders_branch (repository, branch_name, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
