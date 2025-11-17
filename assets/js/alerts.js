/**
 * Elara Space - Beautiful Alert System
 * Using SweetAlert2 for beautiful popups
 */

// Initialize toast notification
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});

/**
 * Show success alert
 */
function showSuccess(message, title = 'Success!') {
    Swal.fire({
        icon: 'success',
        title: title,
        text: message,
        confirmButtonText: 'OK',
        confirmButtonColor: '#10B981',
        timer: 3000,
        timerProgressBar: true
    });
}

/**
 * Show error alert
 */
function showError(message, title = 'Oops...') {
    Swal.fire({
        icon: 'error',
        title: title,
        text: message,
        confirmButtonText: 'OK',
        confirmButtonColor: '#EF4444'
    });
}

/**
 * Show warning alert
 */
function showWarning(message, title = 'Warning!') {
    Swal.fire({
        icon: 'warning',
        title: title,
        text: message,
        confirmButtonText: 'OK',
        confirmButtonColor: '#F59E0B'
    });
}

/**
 * Show info alert
 */
function showInfo(message, title = 'Info') {
    Swal.fire({
        icon: 'info',
        title: title,
        text: message,
        confirmButtonText: 'OK',
        confirmButtonColor: '#3B82F6'
    });
}

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    Toast.fire({
        icon: type,
        title: message
    });
}

/**
 * Show confirmation dialog
 */
function showConfirm(message, title = 'Are you sure?', confirmText = 'Yes', cancelText = 'Cancel') {
    return Swal.fire({
        title: title,
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3B82F6',
        cancelButtonColor: '#6B7280',
        confirmButtonText: confirmText,
        cancelButtonText: cancelText
    });
}

/**
 * Show delete confirmation
 */
function showDeleteConfirm(message = 'You won\'t be able to revert this!') {
    return Swal.fire({
        title: 'Are you sure?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    });
}

/**
 * Show loading alert
 */
function showLoading(message = 'Please wait...') {
    Swal.fire({
        title: message,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * Close loading alert
 */
function closeLoading() {
    Swal.close();
}

/**
 * Show custom alert with HTML content
 */
function showCustomAlert(title, htmlContent, icon = 'info') {
    Swal.fire({
        title: title,
        html: htmlContent,
        icon: icon,
        confirmButtonColor: '#3B82F6'
    });
}

/**
 * Auto-dismiss success message
 */
function showAutoSuccess(message) {
    Swal.fire({
        position: 'center',
        icon: 'success',
        title: message,
        showConfirmButton: false,
        timer: 1500
    });
}

/**
 * Input prompt
 */
function showInputPrompt(title, inputType = 'text', inputPlaceholder = '') {
    return Swal.fire({
        title: title,
        input: inputType,
        inputPlaceholder: inputPlaceholder,
        showCancelButton: true,
        confirmButtonColor: '#3B82F6',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Submit',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (!value) {
                return 'You need to write something!'
            }
        }
    });
}
