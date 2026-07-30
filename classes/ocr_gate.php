<?php
/**
 * Reusable OCR quality gate for imported questions.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_questions;

defined('MOODLE_INTERNAL') || die();

/**
 * Detects recurrent OCR damage in a question statement and its answers.
 *
 * Importers should call {@see self::scan_question()} before writing the question
 * version. If any returned item has severity `grave`, create the version as
 * `draft` and submit a `needs_review` flag after the question exists; otherwise
 * the normal `ready` path may continue. For example:
 *
 * <code>
 * $symptoms = ocr_gate::scan_question($questiontext, $answers);
 * $grave = array_filter($symptoms, fn($item) => $item['severity'] === ocr_gate::SEVERITY_GRAVE);
 * $version->status = $grave ? 'draft' : 'ready';
 * // After inserting the question, use flag_manager::submit_flag() with
 * // flag_manager::REASON_NEEDS_REVIEW and a comment containing the symptom codes.
 * </code>
 *
 * The gate is deliberately side-effect free. It never creates questions, changes
 * version statuses, or writes flags itself.
 */
final class ocr_gate {

    /** @var string A strong signal that must block an automatic ready import. */
    public const SEVERITY_GRAVE = 'grave';

    /** @var string A weaker signal that warrants review but may be legitimate. */
    public const SEVERITY_AVISO = 'aviso';

    /** @var string Closing question mark without an opening mark in that sentence. */
    public const CODE_QUESTION_MARK_WITHOUT_OPENING = 'question_mark_without_opening';

    /** @var string OCR glyph glued to a Spanish interrogative word. */
    public const CODE_OCR_INTERROGATIVE_PREFIX = 'ocr_interrogative_prefix';

    /** @var string Repeated semicolons in positions normally occupied by commas. */
    public const CODE_SEMICOLON_AS_COMMA = 'semicolon_as_comma';

    /** @var string The first letter of the statement is lowercase. */
    public const CODE_LOWERCASE_START = 'lowercase_statement_start';

    /** @var string Missing space after a comma before "de" or "por". */
    public const CODE_COMMA_WITHOUT_SPACE = 'comma_without_space';

    /** @var string Statement appears to end in a truncated function word. */
    public const CODE_TRUNCATED_ENDING = 'truncated_ending';

    /** @var string External case/table/image reference without embedded data. */
    public const CODE_MISSING_REFERENCED_CONTEXT = 'missing_referenced_context';

    /** @var string Literal tilde left by OCR. */
    public const CODE_GARBAGE_TILDE = 'garbage_tilde';

    /** @var string Unbalanced straight double quote. */
    public const CODE_UNBALANCED_DOUBLE_QUOTE = 'unbalanced_double_quote';

    /** @var string Zero used where a letter or conjunction is expected. */
    public const CODE_ZERO_AS_LETTER = 'zero_as_letter';

    /** @var string A known recurrent broken or fused word from the OCR batch. */
    public const CODE_KNOWN_BROKEN_WORD = 'known_broken_word';

    /** @var string Unexpected lowercase-uppercase fusion inside a word. */
    public const CODE_UNEXPECTED_MIXED_CASE = 'unexpected_mixed_case';

    /**
     * Scan a question for recurrent OCR damage.
     *
     * Every returned item contains exactly two keys: a stable machine-readable
     * `code` and a `severity` whose value is `grave` or `aviso`.
     *
     * @param string $questiontext Question statement, plain text or HTML.
     * @param array $answers Answer strings, records with an `answer` property, or arrays with an `answer` key.
     * @return array<int, array{code: string, severity: string}> Detected symptoms; an empty array means clean.
     */
    public static function scan_question(string $questiontext, array $answers): array {
        $question = self::normalise_text($questiontext);
        $normalisedanswers = self::normalise_answers($answers);
        $combined = trim(implode("\n", array_merge([$question], $normalisedanswers)));
        $symptoms = [];

        if (self::has_question_mark_without_opening($question)) {
            self::add_symptom(
                $symptoms,
                self::CODE_QUESTION_MARK_WITHOUT_OPENING,
                self::SEVERITY_GRAVE
            );
        }

        if (preg_match(
                '/(?:^|[^\p{L}])[eé4](?:cu[aá]l(?:es)?|qu[eé]|qui[eé]n(?:es)?|'
                . 'c[oó]mo|cu[aá]ndo|d[oó]nde|cu[aá]nt(?:o|os|a|as))\b/iu',
                $combined
            )) {
            self::add_symptom($symptoms, self::CODE_OCR_INTERROGATIVE_PREFIX, self::SEVERITY_GRAVE);
        }

        if (preg_match_all('/\p{L};\s+\p{L}/u', $combined) >= 2) {
            self::add_symptom($symptoms, self::CODE_SEMICOLON_AS_COMMA, self::SEVERITY_AVISO);
        }

        if ($question !== '' && preg_match('/^[^\p{L}]*\p{Ll}/u', $question)) {
            self::add_symptom($symptoms, self::CODE_LOWERCASE_START, self::SEVERITY_AVISO);
        }

        if (preg_match('/,(?:de|por)\b/iu', $combined)) {
            self::add_symptom($symptoms, self::CODE_COMMA_WITHOUT_SPACE, self::SEVERITY_AVISO);
        }

        if (self::has_truncated_ending($question, $normalisedanswers)) {
            self::add_symptom($symptoms, self::CODE_TRUNCATED_ENDING, self::SEVERITY_GRAVE);
        }

        if (self::has_missing_referenced_context($questiontext, $question)) {
            self::add_symptom($symptoms, self::CODE_MISSING_REFERENCED_CONTEXT, self::SEVERITY_GRAVE);
        }

        if (str_contains($combined, '~')) {
            self::add_symptom($symptoms, self::CODE_GARBAGE_TILDE, self::SEVERITY_GRAVE);
        }

        if (self::has_unbalanced_double_quote(array_merge([$question], $normalisedanswers))) {
            self::add_symptom($symptoms, self::CODE_UNBALANCED_DOUBLE_QUOTE, self::SEVERITY_AVISO);
        }

        if (self::has_zero_as_letter($combined)) {
            self::add_symptom($symptoms, self::CODE_ZERO_AS_LETTER, self::SEVERITY_GRAVE);
        }

        // El año OCR "5t2015" va aparte y case-sensitive: "3T2015" es notación trimestral fiscal legítima.
        if (preg_match(
                '/\b(?:Sociat|Gu[aá]l(?:es)?|Gu[aá]lde|Cu[aá]lde|retaci[oó]n|'
                . 'Gontratos|Edebe)\b|\b(?:el|la|del|al)(?:art[ií]culo|decreto|ley)\b/iu',
                $combined
            ) || preg_match('/\b\d+t\d{4}\b/u', $combined)) {
            self::add_symptom($symptoms, self::CODE_KNOWN_BROKEN_WORD, self::SEVERITY_GRAVE);
        }

        if (preg_match('/\p{Ll}\p{Lu}/u', $combined)) {
            self::add_symptom($symptoms, self::CODE_UNEXPECTED_MIXED_CASE, self::SEVERITY_AVISO);
        }

        return $symptoms;
    }

    /**
     * Add one symptom to the result.
     *
     * @param array $symptoms Result being built.
     * @param string $code Stable symptom code.
     * @param string $severity Symptom severity.
     */
    private static function add_symptom(array &$symptoms, string $code, string $severity): void {
        $symptoms[] = [
            'code' => $code,
            'severity' => $severity,
        ];
    }

    /**
     * Convert HTML or plain text to normalised visible text.
     *
     * @param string $text Input text.
     * @return string Visible text with stable whitespace.
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
     * Normalise supported answer representations.
     *
     * @param array $answers Answer values supplied by an importer or DB recordset.
     * @return string[] Normalised non-empty answer texts.
     */
    private static function normalise_answers(array $answers): array {
        $result = [];

        foreach ($answers as $answer) {
            if (is_object($answer) && isset($answer->answer)) {
                $answer = $answer->answer;
            } else if (is_array($answer) && array_key_exists('answer', $answer)) {
                $answer = $answer['answer'];
            }

            if (!is_scalar($answer) && $answer !== null) {
                continue;
            }

            $text = self::normalise_text((string)$answer);
            if ($text !== '') {
                $result[] = $text;
            }
        }

        return $result;
    }

    /**
     * Check every question-mark-terminated sentence independently.
     *
     * @param string $question Normalised statement.
     * @return bool True when a closing mark lacks an opening mark in its sentence.
     */
    private static function has_question_mark_without_opening(string $question): bool {
        if (self::is_likely_catalan($question)) {
            return false;
        }

        $sentences = preg_split('/(?<=[.!?\n])\s*/u', $question, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($sentences ?: [] as $sentence) {
            if (str_contains($sentence, '?') && !str_contains($sentence, '¿')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Avoid treating the Catalan single-question-mark convention as OCR damage.
     *
     * @param string $question Normalised statement.
     * @return bool True when at least two high-signal Catalan words are present.
     */
    private static function is_likely_catalan(string $question): bool {
        preg_match_all(
            '/\b(?:quin(?:s|a|es)?|s[oó]n|terminis?|resposta|dies|mesos|'
            . 'aquest(?:a|s|es)?|seg[uü]ent(?:s)?|dels|amb|haur[aà]|vol)\b/iu',
            $question,
            $matches
        );
        $markers = array_map(
            fn($marker) => mb_strtolower($marker, 'UTF-8'),
            $matches[0]
        );

        return count(array_unique($markers)) >= 2;
    }

    /**
     * Detect a likely truncated ending while allowing deliberate phrase-completion questions.
     *
     * @param string $question Normalised statement.
     * @param string[] $answers Normalised answers.
     * @return bool True when the ending is likely an OCR truncation.
     */
    private static function has_truncated_ending(string $question, array $answers): bool {
        if (preg_match('/:\s*$/u', $question)) {
            return false;
        }

        $withoutpunctuation = preg_replace('/[\s\p{P}\p{S}]+$/u', '', $question);
        if ($withoutpunctuation === null
                || !preg_match('/\b([\p{L}]+)$/u', $withoutpunctuation, $matches)) {
            return false;
        }

        $lastword = mb_strtolower($matches[1], 'UTF-8');
        $functionwords = [
            'a', 'al', 'ante', 'bajo', 'cabe', 'con', 'contra', 'de', 'del',
            'desde', 'durante', 'e', 'el', 'en', 'entre', 'hacia', 'hasta',
            'la', 'las', 'los', 'mediante', 'ni', 'o', 'para', 'por', 'que',
            'según', 'sin', 'so', 'sobre', 'tras', 'u', 'un', 'una', 'unos',
            'unas', 'versus', 'vía', 'y',
        ];

        if (!in_array($lastword, $functionwords, true)) {
            return false;
        }

        if (in_array($lastword, ['e', 'ni', 'o', 'que', 'u', 'y'], true)) {
            return true;
        }

        return !self::answers_complete_ending($lastword, $answers);
    }

    /**
     * Decide whether answers plausibly complete a statement ending in a function word.
     *
     * This is intentionally conservative: a normal list of noun/number fragments
     * suppresses the truncation signal, while placeholders, anaphoric full
     * sentences, and obviously incompatible starters retain it.
     *
     * @param string $lastword Last word in the statement.
     * @param string[] $answers Normalised answers.
     * @return bool True when the answer set plausibly completes the statement.
     */
    private static function answers_complete_ending(string $lastword, array $answers): bool {
        if (count($answers) < 2) {
            return false;
        }

        $articles = ['el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas'];
        $badstarters = [
            'a', 'al', 'ante', 'con', 'contra', 'de', 'del', 'desde', 'en',
            'entre', 'hacia', 'hasta', 'no', 'para', 'por', 'según', 'si',
            'sí', 'sin', 'sobre', 'tras', 'únicamente',
        ];
        $plausible = 0;

        foreach ($answers as $answer) {
            if (preg_match('/^\(?\s*(?:opci[oó]n|respuesta)\b/iu', $answer)
                    || preg_match('/\bcitad[oa]s?\b/iu', $answer)) {
                continue;
            }

            if (!preg_match('/^\s*([\p{L}]+|\d+)/u', $answer, $matches)) {
                continue;
            }

            $firstword = mb_strtolower($matches[1], 'UTF-8');
            if (in_array($lastword, $articles, true)
                    && in_array($firstword, $badstarters, true)) {
                continue;
            }

            $plausible++;
        }

        return $plausible >= (int)ceil(count($answers) * 0.75);
    }

    /**
     * Detect an external reference that has neither an embedded image nor prose data.
     *
     * @param string $rawquestion Original statement, retained to detect an actual img tag.
     * @param string $question Normalised statement.
     * @return bool True when required context appears to be absent.
     */
    private static function has_missing_referenced_context(string $rawquestion, string $question): bool {
        if ($question === '' || mb_strlen($question, 'UTF-8') >= 600
                || preg_match('/<img\b/iu', $rawquestion)) {
            return false;
        }

        $hasreference = preg_match(
            '/\b(?:presente procedimiento|la matriz|la tabla|la imagen|'
            . '(?:cuadro|caso|supuesto)\s+(?:anterior|descrito|indicado|referido|planteado))\b/iu',
            $question
        );

        if (!$hasreference) {
            $hasreference = preg_match(
                '/\b(?:seg[uú]n|conforme a|de acuerdo con|en atenci[oó]n a|'
                . 'atendiendo a|respecto de)\s+(?:el\s+)?supuesto(?:\s+pr[aá]ctico)?\b/iu',
                $question
            );
        }

        $hasheading = preg_match(
            '/^\s*supuesto\s+\d+\s*[,.;:-]?\s*(?:preg(?:unta)?\.?\s*\d+\s*[:.-]?)?/iu',
            $question
        );
        // Solo "supuesto" con determinante referencial cuenta como remisión externa; las locuciones
        // jurídicas "supuesto de hecho" / "en el supuesto de que" son español correcto, no síntoma.
        $hassupposedreference = (bool)preg_match(
            '/\b(?:el|del|este|dicho|presente)\s+supuesto\b(?!\s+de\s+(?:hecho|que))/iu',
            $question
        );
        $hasreference = $hasreference || $hasheading || $hassupposedreference;

        if (!$hasreference) {
            return false;
        }

        return !self::has_embedded_reference_data(
            $question,
            (bool)$hasheading,
            $hassupposedreference
        );
    }

    /**
     * Check whether referenced material has been transcribed into the statement.
     *
     * @param string $question Normalised statement.
     * @param bool $hasheading Whether the statement starts with a Supuesto/Pregunta heading.
     * @param bool $hassupposedreference Whether the statement contains the word "supuesto".
     * @return bool True when numeric, enumerated, or sufficiently descriptive prose data exists.
     */
    private static function has_embedded_reference_data(
        string $question,
        bool $hasheading,
        bool $hassupposedreference
    ): bool {
        $body = $question;
        if ($hasheading) {
            $body = preg_replace(
                '/^\s*supuesto\s+\d+\s*[,.;:-]?\s*(?:preg(?:unta)?\.?\s*\d+\s*[:.-]?)?/iu',
                '',
                $body
            ) ?? $body;
        }

        preg_match_all('/(?<!\p{L})\d+(?:[.,]\d+)?(?!\p{L})/u', $body, $numbers);
        if (count($numbers[0]) >= 3) {
            return true;
        }

        $enumerated = preg_match_all(
            '/(?:^|[;.\n]\s*)(?:fila|columna|dato|elemento|caso|opci[oó]n)'
            . '\s*[A-Z0-9]*\s*:\s*\S/iu',
            $body
        );
        if ($enumerated >= 2) {
            return true;
        }

        if (preg_match(
                '/\b(?:cuadro|caso|supuesto)\s+(?:anterior|descrito|indicado|referido|planteado)\b|'
                . '\bsupuesto(?:\s+pr[aá]ctico)?\s+del\s+examen\b|'
                . '\bdel\s+supuesto\s+pr[aá]ctico\b/iu',
                $body
            )) {
            return false;
        }

        preg_match_all('/\p{L}{2,}/u', $body, $words);
        if ($hasheading) {
            return count($words[0]) >= 7;
        }

        if ($hassupposedreference) {
            return count($words[0]) >= 12;
        }

        return false;
    }

    /**
     * Detect zero used as an OCR replacement for a letter or conjunction.
     *
     * @param string $text Combined question and answer text.
     * @return bool True when a suspicious zero is present.
     */
    private static function has_zero_as_letter(string $text): bool {
        if (preg_match('/\p{L}0\p{L}/u', $text)
                || preg_match('/(?:^|[(¿¡;:])\s*0\s+\p{L}{3}/u', $text)) {
            return true;
        }

        preg_match_all(
            '/\b(\p{L}{2,})\s+0\s+(\p{L}{2,})\b/iu',
            $text,
            $matches,
            PREG_SET_ORDER
        );
        $legitimateprevious = [
            'a', 'al', 'artículo', 'código', 'da', 'de', 'del', 'desde', 'entre',
            'era', 'es', 'grupo', 'hasta', 'igual', 'modelo', 'nivel', 'número',
            'opción', 'página', 'resta', 'resultado', 'sea', 'suma', 'tipo',
            'vale', 'valor',
        ];
        $legitimatenext = ['a', 'días', 'dias', 'e', 'euros', 'horas', 'hasta',
            'por', 'puntos', 'sobre', 'veces', 'y'];

        foreach ($matches as $match) {
            $previous = mb_strtolower($match[1], 'UTF-8');
            $next = mb_strtolower($match[2], 'UTF-8');
            if (!in_array($previous, $legitimateprevious, true)
                    && !in_array($next, $legitimatenext, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check quote balance separately in each source field.
     *
     * @param string[] $texts Normalised statement and answer fields.
     * @return bool True when any field contains an unmatched straight double quote.
     */
    private static function has_unbalanced_double_quote(array $texts): bool {
        foreach ($texts as $text) {
            if (substr_count($text, '"') % 2 !== 0) {
                return true;
            }
        }

        return false;
    }
}
