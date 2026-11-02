document.addEventListener('DOMContentLoaded', function () {
    const photoInput = document.getElementById('profilePhoto');
    const photoPreview = document.getElementById('photoPreview');

    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', function () {
            const file = photoInput.files[0];
            if (!file) return;

            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type) || file.size > 2 * 1024 * 1024) {
                alert('Choose a JPEG, PNG, or WebP image no larger than 2 MB.');
                photoInput.value = '';
                return;
            }

            photoPreview.src = URL.createObjectURL(file);
        });
    }

    const facultySelect = document.getElementById('faculty');
    const degreeSelect = document.getElementById('degree');
    const settings = window.accountSettingsConfig;

    if (facultySelect && degreeSelect && settings) {
        function updateDegrees(selectedDegree) {
            const options = settings.facultyDegrees[facultySelect.value] || [];
            degreeSelect.replaceChildren(new Option('Select your degree programme', ''));

            options.forEach(function (degree) {
                degreeSelect.add(new Option(degree, degree, false, degree === selectedDegree));
            });

            degreeSelect.disabled = options.length === 0;
        }

        updateDegrees(settings.selectedDegree);
        facultySelect.addEventListener('change', function () {
            updateDegrees('');
        });
    }

    const passwordForm = document.querySelector('.settings-form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function (event) {
            const newPassword = document.getElementById('newPassword');
            const confirmPassword = document.getElementById('confirmPassword');
            if (newPassword && confirmPassword && newPassword.value !== confirmPassword.value) {
                event.preventDefault();
                alert('The new password and confirmation do not match.');
                confirmPassword.focus();
            }
        });
    }
});
