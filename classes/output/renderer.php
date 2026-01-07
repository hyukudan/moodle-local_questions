<?php
/**
 * Renderer for local_questions.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;

class renderer extends plugin_renderer_base {

    /**
     * Render the info page.
     *
     * @param bool $enable_features Whether features are enabled.
     * @return string
     */
    public function render_info_page(bool $enable_features): string {
        $data = [
            'enable_features' => $enable_features,
        ];
        return $this->render_from_template('local_questions/info_page', $data);
    }
    /**
     * Render the dashboard page.
     *
     * @param int $total_questions Total number of questions.
     * @param bool $enable_features Whether features are enabled.
     * @return string
     */
    public function render_dashboard(int $total_questions, bool $enable_features): string {
        $data = [
            'total_questions' => $total_questions,
            'enable_features' => $enable_features,
        ];
        return $this->render_from_template('local_questions/dashboard', $data);
    }

    /**
     * Render the tabs navigation.
     *
     * @param string $currenttab The current active tab.
     * @return string
     */
    public function render_tabs(string $currenttab): string {
        $tabs = [
            'dashboard' => [
                'name' => get_string('dashboard', 'local_questions'),
                'url' => new \moodle_url('/local/questions/index.php', ['tab' => 'dashboard'])
            ],
            'questions' => [
                'name' => get_string('questions', 'local_questions'),
                'url' => new \moodle_url('/local/questions/index.php', ['tab' => 'questions'])
            ],
        ];

        // Add export tab if enabled.
        if (get_config('local_questions', 'enable_export')) {
            $tabs['export'] = [
                'name' => get_string('export', 'local_questions'),
                'url' => new \moodle_url('/local/questions/export.php')
            ];
        }

        // Add import tab if enabled.
        if (get_config('local_questions', 'enable_import')) {
            $tabs['import'] = [
                'name' => get_string('import', 'local_questions'),
                'url' => new \moodle_url('/local/questions/import.php')
            ];
        }

        // Add flags tab if enabled and user has capability.
        if (get_config('local_questions', 'enable_flagging')) {
            $syscontext = \context_system::instance();
            if (has_capability('local/questions:reviewflags', $syscontext)) {
                $counts = \local_questions\flag_manager::count_by_status();
                $pendingcount = $counts[\local_questions\flag_manager::STATUS_PENDING] ?? 0;
                $badgehtml = $pendingcount > 0 ? ' <span class="badge bg-warning text-dark">' . $pendingcount . '</span>' : '';
                $tabs['flags'] = [
                    'name' => get_string('flags', 'local_questions') . $badgehtml,
                    'url' => new \moodle_url('/local/questions/index.php', ['tab' => 'flags'])
                ];
            }
        }

        $data = ['tabs' => []];
        foreach ($tabs as $key => $tab) {
            $data['tabs'][] = [
                'name' => $tab['name'],
                'url' => $tab['url']->out(false),
                'active' => $key === $currenttab
            ];
        }
        return $this->render_from_template('local_questions/tabs', $data);
    }

    /**
     * Render the questions table view.
     *
     * @param int $categoryid The selected category ID.
     * @param bool $recurse Whether to include subcategories.
     * @return string
     */
    /**
     * Render the questions table view.
     *
     * @param int $categoryid The selected category ID.
     * @param bool $recurse Whether to include subcategories.
     * @param int $page Current page number (0-based).
     * @param int $perpage Number of items per page.
     * @return string
     */
    public function render_questions_view(int $categoryid, bool $recurse = false, int $page = 0, int $perpage = 20): string {
        global $DB, $CFG;
        
        // Fetch categories.
        $categories = $DB->get_records('question_categories', null, 'parent ASC, sortorder ASC, name ASC', 'id, name, parent');
        $catoptions = [];
        foreach ($categories as $cat) {
            $catoptions[] = [
                'id' => $cat->id,
                'name' => format_string($cat->name),
                'selected' => $cat->id == $categoryid
            ];
        }

        // Fetch questions.
        $questions = [];
        $totalcount = 0;
        $paginationHtml = '';

        if ($categoryid) {
            $catids = [$categoryid];
            if ($recurse) {
                // Simple recursion to find children (optimize with subquery in real prod)
                $subcats = $DB->get_records('question_categories', ['parent' => $categoryid]);
                foreach ($subcats as $sub) {
                    $catids[] = $sub->id;
                }
            }

            list($insql, $inparams) = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED, 'cat');

            // Moodle 4.x: question.category moved to question_bank_entries.questioncategoryid
            // We need to JOIN through question_versions to get questions in a category.
            $countsql = "SELECT COUNT(DISTINCT q.id)
                           FROM {question} q
                           JOIN {question_versions} qv ON qv.questionid = q.id
                           JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                          WHERE qbe.questioncategoryid $insql
                            AND qv.status = 'ready'";

            $totalcount = $DB->count_records_sql($countsql, $inparams);

            // Build the main query for fetching questions.
            // Include category context to build proper edit URLs.
            $selectsql = "SELECT q.*, qbe.questioncategoryid, qc.contextid
                            FROM {question} q
                            JOIN {question_versions} qv ON qv.questionid = q.id
                            JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                            JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
                           WHERE qbe.questioncategoryid $insql
                             AND qv.status = 'ready'
                             AND qv.version = (
                                 SELECT MAX(qv2.version)
                                   FROM {question_versions} qv2
                                  WHERE qv2.questionbankentryid = qv.questionbankentryid
                             )
                        ORDER BY q.id ASC";

            // Get paged records.
            if ($perpage > 0) {
                $questions = $DB->get_records_sql($selectsql, $inparams, $page * $perpage, $perpage);

                // Render pagination bar.
                $baseurl = new \moodle_url('/local/questions/index.php', [
                    'tab' => 'questions',
                    'cat' => $categoryid,
                    'recurse' => $recurse,
                    'perpage' => $perpage
                ]);
                $paginationHtml = $this->render(new \paging_bar($totalcount, $page, $perpage, $baseurl));
            } else {
                // Show all.
                $questions = $DB->get_records_sql($selectsql, $inparams);
            }
        }

        $qdata = [];
        foreach ($questions as $q) {
            // Fetch answers.
            $answers = $DB->get_records('question_answers', ['question' => $q->id], 'id ASC');
            $answerview = [];
            foreach ($answers as $a) {
                $iscorrect = $a->fraction > 0.9;
                $answerview[] = [
                    'id' => $a->id,
                    'answer' => strip_tags($a->answer),
                    'feedback' => strip_tags($a->feedback),
                    'iscorrect' => $iscorrect,
                    'class' => $iscorrect ? 'text-success font-weight-bold' : ''
                ];
            }

            // Build edit URL for the question.
            // Moodle 4.x requires courseid parameter for the question editor.
            $courseid = SITEID; // Default to site course.
            if (!empty($q->contextid)) {
                $context = \context::instance_by_id($q->contextid, IGNORE_MISSING);
                if ($context) {
                    $coursecontext = $context->get_course_context(false);
                    if ($coursecontext) {
                        $courseid = $coursecontext->instanceid;
                    }
                }
            }
            // Use question.php which accepts courseid without requiring cmid.
            $editurl = new \moodle_url('/question/question.php', [
                'id' => $q->id,
                'courseid' => $courseid,
            ]);

            $qdata[] = [
                'id' => $q->id,
                'name' => $q->name,
                'questiontext' => strip_tags($q->questiontext),
                'generalfeedback' => strip_tags($q->generalfeedback ?? ''),
                'hasgeneralfeedback' => !empty(trim(strip_tags($q->generalfeedback ?? ''))),
                'qtype' => $q->qtype,
                'answers' => $answerview,
                'hasanswers' => !empty($answerview),
                'editurl' => $editurl->out(false),
            ];
        }

        // Per page options
        $perpageoptions = [20, 50, 100, 0]; // 0 for All
        $perpageview = [];
        foreach ($perpageoptions as $opt) {
            $perpageview[] = [
                'value' => $opt,
                'name' => $opt === 0 ? get_string('all', 'core') : $opt,
                'selected' => $perpage == $opt
            ];
        }

        $data = [
            'options' => $catoptions,
            'hasquestions' => !empty($qdata),
            'questions' => $qdata,
            'totalcount' => $totalcount,
            'selectedcategory' => $categoryid,
            'recurse' => $recurse,
            'pagination' => $paginationHtml,
            'perpageoptions' => $perpageview
        ];

        return $this->render_from_template('local_questions/questions_table', $data);
    }



    /**
     * Render the export form.
     *
     * @return string
     */
    public function render_export_form(): string {
        global $DB;

        // Fetch categories.
        $categories = $DB->get_records('question_categories', null, 'parent ASC, sortorder ASC, name ASC', 'id, name, parent');
        $catoptions = [];
        foreach ($categories as $cat) {
            $catoptions[] = [
                'id' => $cat->id,
                'name' => format_string($cat->name),
                'selected' => false
            ];
        }

        // Get available question types.
        $qtypes = \question_bank::get_creatable_qtypes();
        $qtypeoptions = [];
        foreach ($qtypes as $qtype) {
            $qtypeoptions[] = [
                'value' => $qtype->name(),
                'name' => $qtype->local_name()
            ];
        }

        $data = [
            'categories' => $catoptions,
            'qtypes' => $qtypeoptions,
            'actionurl' => (new \moodle_url('/local/questions/export.php'))->out(false),
            'sesskey' => sesskey()
        ];

        return $this->render_from_template('local_questions/export_form', $data);
    }

    /**
     * Render the import form.
     *
     * @param int $categoryid Selected category (for preview/confirm stages).
     * @param array|null $preview Preview data from CSV parsing.
     * @param int|null $draftid Draft item ID for the uploaded file.
     * @param array|null $results Import results.
     * @return string
     */
    public function render_import_form(int $categoryid = 0, ?array $preview = null, ?int $draftid = null, ?array $results = null): string {
        global $DB;

        // Fetch categories.
        $categories = $DB->get_records('question_categories', null, 'parent ASC, sortorder ASC, name ASC', 'id, name, parent');
        $catoptions = [];
        foreach ($categories as $cat) {
            $catoptions[] = [
                'id' => $cat->id,
                'name' => format_string($cat->name),
                'selected' => $cat->id == $categoryid
            ];
        }

        $data = [
            'categories' => $catoptions,
            'actionurl' => (new \moodle_url('/local/questions/import.php'))->out(false),
            'cancelurl' => (new \moodle_url('/local/questions/import.php'))->out(false),
            'sesskey' => sesskey(),
            'categoryid' => $categoryid,
            'haspreview' => !empty($preview),
            'preview' => $preview,
            'previewcount' => $preview ? count($preview) : 0,
            'draftid' => $draftid,
            'hasresults' => !empty($results),
            'imported' => $results['imported'] ?? 0,
            'skipped' => $results['skipped'] ?? 0,
            'errors' => $results['errors'] ?? [],
            'haserrors' => !empty($results['errors'])
        ];

        return $this->render_from_template('local_questions/import_form', $data);
    }

    /**
     * Render the flags review tab.
     *
     * @param string $filter Filter by status (pending, reviewing, resolved, dismissed, or empty for all).
     * @return string
     */
    public function render_flags_tab(string $filter = ''): string {
        global $PAGE;

        // Load JS module.
        $PAGE->requires->js_call_amd('local_questions/flag_review', 'init');

        // Get data.
        $flaggedquestions = \local_questions\flag_manager::get_flagged_questions(
            !empty($filter) ? $filter : null
        );
        $counts = \local_questions\flag_manager::count_by_status();
        $reasons = \local_questions\flag_manager::get_reasons();
        $statuses = \local_questions\flag_manager::get_statuses();

        // Check capability.
        $syscontext = \context_system::instance();
        $canresolve = has_capability('local/questions:resolveflags', $syscontext);

        // Format questions for template.
        $formattedquestions = [];
        foreach ($flaggedquestions as $q) {
            $isresolvable = in_array($q->status, [
                \local_questions\flag_manager::STATUS_PENDING,
                \local_questions\flag_manager::STATUS_REVIEWING
            ]);

            $statusbadge = $this->get_status_badge($q->status);

            $formattedquestions[] = [
                'id' => $q->id,
                'questionid' => $q->questionid,
                'questionname' => $q->questionname ?? '',
                'questiontext_preview' => strip_tags($q->questiontext_preview ?? ''),
                'categoryname' => $q->categoryname ?? '',
                'status' => $q->status,
                'status_badge' => $statusbadge,
                'flagcount' => $q->flagcount,
                'topreason' => \local_questions\flag_manager::get_top_reason($q->questionid) ?? '',
                'topreason_label' => $reasons[\local_questions\flag_manager::get_top_reason($q->questionid)] ?? '',
                'lastflag_date' => userdate($q->timemodified, get_string('strftimedatetimeshort', 'langconfig')),
                'canresolve' => $canresolve,
                'isresolvable' => $isresolvable,
            ];
        }

        $data = [
            'flagged_questions' => $formattedquestions,
            'hasflaggedquestions' => !empty($formattedquestions),
            'counts' => [
                'all' => $counts['all'],
                'pending' => $counts[\local_questions\flag_manager::STATUS_PENDING],
                'reviewing' => $counts[\local_questions\flag_manager::STATUS_REVIEWING],
                'resolved' => $counts[\local_questions\flag_manager::STATUS_RESOLVED],
                'dismissed' => $counts[\local_questions\flag_manager::STATUS_DISMISSED],
            ],
            'filter' => $filter,
            'filter_all' => empty($filter) || $filter === 'all',
            'filter_pending' => $filter === 'pending',
            'filter_reviewing' => $filter === 'reviewing',
            'filter_resolved' => $filter === 'resolved',
            'filter_dismissed' => $filter === 'dismissed',
            'canresolve' => $canresolve,
        ];

        return $this->render_from_template('local_questions/flags_tab', $data);
    }

    /**
     * Get HTML badge for a status (Bootstrap 5 compatible).
     *
     * @param string $status The status code.
     * @return string HTML badge.
     */
    private function get_status_badge(string $status): string {
        $badges = [
            'pending' => '<span class="badge bg-warning text-dark">' . get_string('status_pending', 'local_questions') . '</span>',
            'reviewing' => '<span class="badge bg-info">' . get_string('status_reviewing', 'local_questions') . '</span>',
            'resolved' => '<span class="badge bg-success">' . get_string('status_resolved', 'local_questions') . '</span>',
            'dismissed' => '<span class="badge bg-dark">' . get_string('status_dismissed', 'local_questions') . '</span>',
        ];
        // Security: Escape unknown statuses to prevent XSS.
        return $badges[$status] ?? '<span class="badge bg-secondary">' . s($status) . '</span>';
    }
}

