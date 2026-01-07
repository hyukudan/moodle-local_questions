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
$string['features_enabled'] = '¡Funcionalidades habilitadas!';
$string['questions:manage'] = 'Gestionar administración de preguntas';
$string['questions:export'] = 'Exportar estadísticas de preguntas';
$string['task_recalculate_stats'] = 'Recalcular estadísticas de preguntas';

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
$string['csvhelp'] = 'El CSV debe tener columnas: name, questiontext, qtype, answers (separadas por |), feedback (separadas por |), fractions (separadas por |).';
$string['includesubcategories'] = 'Incluir subcategorías';
$string['exportformat'] = 'Formato de Exportación';
$string['formatcsv'] = 'CSV (Valores Separados por Comas)';
$string['formatxml'] = 'XML de Moodle';
$string['questionsexported'] = '{$a} preguntas exportadas.';
$string['noquestionstoexport'] = 'No hay preguntas para exportar en la categoría seleccionada.';

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
$string['notification_resolved_full'] = 'Tu reporte sobre la pregunta "{$a->questionname}" ha sido revisado y resuelto.

Pregunta: {$a->questionpreview}

Resolución: {$a->resolution}

Feedback del profesor:
{$a->feedback}';
$string['notification_resolved_small'] = 'Tu reporte ha sido resuelto';

$string['notification_dismissed_subject'] = 'Tu reporte sobre la pregunta "{$a->questionname}" ha sido revisado';
$string['notification_dismissed_full'] = 'Tu reporte sobre la pregunta "{$a->questionname}" ha sido revisado.

Pregunta: {$a->questionpreview}

Respuesta del profesor:
{$a->feedback}';
$string['notification_dismissed_small'] = 'Tu reporte ha sido revisado';

$string['notification_newflag_subject'] = 'Nueva pregunta reportada: {$a->questionname}';
$string['notification_newflag_full'] = 'Un estudiante ha reportado un problema con la pregunta "{$a->questionname}" (ID: {$a->questionid}).

Accede al panel de reportes para revisar el detalle.';
$string['notification_newflag_small'] = 'Nueva pregunta reportada';

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


