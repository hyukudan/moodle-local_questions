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
                // Simple dirty check logic would be better but we save what matches existing content for now
                if ($qText.length) {
                    calls.push({
                        methodname: 'local_questions_save_question_field',
                        args: { questionid: qid, field: 'questiontext', value: $qText.text() }
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
                    calls.push({
                        methodname: 'local_questions_save_question_field',
                        args: { questionid: qid, field: 'questiontext', value: $qText.text() }
                    });

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
        }
    };
});
