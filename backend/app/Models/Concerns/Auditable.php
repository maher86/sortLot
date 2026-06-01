<?php

namespace App\Models\Concerns;

trait Auditable
{
    public static function bootAuditable(): void
    {
        // Audit persistence is introduced with the audit_logs table in a later phase.
    }
}
