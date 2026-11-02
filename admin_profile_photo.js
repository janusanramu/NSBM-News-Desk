document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('profile_photo');
    const preview = document.getElementById('profilePhotoPreview');
    const fileName = document.getElementById('photoFileName');
    const feedback = document.getElementById('photoUploadFeedback');

    if (!input || !preview) return;

    let previewUrl = null;
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    const maxFileSize = 2 * 1024 * 1024;

    input.addEventListener('change', function () {
        const file = input.files && input.files[0];
        if (!file) return;

        if (!allowedTypes.includes(file.type) || file.size > maxFileSize) {
            if (feedback) {
                feedback.textContent = 'Choose a JPEG, PNG, or WebP image no larger than 2 MB.';
            }
            input.value = '';
            if (fileName) fileName.textContent = 'No file selected';
            return;
        }

        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
        if (fileName) fileName.textContent = file.name;
        if (feedback) feedback.textContent = 'Previewing selected photo. Submit the upload form to save it.';
    });
});
