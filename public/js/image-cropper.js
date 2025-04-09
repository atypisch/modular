document.addEventListener('DOMContentLoaded', function() {
    let croppieInstance = null;
    let profileImageInput = document.getElementById('profile_image');
    let profileImageLabel = profileImageInput.previousElementSibling;
    let profileImage = profileImageLabel.querySelector('img');
    let imageModal = document.getElementById('imageModal');
    let cropButton = document.getElementById('cropButton');
    let cancelButton = document.getElementById('cancelButton');

    if (!imageModal) {
        // Create modal if it doesn't exist
        imageModal = document.createElement('div');
        imageModal.id = 'imageModal';
        imageModal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 hidden';
        
        const modalContent = `
            <div class="relative mx-auto my-8 w-full max-w-2xl bg-white rounded-lg shadow-xl p-6">
                <div id="imageCropper"></div>
                <div class="mt-4 flex justify-end space-x-4">
                    <button id="cancelButton" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancel</button>
                    <button id="cropButton" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Crop</button>
                </div>
            </div>
        `;
        
        imageModal.innerHTML = modalContent;
        document.body.appendChild(imageModal);
        
        cropButton = document.getElementById('cropButton');
        cancelButton = document.getElementById('cancelButton');
    }

    profileImageLabel.addEventListener('click', function() {
        profileImageInput.click();
    });

    profileImageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                showCropper(event.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    function showCropper(imageData) {
        imageModal.classList.remove('hidden');
        
        if (croppieInstance) {
            croppieInstance.destroy();
        }

        croppieInstance = new Croppie(document.getElementById('imageCropper'), {
            viewport: { width: 200, height: 200, type: 'square' },
            boundary: { width: 300, height: 300 },
            enableExif: true,
            enableOrientation: true
        });

        croppieInstance.bind({
            url: imageData
        });

        cropButton.onclick = () => {
            croppieInstance.result({
                type: 'base64',
                size: 'viewport'
            }).then((base64) => {
                profileImage.src = base64;
                imageModal.classList.add('hidden');
            });
        };

        cancelButton.onclick = () => {
            imageModal.classList.add('hidden');
        };

        document.addEventListener('click', (e) => {
            if (e.target === imageModal) {
                imageModal.classList.add('hidden');
            }
        });
    }
});
