<?php

namespace App\Support;

/**
 * Estado derivado da entrega outbound (matrícula → Gestor), para consulta/admin/cron.
 */
final class OutboundDeliveryStatuses
{
    public const PENDING = 'pending';

    public const RETRY_SCHEDULED = 'retry_scheduled';

    public const COMPLETED = 'completed';

    public const FAILED = 'failed';
}
