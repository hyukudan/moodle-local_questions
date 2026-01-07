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

    // Version 2026010703: Add question flags tables.
    if ($oldversion < 2026010703) {

        // Define table local_questions_flags.
        $table = new xmldb_table('local_questions_flags');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('reason', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('comment', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('questionid', XMLDB_KEY_FOREIGN, ['questionid'], 'question', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        $table->add_index('questionid-userid', XMLDB_INDEX_UNIQUE, ['questionid', 'userid']);
        $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_questions_flag_status.
        $table = new xmldb_table('local_questions_flag_status');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('flagcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('resolvedby', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('resolution', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('resolutionfeedback', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timeresolved', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('questionid', XMLDB_KEY_FOREIGN_UNIQUE, ['questionid'], 'question', ['id']);
        $table->add_key('resolvedby', XMLDB_KEY_FOREIGN, ['resolvedby'], 'user', ['id']);

        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
        $table->add_index('flagcount', XMLDB_INDEX_NOTUNIQUE, ['flagcount']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026010703, 'local', 'questions');
    }

    return true;
}
