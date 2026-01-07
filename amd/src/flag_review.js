/**
 * Flag review module for teachers/reviewers.
 *
 * @module     local_questions/flag_review
 * @copyright  2026 Sergio C.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

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
        ]).done(function(s) {
            strings.resolve = s[0];
            strings.dismiss = s[1];
            strings.resolving = s[2];
            strings.dismissing = s[3];
            strings.nocomment = s[4];
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
     * Show flag details for a question.
     *
     * @param {number} questionId The question ID
     */
    const showDetails = function(questionId) {
        // Show modal with loading state.
        $('#flag-details-loading').removeClass('d-none');
        $('#flag-details-content').addClass('d-none');

        // Use Bootstrap 5 native API.
        const modalEl = document.getElementById('flag-details-modal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        // Fetch details.
        Ajax.call([{
            methodname: 'local_questions_get_flag_details',
            args: {questionid: questionId}
        }])[0].done(function(response) {
            renderDetails(response);
        }).fail(function(error) {
            Notification.exception(error);
            modal.hide();
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
     * Open question editor.
     *
     * @param {number} questionId The question ID
     */
    const editQuestion = function(questionId) {
        // Open Moodle question editor in new tab.
        // Use question.php with courseid=1 (site) as it doesn't require cmid.
        const url = M.cfg.wwwroot + '/question/question.php?id=' + questionId + '&courseid=1';
        window.open(url, '_blank');
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
            $header.removeClass('bg-danger').addClass('bg-success');
            $('#flag-resolution-title').html('<i class="fa fa-check mr-2"></i>' + (strings.resolve || 'Resolver'));
            $submitBtn.removeClass('btn-danger').addClass('btn-success').text(strings.resolve || 'Resolver');
            $typeGroup.show();
        } else {
            $header.removeClass('bg-success').addClass('bg-danger');
            $('#flag-resolution-title').html('<i class="fa fa-times mr-2"></i>' + (strings.dismiss || 'Descartar'));
            $submitBtn.removeClass('btn-success').addClass('btn-danger').text(strings.dismiss || 'Descartar');
            $typeGroup.hide();
        }

        resetResolutionModal();
        // Use Bootstrap 5 native API.
        const modalEl = document.getElementById('flag-resolution-modal');
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
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
                $('#resolution-success-text').text(response.message);
                $('#resolution-success-message').removeClass('d-none');

                // Reload page after delay to show updated data.
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
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

    return {
        init: init
    };
});
