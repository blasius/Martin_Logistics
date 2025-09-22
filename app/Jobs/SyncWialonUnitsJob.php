<?php

namespace App\Jobs;

use App\Services\WialonService;
use Illuminate\Queue\Jobs\Job;

class SyncWialonUnitsJob extends Job
{
    public function handle(WialonService $wialonService): void
    {
        $wialonService->syncUnits();
    }

    public function getJobId()
    {
        // TODO: Implement getJobId() method.
    }

    public function getRawBody()
    {
        // TODO: Implement getRawBody() method.
    }
}
