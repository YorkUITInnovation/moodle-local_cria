$(document).ready(function () {
    var wwwroot = M.cfg.wwwroot;

    // Helper to get the currently active tab pane
    const getActivePane = () => $('#cria-content-tab .tab-pane.active');

    // Get or init DataTable for the active tab's questions table
    const getQuestionTable = () => {
        const $tableEl = getActivePane().find('#cria-questions-table');
        if ($.fn.DataTable.isDataTable($tableEl)) {
            return $tableEl.DataTable();
        }
        return $tableEl.DataTable({
            dom: 'lfrtip',
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": wwwroot + "/local/cria/ajax/datatable_questions.php",
                "type": "POST",
                "data": function (d) {
                    d.bot_id = $('#bot-id').val();
                    d.intent_id = getActivePane().find('#intent_id').val();
                },
                "complete": function () {

                    $('.delete-question').off();
                    $('.delete-question').on('click', function () {
                        const $btn = $(this);
                        const id = $btn.data('id');
                        // Insert title into modal
                        $('#cria-delete-modal-title').html('Question');
                        // Insert delete message into modal
                        $('#cria-delete-modal-message').html('Are you sure you want to delete this question?');
                        $('#cria-delete-modal').modal('toggle');
                        $('#cria-modal-delete-confirm').off();
                        $('#cria-modal-delete-confirm').on('click', function () {
                            $('#cria-delete-modal').modal('toggle');
                            $.ajax({
                                url: wwwroot + '/local/cria/ajax/delete_question.php?id=' + id,
                                type: 'POST',
                                success: function (results) {
                                    const table = getQuestionTable();
                                    const row = table.row($btn.closest('tr'));
                                    if (row.length) {
                                        row.remove().draw(false);
                                    } else {
                                        table.ajax.reload();
                                    }
                                }
                            });
                        });
                    });
                }
            },
            "deferRender": true,
            "columns": [
                { "data": "select" },
                { "data": "name" },
                { "data": "actions" }
            ],
            "order": [[1, "asc"]],
            "columnDefs": [
                { "targets": [0, 2], "orderable": false },
                { "targets": [0], "visible": true, "searchable": false }
            ],
            "lengthMenu": [[5, 10, 25, 50, 100, 500, 1000, 10000], [5, 10, 25, 50, 100, 500, 1000, 10000]],
            "pageLength": 25,
            stateSave: false
        });
    };

    // Initialize for initial active tab
    let question_table = getQuestionTable();

    // Reinitialize when switching tabs
    $(document).on('shown.bs.tab', '#cria-content-tabs [data-toggle="tab"]', function () {
        question_table = getQuestionTable();
    });

    // Delegate select-all for questions, scoped to the current questions card
    $(document).off('click', '#cria-select-questions');
    $(document).on('click', '#cria-select-questions', function () {
        const $card = $(this).closest('.card');
        const checked = $(this).is(':checked');
        $card.find('.cria-question-dt-select-box').prop('checked', checked);
    });

    // Publish selected questions (scoped to same tab/card)
    $(document).off('click', '.cria-publish-questions');
    $(document).on('click', '.cria-publish-questions', function () {
        const $btn = $(this);
        const $card = $btn.closest('.card');
        let selected = [];
        $card.find('.cria-question-dt-select-box').each(function () {
            if ($(this).is(':checked')) {
                selected.push($(this).data('id'));
            }
        });
        if (selected.length === 0) {
            alert('No questions selected. You  must select at least one question to publish.');
        } else {
            $('#cria-publish-question-modal').modal('toggle');
            $('#cria-modal-publish-question-confirm').off();
            $('#cria-modal-publish-question-confirm').on('click', function () {
                document.getElementById('cria-loader').style.display = 'flex';
                $('#cria-publish-question-modal').modal('toggle');
                $.ajax({
                    url: wwwroot + '/local/cria/ajax/publish_question.php',
                    type: 'POST',
                    data: {
                        'bot_id': $('#bot-id').val(),
                        'intent_id': getActivePane().find('#intent_id').val(),
                        'questions': selected
                    },
                    success: function (results) {
                        // Convert json into object
                        results = JSON.parse(results);
                        // Hide the loader
                        document.getElementById('cria-loader').style.display = 'none';
                        if (results.status === 404) {
                            alert(results.message);
                        } else {
                            getQuestionTable().ajax.reload();
                        }
                    }
                });
            });
        }
    });

    // Delete selected questions (scoped to same tab/card)
    $(document).off('click', '#criaDeleteSelectedQuestions');
    $(document).on('click', '#criaDeleteSelectedQuestions', function () {
        const $btn = $(this);
        const $card = $btn.closest('.card');
        let selected = [];
        $card.find('.cria-question-dt-select-box').each(function () {
            if ($(this).is(':checked')) {
                selected.push($(this).data('id'));
            }
        });
        if (selected.length === 0) {
            alert('No questions selected. You  must select at least one question to delete.');
        } else {
            $('#cria-delete-modal-title').html('Question');
            $('#cria-delete-modal-message').html('Are you sure you want to delete these questions?');
            $('#cria-delete-modal').modal('toggle');
            $('#cria-modal-delete-confirm').off();
            $('#cria-modal-delete-confirm').on('click', function () {
                $('#cria-delete-modal').modal('toggle');
                document.getElementById('cria-loader').style.display = 'flex';
                $.ajax({
                    url: wwwroot + '/local/cria/ajax/delete_question.php',
                    type: 'POST',
                    data: {
                        'question_id': 0,
                        'questions': selected
                    },
                    success: function (results) {
                        document.getElementById('cria-loader').style.display = 'none';
                        getQuestionTable().ajax.reload();
                    }
                });
            });
        }
    });
});
