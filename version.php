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
$plugin->version   = 2026073001;  // Add reusable OCR gate and dry-run scanner.
$plugin->requires  = 2024042200;  // Moodle 4.4+ (Bootstrap 5 required).
$plugin->maturity  = MATURITY_BETA;
$plugin->release   = '0.5.4';
