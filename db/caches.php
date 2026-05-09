<?php
/**
 * Cache definitions for local_questions.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    'statistics' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 10,
        // 1h TTL aligns with the 6h recalculate_stats cron cadence: short
        // enough that manual question changes show up reasonably soon,
        // long enough not to thrash on dashboard renders. Cache is also
        // invalidated explicitly by the cron after each refresh (see
        // task/recalculate_stats.php), so this is mainly a safety net for
        // out-of-band edits.
        'ttl' => 3600,
    ],
];
