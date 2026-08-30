CREATE TABLE IF NOT EXISTS questionnaire_submissions (
  submission_id CHAR(36) NOT NULL,
  questionnaire_id VARCHAR(32) NOT NULL,
  questionnaire_version VARCHAR(32) NOT NULL,
  schema_version INT UNSIGNED NOT NULL,
  submitted_at DATETIME(6) NOT NULL,
  payload_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (submission_id),
  INDEX idx_questionnaire_version_submitted (questionnaire_id, questionnaire_version, submitted_at),
  CONSTRAINT chk_questionnaire_schema_version CHECK (schema_version > 0),
  CONSTRAINT chk_questionnaire_payload_json CHECK (JSON_VALID(payload_json))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
