<?php
/**
 * Tests for the reusable OCR quality gate.
 *
 * @package    local_questions
 * @category   test
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions;

defined('MOODLE_INTERNAL') || die();

/**
 * OCR gate unit tests.
 */
final class ocr_gate_test extends \advanced_testcase {

    /**
     * A normal question has no symptoms.
     */
    public function test_clean_question_has_no_symptoms(): void {
        $this->assertSame([], ocr_gate::scan_question(
            '<p>¿Cuál de las siguientes opciones es correcta?</p>',
            ['La primera.', 'La segunda.', 'La tercera.', 'La cuarta.']
        ));
    }

    /**
     * Strong statement-level signals are assigned stable codes and severities.
     */
    public function test_detects_statement_signals(): void {
        $symptoms = ocr_gate::scan_question(
            '<p>eCuál es correcta según la Ley 40/2015,de 1 de octubre?</p>',
            []
        );
        $bycode = array_column($symptoms, 'severity', 'code');

        $this->assertSame(
            ocr_gate::SEVERITY_GRAVE,
            $bycode[ocr_gate::CODE_QUESTION_MARK_WITHOUT_OPENING]
        );
        $this->assertSame(
            ocr_gate::SEVERITY_GRAVE,
            $bycode[ocr_gate::CODE_OCR_INTERROGATIVE_PREFIX]
        );
        $this->assertSame(
            ocr_gate::SEVERITY_AVISO,
            $bycode[ocr_gate::CODE_COMMA_WITHOUT_SPACE]
        );
        $this->assertSame(
            ocr_gate::SEVERITY_AVISO,
            $bycode[ocr_gate::CODE_LOWERCASE_START]
        );
    }

    /**
     * Damage in answers participates in the scan.
     */
    public function test_detects_answer_damage(): void {
        $symptoms = ocr_gate::scan_question(
            '¿Qué opción contiene daño OCR?',
            ['Una c0sa.', 'Seguridad Sociat.', 'Limpia.', 'También limpia.']
        );
        $codes = array_column($symptoms, 'code');

        $this->assertContains(ocr_gate::CODE_ZERO_AS_LETTER, $codes);
        $this->assertContains(ocr_gate::CODE_KNOWN_BROKEN_WORD, $codes);
    }

    /**
     * Numeric data transcribed into prose suppresses a missing-matrix signal.
     */
    public function test_reference_with_embedded_data_is_clean(): void {
        $symptoms = ocr_gate::scan_question(
            'Los números de la matriz son: fila 1: 2, 4, 6; fila 2: 8, 10, 12.',
            ['14', '16', '18', '20']
        );
        $codes = array_column($symptoms, 'code');

        $this->assertNotContains(ocr_gate::CODE_MISSING_REFERENCED_CONTEXT, $codes);
    }

    /**
     * An external reference with no embedded data remains a grave symptom.
     */
    public function test_reference_without_data_is_grave(): void {
        $symptoms = ocr_gate::scan_question(
            'Supuesto 1, preg. 2: Señale la actuación correcta ante el cuadro anterior:',
            ['Primera.', 'Segunda.', 'Tercera.', 'Cuarta.']
        );
        $bycode = array_column($symptoms, 'severity', 'code');

        $this->assertSame(
            ocr_gate::SEVERITY_GRAVE,
            $bycode[ocr_gate::CODE_MISSING_REFERENCED_CONTEXT]
        );
    }

    /**
     * A numbered case heading followed by self-contained prose is not an external reference.
     */
    public function test_case_heading_with_prose_data_is_clean(): void {
        $symptoms = ocr_gate::scan_question(
            'Supuesto 2, preg. 2: La persona está inconsciente, pero respira. ¿Qué debe hacerse?',
            ['Comprobar el pulso.', 'Buscar sangrado.', 'Posición lateral.', 'Levantar las piernas.']
        );
        $codes = array_column($symptoms, 'code');

        $this->assertNotContains(ocr_gate::CODE_MISSING_REFERENCED_CONTEXT, $codes);
    }

    /**
     * Catalan questions correctly use only the closing question mark.
     */
    public function test_catalan_question_mark_is_not_ocr_damage(): void {
        $symptoms = ocr_gate::scan_question(
            'El Sr. Manel vol posar una reclamació. Quins són els terminis de resposta?',
            []
        );
        $codes = array_column($symptoms, 'code');

        $this->assertNotContains(ocr_gate::CODE_QUESTION_MARK_WITHOUT_OPENING, $codes);
    }
}
