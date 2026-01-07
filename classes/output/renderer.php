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
    public function render_questions_view(int $categoryid, bool $recurse = false): string {
        global $DB;
        
        // Fetch categories.
        $categories = $DB->get_records('question_categories', null, 'parent ASC, sortorder ASC, name ASC', 'id, name, parent');
        $catoptions = [];
        // Flatten with indentation could be better, but for now simple listing.
        // A proper walker would be ideal, but simplifying for brevity.
        foreach ($categories as $cat) {
            $catoptions[] = [
                'id' => $cat->id,
                'name' => format_string($cat->name),
                'selected' => $cat->id == $categoryid
            ];
        }

        // Fetch questions.
        $questions = [];
        if ($categoryid) {
            $catids = [$categoryid];
            if ($recurse) {
                // Simple recursion to find children (optimize with subquery in real prod)
                $subcats = $DB->get_records('question_categories', ['parent' => $categoryid]);
                // This is a shallow recurse for demo. Real moodle uses question_category_object::get_subcategories
                foreach ($subcats as $sub) {
                    $catids[] = $sub->id;
                }
            }
            
            list($insql, $inparams) = $DB->get_in_or_equal($catids);
            $questions = $DB->get_records_select('question', "category $insql", $inparams);
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

            $qdata[] = [
                'id' => $q->id,
                'name' => $q->name,
                'questiontext' => strip_tags($q->questiontext),
                'qtype' => $q->qtype,
                'answers' => $answerview,
                'hasanswers' => !empty($answerview)
            ];
        }

        $data = [
            'options' => $catoptions,
            'hasquestions' => !empty($qdata),
            'questions' => $qdata,
            'selectedcategory' => $categoryid,
            'recurse' => $recurse
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
}

