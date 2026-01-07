<?php
/**
 * English strings for local_questions.
 *
 * @package    local_questions
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Questions Management';
$string['questions:view'] = 'View questions management';
$string['settings'] = 'Questions Settings';
$string['enable_features'] = 'Enable Features';
$string['enable_features_desc'] = 'Enable extended features for the questions plugin.';
$string['dashboard'] = 'Questions Dashboard';
$string['totalquestions'] = 'Total Questions';
// Privacy API.
$string['privacy:metadata:local_questions_flags'] = 'Stores question reports submitted by users.';
$string['privacy:metadata:local_questions_flags:userid'] = 'The ID of the user who submitted the report.';
$string['privacy:metadata:local_questions_flags:questionid'] = 'The ID of the reported question.';
$string['privacy:metadata:local_questions_flags:reason'] = 'The reason selected for the report.';
$string['privacy:metadata:local_questions_flags:comment'] = 'The optional comment provided by the user.';
$string['privacy:metadata:local_questions_flags:timecreated'] = 'The time when the report was submitted.';
$string['features_enabled'] = 'Features enabled!';
$string['questions:manage'] = 'Manage questions administration';
$string['questions:export'] = 'Export questions statistics';
$string['task_recalculate_stats'] = 'Recalculate question statistics';

// Tabs
$string['questions'] = 'Questions';
$string['export'] = 'Export';
$string['import'] = 'Import';

// Questions table
$string['category'] = 'Category';
$string['allcategories'] = 'All categories';
$string['subcategories'] = 'Subcategories';
$string['perpage'] = 'Per page';
$string['questiontext'] = 'Question Text';
$string['answers'] = 'Answers';
$string['type'] = 'Type';
$string['actions'] = 'Actions';
$string['choose'] = 'Choose...';
$string['analyzewith_ai'] = 'Analyze with AI';
$string['saveallchanges'] = 'Save All Changes';
$string['noquestionsfound'] = 'No questions found in this category.';
$string['pleaseselectcategory'] = 'Please select a category.';
$string['save'] = 'Save';
$string['generalfeedback'] = 'General Feedback';
$string['totalquestionsincategory'] = 'questions total';
$string['editinmoodle'] = 'Edit in Moodle';

// Settings
$string['enable_export'] = 'Enable Export';
$string['enable_export_desc'] = 'Allow users with export capability to export questions to CSV.';
$string['enable_import'] = 'Enable Import';
$string['enable_import_desc'] = 'Allow users with import capability to import questions from CSV.';

// Export/Import
$string['exportquestions'] = 'Export Questions';
$string['importquestions'] = 'Import Questions';
$string['selectcategory'] = 'Select Category';
$string['selectformat'] = 'Select Format';
$string['downloadcsv'] = 'Download CSV';
$string['downloadxml'] = 'Download Moodle XML';
$string['uploadfile'] = 'Upload File';
$string['csvfile'] = 'CSV File';
$string['previewimport'] = 'Preview Import';
$string['confirmimport'] = 'Confirm Import';
$string['importresults'] = 'Import Results';
$string['questionsimported'] = '{$a} questions imported successfully.';
$string['questionsskipped'] = '{$a} questions skipped due to errors.';
$string['novalidquestions'] = 'No valid questions found in the file.';
$string['invalidcsvformat'] = 'Invalid CSV format. Please check the file structure.';
$string['csvhelp'] = 'CSV must have columns: name, questiontext, qtype, answers (pipe-separated), feedback (pipe-separated), fractions (pipe-separated).';
$string['includesubcategories'] = 'Include subcategories';
$string['exportformat'] = 'Export Format';
$string['formatcsv'] = 'CSV (Comma Separated Values)';
$string['formatxml'] = 'Moodle XML';
$string['questionsexported'] = '{$a} questions exported.';
$string['noquestionstoexport'] = 'No questions to export in the selected category.';

// Errors
$string['invalidfield'] = 'Invalid field specified for update.';
$string['nocategory'] = 'Please select a category.';
$string['nofileselected'] = 'Please select a file to upload.';
$string['importerror'] = 'Error importing question: {$a}';

// Capabilities
$string['questions:import'] = 'Import questions';

// Filter
$string['filterbytype'] = 'Filter by Type';
$string['alltypes'] = 'All Types';

// Gemini AI
$string['gemini_settings'] = 'Gemini AI Configuration';
$string['gemini_settings_desc'] = 'Configure integration with Google Gemini AI for question analysis.';
$string['gemini_apikey'] = 'API Key';
$string['gemini_apikey_desc'] = 'Your Google AI Studio API Key.';
$string['gemini_model'] = 'Model';
$string['gemini_model_desc'] = 'Select the Gemini model to use.';
$string['gemini_prompt'] = 'System Prompt';
$string['gemini_prompt_desc'] = 'Custom instructions for the AI auditor.';

// Question Flagging System
$string['flags'] = 'Reports';
$string['enable_flagging'] = 'Enable Question Reports';
$string['enable_flagging_desc'] = 'Allow students to report problems with questions during quiz review.';

// Capabilities
$string['questions:flag'] = 'Report problematic questions';
$string['questions:reviewflags'] = 'Review question reports';
$string['questions:resolveflags'] = 'Resolve question reports';

// Flag reasons
$string['reason_error_statement'] = 'Error in question text';
$string['reason_wrong_answer'] = 'Wrong answer marked as correct';
$string['reason_outdated_law'] = 'Outdated or repealed regulation';
$string['reason_ambiguous'] = 'Ambiguous or confusing question';
$string['reason_other'] = 'Other reason';

// Flag statuses
$string['status_pending'] = 'Pending';
$string['status_reviewing'] = 'Under review';
$string['status_resolved'] = 'Resolved';
$string['status_dismissed'] = 'Dismissed';

// Flag resolutions
$string['resolution_fixed'] = 'Question fixed';
$string['resolution_no_action'] = 'No action needed';
$string['resolution_duplicate'] = 'Duplicate report';
$string['resolution_dismissed'] = 'Report dismissed';

// Student UI
$string['reportquestion'] = 'Report question as incorrect';
$string['reportquestion_short'] = 'Report error';
$string['flagconfirm_title'] = 'Are you sure?';
$string['flagconfirm_warning'] = 'You are about to report an <strong>error in this question</strong> (incorrect statement, wrong answer, outdated regulation, etc.).<br><br><strong>This is NOT for questions about the content</strong>, which you should ask your course teacher directly.';
$string['flagconfirm_yes'] = 'Yes, there is an error';
$string['flagconfirm_no'] = 'Cancel';
$string['flagmodal_intro'] = 'Indicate the type of error and provide details to help us fix it.';
$string['reason'] = 'Error type';
$string['selectreason'] = 'Select the error type...';
$string['reasonrequired'] = 'Please select the error type';
$string['comment'] = 'Error description (optional)';
$string['commentplaceholder'] = 'Describe the error you found...';
$string['commenthelp'] = 'The more details you provide, the easier it will be to fix the error.';
$string['submitflag'] = 'Submit report';
$string['flagsubmitted'] = 'Report sent!';
$string['flagsubmitted_desc'] = 'Thank you for helping us improve. Your report will be reviewed soon.';
$string['alreadyflagged'] = 'Already reported';

// Teacher/Reviewer UI
$string['flaggedquestions'] = 'Reported Questions';
$string['noflaggedquestions'] = 'No pending questions!';
$string['noflaggedquestions_desc'] = 'There are no reported questions requiring your attention.';
$string['filterbystatus'] = 'Filter by status';
$string['flagcount'] = 'Reports';
$string['topreason'] = 'Top reason';
$string['lastflag'] = 'Last report';
$string['viewdetails'] = 'View details';
$string['editquestion'] = 'Edit question';
$string['resolve'] = 'Resolve';
$string['dismiss'] = 'Dismiss';
$string['resolving'] = 'Resolving...';
$string['dismissing'] = 'Dismissing...';

// Details panel
$string['flagdetails'] = 'Report Details';
$string['totalflags'] = 'Total reports';
$string['studentreports'] = 'Student reports';
$string['nocomment'] = '(No comment)';
$string['resolution'] = 'Resolution';

// Resolution modal
$string['resolutiontype'] = 'Resolution type';
$string['selectresolution'] = 'Select a resolution...';
$string['feedbacktostudents'] = 'Feedback for students';
$string['feedbackplaceholder'] = 'Explain what action has been taken...';
$string['feedbackhelp'] = 'This message will be sent to all students who reported this question.';
$string['flagresolved'] = 'The report has been resolved successfully.';
$string['flagdismissed'] = 'The report has been dismissed.';

// Notifications
$string['notification_resolved_subject'] = 'Your report on question "{$a->questionname}" has been resolved';
$string['notification_resolved_full'] = 'Your report on the question "{$a->questionname}" has been reviewed and resolved.

Question: {$a->questionpreview}

Resolution: {$a->resolution}

Teacher feedback:
{$a->feedback}';
$string['notification_resolved_small'] = 'Your report has been resolved';

$string['notification_dismissed_subject'] = 'Your report on question "{$a->questionname}" has been reviewed';
$string['notification_dismissed_full'] = 'Your report on the question "{$a->questionname}" has been reviewed.

Question: {$a->questionpreview}

Teacher response:
{$a->feedback}';
$string['notification_dismissed_small'] = 'Your report has been reviewed';

$string['notification_newflag_subject'] = 'New question reported: {$a->questionname}';
$string['notification_newflag_full'] = 'A student has reported a problem with the question "{$a->questionname}" (ID: {$a->questionid}).

Access the reports panel to review the details.';
$string['notification_newflag_small'] = 'New question reported';

// Message providers
$string['messageprovider:flagresolved'] = 'Resolved report notifications';
$string['messageprovider:flagdismissed'] = 'Dismissed report notifications';
$string['messageprovider:newflag'] = 'New report notifications';

// Events
$string['eventflagcreated'] = 'Question reported';
$string['eventflagresolved'] = 'Question report resolved';

// Errors
$string['invalidreason'] = 'Invalid report reason.';
$string['invalidresolution'] = 'Invalid resolution type.';
$string['invalidaction'] = 'Invalid action.';
$string['questionnotfound'] = 'Question not found.';

// Edit modal
$string['questionname'] = 'Question name';
$string['questiontextplaceholder'] = 'Enter the question text...';
$string['editquestionhelp'] = 'Edit the question text. Changes will be saved directly to the database.';
$string['questionsaved'] = 'Question saved successfully.';
$string['saving'] = 'Saving...';
$string['selectcorrectanswer'] = 'Select the correct answer by clicking the corresponding radio button.';
$string['generalfeedbackhelp'] = 'This feedback is shown to all students after answering, regardless of whether they got it right.';
$string['answertext'] = 'Answer text';
$string['answerfeedback'] = 'Answer feedback';
$string['savechanges'] = 'Save changes';
$string['savingchanges'] = 'Saving changes...';


