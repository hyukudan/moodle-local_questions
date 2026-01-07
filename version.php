<?php
/**
 * Plugin version information.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_questions';
$plugin->version   = 2026010701;  // Added db tables, caching, navigation.
$plugin->requires  = 2022111800;  // Moodle 4.1
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.2.0';
