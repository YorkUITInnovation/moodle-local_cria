import $ from 'jquery';
import notification from 'core/notification';
import ajax from 'core/ajax';
import select2 from 'local_cria/select2';

export const init = () => {
    $('#id_keywords').select2({
        'theme': 'classic',
        'width': '100%'
    });
    process_content();
    copy_nodes_to_clipboard();
    copy_error_message_to_clipboard();
};

/**
 * Delete a content
 */
function process_content() {
    $("#id_submitbutton").off();
    $("#id_submitbutton").on('click', function () {
        document.getElementById('cria-loader').style.display = 'flex';
    });
}

/**
 * Copy contetns of nodes to the clipboard
 */
function copy_nodes_to_clipboard() {
    // When button cria-copy-nodes is clicked, copy the contents of the nodes to the clipboard. Do not use jQuery
    document.getElementById('cria-copy-nodes').addEventListener('click', function() {
        var nodes = document.getElementById('id_nodes');
        var range = document.createRange();
        range.selectNode(nodes);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
        document.execCommand('copy');
        window.getSelection().removeAllRanges();
        // change the class bi-clipboard to bi-clipboard-check
        document.getElementById('cria-copy-nodes-icon').classList.remove('bi-clipboard');
        document.getElementById('cria-copy-nodes-icon').classList.add('bi-clipboard-check');
        // Wait 5 seconds and retunr the class to bi-clipboard
        setTimeout(function() {
            document.getElementById('cria-copy-nodes-icon').classList.remove('bi-clipboard-check');
            document.getElementById('cria-copy-nodes-icon').classList.add('bi-clipboard');
        }, 5000);
    });
}

/**
 * Copy contents of error_message to the clipboard
 */
function copy_error_message_to_clipboard() {
    // When button cria-copy-nodes is clicked, copy the contents of the nodes to the clipboard. Do not use jQuery
    document.getElementById('cria-copy-error-message').addEventListener('click', function() {
        var nodes = document.getElementById('id_error_message');
        var range = document.createRange();
        range.selectNode(nodes);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
        document.execCommand('copy');
        window.getSelection().removeAllRanges();
        // change the class bi-clipboard to bi-clipboard-check
        document.getElementById('cria-copy-error-message-icon').classList.remove('bi-clipboard');
        document.getElementById('cria-copy-error-message-icon').classList.add('bi-clipboard-check');
        // Wait 5 seconds and retunr the class to bi-clipboard
        setTimeout(function() {
            document.getElementById('cria-copy-error-message-icon').classList.remove('bi-clipboard-check');
            document.getElementById('cria-copy-error-message-icon').classList.add('bi-clipboard');
        }, 5000);
    });
}