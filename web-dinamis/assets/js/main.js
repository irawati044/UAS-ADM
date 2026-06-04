// Tunggu dokumen selesai dimuat
document.addEventListener('DOMContentLoaded', () => {
    // 1. Auto-close Alert setelah 5 detik
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
            alert.style.opacity = '0';
            alert.style.transform = 'scale(0.95)';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // 2. Cover Image Preview saat mengunggah cover novel
    const coverInput = document.getElementById('cover_image');
    const coverPreview = document.getElementById('cover-preview-img');
    
    if (coverInput && coverPreview) {
        coverInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.addEventListener('load', function() {
                    coverPreview.src = this.result;
                    coverPreview.style.display = 'block';
                });
                reader.readAsDataURL(file);
            } else {
                coverPreview.src = '';
                coverPreview.style.display = 'none';
            }
        });
    }
});
