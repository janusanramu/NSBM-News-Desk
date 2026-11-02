const registerForm = document.getElementById("registerForm");
const message = document.getElementById("message");

registerForm.addEventListener("submit", function(event) {
    event.preventDefault();

    const fullName = document.getElementById("fullName").value.trim();
    const email = document.getElementById("email").value.trim();
    const adminId = document.getElementById("adminId").value.trim();
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    const terms = document.getElementById("terms").checked;

    if (password.length < 8) {
        message.style.color = "red";
        message.textContent = "Password must contain at least 8 characters.";
        return;
    }

    if (password !== confirmPassword) {
        message.style.color = "red";
        message.textContent = "Passwords do not match!";
        return;
    }

    if (!terms) {
        message.style.color = "red";
        message.textContent = "Please accept the terms and conditions.";
        return;
    }
    const submitButton = registerForm.querySelector("button[type='submit']");
    submitButton.disabled = true;

    fetch("register.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ fullName, email, adminId, password, confirmPassword, terms })
    })
        .then(async (response) => {
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || "Registration failed.");
            message.style.color = "#047857";
            message.textContent = result.message;
            registerForm.reset();
            window.location.href = "../index.php";
        })
        .catch((error) => {
            message.style.color = "red";
            message.textContent = error.message;
        })
        .finally(() => {
            submitButton.disabled = false;
        });
});