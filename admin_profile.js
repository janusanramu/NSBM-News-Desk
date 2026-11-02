document.addEventListener('DOMContentLoaded', function () {
    const name = document.querySelector('.profile-copy h1');
    const avatar = document.querySelector('.admin-avatar');

    if (name && avatar) {
        const words = name.textContent.trim().split(/\s+/).filter(Boolean);
        const initials = words.length > 1
            ? words[0][0] + words[words.length - 1][0]
            : words[0]?.slice(0, 2);

        if (initials) avatar.textContent = initials.toUpperCase();
    }

    const emailRow = Array.from(document.querySelectorAll('.profile-details div'))
        .find(function (row) {
            return row.querySelector('dt')?.textContent.trim() === 'Email address';
        });
    const email = emailRow?.querySelector('dd')?.textContent.trim();

    if (!email || !navigator.clipboard?.writeText) return;

    const copyButton = document.createElement('button');
    copyButton.type = 'button';
    copyButton.className = 'profile-link';
    copyButton.textContent = 'Copy email';
    copyButton.setAttribute('aria-label', 'Copy administrator email address');

    const feedback = document.createElement('span');
    feedback.className = 'copy-feedback';
    feedback.setAttribute('role', 'status');
    feedback.setAttribute('aria-live', 'polite');

    emailRow.querySelector('dd').append(' ', copyButton, ' ', feedback);

    copyButton.addEventListener('click', async function () {
        copyButton.disabled = true;
        try {
            await navigator.clipboard.writeText(email);
            feedback.textContent = 'Email copied.';
        } catch {
            feedback.textContent = 'Copy failed. Please select and copy the email address.';
        } finally {
            copyButton.disabled = false;
        }
    });
});
