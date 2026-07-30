<?php
/**
 * Spanish strings for local_questions.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Gestión de Preguntas';
$string['questions:view'] = 'Ver gestión de preguntas';
$string['settings'] = 'Configuración de Preguntas';
$string['enable_features'] = 'Habilitar Funcionalidades';
$string['enable_features_desc'] = 'Habilitar funcionalidades extendidas para el plugin de preguntas.';
$string['dashboard'] = 'Tablero de Preguntas';
$string['totalquestions'] = 'Total de Preguntas';
// Privacy API.
$string['privacy:metadata:local_questions_flags'] = 'Almacena los reportes de preguntas enviados por usuarios.';
$string['privacy:metadata:local_questions_flags:userid'] = 'El ID del usuario que envió el reporte.';
$string['privacy:metadata:local_questions_flags:questionid'] = 'El ID de la pregunta reportada.';
$string['privacy:metadata:local_questions_flags:reason'] = 'El motivo seleccionado para el reporte.';
$string['privacy:metadata:local_questions_flags:comment'] = 'El comentario opcional proporcionado por el usuario.';
$string['privacy:metadata:local_questions_flags:timecreated'] = 'La fecha y hora en que se envió el reporte.';
$string['privacy:metadata:local_questions_flag_status'] = 'Almacena el estado agregado y los datos de resolución de los reportes de preguntas.';
$string['privacy:metadata:local_questions_flag_status:resolvedby'] = 'El ID del usuario que resolvió el reporte.';
$string['privacy:metadata:local_questions_flag_status:resolutionfeedback'] = 'El feedback proporcionado al resolver el reporte.';
$string['privacy:metadata:local_questions_log'] = 'Almacena entradas de auditoría de acciones de gestión de preguntas.';
$string['privacy:metadata:local_questions_log:userid'] = 'El ID del usuario que realizó la acción.';
$string['privacy:metadata:local_questions_log:action'] = 'La acción realizada por el usuario.';
$string['privacy:metadata:local_questions_log:questionid'] = 'El ID de la pregunta afectada por la acción.';
$string['privacy:metadata:google_gemini'] = 'El contenido de las preguntas se envía a Google Gemini cuando se usa el análisis IA.';
$string['privacy:metadata:google_gemini:questiontext'] = 'El texto de las preguntas enviado para análisis IA.';
$string['privacy:metadata:google_gemini:answers'] = 'El texto de las respuestas enviado para análisis IA.';
$string['privacy:metadata:google_gemini:feedback'] = 'El feedback de preguntas y respuestas enviado para análisis IA.';
$string['features_enabled'] = '¡Funcionalidades habilitadas!';
$string['questions:manage'] = 'Gestionar administración de preguntas';
$string['questions:export'] = 'Exportar estadísticas de preguntas';
$string['task_recalculate_stats'] = 'Recalcular estadísticas de preguntas';
$string['task_escalate_stale_flags'] = 'Escalar reportes de preguntas envejecidos';

// Tabs
$string['questions'] = 'Preguntas';
$string['export'] = 'Exportar';
$string['import'] = 'Importar';

// Questions table
$string['category'] = 'Categoría';
$string['allcategories'] = 'Todas las categorías';
$string['subcategories'] = 'Subcategorías';
$string['perpage'] = 'Por página';
$string['questiontext'] = 'Texto de la pregunta';
$string['answers'] = 'Respuestas';
$string['type'] = 'Tipo';
$string['actions'] = 'Acciones';
$string['choose'] = 'Seleccionar...';
$string['analyzewith_ai'] = 'Analizar con IA';
$string['saveallchanges'] = 'Guardar todos los cambios';
$string['noquestionsfound'] = 'No se encontraron preguntas en esta categoría.';
$string['pleaseselectcategory'] = 'Por favor selecciona una categoría.';
$string['save'] = 'Guardar';
$string['generalfeedback'] = 'Feedback general';
$string['totalquestionsincategory'] = 'preguntas en total';
$string['editinmoodle'] = 'Editar en Moodle';

// Settings
$string['enable_export'] = 'Habilitar Exportación';
$string['enable_export_desc'] = 'Permitir a usuarios con capacidad de exportación exportar preguntas a CSV.';
$string['enable_import'] = 'Habilitar Importación';
$string['enable_import_desc'] = 'Permitir a usuarios con capacidad de importación importar preguntas desde CSV.';
$string['escalate_days'] = 'Días hasta escalar reportes pendientes';
$string['escalate_days_desc'] = 'Número mínimo de días que un reporte debe permanecer pendiente antes de incluirse en el resumen diario para revisores. El mínimo efectivo es 1 día.';

// Export/Import
$string['exportquestions'] = 'Exportar Preguntas';
$string['importquestions'] = 'Importar Preguntas';
$string['selectcategory'] = 'Seleccionar Categoría';
$string['selectformat'] = 'Seleccionar Formato';
$string['downloadcsv'] = 'Descargar CSV';
$string['downloadxml'] = 'Descargar XML de Moodle';
$string['uploadfile'] = 'Subir Archivo';
$string['csvfile'] = 'Archivo CSV';
$string['previewimport'] = 'Vista Previa de Importación';
$string['confirmimport'] = 'Confirmar Importación';
$string['importresults'] = 'Resultados de Importación';
$string['questionsimported'] = '{$a} preguntas importadas exitosamente.';
$string['questionsskipped'] = '{$a} preguntas omitidas debido a errores.';
$string['novalidquestions'] = 'No se encontraron preguntas válidas en el archivo.';
$string['invalidcsvformat'] = 'Formato CSV inválido. Por favor revise la estructura del archivo.';
$string['invalidcsvfiletype'] = 'Tipo de archivo inválido. Por favor suba un archivo CSV.';
$string['csvfiletoolarge'] = 'El archivo CSV es demasiado grande. El tamaño máximo es {$a}.';
$string['csvhelp'] = 'El CSV debe tener columnas: name, questiontext, qtype, answers (separadas por |), feedback (separadas por |), fractions (separadas por |).';
$string['includesubcategories'] = 'Incluir subcategorías';
$string['exportformat'] = 'Formato de Exportación';
$string['formatcsv'] = 'CSV (Valores Separados por Comas)';
$string['formatxml'] = 'XML de Moodle';
$string['questionsexported'] = '{$a} preguntas exportadas.';
$string['noquestionstoexport'] = 'No hay preguntas para exportar en la categoría seleccionada.';

// Exportación PDF asíncrona (tarea ad-hoc).
$string['pdf_async_threshold'] = 'Umbral PDF asíncrono';
$string['pdf_async_threshold_desc'] = 'Cuando una exportación PDF supere este número de preguntas, la generación se enviará a una tarea ad-hoc en lugar de ejecutarse dentro de la petición web. El PDF resultante se almacenará en los archivos privados del usuario y se le notificará cuando esté listo. Pon 0 para exportar siempre de forma síncrona.';
$string['exportpdf_queued'] = 'Tu exportación PDF de {$a->count} preguntas se ha encolado. Recibirás una notificación con el enlace de descarga cuando esté lista.';
$string['exportpdf_ready_subject'] = 'Exportación PDF de preguntas lista: {$a->category}';
$string['exportpdf_ready_body'] = 'Tu exportación PDF de la categoría "{$a->category}" está lista ({$a->filename}, {$a->size}). Puedes descargarla desde tu zona de archivos privados.';
$string['exportpdf_ready_small'] = 'Tu exportación PDF "{$a->category}" está lista.';
$string['exportpdf_ready_link'] = 'Abrir archivos privados';
$string['exportpdf_failed_subject'] = 'Exportación PDF de preguntas fallida: {$a->category}';
$string['exportpdf_failed_body'] = 'No se pudo generar tu exportación PDF de la categoría "{$a->category}": {$a->reason}';
$string['exportpdf_failed_small'] = 'Tu exportación PDF "{$a->category}" ha fallado.';

// Errors
$string['invalidfield'] = 'Campo inválido especificado para actualización.';
$string['nocategory'] = 'Por favor seleccione una categoría.';
$string['nofileselected'] = 'Por favor seleccione un archivo para subir.';
$string['importerror'] = 'Error importando pregunta: {$a}';

// Capabilities
$string['questions:import'] = 'Importar preguntas';

// Filter
$string['filterbytype'] = 'Filtrar por Tipo';
$string['alltypes'] = 'Todos los Tipos';

// Gemini AI
$string['gemini_settings'] = 'Configuración de Gemini AI';
$string['gemini_settings_desc'] = 'Configura la integración con Google Gemini AI para el análisis de preguntas.';
$string['gemini_apikey'] = 'API Key';
$string['gemini_apikey_desc'] = 'Tu clave API de Google AI Studio.';
$string['gemini_model'] = 'Modelo';
$string['gemini_model_desc'] = 'Selecciona el modelo Gemini a usar.';
$string['gemini_prompt'] = 'Prompt del Sistema';
$string['gemini_prompt_desc'] = 'Instrucciones personalizadas para el auditor AI.';
$string['gemini_api_error'] = 'Error de API de Gemini: {$a}';

// Question Flagging System
$string['flags'] = 'Reportes';
$string['enable_flagging'] = 'Habilitar Reportes de Preguntas';
$string['enable_flagging_desc'] = 'Permitir a los estudiantes reportar problemas en preguntas durante la revisión de exámenes.';

// Capabilities
$string['questions:flag'] = 'Reportar preguntas problemáticas';
$string['questions:reviewflags'] = 'Revisar reportes de preguntas';
$string['questions:resolveflags'] = 'Resolver reportes de preguntas';

// Flag reasons
$string['reason_error_statement'] = 'Error en el enunciado';
$string['reason_wrong_answer'] = 'Respuesta incorrecta marcada como correcta';
$string['reason_outdated_law'] = 'Normativa obsoleta o derogada';
$string['reason_ambiguous'] = 'Pregunta ambigua o confusa';
$string['reason_needs_review'] = 'Necesita revisión';
$string['reason_other'] = 'Otro motivo';

// Flag statuses
$string['status_pending'] = 'Pendiente';
$string['status_reviewing'] = 'En revisión';
$string['status_resolved'] = 'Resuelta';
$string['status_dismissed'] = 'Descartada';

// Flag resolutions
$string['resolution_fixed'] = 'Pregunta corregida';
$string['resolution_no_action'] = 'Sin acción necesaria';
$string['resolution_duplicate'] = 'Reporte duplicado';
$string['resolution_dismissed'] = 'Reporte descartado';

// Student UI
$string['reportquestion'] = 'Reportar pregunta como incorrecta';
$string['reportquestion_short'] = 'Reportar error';
$string['flagconfirm_title'] = '¿Estás seguro?';
$string['flagconfirm_warning'] = 'Estás a punto de notificar que hay un <strong>error en esta pregunta</strong> (enunciado incorrecto, respuesta errónea, normativa desactualizada, etc.).<br><br><strong>Esto NO es para dudas sobre el contenido de la pregunta</strong>, que deberás consultar directamente con el profesor del curso.';
$string['flagconfirm_yes'] = 'Sí, hay un error';
$string['flagconfirm_no'] = 'Cancelar';
$string['flagmodal_intro'] = 'Indica el tipo de error y proporciona detalles para ayudarnos a corregirlo.';
$string['reason'] = 'Tipo de error';
$string['selectreason'] = 'Selecciona el tipo de error...';
$string['reasonrequired'] = 'Por favor selecciona el tipo de error';
$string['comment'] = 'Descripción del error (opcional)';
$string['commentplaceholder'] = 'Describe el error que has encontrado...';
$string['commenthelp'] = 'Cuantos más detalles proporciones, más fácil será corregir el error.';
$string['submitflag'] = 'Enviar reporte';
$string['flagsubmitted'] = '¡Reporte enviado!';
$string['flagsubmitted_desc'] = 'Gracias por ayudarnos a mejorar. Tu reporte será revisado pronto.';
$string['alreadyflagged'] = 'Ya reportada';

// Teacher/Reviewer UI
$string['flaggedquestions'] = 'Preguntas Reportadas';
$string['noflaggedquestions'] = '¡Sin preguntas pendientes!';
$string['noflaggedquestions_desc'] = 'No hay preguntas reportadas que requieran tu atención.';
$string['filterbystatus'] = 'Filtrar por estado';
$string['flagcount'] = 'Reportes';
$string['topreason'] = 'Motivo principal';
$string['lastflag'] = 'Último reporte';
$string['viewdetails'] = 'Ver detalles';
$string['editquestion'] = 'Editar pregunta';
$string['resolve'] = 'Resolver';
$string['dismiss'] = 'Descartar';
$string['resolving'] = 'Resolviendo...';
$string['dismissing'] = 'Descartando...';

// Details panel
$string['flagdetails'] = 'Detalles del Reporte';
$string['totalflags'] = 'Total de reportes';
$string['studentreports'] = 'Reportes de estudiantes';
$string['nocomment'] = '(Sin comentario)';
$string['resolution'] = 'Resolución';

// Resolution modal
$string['resolutiontype'] = 'Tipo de resolución';
$string['selectresolution'] = 'Selecciona una resolución...';
$string['feedbacktostudents'] = 'Feedback para los estudiantes';
$string['feedbackplaceholder'] = 'Explica qué acción se ha tomado...';
$string['feedbackhelp'] = 'Este mensaje será enviado a todos los estudiantes que reportaron esta pregunta.';
$string['flagresolved'] = 'El reporte ha sido resuelto correctamente.';
$string['flagdismissed'] = 'El reporte ha sido descartado.';

// Notifications
$string['notification_resolved_subject'] = 'Tu reporte sobre la pregunta "{$a->questionname}" ha sido resuelto';
$string['notification_resolved_full'] = '¡Gracias por avisarnos! 🙌 Hemos revisado tu reporte y ya está resuelto.

Pregunta: {$a->questionpreview}

Qué hemos corregido: {$a->resolution}

Nota del equipo docente:
{$a->feedback}

Avisos como el tuyo nos ayudan a mejorar el temario para todos. ¡Sigue así!';
$string['notification_resolved_small'] = 'Tu reporte ha sido resuelto';

$string['notification_dismissed_subject'] = 'Tu reporte sobre la pregunta "{$a->questionname}" ha sido revisado';
$string['notification_dismissed_full'] = '¡Gracias por tu reporte! 🙌 La hemos revisado a fondo y, tras comprobarla, la pregunta es correcta tal y como está.

Pregunta: {$a->questionpreview}

Aclaración del equipo docente:
{$a->feedback}

Dudar y cuestionar es parte de aprender: cada revisión nos ayuda a tener un banco más fiable. ¡A por la siguiente!';
$string['notification_dismissed_small'] = 'Tu reporte ha sido revisado';

$string['notification_newflag_subject'] = 'Nueva pregunta reportada: {$a->questionname}';
$string['notification_newflag_full'] = 'Un estudiante ha reportado un problema con la pregunta "{$a->questionname}" (ID: {$a->questionid}).

Accede al panel de reportes para revisar el detalle.';
$string['notification_newflag_small'] = 'Nueva pregunta reportada';

$string['notification_stale_flags_subject'] = '{$a->count} reportes de preguntas pendientes requieren revisión';
$string['notification_stale_flags_intro'] = 'Hay {$a->count} reportes que llevan al menos {$a->days} días pendientes y no se han avisado en los últimos 7 días.';
$string['notification_stale_flags_qid'] = 'ID de pregunta';
$string['notification_stale_flags_days'] = 'Días pendiente';
$string['notification_stale_flags_reason'] = 'Motivo del último reporte';
$string['notification_stale_flags_review'] = 'Abrir la pantalla de revisión de reportes';
$string['notification_stale_flags_uncategorized'] = 'Sin categoría';
$string['notification_stale_flags_more'] = 'reportes adicionales no listados en este resumen';
$string['notification_stale_flags_unknown_reason'] = 'Motivo no disponible';

// Message providers
$string['messageprovider:flagresolved'] = 'Notificaciones de reportes resueltos';
$string['messageprovider:flagdismissed'] = 'Notificaciones de reportes descartados';
$string['messageprovider:newflag'] = 'Notificaciones de nuevos reportes';

// Events
$string['eventflagcreated'] = 'Pregunta reportada';
$string['eventflagresolved'] = 'Reporte de pregunta resuelto';

// Errors
$string['invalidreason'] = 'Motivo de reporte inválido.';
$string['invalidresolution'] = 'Tipo de resolución inválido.';
$string['invalidaction'] = 'Acción inválida.';
$string['questionnotfound'] = 'La pregunta no existe.';

// Edit modal
$string['questionname'] = 'Nombre de la pregunta';
$string['questiontextplaceholder'] = 'Escribe el texto de la pregunta...';
$string['editquestionhelp'] = 'Edita el texto del enunciado de la pregunta. Los cambios se guardarán directamente en la base de datos.';
$string['questionsaved'] = 'Pregunta guardada correctamente.';
$string['saving'] = 'Guardando...';
$string['selectcorrectanswer'] = 'Selecciona la opción correcta marcando el botón de radio correspondiente.';
$string['generalfeedbackhelp'] = 'Este feedback se muestra a todos los estudiantes después de responder, independientemente de si acertaron o no.';
$string['answertext'] = 'Texto de la respuesta';
$string['answerfeedback'] = 'Feedback de la respuesta';
$string['savechanges'] = 'Guardar cambios';
$string['savingchanges'] = 'Guardando cambios...';

// Fraction editing
$string['answerfraction'] = 'Puntuación';
$string['selectcorrectanswer_fractions'] = 'Selecciona la respuesta correcta y ajusta las puntuaciones. Al cambiar la correcta, las puntuaciones se actualizan automáticamente (100% para correcta, -33,33% para incorrectas).';

// Search and Sort
$string['searchplaceholder'] = 'Buscar en preguntas...';
$string['clearsearch'] = 'Limpiar búsqueda';
$string['sortby'] = 'Orden';
$string['sort_id_asc'] = 'ID ↑';
$string['sort_id_desc'] = 'ID ↓';
$string['sort_name_asc'] = 'Nombre A-Z';
$string['sort_name_desc'] = 'Nombre Z-A';
$string['sort_type'] = 'Tipo';
$string['sort_modified_desc'] = 'Recientes primero';
$string['sort_modified_asc'] = 'Antiguos primero';

// PDF Export
$string['formatpdf'] = 'Documento PDF';
$string['downloadpdf'] = 'Descargar PDF';
