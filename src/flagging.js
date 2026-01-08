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
     * Initialize the flagging module.
     */
    const init = function() {
        // Bind click events on flag buttons.
        $(document).on('click', '.local-questions-flag-btn:not(.disabled)', function(e) {
            e.preventDefault();
            currentQuestionId = $(this).data('questionid');
            $('#flag-questionid').val(currentQuestionId);

            // Reset form state.
            resetModal();
        });

        // Bind submit button.
        $(document).on('click', '#submit-flag-btn', function(e) {
            e.preventDefault();
            submitFlag();
        });

        // Reset modal on close.
        $('#local-questions-flag-modal').on('hidden.bs.modal', function() {
            resetModal();
        });
    };

    /**
     * Reset the modal to initial state.
     */
    const resetModal = function() {
        $('#flag-reason').val('').removeClass('is-invalid');
        $('#flag-comment').val('');
        $('#flag-success-message').addClass('d-none');
        $('#flag-error-message').addClass('d-none');
        $('#submit-flag-btn').prop('disabled', false);
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
                // Show success message.
                $('#flag-success-message').removeClass('d-none');
                $('#flag-error-message').addClass('d-none');

                // Update the button to show flagged state.
                updateButtonState(currentQuestionId);

                // Close modal after delay (Bootstrap 5 compatible).
                setTimeout(function() {
                    const modalEl = document.getElementById('local-questions-flag-modal');
                    // Use Bootstrap 5 API if available, fallback to jQuery.
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) {
                            modal.hide();
                        }
                    } else {
                        $('#local-questions-flag-modal').modal('hide');
                    }
                }, 1500);
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

        // Load translated string.
        Str.get_string('alreadyflagged', 'local_questions').done(function(str) {
            $btn.removeClass('btn-outline-warning')
                .addClass('btn-warning disabled')
                .prop('disabled', true)
                .attr('title', str)
                // Bootstrap 5: use ms-1 instead of ml-1.
                .html('<i class="fa fa-flag"></i> <span class="ms-1">' + str + '</span>');
        });
    };

    return {
        init: init
    };
});
