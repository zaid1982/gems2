-- Migration: Add PENDING_CLOSURE status and new history action types for PTW close workflow

-- Add new status to ptw_permit.ptw_status enum if not exists (MySQL workaround requires rebuild)
-- NOTE: For existing environments, run carefully and ensure downtime or use online DDL.
ALTER TABLE `ptw_permit`
  MODIFY `ptw_status` enum('DRAFT','PENDING_SUPERVISOR','PENDING_SHE','PENDING_FM','APPROVED','ACTIVE','PENDING_CLOSURE','COMPLETED','EXPIRED','CANCELLED') NOT NULL DEFAULT 'DRAFT';

-- Extend ptw_status_history.action_type to include closure actions
ALTER TABLE `ptw_status_history`
  MODIFY `action_type` enum('CREATED','SUBMITTED','SUPERVISOR_APPROVED','SUPERVISOR_REJECTED','SHE_APPROVED','SHE_REJECTED','FM_APPROVED','FM_REJECTED','ACTIVATED','CLOSURE_REQUESTED','CLOSED','COMPLETED','CANCELLED') NOT NULL;
