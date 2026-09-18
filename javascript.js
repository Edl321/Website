document.addEventListener("DOMContentLoaded", function () {

    const links = document.querySelectorAll('a[href^="#"]');

    links.forEach(function (link) {

        link.addEventListener("click", function (event) {

            const targetId = this.getAttribute("href");
            const target = document.querySelector(targetId);

            if (target) {
                event.preventDefault();

                target.scrollIntoView({
                    behavior: "smooth"
                });
            }

        });

    });

});
document.addEventListener("DOMContentLoaded", function () {
    
});

/* =========================================================
   PROFILE PAGE — PASSWORD CHANGE VALIDATION
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const passwordForm = document.getElementById("passwordForm");

    if (!passwordForm) {
        return; // not on the profile page
    }

    passwordForm.addEventListener("submit", function (event) {

        const current = document.getElementById("current_password").value.trim();
        const newPw   = document.getElementById("new_password").value.trim();
        const confirm = document.getElementById("confirm_password").value.trim();

        // Missing current password
        if (current === "") {
            event.preventDefault();
            alert("Please enter your current password to change your password.");
            document.getElementById("current_password").focus();
            return false;
        }

        // Missing new password
        if (newPw === "") {
            event.preventDefault();
            alert("Please enter a new password.");
            document.getElementById("new_password").focus();
            return false;
        }

        // Too short
        if (newPw.length < 8) {
            event.preventDefault();
            alert("Your new password must be at least 8 characters.");
            document.getElementById("new_password").focus();
            return false;
        }

        // New equals current
        if (newPw === current) {
            event.preventDefault();
            alert("Your new password must be different from your current password.");
            document.getElementById("new_password").focus();
            return false;
        }

        // Confirmation mismatch
        if (newPw !== confirm) {
            event.preventDefault();
            alert("Your new passwords do not match. Please try again.");
            document.getElementById("confirm_password").focus();
            return false;
        }

        return true;
    });

});