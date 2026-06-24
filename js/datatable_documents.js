$(document).ready(function () {
    let wwwroot = M.cfg.wwwroot;
    const documentTables = {};

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

    function getActiveIntentId() {
        const activePane = document.querySelector('#cria-content-tab .tab-pane.active');
        if (activePane) {
            const input = activePane.querySelector('.cria-intent-id');
            if (input) {
                return input.value;
            }
        }
        const fallback = document.querySelector('.cria-intent-id');
        return fallback ? fallback.value : null;
    }

    function getDocumentTable(intentId) {
        if (intentId && documentTables[intentId]) {
            return documentTables[intentId];
        }
        return null;
    }

    function reloadActiveDocumentTable() {
        const table = getDocumentTable(getActiveIntentId());
        if (table) {
            table.ajax.reload();
        }
    }

    $('.cria-documents-table').each(function () {
        const $table = $(this);
        const intentId = $table.data('intent-id');

        documentTables[intentId] = $table.DataTable({
            dom: 'lfrtip',
            processing: true,
            serverSide: true,
            ajax: {
                url: wwwroot + '/local/cria/ajax/datatable_documents.php',
                type: 'POST',
                data: function () {
                    return {
                        bot_id: $('#bot_id').val() || $('#bot-id').val(),
                        intent_id: intentId
                    };
                },
                complete: function () {
                    $table.find('.delete-content').off('click').on('click', function () {
                        const id = $(this).data('id');
                        $('#cria-delete-modal-title').html('Document');
                        $('#cria-delete-modal-message').html('Are you sure you want to delete this document?');
                        openModal('#cria-delete-modal');
                        $('#cria-modal-delete-confirm').off('click').on('click', function () {
                            closeModal('#cria-delete-modal');
                            $.ajax({
                                url: wwwroot + '/local/cria/ajax/delete_document.php?id=' + id,
                                type: 'POST',
                                success: function () {
                                    documentTables[intentId].ajax.reload();
                                }
                            });
                        });
                    });

                    const fileState = $('#cria-file-state').val();
                    if (fileState !== '0' && intentId === getActiveIntentId()) {
                        let interval = setInterval(function () {
                            reloadActiveDocumentTable();
                            $.ajax({
                                url: wwwroot + '/local/cria/ajax/check_file_state.php',
                                type: 'POST',
                                data: {
                                    intent_id: getActiveIntentId()
                                },
                                success: function (results) {
                                    results = JSON.parse(results);
                                    if (results.count === '0') {
                                        $('#cria-file-state').val('0');
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
                {data: 'select'},
                {data: 'name'},
                {data: 'indexed'},
                {data: 'actions'}
            ],
            order: [[1, 'asc']],
            columnDefs: [
                {
                    targets: [0, 3],
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

    $('.dataTables_length').css('margin-top', '.5rem');
    $('.buttons-html5').addClass('btn-outline-primary mr-2').removeClass('btn-secondary');

    $(document).on('click', '.cria-document-select-all', function () {
        const intentId = $(this).closest('.cria-documents-card').data('intent-id');
        const checked = $(this).is(':checked');
        $('.cria-document-dt-select-box[data-intent-id="' + intentId + '"], .cria-document-dt-select-box').each(function () {
            const tableIntentId = $(this).closest('.cria-documents-card').data('intent-id');
            if (tableIntentId == intentId) {
                $(this).prop('checked', checked);
            }
        });
    });

    $(document).on('click', '.cria-publish-all-files', function () {
        const intentId = $(this).data('intent_id');
        const selected = [];
        const $card = $('.cria-documents-card[data-intent-id="' + intentId + '"]');
        $card.find('.cria-document-dt-select-box:checked').each(function () {
            selected.push($(this).data('id'));
        });

        if (selected.length === 0) {
            alert('No documents selected. You  must select at least one document to publish.');
            return;
        }

        openModal('#cria-publish-document-modal');
        $('#cria-modal-publish-confirm').off('click').on('click', function () {
            document.getElementById('cria-loader').style.display = 'flex';
            $.ajax({
                url: wwwroot + '/local/cria/ajax/publish_documents.php',
                type: 'POST',
                data: {
                    bot_id: $('#bot_id').val() || $('#bot-id').val(),
                    intent_id: intentId,
                    documents: selected
                },
                success: function (results) {
                    results = JSON.parse(results);
                    document.getElementById('cria-loader').style.display = 'none';
                    closeModal('#cria-publish-document-modal');
                    if (results.status === 404) {
                        alert(results.message);
                    } else if (documentTables[intentId]) {
                        documentTables[intentId].ajax.reload();
                    }
                }
            });
        });
    });

    $(document).on('click', '.cria-open-url-modal', function (event) {
        event.preventDefault();
        const intentId = $(this).data('intent-id');
        openModal('#urlModal-' + intentId);
    });

    $(document).on('click', '.btn-cria-save-urls', function () {
        const intentId = $(this).data('intent_id');
        const urls = $('#local-cria-urls-' + intentId).val();
        document.getElementById('cria-loader').style.display = 'flex';
        $.ajax({
            url: wwwroot + '/local/cria/ajax/publish_urls.php',
            type: 'POST',
            data: {
                urls: urls,
                intent_id: intentId
            },
            success: function (data) {
                data = JSON.parse(data);
                closeModal('#urlModal-' + intentId);
                document.getElementById('cria-loader').style.display = 'none';
                if (data.status === 404) {
                    alert(data.message);
                } else if (documentTables[intentId]) {
                    documentTables[intentId].ajax.reload();
                }
            }
        });
    });

    $(document).on('click', '.cria-delete-selected-documents', function () {
        const intentId = $(this).data('intent_id');
        const selected = [];
        const $card = $('.cria-documents-card[data-intent-id="' + intentId + '"]');
        $card.find('.cria-document-dt-select-box:checked').each(function () {
            selected.push($(this).data('id'));
        });

        if (selected.length === 0) {
            alert('No documents selected. You  must select at least one document to delete.');
            return;
        }

        $('#cria-delete-modal-title').html('Document');
        $('#cria-delete-modal-message').html('Are you sure you want to delete the selected documents?');
        openModal('#cria-delete-modal');
        $('#cria-modal-delete-confirm').off('click').on('click', function () {
            closeModal('#cria-delete-modal');
            document.getElementById('cria-loader').style.display = 'flex';
            $.ajax({
                url: wwwroot + '/local/cria/ajax/delete_document.php',
                type: 'POST',
                data: {
                    bot_id: $('#bot_id').val() || $('#bot-id').val(),
                    intent_id: intentId,
                    documents: selected
                },
                success: function (results) {
                    results = JSON.parse(results);
                    document.getElementById('cria-loader').style.display = 'none';
                    if (results.status === 404) {
                        alert(results.message);
                    } else if (documentTables[intentId]) {
                        documentTables[intentId].ajax.reload();
                    }
                }
            });
        });
    });
});
