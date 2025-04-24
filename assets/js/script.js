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