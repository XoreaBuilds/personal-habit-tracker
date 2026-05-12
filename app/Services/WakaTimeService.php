<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Models\FocusSession;

/**
 * Class WakaTimeService
 * 
 * Handles all interactions with the WakaTime API to fetch coding statistics
 * and synchronize them with the local database.
 */
class WakaTimeService
{
    /** @var string|null The user's WakaTime API Key */
    private ?string $apiKey;

    /** @var string The base URL for WakaTime API v1 */
    private string $baseUrl = 'https://wakatime.com/api/v1';

    /**
     * WakaTimeService constructor.
     * Initializes the API key from the authenticated user or the environment configuration.
     */
    public function __construct()
    {
        $this->apiKey = auth()->user()?->wakatime_api_key
            ?? config('services.wakatime.api_key')
            ?? null;

        $this->baseUrl = config('services.wakatime.base_url', 'https://wakatime.com/api/v1');
    }

    /**
     * Check if a valid API key is configured for the service.
     * 
     * @return bool
     */
    public function hasApiKey(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get the standard headers for WakaTime API requests.
     * 
     * @return array
     */
    private function headers(): array
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
        ];
    }

    /**
     * Fetch the coding stats for the current day.
     * Results are cached for 5 minutes (300 seconds) to prevent API rate limiting.
     * 
     * @return array
     */
    public function todayStats(?int $userId = null): array
    {
        if (!$this->hasApiKey())
            return [];

        $userId = $userId ?? Auth::id();
        return Cache::remember("wakatime_today_{$userId}", 300, function () {
            $res = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/users/current/status_bar/today");

            if ($res->failed())
                return [];

            $data = $res->json('data');

            return [
                'total_seconds' => $data['grand_total']['total_seconds'] ?? 0,
                'human_readable' => $data['grand_total']['text'] ?? '0 mins',
                'top_language' => $data['languages'][0]['name'] ?? 'N/A',
                'top_project' => $data['projects'][0]['name'] ?? 'N/A',
            ];
        });
    }

    /**
     * Fetch coding summaries for the past year.
     * Used primarily for the focus heatmap visualization.
     * 
     * @return array
     */
    public function yearSummaries(?int $userId = null): array
    {
        if (!$this->hasApiKey())
            return [];

        $userId = $userId ?? Auth::id();
        return Cache::remember("wakatime_year_{$userId}", 3600, function () {
            $start = now()->subYear()->toDateString();
            $end = now()->toDateString();

            $res = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/users/current/summaries", [
                    'start' => $start,
                    'end' => $end,
                ]);

            if ($res->failed())
                return [];

            return collect($res->json('data'))->mapWithKeys(function ($day) {
                return [
                    $day['range']['date'] => $day['grand_total']['total_seconds'] ?? 0
                ];
            })->toArray();
        });
    }

    /**
     * Synchronize WakaTime data into a local FocusSession record.
     * This creates or updates a session for the current day.
     * 
     * @param int $userId           
     * @return void
     */
    public function syncToFocusSession(int $userId): void
    {
        if (!$this->hasApiKey())
            return;

        $stats = $this->todayStats($userId);
        $minutes = (int) round(($stats['total_seconds'] ?? 0) / 60);

        if ($minutes <= 0)
            return;

        FocusSession::updateOrCreate(
            [
                'user_id' => $userId,
                'source' => 'wakatime',
                'date' => today()->toDateString(),
            ],
            [
                'minutes_completed' => $minutes,
                'completed_at' => now(),
            ]
        );
    }

    /**
     * Synchronize the entire past year of WakaTime data for a user.
     * Use this to populate the heatmap for new users.
     * 
     * @param int $userId
     * @return void
     */
    public function syncFullHistory(int $userId): void
    {
        if (!$this->hasApiKey())
            return;

        $summaries = $this->yearSummaries($userId);

        foreach ($summaries as $date => $seconds) {
            $minutes = (int) round($seconds / 60);
            
            if ($minutes > 0) {
                FocusSession::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'source' => 'wakatime',
                        'date' => $date,
                    ],
                    [
                        'minutes_completed' => $minutes,
                        'completed_at' => \Carbon\Carbon::parse($date),
                    ]
                );
            }
        }
    }
}