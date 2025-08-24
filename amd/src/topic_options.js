/**
 * Topic Options Builder
 *
 * @module     local_cria/topic_options
 * @copyright  2024 Patrick Thibaudeau
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Templates from 'core/templates';
import Notification from 'core/notification';

/**
 * Initialize the topic options builder
 */
export const init = () => {
    const buildButton = document.getElementById('build-options-array');
    if (buildButton) {
        buildButton.addEventListener('click', openModal);
    }
};

/**
 * Open the topic options builder modal
 */
const openModal = async () => {
    try {
        // Get existing options if any
        const existingData = getExistingOptions();
        
        // Render the modal body
        const body = await Templates.render('local_cria/topic_options_modal', {
            options: existingData
        });

        // Create modal using ModalFactory
        const modal = await ModalFactory.create({
            title: 'Topic Options Builder',
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
 * Get existing options from the textarea
 * @returns {Array} Array of option objects
 */
const getExistingOptions = () => {
    const textarea = document.getElementById('id_topic_options');
    console.log('Looking for textarea with id: id_topic_options');
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
        
        // Data should already be in the correct format: [{value: "...", label: "..."}, ...]
        const result = Array.isArray(data) ? data : [];
        
        console.log('Options data for template:', result);
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

    // Add option button
    modalRoot.querySelector('#add-option-btn').addEventListener('click', () => {
        addOptionRow(modalRoot);
    });

    // Save button
    modalRoot.querySelector('#save-options-btn').addEventListener('click', () => {
        saveOptions(modal);
    });

    // Cancel button
    modalRoot.querySelector('#cancel-options-btn').addEventListener('click', () => {
        modal.hide();
    });

    // Remove option buttons (using event delegation)
    modalRoot.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-option-btn')) {
            e.target.closest('.option-row').remove();
        }
    });

    // Modal close events
    modal.getRoot().on(ModalEvents.hidden, () => {
        modal.destroy();
    });
};

/**
 * Add a new option row to the modal
 * @param {Element} modalRoot The modal root element
 */
const addOptionRow = (modalRoot) => {
    const container = modalRoot.querySelector('#options-container');
    const rowHtml = `
        <div class="option-row mb-3 p-3 border rounded">
            <div class="row">
                <div class="col-md-5">
                    <label class="form-label">Option Value:</label>
                    <input type="text" class="form-control option-value" placeholder="e.g., technical">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Option Label:</label>
                    <input type="text" class="form-control option-label" placeholder="e.g., Technical Support">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-sm remove-option-btn">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', rowHtml);
};

/**
 * Save options and update the original textarea
 * @param {Modal} modal The modal instance
 */
const saveOptions = (modal) => {
    const modalRoot = modal.getRoot()[0];
    const optionRows = modalRoot.querySelectorAll('.option-row');
    const optionsArray = [];

    // Validate and collect data
    let isValid = true;
    optionRows.forEach(row => {
        const optionValue = row.querySelector('.option-value').value.trim();
        const optionLabel = row.querySelector('.option-label').value.trim();

        if (optionValue && optionLabel) {
            optionsArray.push({
                value: optionValue,
                label: optionLabel
            });
        } else if (optionValue || optionLabel) {
            // If only one field is filled, show error
            isValid = false;
            row.style.border = '2px solid red';
        }
    });

    if (!isValid) {
        Notification.alert('Error', 'Please fill in both option value and label for all rows, or remove empty rows.');
        return;
    }

    // Update the original textarea using the specific ID
    const textarea = document.getElementById('id_topic_options');
    if (textarea) {
        textarea.value = JSON.stringify(optionsArray, null, 2);
        
        // Trigger change event
        const event = new Event('change', { bubbles: true });
        textarea.dispatchEvent(event);
    }

    // Show success message
    Notification.addNotification({
        message: 'Options array updated successfully!',
        type: 'success'
    });

    // Close modal
    modal.hide();
};
