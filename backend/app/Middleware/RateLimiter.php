<?php

namespace App\Middleware;

/**
 * Simple file-based rate limiter
 * Limits requests per IP + endpoint combination
 */
class RateLimiter
{
    private string $storageDir;

    public function __construct()
    {
        $this->storageDir = sys_get_temp_dir() . '/rate_limit';
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * Check if request is rate limited
     * @param string $key Unique identifier (e.g., IP + action)
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $windowSeconds Time window in seconds
     * @return bool True if allowed, false if rate limited
     */
    public function attempt(string $key, int $maxAttempts = 5, int $windowSeconds = 900): bool
    {
        $file = $this->storageDir . '/' . md5($key) . '.json';

        $data = ['attempts' => [], 'locked_until' => 0];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?: $data;
        }

        $now = time();

        // Check if currently locked out
        if ($data['locked_until'] > $now) {
            return false;
        }

        // Remove expired attempts
        $data['attempts'] = array_filter(
            $data['attempts'],
            fn($timestamp) => ($now - $timestamp) < $windowSeconds
        );

        // Check if under limit
        if (count($data['attempts']) >= $maxAttempts) {
            // Lock out for the window duration
            $data['locked_until'] = $now + $windowSeconds;
            file_put_contents($file, json_encode($data));
            return false;
        }

        // Record this attempt
        $data['attempts'][] = $now;
        file_put_contents($file, json_encode($data));
        return true;
    }

    /**
     * Clear attempts for a key (e.g., on successful login)
     */
    public function clear(string $key): void
    {
        $file = $this->storageDir . '/' . md5($key) . '.json';
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Get remaining attempts
     */
    public function remainingAttempts(string $key, int $maxAttempts = 5, int $windowSeconds = 900): int
    {
        $file = $this->storageDir . '/' . md5($key) . '.json';

        if (!file_exists($file)) {
            return $maxAttempts;
        }

        $data = json_decode(file_get_contents($file), true);
        if (!$data) {
            return $maxAttempts;
        }

        $now = time();
        $recentAttempts = array_filter(
            $data['attempts'] ?? [],
            fn($timestamp) => ($now - $timestamp) < $windowSeconds
        );

        return max(0, $maxAttempts - count($recentAttempts));
    }
}
