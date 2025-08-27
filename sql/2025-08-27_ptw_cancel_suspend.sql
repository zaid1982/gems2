-- Migration: Add cancellation and suspension fields and statuses for PTW

ALTER TABLE ptw_permit
  ADD COLUMN cancel_requested_by INT NULL AFTER ptw_valid_to,
  ADD COLUMN cancel_requested_at DATETIME NULL AFTER cancel_requested_by,
  ADD COLUMN cancel_reason TEXT NULL AFTER cancel_requested_at,
  ADD COLUMN suspend_requested_by INT NULL AFTER cancel_reason,
  ADD COLUMN suspend_requested_at DATETIME NULL AFTER suspend_requested_by,
  ADD COLUMN suspend_reason TEXT NULL AFTER suspend_requested_at,
  ADD COLUMN suspend_ncr_no VARCHAR(64) NULL AFTER suspend_reason,
  ADD COLUMN suspended_by INT NULL AFTER suspend_ncr_no,
  ADD COLUMN suspended_date DATETIME NULL AFTER suspended_by;

-- Extend ptw_status enum; adjust list to include new values.
-- NOTE: adjust actual enum to match your DB schema if already migrated.
ALTER TABLE ptw_permit
  MODIFY COLUMN ptw_status ENUM(
    'DRAFT','SUBMITTED','PENDING_SUPERVISOR','PENDING_SHE','PENDING_FM',
    'APPROVED','ACTIVE','PENDING_CLOSURE','COMPLETED',
    'PENDING_CANCELLATION','PENDING_SUSPENSION','CANCELLED','SUSPENDED'
  ) NOT NULL DEFAULT 'DRAFT';

-- Extend ptw_status_history action_type enum
ALTER TABLE ptw_status_history
  MODIFY COLUMN action_type ENUM(
    'SUBMITTED','SUPERVISOR_APPROVED','SUPERVISOR_REJECTED',
    'SHE_APPROVED','SHE_REJECTED','FM_APPROVED','FM_REJECTED',
    'CLOSURE_REQUESTED','CLOSED','EXTENSION_REQUESTED','EXTENDED',
    'CANCELLATION_REQUESTED','CANCELLED','SUSPENSION_REQUESTED','SUSPENDED'
  ) NOT NULL;
