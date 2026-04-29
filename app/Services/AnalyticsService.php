<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function all(string $from, string $to): array
    {
        return [
            'skill_demand' => $this->skillDemand($from, $to),
            'application_volume' => $this->applicationVolume($from, $to),
            'registration_trends' => $this->registrationTrends($from, $to),
            'skill_distribution' => $this->skillDistribution(),
            'geographic_activity' => $this->geographicActivity($from, $to)
        ];
    }

    public function skillDemand(string $from, string $to): array
    {
        return DB::select("
            SELECT jp.category, COUNT(jp.id) AS total_postings
            FROM job_posts jp
            WHERE jp.deleted_at IS NULL AND jp.created_at BETWEEN ? AND ?
            GROUP BY jp.category 
            ORDER BY total_postings DESC
        ", [$from, $to]);
    }

    public function applicationVolume(string $from, string $to): array
    {
        return DB::select("
            SELECT jp.id AS job_post_id, jp.reference_number, jp.title, jp.category,
                   COUNT(a.id) AS total_applications,
                   SUM(CASE WHEN a.status='pending' THEN 1 ELSE 0 END) AS pending,
                   SUM(CASE WHEN a.status='pending_negotiation' THEN 1 ELSE 0 END) AS in_negotiation,
                   SUM(CASE WHEN a.status='employer_confirmed' THEN 1 ELSE 0 END) AS confirmed,
                   SUM(CASE WHEN a.status='accepted' THEN 1 ELSE 0 END) AS accepted,
                   SUM(CASE WHEN a.status='completed' THEN 1 ELSE 0 END) AS completed,
                   SUM(CASE WHEN a.status='rejected' THEN 1 ELSE 0 END) AS rejected,
                   SUM(CASE WHEN a.status='withdrawn' THEN 1 ELSE 0 END) AS withdrawn
            FROM job_posts jp
            LEFT JOIN applications a ON a.job_post_id = jp.id
            WHERE jp.deleted_at IS NULL AND jp.created_at BETWEEN ? AND ?
            GROUP BY jp.id, jp.reference_number, jp.title, jp.category
            ORDER BY total_applications DESC 
            LIMIT 50
        ", [$from, $to]);
    }

    public function registrationTrends(string $from, string $to): array
    {
        return DB::select("
            SELECT TO_CHAR(created_at, 'YYYY-MM') AS month, role, municipality, COUNT(id) AS registrations
            FROM users
            WHERE created_at BETWEEN ? AND ?
            GROUP BY month, role, municipality
            ORDER BY month ASC, registrations DESC
        ", [$from, $to]);
    }

    public function skillDistribution(): array
    {
        return DB::select("
            SELECT s.category, s.name AS skill_name, COUNT(ws.worker_profile_id) AS worker_count
            FROM worker_skill ws
            JOIN skills s ON s.id = ws.skill_id
            JOIN worker_profiles wp ON wp.id = ws.worker_profile_id
            JOIN users u ON u.id = wp.user_id
            WHERE u.is_suspended = FALSE AND u.verification_status = 'approved'
            GROUP BY s.category, s.name
            ORDER BY s.category ASC, worker_count DESC
        ");
    }

    public function geographicActivity(string $from, string $to): array
    {
        return DB::select("
            SELECT jp.municipality, jp.barangay,
                   COUNT(DISTINCT jp.id) AS job_postings,
                   COUNT(a.id) AS total_applications
            FROM job_posts jp
            LEFT JOIN applications a ON a.job_post_id = jp.id
            WHERE jp.deleted_at IS NULL AND jp.created_at BETWEEN ? AND ?
            GROUP BY jp.municipality, jp.barangay
            ORDER BY jp.municipality ASC, job_postings DESC
        ", [$from, $to]);
    }
}
