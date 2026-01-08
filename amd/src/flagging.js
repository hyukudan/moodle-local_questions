/**
 * Flagging module for question reports.
 *
 * @module     local_questions/flagging
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

    /**
     * Current question ID being flagged.
     * @type {number}
     */
    let currentQuestionId = 0;

    /**
     * Cached strings.
     * @type {Object}
     */
    let strings = {};

    /**
     * Show toast notification using gamification toast if available.
     *
     * @param {Object} options Toast options
     */
    const showToast = function(options) {
        require(['theme_remui/gamification_toast'], function(GamificationToast) {
            if (GamificationToast && typeof GamificationToast.show === 'function') {
                GamificationToast.show(options);
            } else {
                showFallbackNotification(options);
            }
        }, function() {
            showFallbackNotification(options);
        });
    };

    /**
     * Fallback notification when gamification toast is not available.
     *
     * @param {Object} options Notification options
     */
    const showFallbackNotification = function(options) {
        Notification.addNotification({
            message: options.title + ': ' + options.message,
            type: 'success'
        });
    };

    /**
     * Load required strings.
     */
    const loadStrings = function() {
        Str.get_strings([
            {key: 'alreadyflagged', component: 'local_questions'},
            {key: 'flagsubmitted', component: 'local_questions'},
            {key: 'flagsubmitted_desc', component: 'local_questions'}
        ]).done(function(strs) {
            strings.alreadyflagged = strs[0];
            strings.flagsubmitted = strs[1];
            strings.flagsubmitted_desc = strs[2];
        });
    };

    /**
     * Open modal helper (Bootstrap 5 compatible).
     *
     * @param {string} modalId The modal element ID
     */
    const openModal = function(modalId) {
        const modalEl = document.getElementById(modalId);
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        } else {
            $('#' + modalId).modal('show');
        }
    };

    /**
     * Close modal helper (Bootstrap 5 compatible).
     *
     * @param {string} modalId The modal element ID
     */
    const closeModal = function(modalId) {
        const modalEl = document.getElementById(modalId);
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        } else {
            $('#' + modalId).modal('hide');
        }
    };

    /**
     * Initialize the flagging module.
     */
    const init = function() {
        loadStrings();

        // Bind click events on flag buttons - open modal with confirmation step.
        $(document).on('click', '.local-questions-flag-btn:not(.disabled)', function(e) {
            e.preventDefault();
            currentQuestionId = $(this).data('questionid');
            $('#flag-questionid').val(currentQuestionId);

            // Reset to confirmation step.
            resetModal();

            // Open modal.
            openModal('local-questions-flag-modal');
        });

        // Bind confirm button - proceed to form step.
        $(document).on('click', '#confirm-flag-btn', function(e) {
            e.preventDefault();
            showFormStep();
        });

        // Bind submit button.
        $(document).on('click', '#submit-flag-btn', function(e) {
            e.preventDefault();
            submitFlag();
        });

        // Bind close/cancel buttons explicitly for BS5 compatibility.
        $(document).on('click', '#local-questions-flag-modal [data-bs-dismiss="modal"]', function(e) {
            e.preventDefault();
            closeModal('local-questions-flag-modal');
        });

        // Reset modal on close.
        $('#local-questions-flag-modal').on('hidden.bs.modal', function() {
            resetModal();
        });
    };

    /**
     * Reset the modal to initial state (confirmation step).
     */
    const resetModal = function() {
        // Show confirmation step, hide form step.
        $('#flag-step-confirm').removeClass('d-none');
        $('#flag-step-form').addClass('d-none');

        // Reset form fields.
        $('#flag-reason').val('').removeClass('is-invalid');
        $('#flag-comment').val('');
        $('#flag-error-message').addClass('d-none');
        $('#submit-flag-btn').prop('disabled', false);
    };

    /**
     * Show the form step (after confirmation).
     */
    const showFormStep = function() {
        $('#flag-step-confirm').addClass('d-none');
        $('#flag-step-form').removeClass('d-none');
    };

    /**
     * Submit the flag to the server.
     */
    const submitFlag = function() {
        const reason = $('#flag-reason').val();
        const comment = $('#flag-comment').val().trim();

        // Validate reason.
        if (!reason) {
            $('#flag-reason').addClass('is-invalid');
            return;
        }
        $('#flag-reason').removeClass('is-invalid');

        // Disable button to prevent double submission.
        $('#submit-flag-btn').prop('disabled', true);

        // Get attempt ID if available in URL.
        const urlParams = new URLSearchParams(window.location.search);
        const attemptId = parseInt(urlParams.get('attempt')) || 0;

        // Call AJAX service.
        Ajax.call([{
            methodname: 'local_questions_submit_flag',
            args: {
                questionid: currentQuestionId,
                reason: reason,
                comment: comment,
                attemptid: attemptId
            }
        }])[0].done(function(response) {
            if (response.success) {
                // Close modal immediately.
                closeModal('local-questions-flag-modal');

                // Show toast notification.
                showToast({
                    type: 'info',
                    title: strings.flagsubmitted || '¡Reporte enviado!',
                    message: strings.flagsubmitted_desc || 'Gracias por ayudarnos a mejorar.',
                    category: 'success',
                    duration: 4000,
                    sound: false
                });

                // Update the button to show flagged state.
                updateButtonState(currentQuestionId);
            } else {
                // Show error message.
                $('#flag-error-text').text(response.message);
                $('#flag-error-message').removeClass('d-none');
                $('#submit-flag-btn').prop('disabled', false);
            }
        }).fail(function(error) {
            Notification.exception(error);
            $('#submit-flag-btn').prop('disabled', false);
        });
    };

    /**
     * Update button state after successful flag submission.
     *
     * @param {number} questionId The question ID
     */
    const updateButtonState = function(questionId) {
        const $btn = $('.local-questions-flag-btn[data-questionid="' + questionId + '"]');
        const str = strings.alreadyflagged || 'Ya reportada';

        $btn.removeClass('btn-outline-danger')
            .addClass('btn-secondary disabled')
            .prop('disabled', true)
            .attr('title', str)
            .html('<i class="fa fa-check-circle"></i> <span class="ms-1">' + str + '</span>');
    };

    return {
        init: init
    };
});
