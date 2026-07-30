<?php
/**
 * Scan ready question versions for recurrent OCR damage.
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
        'subcats' => false,
        'since' => '0',
        'courseid' => '0',
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
Escanea versiones vigentes ready con el gate OCR.

Por defecto el script es de SOLO INFORME. --flag crea flags needs_review,
pero nunca modifica el status de question_versions.

Debe indicarse exactamente un ámbito:
  --categoryid=N[,N]  Una o varias categorías.
  --courseid=N        Categorías de módulos qbank del curso.
  --all-ctx=N         Todas las categorías del contextid indicado.

Opciones:
  --subcats           Incluye descendientes de --categoryid.
  --since=EPOCH       Limita por question.timecreated >= EPOCH.
  --flag              Crea flags con el administrador principal; salta los
                      pares (questionid, userid) que ya existen.
  -h, --help          Muestra esta ayuda.

Ejemplos:
  php local/questions/cli/scan_ocr.php --categoryid=13470 --subcats
  php local/questions/cli/scan_ocr.php --courseid=1 --since=1782864000
  php local/questions/cli/scan_ocr.php --all-ctx=10995
  php local/questions/cli/scan_ocr.php --all-ctx=10995 --flag
HELP;

if (!empty($options['help'])) {
    mtrace($help);
    exit(0);
}

if (is_bool($options['categoryid'])) {
    cli_error('--categoryid requiere un valor N[,N].');
}
if (!is_bool($options['subcats'])) {
    cli_error('--subcats no admite un valor; úselo como interruptor.');
}
if (!is_bool($options['flag'])) {
    cli_error('--flag no admite un valor; úselo como interruptor.');
}

$categoryoption = trim((string)$options['categoryid']);
$courseid = local_questions_ocr_positive_int($options['courseid'], '--courseid', true);
$contextid = local_questions_ocr_positive_int($options['all-ctx'], '--all-ctx', true);
$since = local_questions_ocr_epoch($options['since']);
$scopes = (int)($categoryoption !== '') + (int)($courseid > 0) + (int)($contextid > 0);

if ($scopes !== 1) {
    cli_error(
        'Indique exactamente uno de --categoryid, --courseid o --all-ctx. '
        . 'Use --help para ver ejemplos.'
    );
}

if (!empty($options['subcats']) && $categoryoption === '') {
    cli_error('--subcats solo puede usarse con --categoryid.');
}

$categoryids = [];
if ($categoryoption !== '') {
    $categoryids = local_questions_ocr_category_ids($categoryoption, !empty($options['subcats']));
}

if ($courseid > 0 && !$DB->record_exists('course', ['id' => $courseid])) {
    cli_error("No existe el curso {$courseid}.");
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
$joins = '';
$where = '';

if ($categoryids) {
    [$categorysql, $categoryparams] = $DB->get_in_or_equal(
        $categoryids,
        SQL_PARAMS_NAMED,
        'ocrcat'
    );
    $where .= " AND qbe.questioncategoryid {$categorysql}";
    $params += $categoryparams;
} else if ($courseid > 0) {
    $joins .= " JOIN {context} ctx
                  ON ctx.id = qc.contextid
                JOIN {course_modules} cm
                  ON cm.id = ctx.instanceid
                 AND ctx.contextlevel = :ocrcontextmodule
                JOIN {modules} md
                  ON md.id = cm.module
                 AND md.name = :ocrqbankmodule";
    $where .= ' AND cm.course = :ocrcourseid';
    $params['ocrcontextmodule'] = CONTEXT_MODULE;
    $params['ocrqbankmodule'] = 'qbank';
    $params['ocrcourseid'] = $courseid;
} else {
    $where .= ' AND qc.contextid = :ocrcontextid';
    $params['ocrcontextid'] = $contextid;
}

if ($since > 0) {
    $where .= ' AND q.timecreated >= :ocrsince';
    $params['ocrsince'] = $since;
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
          {$joins}
         WHERE qv.status = :readystatus
           AND qv.version = (
               SELECT MAX(qv2.version)
                 FROM {question_versions} qv2
                WHERE qv2.questionbankentryid = qv.questionbankentryid
           )
           AND q.id > :lastid
           {$where}
      ORDER BY q.id ASC";

$rows = [
    \local_questions\ocr_gate::SEVERITY_GRAVE => [],
    \local_questions\ocr_gate::SEVERITY_AVISO => [],
];
$questioncountbyseverity = [
    \local_questions\ocr_gate::SEVERITY_GRAVE => 0,
    \local_questions\ocr_gate::SEVERITY_AVISO => 0,
];
$symptomcountbyseverity = [
    \local_questions\ocr_gate::SEVERITY_GRAVE => 0,
    \local_questions\ocr_gate::SEVERITY_AVISO => 0,
];
$scanned = 0;
$affected = 0;
$flagged = 0;
$flagskipped = 0;
$flagerrors = 0;
$batchsize = 500;

mtrace($flagmode
    ? "Modo: INFORME + FLAGS needs_review (userid={$flaguserid})"
    : 'Modo: SOLO INFORME (sin escrituras)');

while (true) {
    $questions = $DB->get_records_sql($sql, $params, 0, $batchsize);
    if (!$questions) {
        break;
    }

    $questionids = array_map('intval', array_keys($questions));
    [$answerinsql, $answerparams] = $DB->get_in_or_equal(
        $questionids,
        SQL_PARAMS_NAMED,
        'ocranswer'
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

        $symptoms = \local_questions\ocr_gate::scan_question(
            $question->questiontext,
            $answersbyquestion[$questionid] ?? []
        );
        if (!$symptoms) {
            continue;
        }

        $affected++;
        $allcodes = [];
        $codesbyseverity = [
            \local_questions\ocr_gate::SEVERITY_GRAVE => [],
            \local_questions\ocr_gate::SEVERITY_AVISO => [],
        ];

        foreach ($symptoms as $symptom) {
            $severity = $symptom['severity'];
            $code = $symptom['code'];
            $allcodes[$code] = true;
            $codesbyseverity[$severity][$code] = true;
            $symptomcountbyseverity[$severity]++;
        }

        foreach ($codesbyseverity as $severity => $codes) {
            if (!$codes) {
                continue;
            }

            $questioncountbyseverity[$severity]++;
            $rows[$severity][] = [
                'questionid' => $questionid,
                'categoryid' => (int)$question->questioncategoryid,
                'codes' => implode(',', array_keys($codes)),
            ];
        }

        if ($flagmode) {
            $comment = 'OCR gate: ' . implode(',', array_keys($allcodes));
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
                    false // Sin notificación por pregunta: el resumen diario de escalado cubre el aviso.
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
local_questions_ocr_print_table(
    'GRAVE',
    $rows[\local_questions\ocr_gate::SEVERITY_GRAVE]
);
mtrace('');
local_questions_ocr_print_table(
    'AVISO',
    $rows[\local_questions\ocr_gate::SEVERITY_AVISO]
);
mtrace('');
mtrace('CONTEOS');
mtrace("  escaneadas={$scanned}");
mtrace('  limpias=' . ($scanned - $affected));
mtrace("  con_sintomas={$affected}");
mtrace(
    '  grave_preguntas='
    . $questioncountbyseverity[\local_questions\ocr_gate::SEVERITY_GRAVE]
    . ' grave_sintomas='
    . $symptomcountbyseverity[\local_questions\ocr_gate::SEVERITY_GRAVE]
);
mtrace(
    '  aviso_preguntas='
    . $questioncountbyseverity[\local_questions\ocr_gate::SEVERITY_AVISO]
    . ' aviso_sintomas='
    . $symptomcountbyseverity[\local_questions\ocr_gate::SEVERITY_AVISO]
);

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
function local_questions_ocr_positive_int($value, string $optionname, bool $allowzero = false): int {
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
function local_questions_ocr_epoch($value): int {
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
function local_questions_ocr_category_ids(string $option, bool $includesubcats): array {
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
 * Print a compact severity table.
 *
 * @param string $title Table title.
 * @param array $rows Rows containing questionid, categoryid and codes.
 */
function local_questions_ocr_print_table(string $title, array $rows): void {
    mtrace($title . ' (' . count($rows) . ' preguntas)');
    if (!$rows) {
        mtrace('  (sin hallazgos)');
        return;
    }

    mtrace('  QUESTIONID  CATEGORYID  CODES');
    mtrace('  ----------  ----------  -----');
    foreach ($rows as $row) {
        mtrace(sprintf(
            '  %-10d  %-10d  %s',
            $row['questionid'],
            $row['categoryid'],
            $row['codes']
        ));
    }
}
