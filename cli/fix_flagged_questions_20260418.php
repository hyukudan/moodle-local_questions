<?php
declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once('/home/preparaoposiciones/formacion51/config.php');

use local_questions\flag_manager;

global $DB;

$admin = get_admin();
if (!$admin) {
    throw new moodle_exception('Admin user not found.');
}

$now = time();
$backupdir = '/home/preparaoposiciones/desarrollo/temp_backups';
if (!is_dir($backupdir)) {
    mkdir($backupdir, 0775, true);
}
$backuppath = $backupdir . '/flagged_question_fix_20260418_' . date('His', $now) . '.json';

$updates = [
    7688 => [
        'name' => 'Art. 14 CE - Discriminación expresa',
        'questiontext' => 'Señale la causa que NO aparece mencionada expresamente en el artículo 14 de la Constitución Española:',
        'generalfeedback' => 'El artículo 14 CE prohíbe la discriminación por razón de nacimiento, raza, sexo, religión, opinión o cualquier otra condición o circunstancia personal o social. La edad no figura mencionada de forma expresa, aunque podría quedar comprendida en la cláusula final.',
        'answers' => [
            ['text' => 'La edad.', 'fraction' => 1.0],
            ['text' => 'El sexo.', 'fraction' => -0.3333333],
            ['text' => 'La religión.', 'fraction' => -0.3333333],
            ['text' => 'La opinión.', 'fraction' => -0.3333333],
        ],
        'resolutionfeedback' => 'Se ha corregido el enunciado para que exista una única respuesta válida y se han ajustado las opciones y la explicación.',
    ],
    1795933 => [
        'name' => 'EAIB - Título Preliminar',
        'questiontext' => 'El Título Preliminar del Estatuto de Autonomía de las Illes Balears regula:',
        'generalfeedback' => 'El Título Preliminar del EAIB contiene las disposiciones generales (arts. 1 a 13): definición de la Comunidad Autónoma, territorio, capitalidad, lengua propia, símbolos, condición política, comunidades baleares en el exterior y principio de insularidad. Los derechos se recogen en el Título I y la reforma del Estatuto en el Título VIII.',
        'answers' => [
            ['text' => 'Las disposiciones generales: territorio, capitalidad, símbolos y lenguas.', 'fraction' => 1.0],
            ['text' => 'Los derechos y deberes de la ciudadanía balear.', 'fraction' => -0.3333333],
            ['text' => 'Las competencias de la Comunidad Autónoma.', 'fraction' => -0.3333333],
            ['text' => 'El procedimiento de reforma del Estatuto.', 'fraction' => -0.3333333],
        ],
        'resolutionfeedback' => 'La pregunta era válida, pero se ha mejorado el feedback y los distractores para evitar confusión con el Título I del Estatuto.',
    ],
    1830468 => [
        'name' => 'Art. 162.1 CE - Recurso de inconstitucionalidad',
        'questiontext' => '¿Cuál de los siguientes NO está legitimado para interponer recurso de inconstitucionalidad según el artículo 162.1.a) CE?',
        'generalfeedback' => 'El artículo 162.1.a) CE legitima para interponer recurso de inconstitucionalidad al Presidente del Gobierno, al Defensor del Pueblo, a cincuenta Diputados, a cincuenta Senadores y a los órganos colegiados ejecutivos y Asambleas de las Comunidades Autónomas en su caso. El Ministerio Fiscal no figura entre los legitimados.',
        'answers' => [
            ['text' => 'El Presidente del Gobierno.', 'fraction' => -0.3333333],
            ['text' => 'El Defensor del Pueblo.', 'fraction' => -0.3333333],
            ['text' => 'Cincuenta Diputados.', 'fraction' => -0.3333333],
            ['text' => 'El Ministerio Fiscal.', 'fraction' => 1.0],
        ],
        'resolutionfeedback' => 'Se ha reformulado la pregunta para que solo exista una opción válida y se ha corregido la explicación.',
    ],
    1795449 => [
        'name' => 'EAIB Consells Insulars - Competencias propias',
        'questiontext' => '¿Cuál de las siguientes es una competencia propia de los Consells Insulars según el artículo 70 EAIB?',
        'generalfeedback' => 'El artículo 70 EAIB atribuye a los Consells Insulars competencias propias en materias como la ordenación y promoción turística, los servicios sociales, los transportes terrestres, la cultura o la juventud. Los aranceles aduaneros, la política arancelaria exterior y la legislación procesal corresponden al Estado.',
        'answers' => [
            ['text' => 'Establecer aranceles aduaneros para productos de otras islas.', 'fraction' => -0.3333333],
            ['text' => 'La ordenación y promoción turística.', 'fraction' => 1.0],
            ['text' => 'La legislación procesal.', 'fraction' => -0.3333333],
            ['text' => 'La política arancelaria exterior del Estado.', 'fraction' => -0.3333333],
        ],
        'resolutionfeedback' => 'Se ha sustituido el enunciado defectuoso por una pregunta válida sobre competencias propias de los Consells Insulars.',
    ],
    1796249 => [
        'name' => 'Art. 147.1 CE - Estatuto norma institucional básica',
        'questiontext' => '¿Qué opción es correcta respecto a la naturaleza del Estatuto balear?',
        'generalfeedback' => 'Según el artículo 147.1 CE, dentro de los términos de la Constitución, los Estatutos serán la norma institucional básica de cada Comunidad Autónoma y el Estado los reconocerá y amparará como parte integrante de su ordenamiento jurídico.',
        'answers' => [
            ['text' => 'El Estatuto de Autonomía es la norma institucional básica de la Comunidad Autónoma.', 'fraction' => 1.0],
            ['text' => 'El Estatuto es un reglamento aprobado por el Govern autonómico.', 'fraction' => -0.3333333],
            ['text' => 'El Estatuto carece de valor institucional y solo organiza servicios administrativos.', 'fraction' => -0.3333333],
            ['text' => 'El Estatuto es una ley ordinaria estatal sin función constitucional propia.', 'fraction' => -0.3333333],
        ],
        'resolutionfeedback' => 'Se ha mantenido el criterio correcto y se han mejorado los distractores y el feedback para evitar ambigüedad.',
    ],
    1789656 => [
        'name' => 'Art. 32.1 LOVG - Planes de colaboración',
        'questiontext' => 'Según el artículo 32.1 de la Ley Orgánica 1/2004, los planes de colaboración deben garantizar la ordenación de las actuaciones en:',
        'generalfeedback' => 'El artículo 32.1 de la LO 1/2004 dispone que los poderes públicos elaborarán planes de colaboración que garanticen la ordenación de sus actuaciones en la prevención, asistencia y persecución de los actos de violencia de género. Deben implicar a las Administraciones sanitarias, la Administración de Justicia, las Fuerzas y Cuerpos de Seguridad y los servicios sociales y organismos de igualdad.',
        'answers' => [
            ['text' => 'La prevención, asistencia y persecución de los actos de violencia de género.', 'fraction' => 1.0],
            ['text' => 'La mediación obligatoria entre víctima y agresor.', 'fraction' => -0.3333333],
            ['text' => 'La sustitución de la Administración de Justicia por los servicios sociales.', 'fraction' => -0.3333333],
            ['text' => 'La concesión automática de indemnizaciones civiles.', 'fraction' => -0.3333333],
        ],
        'resolutionfeedback' => 'Se ha reconstruido la pregunta porque enunciado, opciones y feedback no correspondían entre sí.',
    ],
    1832850 => [
        'name' => 'Art. 9.3 CE - Responsabilidad de los poderes públicos',
        'questiontext' => 'Según el artículo 9.3 CE, la Constitución garantiza:',
        'generalfeedback' => 'El artículo 9.3 CE garantiza la publicidad de las normas, la irretroactividad de las disposiciones sancionadoras no favorables o restrictivas de derechos individuales, la seguridad jurídica, la responsabilidad y la interdicción de la arbitrariedad de los poderes públicos.',
        'answers' => [
            ['text' => 'La responsabilidad de los poderes públicos.', 'fraction' => 1.0],
            ['text' => 'La irresponsabilidad de los poderes públicos.', 'fraction' => -0.3333333],
            ['text' => 'La inmunidad de los poderes públicos frente al control del Derecho.', 'fraction' => -0.3333333],
            ['text' => 'La arbitrariedad de los poderes públicos.', 'fraction' => -0.3333333],
        ],
        'resolutionfeedback' => 'Se ha corregido la contradicción entre enunciado y feedback, y se ha dejado una única respuesta correcta.',
    ],
    1833784 => [
        'name' => 'Art. 38 CE - Libertad de empresa',
        'questiontext' => 'La iniciativa económica privada se garantiza en el contexto de:',
        'generalfeedback' => 'El artículo 38 CE reconoce la libertad de empresa en el marco de la economía de mercado. Los poderes públicos garantizan y protegen su ejercicio y la defensa de la productividad, de acuerdo con las exigencias de la economía general y, en su caso, de la planificación.',
        'answers' => [
            ['text' => 'La economía de mercado.', 'fraction' => 1.0],
            ['text' => 'La planificación central obligatoria.', 'fraction' => -0.3333333],
            ['text' => 'La autarquía económica.', 'fraction' => -0.3333333],
            ['text' => 'Un régimen sin referencia constitucional al modelo económico.', 'fraction' => -0.3333333],
        ],
        'resolutionfeedback' => 'Se han eliminado las opciones duplicadas y se ha reforzado la explicación jurídica.',
    ],
    1831595 => [
        'name' => 'Art. 27.8 CE - Inspección educativa',
        'questiontext' => 'Según el artículo 27.8 CE, ¿quién inspeccionará y homologará el sistema educativo para garantizar el cumplimiento de las leyes?',
        'generalfeedback' => 'El artículo 27.8 CE establece que los poderes públicos inspeccionarán y homologarán el sistema educativo para garantizar el cumplimiento de las leyes.',
        'answers' => [
            ['text' => 'Los poderes públicos.', 'fraction' => 1.0],
            ['text' => 'Exclusivamente las universidades.', 'fraction' => -0.3333333],
            ['text' => 'El Ministerio Fiscal.', 'fraction' => -0.3333333],
            ['text' => 'Las asociaciones de madres y padres.', 'fraction' => -0.3333333],
        ],
        'resolutionfeedback' => 'Se ha corregido la pregunta y las respuestas duplicadas; ahora se ajusta al artículo 27.8 CE.',
    ],
];

$backup = [
    'generated_at' => date('c', $now),
    'questions' => [],
];

foreach (array_keys($updates) as $questionid) {
    $question = $DB->get_record('question', ['id' => $questionid], '*', MUST_EXIST);
    $answers = $DB->get_records('question_answers', ['question' => $questionid], 'id ASC');
    $flags = $DB->get_records('local_questions_flags', ['questionid' => $questionid], 'id ASC');
    $flagstatus = $DB->get_record('local_questions_flag_status', ['questionid' => $questionid]);

    $backup['questions'][$questionid] = [
        'question' => $question,
        'answers' => array_values($answers),
        'flags' => array_values($flags),
        'flagstatus' => $flagstatus,
    ];
}

file_put_contents($backuppath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$transaction = $DB->start_delegated_transaction();

foreach ($updates as $questionid => $data) {
    $question = $DB->get_record('question', ['id' => $questionid], '*', MUST_EXIST);
    $answers = array_values($DB->get_records('question_answers', ['question' => $questionid], 'id ASC'));

    if (count($answers) !== count($data['answers'])) {
        throw new moodle_exception('Unexpected number of answers for question ' . $questionid);
    }

    $question->name = $data['name'];
    $question->questiontext = $data['questiontext'];
    $question->generalfeedback = $data['generalfeedback'];
    $question->timemodified = time();
    $question->modifiedby = $admin->id;
    $DB->update_record('question', $question);

    foreach ($answers as $index => $answer) {
        $answer->answer = $data['answers'][$index]['text'];
        $answer->fraction = $data['answers'][$index]['fraction'];
        $DB->update_record('question_answers', $answer);
    }

    $status = $DB->get_record('local_questions_flag_status', ['questionid' => $questionid]);
    if ($status && $status->status !== flag_manager::STATUS_RESOLVED) {
        flag_manager::resolve(
            $questionid,
            (int)$admin->id,
            flag_manager::RESOLUTION_FIXED,
            $data['resolutionfeedback']
        );
    }
}

$transaction->allow_commit();

echo "Backup written to: {$backuppath}\n";
echo "Questions updated: " . count($updates) . "\n";
