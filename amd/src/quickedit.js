define(['jquery', 'core/ajax', 'core/str', 'core/notification'], function ($, Ajax, Str, Notification) {
    return {
        init: function () {
            var $table = $('#questions-table');

            // Inline edit: show save buttons on input
            $table.on('input', '.inplace-edit', function () {
                $(this).closest('tr').find('.save-row').show();
                $('#batch-save-btn').show();
            });

            // Save single row
            $table.on('click', '.save-row', function () {
                var $btn = $(this);
                var $row = $btn.closest('tr');
                var qid = $row.data('id');
                var calls = [];

                $btn.prop('disabled', true);

                // Check for questiontext changes
                var $qText = $row.find('[data-field="questiontext"]');
                if ($qText.length) {
                    calls.push({
                        methodname: 'local_questions_save_question_field',
                        args: { questionid: qid, field: 'questiontext', value: $qText.text() }
                    });
                }

                // Check for generalfeedback changes
                var $gFeedback = $row.find('[data-field="generalfeedback"]');
                if ($gFeedback.length) {
                    calls.push({
                        methodname: 'local_questions_save_question_field',
                        args: { questionid: qid, field: 'generalfeedback', value: $gFeedback.text() }
                    });
                }

                // Check for answer/feedback changes
                $row.find('[data-answerid]').each(function () {
                    var $el = $(this);
                    var fieldName = $el.data('field'); // answer or feedback
                    var answerId = $el.data('answerid');
                    var value = $el.text();

                    // We send field as 'answer:123' or 'feedback:123'
                    calls.push({
                        methodname: 'local_questions_save_question_field',
                        args: { questionid: qid, field: fieldName + ':' + answerId, value: value }
                    });
                });

                if (calls.length) {
                    $.when.apply($, Ajax.call(calls)).then(function () {
                        $btn.hide().prop('disabled', false);
                        if ($('.save-row:visible').length === 0) {
                            $('#batch-save-btn').hide();
                        }
                    }).fail(function (ex) {
                        $btn.prop('disabled', false);
                        Notification.exception(ex);
                    });
                } else {
                    $btn.hide().prop('disabled', false);
                }
            });

            // Batch save
            $('#batch-save-btn').click(function () {
                var calls = [];
                $table.find('.save-row:visible').each(function () {
                    var $row = $(this).closest('tr');
                    var qid = $row.data('id');

                    // Question Text
                    var $qText = $row.find('[data-field="questiontext"]');
                    if ($qText.length) {
                        calls.push({
                            methodname: 'local_questions_save_question_field',
                            args: { questionid: qid, field: 'questiontext', value: $qText.text() }
                        });
                    }

                    // General Feedback
                    var $gFeedback = $row.find('[data-field="generalfeedback"]');
                    if ($gFeedback.length) {
                        calls.push({
                            methodname: 'local_questions_save_question_field',
                            args: { questionid: qid, field: 'generalfeedback', value: $gFeedback.text() }
                        });
                    }

                    // Answers
                    $row.find('[data-answerid]').each(function () {
                        var $el = $(this);
                        calls.push({
                            methodname: 'local_questions_save_question_field',
                            args: { questionid: qid, field: $el.data('field') + ':' + $el.data('answerid'), value: $el.text() }
                        });
                    });
                });

                if (calls.length) {
                    $.when.apply($, Ajax.call(calls)).then(function () {
                        $('.save-row').hide();
                        $('#batch-save-btn').hide();
                        Notification.addNotification({
                            message: 'Batch save successful',
                            type: 'success'
                        });
                    }).fail(Notification.exception);
                }
            });

            // Bulk selection logic
            var $selectAll = $('#select-all-questions');
            var $checkboxes = $('.question-checkbox');
            var $analyzeBtn = $('#batch-analyze-btn');

            function updateButtons() {
                var checkedCount = $('.question-checkbox:checked').length;
                $analyzeBtn.prop('disabled', checkedCount === 0);
            }

            $selectAll.change(function () {
                $checkboxes.prop('checked', this.checked);
                updateButtons();
            });

            $table.on('change', '.question-checkbox', function () {
                updateButtons();
                // Update select-all state
                $selectAll.prop('checked', $('.question-checkbox:checked').length === $checkboxes.length);
            });

            // Analyze button handler
            $analyzeBtn.click(function () {
                var selectedIds = [];
                $('.question-checkbox:checked').each(function () {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length === 0) return;

                // Show modal
                var $modal = $('#ai-review-modal');
                $modal.modal('show');
                $('#ai-loading').show();
                $('#ai-results').hide();
                $('#ai-error').hide();
                $('#ai-suggestions-list').empty();
                $('#ai-apply-all').hide();

                Ajax.call([{
                    methodname: 'local_questions_analyze_batch',
                    args: { questionids: selectedIds }
                }])[0].then(function (response) {
                    $('#ai-loading').hide();

                    if (response.status === 'error') {
                        $('#ai-error').text(response.message).show();
                        return;
                    }

                    var results = JSON.parse(response.dataset);
                    if (results.questions && results.questions.length > 0) {
                        $('#ai-results').show();
                        $('#ai-apply-all').show();

                        // Render AI suggestions using Moodle's template system.
                        require(['core/templates'], function (Templates) {
                            results.questions.forEach(function (q) {
                                if (!q.suggestions || q.suggestions.length === 0) {
                                    return;
                                }
                                q.issueCount = q.issues.length;
                                Templates.render('local_questions/ai_suggestion_card', q).then(function (html) {
                                    $('#ai-suggestions-list').append(html);
                                });
                            });
                        });
                    } else {
                        $('#ai-results').show().html('<div class="alert alert-success">No issues found by AI!</div>');
                    }

                }).fail(function (ex) {
                    $('#ai-loading').hide();
                    $('#ai-error').text(ex.message).show();
                });
            });

            // Delegate Apply events (inside modal)
            $('body').on('click', '.btn-apply-change', function () {
                var $btn = $(this);
                var $row = $btn.closest('tr');
                var qid = $row.data('qid');
                var field = $row.data('field');
                var value = $row.find('.text-success').text(); // The suggested value

                $btn.prop('disabled', true).text('Applying...');

                Ajax.call([{
                    methodname: 'local_questions_save_question_field',
                    args: { questionid: qid, field: field, value: value }
                }])[0].then(function () {
                    $btn.removeClass('btn-outline-success').addClass('btn-success').text('Applied');
                    $row.find('.text-danger').css('text-decoration', 'line-through');

                    // Update main table if visible
                    var $mainRow = $('#questions-table tr[data-id="' + qid + '"]');
                    if (field === 'questiontext') {
                        $mainRow.find('[data-field="questiontext"]').text(value);
                    } else {
                        // answer:123
                        var parts = field.split(':');
                        if (parts.length === 2) {
                            $mainRow.find('[data-field="' + parts[0] + '"][data-answerid="' + parts[1] + '"]').text(value);
                        }
                    }

                }).fail(Notification.exception);
            });

            // Apply All
            $('#ai-apply-all').click(function () {
                $('.btn-apply-change:visible:not(:disabled)').click();
            });
        }
    };
});
