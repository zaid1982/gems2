-- Fix: ensure REJECTED exists as a valid ptw_status and action_type
-- Run this on production/staging safely.

-- 1) Extend ptw_permit.ptw_status enum to include REJECTED
ALTER TABLE `ptw_permit`
  MODIFY `ptw_status` enum(
    'DRAFT','PENDING_SUPERVISOR','PENDING_SHE','PENDING_FM',
    'APPROVED','ACTIVE','PENDING_CLOSURE','COMPLETED','EXPIRED',
    'PENDING_CANCELLATION','PENDING_SUSPENSION','CANCELLED','SUSPENDED','REJECTED'
  ) NOT NULL DEFAULT 'DRAFT';

-- 2) Extend ptw_status_history.action_type to include REJECTED (generic) if useful
ALTER TABLE `ptw_status_history`
  MODIFY `action_type` enum(
    'CREATED','SUBMITTED','SUPERVISOR_APPROVED','SUPERVISOR_REJECTED',
    'SHE_APPROVED','SHE_REJECTED','FM_APPROVED','FM_REJECTED',
    'ACTIVATED','CLOSURE_REQUESTED','CLOSED','COMPLETED',
    'CANCELLATION_REQUESTED','CANCELLED','SUSPENSION_REQUESTED','SUSPENDED','REJECTED'
  ) NOT NULL;
