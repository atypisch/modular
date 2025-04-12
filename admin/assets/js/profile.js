document.addEventListener('DOMContentLoaded', function() {
    // Profile image upload
    const profileImageInput = document.getElementById('profile_image');
    const profileImageLabel = document.querySelector('.profile-image-overlay');
    
    if (profileImageInput && profileImageLabel) {
        profileImageLabel.addEventListener('click', () => {
            profileImageInput.click();
        });

        profileImageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.querySelector('.profile-image');
                    if (img) {
                        img.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Password requirements validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const passwordRequirements = document.querySelectorAll('.password-requirements .requirement');
    const passwordMatch = document.querySelector('.password-match');

    const requirements = {
        length: /.{8,}/,
        uppercase: /[A-Z]/,
        lowercase: /[a-z]/,
        number: /[0-9]/,
        special: /[^A-Za-z0-9]/
    };

    function updateRequirement(requirement, isValid) {
        const element = document.querySelector(`[data-requirement="${requirement}"]`);
        const icon = element.querySelector('.icon');
        const text = element.querySelector('.text');
        
        if (isValid) {
            icon.textContent = '✓';
            icon.style.color = '#28a745';
            text.style.color = '#28a745';
        } else {
            icon.textContent = '✗';
            icon.style.color = '#dc3545';
            text.style.color = '#dc3545';
        }
    }

    function checkPasswordMatch() {
        const icon = passwordMatch.querySelector('.icon');
        const text = passwordMatch.querySelector('.text');
        
        if (confirmPassword && confirmPassword.value.length > 0) {
            passwordMatch.style.display = 'block';
            const passwordsMatch = newPassword.value === confirmPassword.value && newPassword.value !== '';

            if (passwordsMatch) {
                icon.textContent = '✓';
                icon.style.color = '#28a745';
                text.style.color = '#28a745';
                text.textContent = 'Passwords match';
            } else {
                icon.textContent = '✗';
                icon.style.color = '#dc3545';
                text.style.color = '#dc3545';
                text.textContent = 'Passwords do not match';
            }

            return passwordsMatch;
        } else {
            passwordMatch.style.display = 'none';
            return false;
        }
    }

    function validatePassword() {
        let allValid = true;
        for (const [requirement, regex] of Object.entries(requirements)) {
            const isValid = regex.test(newPassword.value);
            updateRequirement(requirement, isValid);
            if (!isValid) allValid = false;
        }
        return allValid;
    }

    if (newPassword) {
        newPassword.addEventListener('input', function() {
            validatePassword();
            checkPasswordMatch();
        });
    }

    if (confirmPassword) {
        confirmPassword.addEventListener('input', checkPasswordMatch);
    }
});
