// Common JavaScript functions for the application

// Validate time format (mm:ss:ms)
function validateTimeFormat(input, errorSpan) {
    const regex = /^\d+:\d{2}:\d{2}$/;
    const isValid = regex.test(input.value);
    
    if (errorSpan) {
        errorSpan.textContent = isValid ? '' : 'Please use mm:ss:ms format (e.g., 01:23:45)';
    }
    
    return isValid;
}

// Show/hide elements by ID
function toggleElement(elementId, show) {
    const element = document.getElementById(elementId);
    if (element) {
        element.style.display = show ? 'block' : 'none';
    }
}

// Confirm deletion
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this record?');
}

/**
 * Generic function to populate form fields from a dropdown selection
 * @param {string} selectorId - ID of the dropdown element
 * @param {Object} fieldMappings - Object mapping JSON property names to form field IDs
 * @param {function} afterPopulationCallback - Optional callback function to run after populating fields
 */
function setupAutoPopulation(selectorId, fieldMappings, afterPopulationCallback) {
    const selector = document.getElementById(selectorId);
    if (!selector) return;
    
    selector.addEventListener('change', function() {
        // If a valid option is selected (not the default empty option)
        if (this.value) {
            try {
                const data = JSON.parse(this.value);
                
                // Loop through field mappings and populate each field
                for (const [dataProperty, fieldId] of Object.entries(fieldMappings)) {
                    const field = document.getElementById(fieldId);
                    if (field && data[dataProperty] !== undefined) {
                        field.value = data[dataProperty];
                        
                        // Trigger change event on the field to activate any dependent logic
                        const event = new Event('change');
                        field.dispatchEvent(event);
                    }
                }
                
                // Run callback function if provided
                if (typeof afterPopulationCallback === 'function') {
                    afterPopulationCallback(data);
                }
            } catch (error) {
                console.error('Error parsing data:', error);
            }
        } else {
            // Clear fields if default option is selected
            for (const fieldId of Object.values(fieldMappings)) {
                const field = document.getElementById(fieldId);
                if (field) field.value = '';
            }
            
            // Run callback function with null data if provided
            if (typeof afterPopulationCallback === 'function') {
                afterPopulationCallback(null);
            }
        }
    });
}

// Example usage for meet selection:
// setupAutoPopulation('meetSelector', {
//     'meetName': 'meetNameField',
//     'location': 'meetLocationField',
//     'date': 'meetDateField'
// });