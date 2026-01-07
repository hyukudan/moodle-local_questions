<?php
/**
 * Upgrade code for local_questions.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Perform any custom actions on plugin installation or upgrade.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool
 */
function xmldb_local_questions_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Upgrade logic here if needed in the future.
    
    return true;
}
