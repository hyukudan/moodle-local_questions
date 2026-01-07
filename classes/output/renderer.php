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
}
