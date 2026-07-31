<?php
/**
 * Scan current ready question versions for inconsistent arithmetic options.
 *
 * The default mode is read-only. Pass --flag explicitly to create needs_review
 * flags; question version statuses are never modified.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
        'categoryid' => '',
        'include-subcats' => false,
        'since' => '0',
        'all-ctx' => '0',
        'flag' => false,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognised) {
    cli_error("Opciones desconocidas:\n  " . implode("\n  ", $unrecognised));
}

$help = <<<'HELP'
Escanea las versiones vigentes ready con el validador aritmético.

Por defecto el script es de SOLO INFORME. --flag crea flags needs_review
para los veredictos NO_MATCH y MULTIPLE_MATCHES, pero nunca modifica
question_versions.

Debe indicarse exactamente un ámbito:
  --categoryid=N[,N]  Una o varias categorías.
  --all-ctx=N          Todas las categorías del contextid indicado.

Opciones:
  --include-subcats    Incluye descendientes de --categoryid.
  --since=EPOCH        Limita por question.timecreated >= EPOCH.
  --flag               Crea flags con el administrador principal; salta los
                       pares (questionid, userid) que ya existen.
  -h, --help           Muestra esta ayuda.

Ejemplos:
  php local/questions/cli/scan_arith.php --categoryid=14471
  php local/questions/cli/scan_arith.php --categoryid=4928 --include-subcats
  php local/questions/cli/scan_arith.php --all-ctx=10995 --since=1782864000
  php local/questions/cli/scan_arith.php --all-ctx=10995 --flag
HELP;

if (!empty($options['help'])) {
    mtrace($help);
    exit(0);
}

if (is_bool($options['categoryid'])) {
    cli_error('--categoryid requiere un valor N[,N].');
}
if (!is_bool($options['include-subcats'])) {
    cli_error('--include-subcats no admite un valor; úselo como interruptor.');
}
if (!is_bool($options['flag'])) {
    cli_error('--flag no admite un valor; úselo como interruptor.');
}

$categoryoption = trim((string)$options['categoryid']);
$contextid = local_questions_arith_positive_int($options['all-ctx'], '--all-ctx', true);
$since = local_questions_arith_epoch($options['since']);
$scopes = (int)($categoryoption !== '') + (int)($contextid > 0);

if ($scopes !== 1) {
    cli_error(
        'Indique exactamente uno de --categoryid o --all-ctx. '
        . 'Use --help para ver ejemplos.'
    );
}

if (!empty($options['include-subcats']) && $categoryoption === '') {
    cli_error('--include-subcats solo puede usarse con --categoryid.');
}

$categoryids = [];
if ($categoryoption !== '') {
    $categoryids = local_questions_arith_category_ids(
        $categoryoption,
        !empty($options['include-subcats'])
    );
}

if ($contextid > 0 && !$DB->record_exists('context', ['id' => $contextid])) {
    cli_error("No existe el contexto {$contextid}.");
}

$flagmode = !empty($options['flag']);
$flaguserid = 0;
if ($flagmode) {
    $admin = get_admin();
    if (!$admin) {
        cli_error('No se encontró un administrador principal para crear los flags.');
    }
    $flaguserid = (int)$admin->id;
}

$params = [
    'readystatus' => 'ready',
    'lastid' => 0,
];
$where = '';

if ($categoryids) {
    [$categorysql, $categoryparams] = $DB->get_in_or_equal(
        $categoryids,
        SQL_PARAMS_NAMED,
        'arithcat'
    );
    $where .= " AND qbe.questioncategoryid {$categorysql}";
    $params += $categoryparams;
} else {
    $where .= ' AND qc.contextid = :arithcontextid';
    $params['arithcontextid'] = $contextid;
}

if ($since > 0) {
    $where .= ' AND q.timecreated >= :arithsince';
    $params['arithsince'] = $since;
}

$sql = "SELECT q.id,
               q.questiontext,
               qbe.questioncategoryid,
               qc.contextid
          FROM {question} q
          JOIN {question_versions} qv
            ON qv.questionid = q.id
          JOIN {question_bank_entries} qbe
            ON qbe.id = qv.questionbankentryid
          JOIN {question_categories} qc
            ON qc.id = qbe.questioncategoryid
         WHERE qv.status = :readystatus
           AND qv.version = (
               SELECT MAX(qv2.version)
                 FROM {question_versions} qv2
                WHERE qv2.questionbankentryid = qv.questionbankentryid
           )
           AND q.id > :lastid
           {$where}
      ORDER BY q.id ASC";

$scanned = 0;
$applies = 0;
$matches = 0;
$nomatches = 0;
$multiplematches = 0;
$failures = 0;
$flagged = 0;
$flagskipped = 0;
$flagerrors = 0;
$batchsize = 500;

mtrace($flagmode
    ? "Modo: INFORME + FLAGS needs_review (userid={$flaguserid})"
    : 'Modo: SOLO INFORME (sin escrituras)');
mtrace('');
mtrace('QID | EXPRESION | ESPERADO | OPCIONES | VEREDICTO');
mtrace('----|-----------|----------|----------|----------');

while (true) {
    $questions = $DB->get_records_sql($sql, $params, 0, $batchsize);
    if (!$questions) {
        break;
    }

    $questionids = array_map('intval', array_keys($questions));
    [$answerinsql, $answerparams] = $DB->get_in_or_equal(
        $questionids,
        SQL_PARAMS_NAMED,
        'arithanswer'
    );
    $answerrecords = $DB->get_records_select(
        'question_answers',
        "question {$answerinsql}",
        $answerparams,
        'question ASC, id ASC',
        'id, question, answer'
    );
    $answersbyquestion = [];
    foreach ($answerrecords as $answer) {
        $answersbyquestion[(int)$answer->question][] = $answer;
    }

    foreach ($questions as $question) {
        $questionid = (int)$question->id;
        $params['lastid'] = $questionid;
        $scanned++;
        $questionanswers = $answersbyquestion[$questionid] ?? [];

        $result = \local_questions\arith_validator::validate(
            $question->questiontext,
            $questionanswers
        );
        if ($result === null) {
            continue;
        }

        $applies++;
        if ($result['matches']) {
            $matches++;
        } else {
            $nomatches++;
        }
        if ($result['multiple_matches']) {
            $multiplematches++;
        }

        $failure = !$result['matches'] || $result['multiple_matches'];
        if ($failure) {
            $failures++;
        }

        $verdict = local_questions_arith_verdict($result);
        mtrace(implode(' | ', [
            $questionid,
            $result['expression'],
            local_questions_arith_number($result['expected']),
            local_questions_arith_options($questionanswers),
            $verdict,
        ]));

        if ($flagmode && $failure) {
            $comment = 'Arithmetic gate: expression=' . $result['expression']
                . '; expected=' . local_questions_arith_number($result['expected'])
                . '; verdict=' . $verdict;
            try {
                if (\local_questions\flag_manager::has_user_flagged($questionid, $flaguserid)) {
                    $flagskipped++;
                    continue;
                }

                \local_questions\flag_manager::submit_flag(
                    $questionid,
                    $flaguserid,
                    \local_questions\flag_manager::REASON_NEEDS_REVIEW,
                    $comment,
                    null,
                    false // El resumen diario de escalado evita una notificación por pregunta.
                );
                $flagged++;
            } catch (\dml_write_exception $e) {
                // Cover the unique-index race between the existence check and insert.
                if (\local_questions\flag_manager::has_user_flagged($questionid, $flaguserid)) {
                    $flagskipped++;
                } else {
                    $flagerrors++;
                    mtrace("ERROR flag q={$questionid}: " . $e->getMessage());
                }
            } catch (\moodle_exception $e) {
                if ($e->errorcode === 'alreadyflagged') {
                    $flagskipped++;
                } else {
                    $flagerrors++;
                    mtrace("ERROR flag q={$questionid}: " . $e->getMessage());
                }
            } catch (\Throwable $e) {
                $flagerrors++;
                mtrace("ERROR flag q={$questionid}: " . $e->getMessage());
            }
        }
    }

    unset($answerrecords, $answersbyquestion, $questions);
}

mtrace('');
mtrace('CONTEOS');
mtrace("  escaneadas={$scanned}");
mtrace("  no_aplica=" . ($scanned - $applies));
mtrace("  aplica={$applies}");
mtrace("  matches_true={$matches}");
mtrace("  matches_false={$nomatches}");
mtrace("  multiple_matches={$multiplematches}");
mtrace("  fallos={$failures}");

if ($flagmode) {
    mtrace("  flags_creados={$flagged} flags_existentes_saltados={$flagskipped} flags_error={$flagerrors}");
}

exit($flagerrors > 0 ? 2 : 0);

/**
 * Parse an integer CLI option.
 *
 * @param mixed $value Raw option value.
 * @param string $optionname Option name for errors.
 * @param bool $allowzero Whether zero is accepted as the unset value.
 * @return int Parsed value.
 */
function local_questions_arith_positive_int($value, string $optionname, bool $allowzero = false): int {
    if (is_bool($value)) {
        cli_error("{$optionname} requiere un valor entero.");
    }

    $text = (string)$value;
    if (!ctype_digit($text)) {
        cli_error("{$optionname} debe ser un entero positivo.");
    }

    $number = (int)$text;
    if ($number < 0 || (!$allowzero && $number === 0)) {
        cli_error("{$optionname} debe ser un entero positivo.");
    }

    return $number;
}

/**
 * Parse and validate --since.
 *
 * @param mixed $value Raw option value.
 * @return int Unix epoch, or zero when unset.
 */
function local_questions_arith_epoch($value): int {
    if (is_bool($value)) {
        cli_error('--since requiere un valor epoch.');
    }

    $text = (string)$value;
    if (!ctype_digit($text)) {
        cli_error('--since debe ser un epoch Unix no negativo.');
    }

    return (int)$text;
}

/**
 * Parse category IDs and optionally expand their descendant closure.
 *
 * @param string $option Comma-separated category IDs.
 * @param bool $includesubcats Include all descendants.
 * @return int[] Validated category IDs.
 */
function local_questions_arith_category_ids(string $option, bool $includesubcats): array {
    global $DB;

    if (!preg_match('/^\d+(?:,\d+)*$/', $option)) {
        cli_error('--categoryid debe tener el formato N[,N].');
    }

    $requested = array_values(array_unique(array_map('intval', explode(',', $option))));
    if (in_array(0, $requested, true)) {
        cli_error('--categoryid solo admite IDs positivos.');
    }

    $categories = $DB->get_records(
        'question_categories',
        null,
        '',
        'id, parent'
    );
    $known = [];
    $children = [];
    foreach ($categories as $category) {
        $id = (int)$category->id;
        $parent = (int)$category->parent;
        $known[$id] = true;
        $children[$parent][] = $id;
    }

    foreach ($requested as $categoryid) {
        if (!isset($known[$categoryid])) {
            cli_error("No existe la categoría {$categoryid}.");
        }
    }

    if (!$includesubcats) {
        sort($requested, SORT_NUMERIC);
        return $requested;
    }

    $expanded = array_fill_keys($requested, true);
    $pending = $requested;
    while ($pending) {
        $parentid = array_pop($pending);
        foreach ($children[$parentid] ?? [] as $childid) {
            if (isset($expanded[$childid])) {
                continue;
            }
            $expanded[$childid] = true;
            $pending[] = $childid;
        }
    }

    $result = array_map('intval', array_keys($expanded));
    sort($result, SORT_NUMERIC);
    return $result;
}

/**
 * Render a stable decimal without locale-dependent separators.
 *
 * @param float $number Value.
 * @return string Compact decimal.
 */
function local_questions_arith_number(float $number): string {
    $formatted = rtrim(rtrim(sprintf('%.10F', $number), '0'), '.');
    return $formatted === '-0' ? '0' : $formatted;
}

/**
 * Render answer options on one line.
 *
 * @param array $answers Answer records.
 * @return string Compact indexed option list.
 */
function local_questions_arith_options(array $answers): string {
    $options = [];
    $index = 0;
    foreach ($answers as $answer) {
        if (is_object($answer) && isset($answer->answer)) {
            $answer = $answer->answer;
        } else if (is_array($answer) && array_key_exists('answer', $answer)) {
            $answer = $answer['answer'];
        }

        $text = is_scalar($answer) || $answer === null ? (string)$answer : '';
        $text = strip_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = str_replace('|', '/', trim((string)$text));
        $options[] = $index . ':' . $text;
        $index++;
    }

    return '{' . implode('; ', $options) . '}';
}

/**
 * Convert validation state to a stable report/flag verdict.
 *
 * @param array $result Validator result.
 * @return string Verdict.
 */
function local_questions_arith_verdict(array $result): string {
    if ($result['multiple_matches']) {
        return 'MULTIPLE_MATCHES';
    }
    if (!$result['matches']) {
        return 'NO_MATCH';
    }

    return 'OK(answer=' . $result['matched_answer'] . ')';
}
