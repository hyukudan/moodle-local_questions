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
}
