/**
 * Flag review module for teachers/reviewers.
 *
 * @module     local_questions/flag_review
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'],
function($, Ajax, Notification, Str) {

    /**
     * Show a toast notification using gamification toast with fallback.
     *
     * @param {Object} options Toast options
     */
    const showToast = function(options) {
        // Try to load and use gamification toast at runtime.
        require(['theme_remui/gamification_toast'], function(GamificationToast) {
            if (GamificationToast && typeof GamificationToast.show === 'function') {
                GamificationToast.show(options);
            } else {
                showFallbackNotification(options);
            }
        }, function() {
            // Module not available, use fallback.
            showFallbackNotification(options);
        });
    };

    /**
     * Show fallback Moodle notification.
     *
     * @param {Object} options Toast options
     */
    const showFallbackNotification = function(options) {
        Notification.addNotification({
            message: options.title + ': ' + options.message,
            type: options.category === 'success' ? 'success' : 'info'
        });
    };

    /**
     * Strings cache.
     * @type {Object}
     */
    let strings = {};

    /**
     * Initialize the flag review module.
     */
    const init = function() {
        // Load strings.
        loadStrings();

        // Bind filter buttons.
        $(document).on('click', '.filter-btn', function(e) {
            e.preventDefault();
            const filter = $(this).data('filter');
            setFilter(filter);
        });

        // Bind view details button.
        $(document).on('click', '.view-details-btn', function(e) {
            e.preventDefault();
            const questionId = $(this).data('questionid');
            showDetails(questionId);
        });

        // Bind edit question button.
        $(document).on('click', '.edit-question-btn', function(e) {
            e.preventDefault();
            const questionId = $(this).data('questionid');
            editQuestion(questionId);
        });

        // Bind resolve button.
        $(document).on('click', '.resolve-btn', function(e) {
            e.preventDefault();
            const questionId = $(this).data('questionid');
            openResolutionModal(questionId, 'resolve');
        });

        // Bind dismiss button.
        $(document).on('click', '.dismiss-btn', function(e) {
            e.preventDefault();
            const questionId = $(this).data('questionid');
            openResolutionModal(questionId, 'dismiss');
        });

        // Bind submit resolution.
        $(document).on('click', '#submit-resolution-btn', function(e) {
            e.preventDefault();
            submitResolution();
        });

        // Reset modals on close.
        $('#flag-resolution-modal').on('hidden.bs.modal', function() {
            resetResolutionModal();
        });

        // Close modal buttons (manual handler for BS5 compatibility).
        $(document).on('click', '#flag-details-modal .close-modal-btn', function() {
            modalAction('flag-details-modal', 'hide');
        });

        // Close resolution modal buttons.
        $(document).on('click', '#flag-resolution-modal .close-resolution-btn', function() {
            modalAction('flag-resolution-modal', 'hide');
        });

        // Close edit modal buttons.
        $(document).on('click', '#flag-edit-modal .close-edit-btn', function() {
            modalAction('flag-edit-modal', 'hide');
        });

        // Save edit button.
        $(document).on('click', '#save-edit-btn', function(e) {
            e.preventDefault();
            saveQuestionEdit();
        });
    };

    /**
     * Load required strings.
     */
    const loadStrings = function() {
        Str.get_strings([
            {key: 'resolve', component: 'local_questions'},
            {key: 'dismiss', component: 'local_questions'},
            {key: 'resolving', component: 'local_questions'},
            {key: 'dismissing', component: 'local_questions'},
            {key: 'nocomment', component: 'local_questions'},
            {key: 'save', component: 'core'},
            {key: 'saving', component: 'local_questions'},
            {key: 'savechanges', component: 'local_questions'},
            {key: 'questionsaved', component: 'local_questions'},
        ]).done(function(s) {
            strings.resolve = s[0];
            strings.dismiss = s[1];
            strings.resolving = s[2];
            strings.dismissing = s[3];
            strings.nocomment = s[4];
            strings.save = s[5];
            strings.saving = s[6];
            strings.savechanges = s[7];
            strings.questionsaved = s[8];
        });
    };

    /**
     * Set filter and reload data.
     *
     * @param {string} filter The filter value
     */
    const setFilter = function(filter) {
        // Update button states.
        $('.filter-btn').removeClass('active');
        $('.filter-btn[data-filter="' + filter + '"]').addClass('active');

        // Filter table rows.
        if (filter === 'all') {
            $('#flags-table tbody tr').show();
        } else {
            $('#flags-table tbody tr').hide();
            $('#flags-table tbody tr[data-status="' + filter + '"]').show();
        }
    };

    /**
     * Show a Bootstrap modal (compatible with BS4 jQuery and BS5 native).
     *
     * @param {string} modalId The modal element ID
     * @param {string} action 'show' or 'hide'
     */
    const modalAction = function(modalId, action) {
        const modalEl = document.getElementById(modalId);
        if (typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal) {
            // Bootstrap 5 native.
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal[action]();
        } else {
            // Fallback to jQuery (Bootstrap 4 style).
            $('#' + modalId).modal(action);
        }
    };

    /**
     * Show flag details for a question.
     *
     * @param {number} questionId The question ID
     */
    const showDetails = function(questionId) {
        // Show modal with loading state.
        $('#flag-details-loading').removeClass('d-none');
        $('#flag-details-content').addClass('d-none');

        modalAction('flag-details-modal', 'show');

        // Fetch details.
        Ajax.call([{
            methodname: 'local_questions_get_flag_details',
            args: {questionid: questionId}
        }])[0].done(function(response) {
            renderDetails(response);
        }).fail(function(error) {
            Notification.exception(error);
            modalAction('flag-details-modal', 'hide');
        });
    };

    /**
     * Render flag details in the modal.
     *
     * @param {Object} data The flag details data
     */
    const renderDetails = function(data) {
        // Update basic info.
        $('#detail-questionid').text('#' + data.questionid);
        $('#detail-questionname').text(data.questionname);
        // Security: Use text() to prevent XSS, questiontext is displayed as plain text preview.
        $('#detail-questiontext').text(stripHtml(data.questiontext));
        $('#detail-flagcount').text(data.flagcount);

        // Update status badge.
        let statusBadge = getStatusBadge(data.status);
        $('#detail-status-badge').html(statusBadge);

        // Resolution info (if resolved/dismissed).
        if (data.status === 'resolved' || data.status === 'dismissed') {
            $('#detail-resolution-info').removeClass('d-none');
            $('#detail-resolution-text').text(data.resolution);
            $('#detail-resolution-feedback').text(data.resolutionfeedback);
        } else {
            $('#detail-resolution-info').addClass('d-none');
        }

        // Render individual flags.
        const $flagsList = $('#detail-flags-list').empty();
        const template = document.getElementById('flag-item-template');

        data.flags.forEach(function(flag) {
            const $item = $(template.content.cloneNode(true));
            $item.find('.flag-reason').text(flag.reason_label);
            $item.find('.flag-date').text(formatDate(flag.timecreated));
            $item.find('.flag-user').text(flag.username);
            $item.find('.flag-comment').text(flag.comment || strings.nocomment || '(Sin comentario)');
            $flagsList.append($item);
        });

        // Show content.
        $('#flag-details-loading').addClass('d-none');
        $('#flag-details-content').removeClass('d-none');
    };

    /**
     * Get status badge HTML (Bootstrap 5 compatible).
     *
     * @param {string} status The status code
     * @return {string} HTML for badge
     */
    const getStatusBadge = function(status) {
        const badges = {
            'pending': '<span class="badge bg-warning text-dark">Pendiente</span>',
            'reviewing': '<span class="badge bg-info">En revisión</span>',
            'resolved': '<span class="badge bg-success">Resuelta</span>',
            'dismissed': '<span class="badge bg-dark">Descartada</span>'
        };
        // Security: Escape unknown statuses to prevent XSS.
        return badges[status] || '<span class="badge bg-secondary">' + escapeHtml(status) + '</span>';
    };

    /**
     * Strip HTML tags from a string using regex (safe approach without DOM parsing).
     *
     * @param {string} html The HTML string
     * @return {string} Plain text
     */
    const stripHtml = function(html) {
        if (!html) {
            return '';
        }
        // Remove HTML tags using regex - safe as we're stripping, not parsing.
        return String(html)
            .replace(/<[^>]*>/g, '')
            .replace(/&nbsp;/g, ' ')
            .replace(/&amp;/g, '&')
            .replace(/&lt;/g, '<')
            .replace(/&gt;/g, '>')
            .replace(/&quot;/g, '"')
            .trim();
    };

    /**
     * Escape HTML special characters.
     *
     * @param {string} text The text to escape
     * @return {string} Escaped text
     */
    const escapeHtml = function(text) {
        if (!text) {
            return '';
        }
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    };

    /**
     * Format Unix timestamp to readable date.
     *
     * @param {number} timestamp Unix timestamp
     * @return {string} Formatted date
     */
    const formatDate = function(timestamp) {
        const date = new Date(timestamp * 1000);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
    };

    /**
     * Open question editor modal.
     *
     * @param {number} questionId The question ID
     */
    const editQuestion = function(questionId) {
        // Show modal with loading state.
        $('#edit-loading').removeClass('d-none');
        $('#edit-content').addClass('d-none');
        $('#edit-success-message, #edit-error-message').addClass('d-none');
        $('#save-edit-btn').prop('disabled', false);

        modalAction('flag-edit-modal', 'show');

        // Fetch question data using existing get_flag_details service.
        Ajax.call([{
            methodname: 'local_questions_get_flag_details',
            args: {questionid: questionId}
        }])[0].done(function(response) {
            loadEditModal(response);
        }).fail(function(error) {
            Notification.exception(error);
            modalAction('flag-edit-modal', 'hide');
        });
    };

    /**
     * Load question data into edit modal.
     *
     * @param {Object} data The question data
     */
    const loadEditModal = function(data) {
        // Store question ID.
        $('#edit-questionid').val(data.questionid);

        // Set question name (readonly).
        $('#edit-questionname').text(data.questionname);

        // Set question text (editable).
        $('#edit-questiontext').val(stripHtml(data.questiontext));

        // Set general feedback.
        $('#edit-generalfeedback').val(stripHtml(data.generalfeedback || ''));

        // Populate answers.
        const $container = $('#edit-answers-container').empty();
        const template = document.getElementById('edit-answer-template');
        const labels = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        if (data.answers && data.answers.length > 0) {
            data.answers.forEach(function(answer, index) {
                const $item = $(template.content.cloneNode(true));
                $item.find('.answer-item').attr('data-answerid', answer.id);
                $item.find('.answer-label').text(labels[index] || (index + 1));
                $item.find('.correct-answer-radio').val(answer.id);
                if (answer.iscorrect) {
                    $item.find('.correct-answer-radio').prop('checked', true);
                }
                $item.find('.answer-text').val(stripHtml(answer.answer));
                $item.find('.answer-feedback').val(stripHtml(answer.feedback || ''));
                $container.append($item);
            });
        }

        // Hide loading, show content.
        $('#edit-loading').addClass('d-none');
        $('#edit-content').removeClass('d-none');
    };

    /**
     * Save question edits - all fields.
     */
    const saveQuestionEdit = function() {
        const questionId = $('#edit-questionid').val();
        const questionText = $('#edit-questiontext').val().trim();
        const generalFeedback = $('#edit-generalfeedback').val().trim();

        if (!questionText) {
            $('#edit-questiontext').addClass('is-invalid');
            return;
        }

        $('#edit-questiontext').removeClass('is-invalid');
        $('#save-edit-btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>' + (strings.saving || 'Guardando...'));

        // Build array of all save calls.
        const calls = [];

        // Question text.
        calls.push({
            methodname: 'local_questions_save_question_field',
            args: {questionid: parseInt(questionId), field: 'questiontext', value: questionText}
        });

        // General feedback.
        calls.push({
            methodname: 'local_questions_save_question_field',
            args: {questionid: parseInt(questionId), field: 'generalfeedback', value: generalFeedback}
        });

        // Answers.
        const selectedCorrectId = $('input[name="correct-answer"]:checked').val();
        $('#edit-answers-container .answer-item').each(function() {
            const $item = $(this);
            const answerId = $item.data('answerid');
            const answerText = $item.find('.answer-text').val().trim();
            const answerFeedback = $item.find('.answer-feedback').val().trim();

            // Save answer text.
            calls.push({
                methodname: 'local_questions_save_question_field',
                args: {questionid: parseInt(questionId), field: 'answer:' + answerId, value: answerText}
            });

            // Save answer feedback.
            calls.push({
                methodname: 'local_questions_save_question_field',
                args: {questionid: parseInt(questionId), field: 'feedback:' + answerId, value: answerFeedback}
            });
        });

        // If correct answer changed, update fractions.
        if (selectedCorrectId) {
            calls.push({
                methodname: 'local_questions_save_question_field',
                args: {questionid: parseInt(questionId), field: 'correctanswer', value: selectedCorrectId}
            });
        }

        // Execute all calls.
        const promises = Ajax.call(calls);
        $.when.apply($, promises).then(function() {
            // Close modal immediately.
            modalAction('flag-edit-modal', 'hide');

            // Show success toast.
            showToast({
                type: 'info',
                title: '✏️ Pregunta actualizada',
                message: strings.questionsaved || 'Pregunta guardada correctamente.',
                category: 'success',
                duration: 3000,
                sound: false
            });

            // Update the table row preview.
            updateTablePreview(questionId, questionText);
        }).fail(function(error) {
            $('#edit-error-text').text(error.message || 'Error al guardar');
            $('#edit-error-message').removeClass('d-none');
            $('#save-edit-btn').prop('disabled', false).html('<i class="fa fa-save me-1"></i>' + (strings.savechanges || 'Guardar cambios'));
        });
    };

    /**
     * Update table row with new question text preview.
     *
     * @param {number} questionId The question ID
     * @param {string} newText The new question text
     */
    const updateTablePreview = function(questionId, newText) {
        const $row = $('#flags-table tbody tr[data-questionid="' + questionId + '"]');
        if ($row.length) {
            // Truncate for preview.
            const preview = newText.length > 100 ? newText.substring(0, 100) + '...' : newText;
            $row.find('.text-truncate').text(preview);
        }
    };

    /**
     * Open resolution modal.
     *
     * @param {number} questionId The question ID
     * @param {string} action The action (resolve/dismiss)
     */
    const openResolutionModal = function(questionId, action) {
        $('#resolution-questionid').val(questionId);
        $('#resolution-action').val(action);

        const $header = $('#resolution-modal-header');
        const $submitBtn = $('#submit-resolution-btn');
        const $typeGroup = $('#resolution-type-group');

        if (action === 'resolve') {
            $header.removeClass('bg-danger text-dark').addClass('bg-success text-white');
            $('#flag-resolution-title').html('<i class="fa fa-check me-2"></i>' + (strings.resolve || 'Resolver'));
            $submitBtn.removeClass('btn-danger').addClass('btn-success text-white').text(strings.resolve || 'Resolver');
            $typeGroup.show();
        } else {
            $header.removeClass('bg-success').addClass('bg-danger text-white');
            $('#flag-resolution-title').html('<i class="fa fa-times me-2"></i>' + (strings.dismiss || 'Descartar'));
            $submitBtn.removeClass('btn-success').addClass('btn-danger text-white').text(strings.dismiss || 'Descartar');
            $typeGroup.hide();
        }

        resetResolutionModal();
        modalAction('flag-resolution-modal', 'show');
    };

    /**
     * Reset resolution modal.
     */
    const resetResolutionModal = function() {
        $('#resolution-type').val('');
        $('#resolution-feedback').val('');
        $('#resolution-success-message').addClass('d-none');
        $('#resolution-error-message').addClass('d-none');
        $('#submit-resolution-btn').prop('disabled', false);
    };

    /**
     * Submit resolution.
     */
    const submitResolution = function() {
        const questionId = $('#resolution-questionid').val();
        const action = $('#resolution-action').val();
        const resolution = $('#resolution-type').val();
        const feedback = $('#resolution-feedback').val().trim();

        // Validate.
        if (action === 'resolve' && !resolution) {
            $('#resolution-type').addClass('is-invalid');
            return;
        }
        if (!feedback) {
            $('#resolution-feedback').addClass('is-invalid');
            return;
        }

        // Clear validation.
        $('#resolution-type, #resolution-feedback').removeClass('is-invalid');

        // Disable button.
        $('#submit-resolution-btn').prop('disabled', true);

        // Call API.
        Ajax.call([{
            methodname: 'local_questions_update_flag_status',
            args: {
                questionid: parseInt(questionId),
                action: action,
                resolution: resolution,
                feedback: feedback
            }
        }])[0].done(function(response) {
            if (response.success) {
                // Close modal immediately.
                modalAction('flag-resolution-modal', 'hide');

                // Show toast notification.
                showToast({
                    type: 'info',
                    title: action === 'resolve' ? '✅ Reporte resuelto' : '❌ Reporte descartado',
                    message: response.message,
                    category: action === 'resolve' ? 'success' : 'danger',
                    duration: 4000,
                    sound: false
                });

                // Update the row in the table.
                updateRowStatus(questionId, action);
            } else {
                $('#resolution-error-text').text(response.message);
                $('#resolution-error-message').removeClass('d-none');
                $('#submit-resolution-btn').prop('disabled', false);
            }
        }).fail(function(error) {
            Notification.exception(error);
            $('#submit-resolution-btn').prop('disabled', false);
        });
    };

    /**
     * Update row status after resolution without page reload.
     *
     * @param {number} questionId The question ID
     * @param {string} action The action taken (resolve/dismiss)
     */
    const updateRowStatus = function(questionId, action) {
        const $row = $('#flags-table tbody tr[data-questionid="' + questionId + '"]');
        if (!$row.length) {
            return;
        }

        const newStatus = action === 'resolve' ? 'resolved' : 'dismissed';
        $row.attr('data-status', newStatus);

        // Update status badge.
        const badges = {
            'resolved': '<span class="badge bg-success">Resuelta</span>',
            'dismissed': '<span class="badge bg-dark">Descartada</span>'
        };
        $row.find('td:eq(4)').html(badges[newStatus]);

        // Hide action buttons (resolve/dismiss) since it's now resolved/dismissed.
        $row.find('.resolve-btn, .dismiss-btn').hide();

        // Update filter counts.
        updateFilterCounts(action);

        // If current filter is 'pending' or 'reviewing', hide this row.
        const activeFilter = $('.filter-btn.active').data('filter');
        if (activeFilter === 'pending' || activeFilter === 'reviewing') {
            $row.fadeOut(300);
        }
    };

    /**
     * Update filter button counts after resolution.
     *
     * @param {string} action The action taken
     */
    const updateFilterCounts = function(action) {
        // Decrement pending count.
        const $pendingBadge = $('.filter-btn[data-filter="pending"] .badge');
        let pendingCount = parseInt($pendingBadge.text()) || 0;
        if (pendingCount > 0) {
            pendingCount--;
            $pendingBadge.text(pendingCount);
        }

        // Increment resolved/dismissed count.
        const targetFilter = action === 'resolve' ? 'resolved' : 'dismissed';
        const $targetBadge = $('.filter-btn[data-filter="' + targetFilter + '"] .badge');
        let targetCount = parseInt($targetBadge.text()) || 0;
        targetCount++;
        $targetBadge.text(targetCount);
    };

    return {
        init: init
    };
});
