<?php
/**
 * Side-effect-free validator for explicit arithmetic questions.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions;

defined('MOODLE_INTERNAL') || die();

/**
 * Extracts and evaluates explicit arithmetic expressions without using eval().
 *
 * The validator is deliberately conservative: isolated fractions, dates,
 * statutory references and numeric-series notation are not treated as
 * arithmetic unless the surrounding text supplies an unambiguous calculation
 * cue. This makes the class suitable for broad read-only bank scans.
 */
final class arith_validator {

    /** @var float Maximum absolute difference accepted between result and option. */
    private const ANSWER_TOLERANCE = 0.01;

    /**
     * Validate an explicit arithmetic expression against its answer options.
     *
     * Answer indexes are zero-based positions in the supplied array. `matches`
     * means that at least one option matches; callers must also reject
     * `multiple_matches`, because more than one matching option is ambiguous.
     *
     * A null result means that no supported, safely identifiable expression was
     * found. Invalid syntax and division by zero also return null, rather than
     * guessing or executing input as PHP.
     *
     * @param string $questiontext Question statement, as HTML or plain text.
     * @param array $answers Answer strings, records with an answer property, or arrays with an answer key.
     * @return array{expression: string, expected: float, matches: bool,
     *     matched_answer: int|null, multiple_matches: bool}|null Validation result, or null when not applicable.
     */
    public static function validate(string $questiontext, array $answers): ?array {
        $text = self::normalise_text($questiontext);
        if ($text === '') {
            return null;
        }

        $percentage = self::extract_percentage($text);
        if ($percentage !== null) {
            return self::build_result(
                $percentage['expression'],
                $percentage['expected'],
                $answers
            );
        }

        foreach (self::extract_expressions($text) as $expression) {
            $expected = self::evaluate_expression($expression);
            if ($expected === null) {
                continue;
            }

            return self::build_result($expression, $expected, $answers);
        }

        return null;
    }

    /**
     * Compare one computed value with all supplied answer options.
     *
     * @param string $expression Normalised expression.
     * @param float $expected Computed result.
     * @param array $answers Raw answer representations.
     * @return array{expression: string, expected: float, matches: bool,
     *     matched_answer: int|null, multiple_matches: bool}
     */
    private static function build_result(string $expression, float $expected, array $answers): array {
        $matchinganswers = [];
        $answerindex = 0;

        foreach ($answers as $answer) {
            $text = self::answer_text($answer);
            foreach (self::answer_values($text) as $value) {
                if (abs($value - $expected) <= self::ANSWER_TOLERANCE + PHP_FLOAT_EPSILON) {
                    $matchinganswers[] = $answerindex;
                    break;
                }
            }
            $answerindex++;
        }

        return [
            'expression' => $expression,
            'expected' => $expected,
            'matches' => count($matchinganswers) > 0,
            'matched_answer' => $matchinganswers[0] ?? null,
            'multiple_matches' => count($matchinganswers) > 1,
        ];
    }

    /**
     * Extract a simple "N% de M" calculation.
     *
     * @param string $text Normalised visible text.
     * @return array{expression: string, expected: float}|null Percentage data.
     */
    private static function extract_percentage(string $text): ?array {
        $number = '[+-]?(?:\d{1,3}(?:\.\d{3})+(?:,\d+)?|\d+(?:[.,]\d+)?|[.,]\d+)';
        if (!preg_match(
                '/(?<![\p{L}\d])(' . $number . ')\s*%\s+de\s+(' . $number . ')(?![\p{L}\d])/iu',
                $text,
                $matches
            )) {
            return null;
        }

        $percentage = self::parse_number($matches[1]);
        $base = self::parse_number($matches[2]);
        if ($percentage === null || $base === null) {
            return null;
        }

        $expected = ($percentage / 100.0) * $base;
        if (!is_finite($expected)) {
            return null;
        }

        return [
            'expression' => $matches[1] . '% de ' . $matches[2],
            'expected' => $expected,
        ];
    }

    /**
     * Find supported arithmetic candidates in textual order.
     *
     * @param string $text Normalised visible text.
     * @return string[] Canonical ASCII expressions.
     */
    private static function extract_expressions(string $text): array {
        $normalised = self::normalise_math_symbols($text);
        preg_match_all(
            '/[\d\s.,+\-*\/():=\[\]¿?]+/u',
            $normalised,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        $expressions = [];
        foreach ($matches[0] as [$rawcandidate, $offset]) {
            $expression = self::canonicalise_expression($rawcandidate);
            if ($expression === null
                    || !self::is_arithmetic_candidate(
                        $rawcandidate,
                        $expression,
                        $normalised,
                        (int)$offset
                    )) {
                continue;
            }

            $expressions[] = $expression;
        }

        return $expressions;
    }

    /**
     * Decide whether a numeric run is arithmetic rather than metadata or prose.
     *
     * @param string $rawcandidate Candidate before punctuation stripping.
     * @param string $expression Canonical candidate.
     * @param string $text Full normalised statement.
     * @param int $offset Byte offset of the candidate.
     * @return bool True for a safely identifiable arithmetic expression.
     */
    private static function is_arithmetic_candidate(
        string $rawcandidate,
        string $expression,
        string $text,
        int $offset
    ): bool {
        if (!preg_match('/[+\-*\/]/', $expression)
                || !preg_match('/\d/', $expression)) {
            return false;
        }

        preg_match_all('/(?:\d+(?:[.,]\d+)*|[.,]\d+)/', $expression, $numbers);
        if (count($numbers[0]) < 2) {
            return false;
        }

        if (self::is_date_or_time($expression, $rawcandidate)) {
            return false;
        }

        $mathcue = (bool)preg_match(
            '/\b(?:calcul[ae]|calcule|c[aá]lculo|cociente|cu[aá]nto\s+(?:es|son)|'
            . 'divisi[oó]n|expresi[oó]n|multiplicaci[oó]n|operaci[oó]n|producto|'
            . 'resolv[ae]|resuelva|resultado|resta|suma)\b/iu',
            $text
        );
        $before = mb_strcut(
            $text,
            max(0, $offset - 100),
            min(100, $offset),
            'UTF-8'
        );
        $after = substr($text, $offset + strlen($rawcandidate), 100);
        $legalreference = (bool)preg_match(
            '/\b(?:art(?:[ií]culo)?\.?|constituci[oó]n|decreto|ley|orden|'
            . 'reglamento|resoluci[oó]n)\D{0,45}$/iu',
            $before
        );

        // "Ley 39/2015" and similar nearby legal references are never
        // calculations. A genuine "Calcule 39/2015" has no such legal prefix.
        if ($legalreference
                && preg_match('/^\d{1,4}\/\d{4}$/', $expression)) {
            return false;
        }

        // Do not validate only the numeric fragment of a larger unsupported
        // construction: "10/50 de 5" needs an extra operation, while
        // "6/18 = A/45" is an algebraic proportion with variables.
        if (preg_match('/^\d+(?:[.,]\d+)?\/\d+(?:[.,]\d+)?$/', $expression)
                && (preg_match('/^\s*de\s+\d/iu', $after)
                    || (str_contains($rawcandidate, '=')
                        && preg_match('/^\s*\p{L}/u', $after)))) {
            return false;
        }

        // A colon-separated ratio is data for a word problem, not a chain of
        // divisions. Colons remain supported in explicitly framed operations.
        if (preg_match('/\b(?:proporci[oó]n|raz[oó]n)\b/iu', $text)
                && preg_match('/^\d+(?:\/\d+){2,}$/', $expression)) {
            return false;
        }

        // Hyphen-separated series would otherwise be evaluated as repeated
        // subtraction. Series with an explicit calculation cue remain valid.
        if (!$mathcue
                && preg_match('/\b(?:serie|secuencia|sucesi[oó]n)\b/iu', $text)
                && preg_match('/^\d+(?:-\d+){2,}$/', $expression)) {
            return false;
        }

        $operatorcount = preg_match_all('/[+\-*\/]/', $expression);
        $hasendingequals = (bool)preg_match('/=\s*(?:¿?\s*\?)?\s*$/u', $rawcandidate);
        $hasendingquestion = (bool)preg_match('/(?:¿\s*\?|\?)\s*$/u', $rawcandidate);
        $hasgrouping = str_contains($expression, '(');
        $hasstrongoperator = str_contains($expression, '+') || str_contains($expression, '*');

        // A lone slash or hyphen is far more often a statute, fraction, date,
        // range or identifier. Require visible mathematical framing for it.
        return $mathcue
            || $hasendingequals
            || $hasendingquestion
            || $hasgrouping
            || $hasstrongoperator
            || $operatorcount >= 2;
    }

    /**
     * Reject common date and clock formats before arithmetic parsing.
     *
     * @param string $expression Canonical candidate.
     * @param string $rawcandidate Original candidate, where colons remain visible.
     * @return bool True when the candidate looks like a date or time.
     */
    private static function is_date_or_time(string $expression, string $rawcandidate): bool {
        if (preg_match(
                '/^(?:\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}'
                . '|\d{4}[-\/]\d{1,2}[-\/]\d{1,2})$/',
                $expression
            )) {
            return true;
        }

        $rawcandidate = preg_replace(
            '/^[\s¿?=:;,\.]+|[\s¿?=;,\.]+$/u',
            '',
            $rawcandidate
        );
        $rawcandidate = preg_replace('/\s+/u', '', (string)$rawcandidate);
        return (bool)preg_match(
            '/^\d{1,2}:\d{2}(?:-\d{1,2}:\d{2})?$/',
            (string)$rawcandidate
        );
    }

    /**
     * Strip framing punctuation and convert a candidate to parser syntax.
     *
     * @param string $candidate Raw candidate.
     * @return string|null Canonical expression, or null for an empty run.
     */
    private static function canonicalise_expression(string $candidate): ?string {
        $candidate = preg_replace(
            '/^[\s¿?=:;]+|[\s¿?=;]+$/u',
            '',
            $candidate
        );
        $candidate = preg_replace('/(?<=\d)[.,]$/', '', (string)$candidate);
        // Never concatenate separate cells/list items into a new number when
        // whitespace is removed (for example "7 10 4" below a table rule).
        if (preg_match('/\d\s+\d/u', (string)$candidate)) {
            return null;
        }
        $candidate = preg_replace('/\s+/u', '', (string)$candidate);
        $candidate = str_replace(['[', ']', ':'], ['(', ')', '/'], (string)$candidate);

        return $candidate === '' ? null : $candidate;
    }

    /**
     * Evaluate a canonical expression with a recursive-descent parser.
     *
     * Grammar:
     * expression := product (("+" | "-") product)*
     * product    := unary (("*" | "/") unary)*
     * unary      := ("+" | "-") unary | primary
     * primary    := NUMBER | "(" expression ")"
     *
     * @param string $expression Canonical expression.
     * @return float|null Result, or null for invalid input/division by zero.
     */
    private static function evaluate_expression(string $expression): ?float {
        $tokens = self::tokenise($expression);
        if ($tokens === null || !$tokens) {
            return null;
        }

        $position = 0;
        $valid = true;
        $value = self::parse_sum($tokens, $position, $valid);
        if (!$valid || $position !== count($tokens) || !is_finite($value)) {
            return null;
        }

        return (float)$value;
    }

    /**
     * Tokenise canonical parser input.
     *
     * @param string $expression Canonical expression.
     * @return array<int, array{type: string, value?: float}>|null Tokens.
     */
    private static function tokenise(string $expression): ?array {
        $tokens = [];
        $length = strlen($expression);
        $position = 0;

        while ($position < $length) {
            $character = $expression[$position];
            if (str_contains('+-*/()', $character)) {
                $tokens[] = ['type' => $character];
                $position++;
                continue;
            }

            if (ctype_digit($character) || $character === '.' || $character === ',') {
                $start = $position;
                while ($position < $length) {
                    $numbercharacter = $expression[$position];
                    if (!ctype_digit($numbercharacter)
                            && $numbercharacter !== '.'
                            && $numbercharacter !== ',') {
                        break;
                    }
                    $position++;
                }

                $number = self::parse_number(substr($expression, $start, $position - $start));
                if ($number === null) {
                    return null;
                }
                $tokens[] = [
                    'type' => 'number',
                    'value' => $number,
                ];
                continue;
            }

            return null;
        }

        return $tokens;
    }

    /**
     * Parse addition and subtraction.
     *
     * @param array $tokens Parser tokens.
     * @param int $position Current token position.
     * @param bool $valid Parser validity flag.
     * @return float Parsed value.
     */
    private static function parse_sum(array $tokens, int &$position, bool &$valid): float {
        $value = self::parse_product($tokens, $position, $valid);

        while ($valid && isset($tokens[$position])
                && in_array($tokens[$position]['type'], ['+', '-'], true)) {
            $operator = $tokens[$position]['type'];
            $position++;
            $right = self::parse_product($tokens, $position, $valid);
            $value = $operator === '+' ? $value + $right : $value - $right;
        }

        return $value;
    }

    /**
     * Parse multiplication and division.
     *
     * @param array $tokens Parser tokens.
     * @param int $position Current token position.
     * @param bool $valid Parser validity flag.
     * @return float Parsed value.
     */
    private static function parse_product(array $tokens, int &$position, bool &$valid): float {
        $value = self::parse_unary($tokens, $position, $valid);

        while ($valid && isset($tokens[$position])
                && in_array($tokens[$position]['type'], ['*', '/'], true)) {
            $operator = $tokens[$position]['type'];
            $position++;
            $right = self::parse_unary($tokens, $position, $valid);
            if (!$valid) {
                break;
            }

            if ($operator === '/') {
                if ($right == 0.0) {
                    $valid = false;
                    break;
                }
                $value /= $right;
            } else {
                $value *= $right;
            }
        }

        return $value;
    }

    /**
     * Parse unary signs.
     *
     * @param array $tokens Parser tokens.
     * @param int $position Current token position.
     * @param bool $valid Parser validity flag.
     * @return float Parsed value.
     */
    private static function parse_unary(array $tokens, int &$position, bool &$valid): float {
        if (!$valid || !isset($tokens[$position])) {
            $valid = false;
            return 0.0;
        }

        if (in_array($tokens[$position]['type'], ['+', '-'], true)) {
            $operator = $tokens[$position]['type'];
            $position++;
            $value = self::parse_unary($tokens, $position, $valid);
            return $operator === '-' ? -$value : $value;
        }

        return self::parse_primary($tokens, $position, $valid);
    }

    /**
     * Parse a number or parenthesised expression.
     *
     * @param array $tokens Parser tokens.
     * @param int $position Current token position.
     * @param bool $valid Parser validity flag.
     * @return float Parsed value.
     */
    private static function parse_primary(array $tokens, int &$position, bool &$valid): float {
        if (!$valid || !isset($tokens[$position])) {
            $valid = false;
            return 0.0;
        }

        $token = $tokens[$position];
        if ($token['type'] === 'number') {
            $position++;
            return $token['value'];
        }

        if ($token['type'] !== '(') {
            $valid = false;
            return 0.0;
        }

        $position++;
        $value = self::parse_sum($tokens, $position, $valid);
        if (!$valid || !isset($tokens[$position]) || $tokens[$position]['type'] !== ')') {
            $valid = false;
            return 0.0;
        }
        $position++;

        return $value;
    }

    /**
     * Parse Spanish-formatted or plain decimal numbers.
     *
     * Heuristic for the ambiguous dot: one dot followed by exactly three
     * digits is treated as a Spanish thousands separator (`1.404` => 1404),
     * except when the integer part is zero or has more than three digits.
     * Other single dots are decimal; a single comma is decimal. Repeated
     * three-digit groups are thousands, and mixed separators use the last
     * separator as the decimal mark (`1.234,56` and `1,234.56`).
     *
     * @param string $raw Raw numeric token.
     * @return float|null Parsed value.
     */
    private static function parse_number(string $raw): ?float {
        $text = str_replace([" ", "\xc2\xa0"], '', trim($raw));
        if ($text === '') {
            return null;
        }

        $sign = 1.0;
        if ($text[0] === '+' || $text[0] === '-') {
            if ($text[0] === '-') {
                $sign = -1.0;
            }
            $text = substr($text, 1);
        }
        if ($text === '' || !preg_match('/^(?:\d+(?:[.,]\d+)*|[.,]\d+)$/', $text)) {
            return null;
        }

        $commas = substr_count($text, ',');
        $dots = substr_count($text, '.');
        $normalised = $text;

        if ($commas > 0 && $dots > 0) {
            $lastcomma = strrpos($text, ',');
            $lastdot = strrpos($text, '.');
            if ($lastcomma > $lastdot) {
                $normalised = str_replace('.', '', $text);
                $normalised = str_replace(',', '.', $normalised);
            } else {
                $normalised = str_replace(',', '', $text);
            }
        } else if ($commas === 1) {
            $normalised = str_replace(',', '.', $text);
        } else if ($commas > 1) {
            $groups = explode(',', $text);
            if (!self::has_thousands_groups($groups)) {
                return null;
            }
            $normalised = implode('', $groups);
        } else if ($dots === 1) {
            [$integer, $fraction] = explode('.', $text, 2);
            $isthousands = $integer !== ''
                && $integer !== '0'
                && strlen($integer) <= 3
                && strlen($fraction) === 3;
            $normalised = $isthousands ? $integer . $fraction : $text;
        } else if ($dots > 1) {
            $groups = explode('.', $text);
            if (!self::has_thousands_groups($groups)) {
                return null;
            }
            $normalised = implode('', $groups);
        }

        if (!is_numeric($normalised)) {
            return null;
        }

        $value = $sign * (float)$normalised;
        return is_finite($value) ? $value : null;
    }

    /**
     * Check conventional three-digit thousands groups.
     *
     * @param string[] $groups Separator-delimited groups.
     * @return bool True for a valid grouped integer.
     */
    private static function has_thousands_groups(array $groups): bool {
        if (!$groups || $groups[0] === '' || strlen($groups[0]) > 3) {
            return false;
        }

        foreach (array_slice($groups, 1) as $group) {
            if (strlen($group) !== 3 || !ctype_digit($group)) {
                return false;
            }
        }

        return ctype_digit($groups[0]);
    }

    /**
     * Parse all numeric meanings carried by one answer.
     *
     * A bare supported expression such as `95/42` is evaluated as a whole.
     * Otherwise every visible numeric token is considered; a matching answer
     * still counts only once.
     *
     * @param string $answer Normalised answer text.
     * @return float[] Parsed values.
     */
    private static function answer_values(string $answer): array {
        if ($answer === '') {
            return [];
        }

        $mathanswer = self::normalise_math_symbols($answer);
        $expression = self::canonicalise_expression($mathanswer);
        if ($expression !== null
                && preg_match('/[+\-*\/]/', $expression)
                && preg_match('/^[\d.,+\-*\/()]+$/', $expression)) {
            $value = self::evaluate_expression($expression);
            if ($value !== null) {
                return [$value];
            }
        }

        $number = '[+-]?(?:\d{1,3}(?:\.\d{3})+(?:,\d+)?|\d+(?:[.,]\d+)?|[.,]\d+)';
        preg_match_all('/(?<![\p{L}\d])' . $number . '(?![\p{L}\d])/u', $answer, $matches);

        $values = [];
        foreach ($matches[0] as $rawvalue) {
            $value = self::parse_number($rawvalue);
            if ($value !== null) {
                $values[(string)$value] = $value;
            }
        }

        return array_values($values);
    }

    /**
     * Get visible text from a supported answer representation.
     *
     * @param mixed $answer Raw answer.
     * @return string Normalised visible text.
     */
    private static function answer_text($answer): string {
        if (is_object($answer) && isset($answer->answer)) {
            $answer = $answer->answer;
        } else if (is_array($answer) && array_key_exists('answer', $answer)) {
            $answer = $answer['answer'];
        }

        if (!is_scalar($answer) && $answer !== null) {
            return '';
        }

        return self::normalise_text((string)$answer);
    }

    /**
     * Convert HTML or plain text to stable visible text.
     *
     * @param string $text Raw text.
     * @return string Normalised visible text.
     */
    private static function normalise_text(string $text): string {
        $text = preg_replace('/<\s*(?:br\s*\/?|\/p|\/div|\/li|\/tr)\s*>/iu', "\n", $text);
        $text = strip_tags($text ?? '');
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace('/[^\S\n]+/u', ' ', $text);
        $text = preg_replace('/\s*\n\s*/u', "\n", $text);
        return trim($text ?? '');
    }

    /**
     * Convert supported Unicode/operator variants to parser symbols.
     *
     * The letter x is only multiplication when it is between numeric/grouping
     * operands, so ordinary words are not modified.
     *
     * @param string $text Visible text.
     * @return string Text using ASCII parser symbols.
     */
    private static function normalise_math_symbols(string $text): string {
        $number = '(?:\d+(?:[.,]\d+)?)';
        $text = preg_replace(
            '/(' . $number . '\/' . $number . ')\s*÷\s*(' . $number . '\/' . $number . ')/u',
            '($1)/($2)',
            $text
        ) ?? $text;
        $text = str_replace(
            ['−', '–', '—', '×', '✕', '÷', '[', ']'],
            ['-', '-', '-', '*', '*', '/', '(', ')'],
            $text
        );
        return preg_replace(
            '/(?<=[\d)])\s*[xX]\s*(?=[\d(])/u',
            '*',
            $text
        ) ?? $text;
    }
}
