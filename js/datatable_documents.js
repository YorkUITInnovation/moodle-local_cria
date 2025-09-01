$(document).ready(function () {
    let wwwroot = M.cfg.wwwroot;

    // Helper to get the currently active tab pane
    const getActivePane = () => $('#cria-content-tab .tab-pane.active');

    // Get or init DataTable for the active tab
    const getTable = () => {
        const $tableEl = getActivePane().find('#cria-documents-table');
        if ($.fn.DataTable.isDataTable($tableEl)) {
            return $tableEl.DataTable();
        }
        return $tableEl.DataTable({
            dom: 'lfrtip',
            processing: true,
            serverSide: true,
            ajax: {
                url: wwwroot + '/local/cria/ajax/datatable_documents.php',
                type: 'POST',
                data: function (d) {
                    d.bot_id = $('#bot-id').val();
                    d.intent_id = getActivePane().find('#intent_id').val();
                },
                complete: function () {
                    // Single delete button in actions column
                    $('.delete-content').off();
                    $('.delete-content').on('click', function () {
                        const $btn = $(this);
                        const id = $btn.data('id');
                        $('#cria-delete-modal-title').html('Document');
                        $('#cria-delete-modal-message').html('Are you sure you want to delete this document?');
                        $('#cria-delete-modal').modal('toggle');
                        $('#cria-modal-delete-confirm').off();
                        $('#cria-modal-delete-confirm').on('click', function () {
                            $('#cria-delete-modal').modal('toggle');
                            $.ajax({
                                url: wwwroot + '/local/cria/ajax/delete_document.php?id=' + id,
                                type: 'POST',
                                success: function () {
                                    const table = getTable();
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

                    // If indexing is in progress, poll until complete
                    let fileState = $('#cria-file-state').val();
                    if (fileState !== '0') {
                        let interval = setInterval(function () {
                            getTable().ajax.reload();
                            $.ajax({
                                url: wwwroot + '/local/cria/ajax/check_file_state.php',
                                type: 'POST',
                                data: {
                                    intent_id: getActivePane().find('#intent_id').val()
                                },
                                success: function (results) {
                                    results = JSON.parse(results);
                                    if (results.count === '0') {
                                        $('#cria-file-state').val('0');
                                        fileState = '0';
                                        clearInterval(interval);
                                    }
                                }
                            });
                            if ($('#cria-file-state').val() === '0') {
                                clearInterval(interval);
                            }
                        }, 30000);
                    }
                }
            },
            deferRender: true,
            columns: [
                { data: 'select' },
                { data: 'name' },
                { data: 'indexed' },
                { data: 'actions' }
            ],
            order: [[1, 'asc']],
            columnDefs: [
                { targets: [0, 3], orderable: false },
                { targets: [0], visible: true, searchable: false }
            ],
            lengthMenu: [[5, 10, 25, 50, 100, 500, 1000, 10000], [5, 10, 25, 50, 100, 500, 1000, 10000]],
            pageLength: 25,
            stateSave: false
        });
    };

    // Initialize for initial active tab
    getTable();

    // Reinitialize when switching tabs
    $(document).on('shown.bs.tab', '#cria-content-tabs [data-toggle="tab"]', function () {
        getTable();
    });

    // Style tweaks for DataTables buttons
    $('.dataTables_length').css('margin-top', '.5rem');
    $('.buttons-html5').addClass('btn-outline-primary mr-2').removeClass('btn-secondary');

    // Select all within the current card (handles duplicate IDs across tabs)
    $(document).off('click', '#cria-document-select-all');
    $(document).on('click', '#cria-document-select-all', function () {
        const $card = $(this).closest('.card');
        const checked = $(this).is(':checked');
        $card.find('.cria-document-dt-select-box').prop('checked', checked);
    });

    // Publish all selected documents within the same tab/card
    $(document).off('click', '#cria-publish-all-files');
    $(document).on('click', '#cria-publish-all-files', function () {
        const $btn = $(this);
        const $card = $btn.closest('.card');
        let selected = [];
        $card.find('.cria-document-dt-select-box').each(function () {
            if ($(this).is(':checked')) {
                selected.push($(this).data('id'));
            }
        });
        if (selected.length === 0) {
            alert('No documents selected. You  must select at least one document to publish.');
            return;
        }
        const intentId = $btn.data('intent_id') || getActivePane().find('#intent_id').val();
        $('#cria-publish-document-modal').modal('toggle');
        $('#cria-modal-publish-confirm').off();
        $('#cria-modal-publish-confirm').on('click', function () {
            // Close the correct modal
            $('#cria-publish-document-modal').modal('toggle');
            document.getElementById('cria-loader').style.display = 'flex';
            $('#cria-loader').show();
            $.ajax({
                url: wwwroot + '/local/cria/ajax/publish_documents.php',
                type: 'POST',
                data: {
                    bot_id: $('#bot-id').val(),
                    intent_id: intentId,
                    documents: selected
                },
                success: function (results) {
                    results = JSON.parse(results);
                    document.getElementById('cria-loader').style.display = 'none';
                    $('#cria-publish-document-modal').modal('toggle');
                    if (results.status === 404) {
                        alert(results.message);
                    } else {
                        getTable().ajax.reload();
                    }
                }
            });
        });
    });

    // Save URLs (scoped to the same tab)
    $(document).off('click', '#btn-cria-save-urls');
    $(document).on('click', '#btn-cria-save-urls', function () {
        const $btn = $(this);
        const $pane = $btn.closest('.tab-pane');
        let urls = $pane.find('#local-cria-urls').val();
        let intent_id = $btn.data('intent_id') || getActivePane().find('#intent_id').val();
        document.getElementById('cria-loader').style.display = 'flex';
        $.ajax({
            url: wwwroot + '/local/cria/ajax/publish_urls.php',
            type: 'POST',
            data: { urls: urls, intent_id: intent_id },
            success: function (data) {
                data = JSON.parse(data);
                $('#urlModal').modal('toggle');
                document.getElementById('cria-loader').style.display = 'none';
                if (data.status === 404) {
                    alert(data.message);
                } else {
                    getTable().ajax.reload();
                }
            }
        });
    });

    // Delete all selected documents within same tab/card
    $(document).off('click', '#criaDeleteSelectedDocuments');
    $(document).on('click', '#criaDeleteSelectedDocuments', function () {
        const $btn = $(this);
        const $card = $btn.closest('.card');
        let selected = [];
        $card.find('.cria-document-dt-select-box').each(function () {
            if ($(this).is(':checked')) {
                selected.push($(this).data('id'));
            }
        });
        if (selected.length === 0) {
            alert('No documents selected. You  must select at least one document to delete.');
            return;
        }
        $('#cria-delete-modal-title').html('Document');
        $('#cria-delete-modal-message').html('Are you sure you want to delete the selected documents?');
        $('#cria-delete-modal').modal('toggle');
        $('#cria-modal-delete-confirm').off();
        $('#cria-modal-delete-confirm').on('click', function () {
            $('#cria-delete-modal').modal('toggle');
            document.getElementById('cria-loader').style.display = 'flex';
            $.ajax({
                url: wwwroot + '/local/cria/ajax/delete_document.php',
                type: 'POST',
                data: {
                    bot_id: $('#bot-id').val(),
                    intent_id: $btn.data('intent_id') || getActivePane().find('#intent_id').val(),
                    documents: selected
                },
                success: function (results) {
                    results = JSON.parse(results);
                    document.getElementById('cria-loader').style.display = 'none';
                    if (results.status === 404) {
                        alert(results.message);
                    } else {
                        getTable().ajax.reload();
                    }
                }
            });
        });
    });
});
