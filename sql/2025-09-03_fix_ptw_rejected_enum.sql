-- Fix: ensure REJECTED exists as a valid ptw_status and action_type
-- Run this on production/staging safely.

-- 1) Extend ptw_permit.ptw_status enum to include REJECTED
ALTER TABLE `ptw_permit`
  MODIFY `ptw_status` enum(
    'DRAFT','SUBMITTED','PENDING_SUPERVISOR','PENDING_SHE','PENDING_FM','APPROVED','ACTIVE','PENDING_CLOSURE','COMPLETED','PENDING_CANCELLATION','PENDING_SUSPENSION','PENDING_EXTENSION','CANCELLED','SUSPENDED','REJECTED','EXTENDED'

  ) NOT NULL DEFAULT 'DRAFT';

-- 2) Extend ptw_status_history.action_type to include REJECTED (generic) if useful
ALTER TABLE `ptw_status_history`
  MODIFY `action_type` enum(
        'DRAFT','SUBMITTED','PENDING_SUPERVISOR','PENDING_SHE','PENDING_FM','APPROVED','ACTIVE','PENDING_CLOSURE','COMPLETED','PENDING_CANCELLATION','PENDING_SUSPENSION','PENDING_EXTENSION','CANCELLED','SUSPENDED','REJECTED','EXTENDED'
  ) NOT NULL;
