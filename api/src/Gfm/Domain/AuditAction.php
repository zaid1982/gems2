<?php

declare(strict_types=1);

namespace Gfm\Domain;

/**
 * Named audit action IDs passed to Class_general::save_audit().
 *
 * The legacy code sprinkles bare strings like save_audit('1', ...) /
 * save_audit('136', ...) across endpoints. These typed constants give those
 * numbers a name. Values are strings to match the existing save_audit()
 * signature exactly (behaviour-preserving drop-in).
 *
 * This is a PARTIAL, verified subset. The full list lives in the audit
 * reference table; see docs/REFERENCE_IDS.md and extend this as endpoints are
 * migrated.
 */
final class AuditAction
{
    public const string LOGIN = '1';
    public const string FORGOT_PASSWORD = '4';
    public const string RESET_PASSWORD = '103';

    public const string WO_DELETE = '124';
    public const string WO_MANUAL_REPORT_ADD = '125';
    public const string WO_MANUAL_REPORT_EDIT = '126';
    public const string WO_HELPDESK_ASSIGN = '136';
}
