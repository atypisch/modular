// Form Handling Functions

// Form Validation
function validateForm(form) {
    const inputs = form.querySelectorAll('.form-control');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('has-error');
            isValid = false;
        } else {
            input.classList.remove('has-error');
        }
    });

    return isValid;
}

// Form Submission
async function submitForm(form, url, method = 'POST') {
    const formData = new FormData(form);
    const data = {};

    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }

    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('Form Submission Error:', error);
        throw error;
    }
}

// File Upload
async function handleFileUpload(fileInput, url) {
    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('File Upload Error:', error);
        throw error;
    }
}

// Form Reset
function resetForm(form) {
    form.reset();
    form.querySelectorAll('.has-error').forEach(element => {
        element.classList.remove('has-error');
    });
}

// Form Initialization
function initForm(form, submitHandler) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (validateForm(form)) {
            try {
                const result = await submitHandler(form);
                // Handle success
                showAlert('success', 'Form submitted successfully');
                resetForm(form);
            } catch (error) {
                // Handle error
                showAlert('error', error.message);
            }
        }
    });
}

// Export functions
export {
    validateForm,
    submitForm,
    handleFileUpload,
    resetForm,
    initForm
};
