<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentPriority;
use App\Enums\AppointmentStatus;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\DashboardResource;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Appointment::class);

        $windowStart = Carbon::today()->subDays(13);
        $windowEnd = Carbon::today();

        $statusCounts = Appointment::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $priorityCounts = Appointment::query()
            ->selectRaw('priority, count(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->all();

        $dates = collect(range(0, 13))
            ->map(fn (int $days) => $windowStart->clone()->addDays($days)->format('Y-m-d'))
            ->values();

        $appointmentsByDate = Appointment::query()
            ->whereDate('appointment_date', '>=', $windowStart)
            ->whereDate('appointment_date', '<=', $windowEnd)
            ->selectRaw('appointment_date, count(*) as count')
            ->groupBy('appointment_date')
            ->pluck('count', 'appointment_date')
            ->all();

        $topServices = Service::query()
            ->select('services.id', 'services.name')
            ->selectRaw('count(appointments.id) as appointments_count')
            ->leftJoin('appointments', 'services.id', '=', 'appointments.service_id')
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('appointments_count')
            ->orderBy('services.name')
            ->limit(5)
            ->get();

        $upcoming = Appointment::query()
            ->withDetails()
            ->whereIn('status', [AppointmentStatus::Requested, AppointmentStatus::Confirmed])
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        $payload = [
            'totals' => [
                'appointments' => Appointment::count(),
                'activeClients' => Client::where('active', true)->count(),
                'activeServices' => Service::where('active', true)->count(),
                'pending' => Appointment::where('status', AppointmentStatus::Requested)->count(),
                'urgent' => Appointment::where('priority', AppointmentPriority::High)
                    ->whereIn('status', [AppointmentStatus::Requested, AppointmentStatus::Confirmed])
                    ->count(),
            ],
            'statusDistribution' => $this->distribution(
                $statusCounts,
                AppointmentStatus::class,
                [
                    AppointmentStatus::Requested->value => '#f59e0b',
                    AppointmentStatus::Confirmed->value => '#3b82f6',
                    AppointmentStatus::Completed->value => '#22c55e',
                    AppointmentStatus::Cancelled->value => '#ef4444',
                ]
            ),
            'priorityDistribution' => $this->distribution(
                $priorityCounts,
                AppointmentPriority::class,
                [
                    AppointmentPriority::Low->value => '#64748b',
                    AppointmentPriority::Medium->value => '#3b82f6',
                    AppointmentPriority::High->value => '#ef4444',
                ]
            ),
            'appointmentsOverTime' => [
                'labels' => $dates->all(),
                'series' => $dates->map(fn (string $date) => (int) ($appointmentsByDate[$date] ?? 0))->all(),
            ],
            'topServices' => [
                'labels' => $topServices->pluck('name')->all(),
                'series' => $topServices->pluck('appointments_count')->all(),
            ],
            'upcoming' => AppointmentResource::collection($upcoming)->resolve(),
        ];

        return (new DashboardResource($payload))->response();
    }

    /**
     * Build a zero-filled distribution for the given backed enum class.
     *
     * @param  array<string, int>  $counts
     * @param  class-string<\BackedEnum>  $enumClass
     * @param  array<string, string>  $colors
     * @return array{labels: array<int, string>, series: array<int, int>, colors: array<int, string>}
     */
    private function distribution(array $counts, string $enumClass, array $colors): array
    {
        $labels = [];
        $series = [];
        $colorList = [];

        foreach ($enumClass::cases() as $case) {
            $labels[] = $case->value;
            $series[] = (int) ($counts[$case->value] ?? 0);
            $colorList[] = $colors[$case->value] ?? '#94a3b8';
        }

        return [
            'labels' => $labels,
            'series' => $series,
            'colors' => $colorList,
        ];
    }
}
