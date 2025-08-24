/**
 * Topic Keywords Builder
 *
 * @module     local_cria/topic_keywords
 * @copyright  2024 Patrick Thibaudeau
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Templates from 'core/templates';
import Notification from 'core/notification';

/**
 * Initialize the topic keywords builder
 */
export const init = () => {
    const buildButton = document.getElementById('build-keywords-array');
    if (buildButton) {
        buildButton.addEventListener('click', openModal);
    }
};

/**
 * Open the topic keywords builder modal
 */
const openModal = async () => {
    try {
        // Get existing keywords if any
        const existingData = getExistingKeywords();
        
        // Render the modal body
        const body = await Templates.render('local_cria/topic_keywords_modal', {
            topics: existingData
        });

        // Create modal using ModalFactory
        const modal = await ModalFactory.create({
            title: 'Topic Keywords Builder',
            body: body,
            large: true
        });

        // Set up event listeners
        setupModalEvents(modal);
        
        // Show the modal
        modal.show();

    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Get existing keywords from the textarea
 * @returns {Array} Array of topic objects
 */
const getExistingKeywords = () => {
    const textarea = document.getElementById('id_topic_keywords');
    console.log('Looking for textarea with id: id_topic_keywords');
    console.log('Textarea found:', textarea);

    if (!textarea) {
        console.log('Textarea not found!');
        return [];
    }

    console.log('Textarea value:', textarea.value);

    if (!textarea.value.trim()) {
        console.log('Textarea is empty');
        return [];
    }

    try {
        const data = JSON.parse(textarea.value);
        console.log('Parsed JSON data:', data);

        const result = Object.entries(data).map(([key, keywords]) => ({
            topic: key,
            keywords: Array.isArray(keywords) ? keywords.join(', ') : ''
        }));

        console.log('Converted data for template:', result);
        return result;
    } catch (e) {
        console.error('Error parsing JSON:', e);
        return [];
    }
};

/**
 * Setup modal event listeners
 * @param {Modal} modal The modal instance
 */
const setupModalEvents = (modal) => {
    const modalRoot = modal.getRoot()[0];

    // Add topic button
    modalRoot.querySelector('#add-topic-btn').addEventListener('click', () => {
        addTopicRow(modalRoot);
    });

    // Save button
    modalRoot.querySelector('#save-keywords-btn').addEventListener('click', () => {
        saveKeywords(modal);
    });

    // Cancel button
    modalRoot.querySelector('#cancel-keywords-btn').addEventListener('click', () => {
        modal.hide();
    });

    // Remove topic buttons (using event delegation)
    modalRoot.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-topic-btn')) {
            e.target.closest('.topic-row').remove();
        }
    });

    // Modal close events
    modal.getRoot().on(ModalEvents.hidden, () => {
        modal.destroy();
    });
};

/**
 * Add a new topic row to the modal
 * @param {Element} modalRoot The modal root element
 */
const addTopicRow = (modalRoot) => {
    const container = modalRoot.querySelector('#topics-container');
    const rowHtml = `
        <div class="topic-row mb-3 p-3 border rounded">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Topic Name:</label>
                    <input type="text" class="form-control topic-name" placeholder="e.g., technical">
                </div>
                <div class="col-md-7">
                    <label class="form-label">Keywords (comma-separated):</label>
                    <input type="text" class="form-control topic-keywords" placeholder="e.g., API, integration, bug, error">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-sm remove-topic-btn">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', rowHtml);
};

/**
 * Save keywords and update the original textarea
 * @param {Modal} modal The modal instance
 */
const saveKeywords = (modal) => {
    const modalRoot = modal.getRoot()[0];
    const topicRows = modalRoot.querySelectorAll('.topic-row');
    const keywordsObject = {};

    // Validate and collect data
    let isValid = true;
    topicRows.forEach(row => {
        const topicName = row.querySelector('.topic-name').value.trim();
        const keywordsText = row.querySelector('.topic-keywords').value.trim();

        if (topicName && keywordsText) {
            // Convert comma-separated string to array and clean up
            const keywordsArray = keywordsText.split(',')
                .map(keyword => keyword.trim())
                .filter(keyword => keyword.length > 0);
            
            if (keywordsArray.length > 0) {
                keywordsObject[topicName] = keywordsArray;
            }
        } else if (topicName || keywordsText) {
            // If only one field is filled, show error
            isValid = false;
            row.style.border = '2px solid red';
        }
    });

    if (!isValid) {
        Notification.alert('Error', 'Please fill in both topic name and keywords for all rows, or remove empty rows.');
        return;
    }

    // Update the original textarea using the specific ID
    const textarea = document.getElementById('id_topic_keywords');
    if (textarea) {
        textarea.value = JSON.stringify(keywordsObject, null, 2);
        
        // Trigger change event
        const event = new Event('change', { bubbles: true });
        textarea.dispatchEvent(event);
    }

    // Show success message
    Notification.addNotification({
        message: 'Keywords array updated successfully!',
        type: 'success'
    });

    // Close modal
    modal.hide();
};
