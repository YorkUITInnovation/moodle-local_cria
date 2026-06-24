$(document).ready(function () {
    const wwwroot = M.cfg.wwwroot;
    const questionTables = {};

    function openModal(selector) {
        const element = document.querySelector(selector);
        if (!element) {
            return;
        }
        if (window.bootstrap && window.bootstrap.Modal) {
            const Modal = window.bootstrap.Modal;
            if (typeof Modal.getOrCreateInstance === 'function') {
                Modal.getOrCreateInstance(element).show();
                return;
            }
            if (typeof Modal.getInstance === 'function') {
                const existing = Modal.getInstance(element);
                (existing || new Modal(element)).show();
                return;
            }
            new Modal(element).show();
            return;
        }
        if (typeof $(selector).modal === 'function') {
            $(selector).modal('show');
        }
    }

    function closeModal(selector) {
        const element = document.querySelector(selector);
        if (!element) {
            return;
        }
        if (window.bootstrap && window.bootstrap.Modal) {
            const Modal = window.bootstrap.Modal;
            if (typeof Modal.getOrCreateInstance === 'function') {
                Modal.getOrCreateInstance(element).hide();
                return;
            }
            if (typeof Modal.getInstance === 'function') {
                const existing = Modal.getInstance(element);
                (existing || new Modal(element)).hide();
                return;
            }
            new Modal(element).hide();
            return;
        }
        if (typeof $(selector).modal === 'function') {
            $(selector).modal('hide');
        }
    }

    function getQuestionTable(intentId) {
        if (intentId && questionTables[intentId]) {
            return questionTables[intentId];
        }
        return null;
    }

    $('.cria-questions-table').each(function () {
        const $table = $(this);
        const intentId = $table.data('intent-id');

        questionTables[intentId] = $table.DataTable({
            dom: 'lfrtip',
            processing: true,
            serverSide: true,
            ajax: {
                url: wwwroot + '/local/cria/ajax/datatable_questions.php',
                type: 'POST',
                data: function () {
                    return {
                        bot_id: $('#bot_id').val() || $('#bot-id').val(),
                        intent_id: intentId
                    };
                },
                complete: function () {
                    $table.find('.delete-question').off('click').on('click', function () {
                        const id = $(this).data('id');
                        $('#cria-delete-modal-title').html('Question');
                        $('#cria-delete-modal-message').html('Are you sure you want to delete this question?');
                        openModal('#cria-delete-modal');
                        $('#cria-modal-delete-confirm').off('click').on('click', function () {
                            closeModal('#cria-delete-modal');
                            $.ajax({
                                url: wwwroot + '/local/cria/ajax/delete_question.php?id=' + id,
                                type: 'POST',
                                success: function () {
                                    questionTables[intentId].ajax.reload();
                                }
                            });
                        });
                    });
                }
            },
            deferRender: true,
            columns: [
                {data: 'select'},
                {data: 'name'},
                {data: 'actions'}
            ],
            order: [[1, 'asc']],
            columnDefs: [
                {
                    targets: [0, 2],
                    orderable: false
                },
                {
                    targets: [0],
                    visible: true,
                    searchable: false
                }
            ],
            lengthMenu: [[5, 10, 25, 50, 100, 500, 1000, 10000], [5, 10, 25, 50, 100, 500, 1000, 10000]],
            pageLength: 25,
            stateSave: false
        });
    });

    $(document).on('click', '.cria-select-questions', function () {
        const intentId = $(this).closest('.cria-questions-card').data('intent-id');
        const checked = $(this).is(':checked');
        const $card = $('.cria-questions-card[data-intent-id="' + intentId + '"]');
        $card.find('.cria-question-dt-select-box').prop('checked', checked);
    });

    $(document).on('click', '.cria-publish-questions', function () {
        const intentId = $(this).data('intent_id');
        const selected = [];
        const $card = $('.cria-questions-card[data-intent-id="' + intentId + '"]');

        $card.find('.cria-question-dt-select-box:checked').each(function () {
            selected.push($(this).data('id'));
        });

        if (selected.length === 0) {
            alert('No questions selected. You  must select at least one question to publish.');
            return;
        }

        openModal('#cria-publish-question-modal');
        $('#cria-modal-publish-question-confirm').off('click').on('click', function () {
            document.getElementById('cria-loader').style.display = 'flex';
            closeModal('#cria-publish-question-modal');
            $.ajax({
                url: wwwroot + '/local/cria/ajax/publish_question.php',
                type: 'POST',
                data: {
                    bot_id: $('#bot_id').val() || $('#bot-id').val(),
                    intent_id: intentId,
                    questions: selected
                },
                success: function (results) {
                    results = JSON.parse(results);
                    document.getElementById('cria-loader').style.display = 'none';
                    if (results.status === 404) {
                        alert(results.message);
                    } else {
                        const table = getQuestionTable(intentId);
                        if (table) {
                            table.ajax.reload();
                        }
                    }
                }
            });
        });
    });

    $(document).on('click', '.cria-delete-selected-questions', function () {
        const intentId = $(this).data('intent_id');
        const selected = [];
        const $card = $('.cria-questions-card[data-intent-id="' + intentId + '"]');

        $card.find('.cria-question-dt-select-box:checked').each(function () {
            selected.push($(this).data('id'));
        });

        if (selected.length === 0) {
            alert('No questions selected. You  must select at least one question to delete.');
            return;
        }

        $('#cria-delete-modal-title').html('Question');
        $('#cria-delete-modal-message').html('Are you sure you want to delete these questions?');
        openModal('#cria-delete-modal');
        $('#cria-modal-delete-confirm').off('click').on('click', function () {
            closeModal('#cria-delete-modal');
            document.getElementById('cria-loader').style.display = 'flex';
            $.ajax({
                url: wwwroot + '/local/cria/ajax/delete_question.php',
                type: 'POST',
                data: {
                    question_id: 0,
                    questions: selected
                },
                success: function () {
                    document.getElementById('cria-loader').style.display = 'none';
                    const table = getQuestionTable(intentId);
                    if (table) {
                        table.ajax.reload();
                    }
                }
            });
        });
    });
});
