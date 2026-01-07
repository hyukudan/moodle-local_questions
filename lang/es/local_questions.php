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
$string['privacy:metadata'] = 'El plugin de Gestión de Preguntas no almacena ningún dato personal.';
$string['features_enabled'] = '¡Funcionalidades habilitadas!';
$string['questions:manage'] = 'Gestionar administración de preguntas';
$string['questions:export'] = 'Exportar estadísticas de preguntas';
$string['task_recalculate_stats'] = 'Recalcular estadísticas de preguntas';

// Tabs
$string['questions'] = 'Preguntas';
$string['export'] = 'Exportar';
$string['import'] = 'Importar';

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


