<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * CalendarService — aggregates campaigns by date for Gantt calendar view.
 */

namespace plugin\ads_report\service;

use Illuminate\Database\Capsule\Manager as DB;

class CalendarService
{
    public function getEvents(string $dateStart, string $dateEnd, int $tenantId = 1, ?string $platform = null): array
    {
        $query = DB::table('erik_campaigns')
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($dateStart, $dateEnd) {
                $q->whereBetween('start_date', [$dateStart, $dateEnd])
                  ->orWhereBetween('end_date', [$dateStart, $dateEnd])
                  ->orWhere(function ($q2) use ($dateStart, $dateEnd) {
                      $q2->where('start_date', '<=', $dateStart)
                         ->where('end_date', '>=', $dateEnd);
                  });
            });

        if ($platform) $query->where('platform', $platform);

        $campaigns = $query->orderBy('start_date')->get();

        $events = [];
        foreach ($campaigns as $c) {
            $events[] = [
                'id'         => $c->id,
                'name'       => $c->name,
                'platform'   => $c->platform,
                'status'     => $c->status,
                'start_date' => $c->start_date,
                'end_date'   => $c->end_date,
                'budget'     => $c->daily_budget,
            ];
        }

        return $events;
    }
}
