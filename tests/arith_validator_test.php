<?php
/**
 * Tests for the side-effect-free arithmetic validator.
 *
 * @package    local_questions
 * @category   test
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions;

defined('MOODLE_INTERNAL') || die();

/**
 * Arithmetic validator unit tests.
 */
final class arith_validator_test extends \advanced_testcase {

    /**
     * The two broken questions found in the 28 June forensic analysis have no matching option.
     */
    public function test_detects_real_broken_questions(): void {
        $cases = [
            3338035 => [
                '<p>¿Cuál es el resultado correcto de la siguiente operación?<br>'
                    . '<br>(12+6)×(52−9)÷3=</p>',
                ['96', '88', '102', '90'],
                '(12+6)*(52-9)/3',
                258.0,
            ],
            3338068 => [
                '<p>¿Cuál es el resultado correcto de la siguiente operación?<br>'
                    . '<br>(73+53)×(20−8)÷4=?</p>',
                ['672', '936', '9360', '1404'],
                '(73+53)*(20-8)/4',
                378.0,
            ],
        ];

        foreach ($cases as $questionid => [$question, $answers, $expression, $expected]) {
            $result = arith_validator::validate($question, $answers);

            $this->assertNotNull($result, "q{$questionid} should contain an applicable expression");
            $this->assertSame($expression, $result['expression']);
            $this->assertEqualsWithDelta($expected, $result['expected'], 0.000001);
            $this->assertFalse($result['matches']);
            $this->assertNull($result['matched_answer']);
            $this->assertFalse($result['multiple_matches']);
        }
    }

    /**
     * Five ready questions selected from Cálculo y porcentajes match exactly one option.
     */
    public function test_real_healthy_questions_match(): void {
        $cases = [
            3338527 => [
                'Resuelva el siguiente cálculo: ((71 - 43) x 3) / (61 - 49) + 67',
                ['73', '72', '74', 'ninguna respuesta anterior es correcta'],
                74.0,
                2,
            ],
            3338528 => [
                'Resuelva el siguiente cálculo: (((23 + 9) / (25 - 9)) x 12) / (21 - 7 - 8)',
                ['5', '6', '4', 'ninguna respuesta anterior es correcta'],
                4.0,
                2,
            ],
            3338529 => [
                'Resuelva el siguiente cálculo: ((34 - 15) x 5 + 29) / (83 - 79)',
                ['34', '32', '35', '31'],
                31.0,
                3,
            ],
            3338530 => [
                'Resuelva el siguiente cálculo: ((42 + 66) / 4) x ((42 + 24) / 6)',
                ['286', '324', '297', '295'],
                297.0,
                2,
            ],
            3338531 => [
                'Resuelva el siguiente cálculo: [(45 - 17) x 3 + (21 - 13) x 4] x 3',
                ['351', '327', '348', 'ninguna respuesta anterior es correcta'],
                348.0,
                2,
            ],
        ];

        foreach ($cases as $questionid => [$question, $answers, $expected, $answerindex]) {
            $result = arith_validator::validate($question, $answers);

            $this->assertNotNull($result, "q{$questionid} should contain an applicable expression");
            $this->assertEqualsWithDelta($expected, $result['expected'], 0.000001);
            $this->assertTrue($result['matches'], "q{$questionid} should match an option");
            $this->assertSame($answerindex, $result['matched_answer']);
            $this->assertFalse($result['multiple_matches']);
        }
    }

    /**
     * Comma and dot decimals can participate in the same calculation.
     */
    public function test_decimal_comma_and_point(): void {
        $result = arith_validator::validate(
            'Calcule 12,5 + 0.25 =',
            ['12,70', '12,75', '13']
        );

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(12.75, $result['expected'], 0.000001);
        $this->assertTrue($result['matches']);
        $this->assertSame(1, $result['matched_answer']);
    }

    /**
     * Simple Spanish percentages are evaluated directly.
     */
    public function test_simple_percentage(): void {
        $result = arith_validator::validate(
            '¿Cuánto es el 15% de 240?',
            ['24', '32', '36', '40']
        );

        $this->assertNotNull($result);
        $this->assertSame('15% de 240', $result['expression']);
        $this->assertEqualsWithDelta(36.0, $result['expected'], 0.000001);
        $this->assertTrue($result['matches']);
        $this->assertSame(2, $result['matched_answer']);
    }

    /**
     * Unicode division has normal left-associative arithmetic semantics.
     */
    public function test_division_and_precedence(): void {
        $result = arith_validator::validate(
            'Resultado: 84 ÷ 4 + 3 × 2 =',
            ['24', '27', '42']
        );

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(27.0, $result['expected'], 0.000001);
        $this->assertTrue($result['matches']);
        $this->assertSame(1, $result['matched_answer']);
    }

    /**
     * A colon is accepted as division when the text explicitly frames a calculation.
     */
    public function test_colon_division(): void {
        $result = arith_validator::validate('Calcule (18:3):2 =', ['2', '3', '6']);

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(3.0, $result['expected'], 0.000001);
        $this->assertTrue($result['matches']);
        $this->assertSame(1, $result['matched_answer']);
    }

    /**
     * Fraction operands around an explicit division sign retain their grouping.
     */
    public function test_fraction_operands_around_division(): void {
        $result = arith_validator::validate(
            'Calcule (7/8 ÷ 1/4) =',
            ['7/32', '7/2', '2/7']
        );

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(3.5, $result['expected'], 0.000001);
        $this->assertTrue($result['matches']);
        $this->assertSame(1, $result['matched_answer']);
    }

    /**
     * Prose without a safely identifiable expression is outside the validator.
     */
    public function test_prose_without_expression_does_not_apply(): void {
        $this->assertNull(arith_validator::validate(
            'Indique cuál de las siguientes afirmaciones es correcta.',
            ['La primera.', 'La segunda.', 'La tercera.']
        ));
    }

    /**
     * Statutory article/year notation must not be mistaken for division.
     */
    public function test_statutory_reference_does_not_apply(): void {
        $this->assertNull(arith_validator::validate(
            'Según el art. 25 de la Ley 39/2015, ¿qué plazo resulta aplicable?',
            ['Diez días.', 'Quince días.', 'Un mes.']
        ));
    }

    /**
     * Calendar dates must not be mistaken for repeated subtraction.
     */
    public function test_date_does_not_apply(): void {
        $this->assertNull(arith_validator::validate(
            'El examen se celebró el 24-02-2024.',
            ['Verdadero', 'Falso']
        ));
    }

    /**
     * Colon-separated ratios and algebraic proportions need semantics outside this validator.
     */
    public function test_ratios_and_variable_equations_do_not_apply(): void {
        $this->assertNull(arith_validator::validate(
            'Una cantidad se reparte en razón 2:3:4. ¿Qué parte recibe cada persona?',
            ['2', '3', '4']
        ));
        $this->assertNull(arith_validator::validate(
            'Complete la proporción 6/18 = A/45.',
            ['3', '5', '15']
        ));
    }

    /**
     * A dot with a three-digit group follows the documented Spanish thousands heuristic.
     */
    public function test_spanish_thousands_in_answer(): void {
        $result = arith_validator::validate(
            'Calcule (73+53) × (20-8) - 108 =',
            ['1.404', '1,404', '140']
        );

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(1404.0, $result['expected'], 0.000001);
        $this->assertTrue($result['matches']);
        $this->assertSame(0, $result['matched_answer']);
    }

    /**
     * More than one matching option is exposed independently from the match boolean.
     */
    public function test_multiple_matches_are_reported(): void {
        $result = arith_validator::validate('6 × 7 =', ['42', '41', '42']);

        $this->assertNotNull($result);
        $this->assertTrue($result['matches']);
        $this->assertSame(0, $result['matched_answer']);
        $this->assertTrue($result['multiple_matches']);
    }

    /**
     * Division by zero is not applicable and never reaches PHP eval().
     */
    public function test_division_by_zero_does_not_apply(): void {
        $this->assertNull(arith_validator::validate('Calcule 10 ÷ (3 - 3) =', ['0', '10']));
    }
}
