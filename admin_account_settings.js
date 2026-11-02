document.addEventListener('DOMContentLoaded', function () {
    const fullName = document.getElementById('full_name');
    const email = document.getElementById('email');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const currentPassword = document.getElementById('current_password');
    const saveButton = document.querySelector('.settings-actions button');
    const feedback = document.querySelector('.settings-feedback');
    const controls = Array.from(document.querySelectorAll(
        '.admin-settings input:not([disabled]), .admin-settings select'
    ));

    if (!fullName || !email || !newPassword || !confirmPassword ||
        !currentPassword || !saveButton || !feedback) {
        return;
    }

    const initialValues = new Map(controls.map(function (control) {
        return [control, control.type === 'checkbox' ? control.checked : control.value];
    }));

    function updateSaveButton() {
        const hasChanges = controls.some(function (control) {
            const value = control.type === 'checkbox' ? control.checked : control.value;
            return value !== initialValues.get(control);
        });
        saveButton.disabled = !hasChanges;
        if (!hasChanges) feedback.textContent = '';
    }

    controls.forEach(function (control) {
        control.addEventListener('input', updateSaveButton);
        control.addEventListener('change', updateSaveButton);
    });

    saveButton.addEventListener('click', function () {
        feedback.textContent = '';

        if (!fullName.reportValidity() || !email.reportValidity()) return;

        const isChangingPassword = Boolean(
            currentPassword.value || newPassword.value || confirmPassword.value
        );

        if (isChangingPassword) {
            if (!currentPassword.value || !newPassword.value || !confirmPassword.value) {
                feedback.textContent = 'Enter your current password, new password, and confirmation to validate a password change.';
                (!currentPassword.value ? currentPassword : !newPassword.value ? newPassword : confirmPassword).focus();
                return;
            }

            if (newPassword.value.length < 12) {
                feedback.textContent = 'The new password must be at least 12 characters.';
                newPassword.focus();
                return;
            }

            if (newPassword.value !== confirmPassword.value) {
                feedback.textContent = 'The new password and confirmation do not match.';
                confirmPassword.focus();
                return;
            }
        }

        feedback.textContent = 'The settings look valid, but this static HTML preview cannot save changes. Use the PHP account settings page.';
    });
});
