/**
 * Admin Dashboard Common Functions
 */

// Check authentication on page load
document.addEventListener('DOMContentLoaded', async () => {
    // Check authentication
    try {
        const response = await fetch('../php/admin/auth.php?check=true', { credentials: 'include' });
        const data = await response.json();

        if (!data.authenticated) {
            window.location.href = 'login.html';
        }
    } catch (error) {
        console.error('Auth check failed:', error);
    }

    // Sidebar Toggle Logic
    const toggleBtn = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('active');
            } else {
                sidebar.classList.toggle('collapsed');
            }
        });
    }
});

// Logout function
async function logout() {
    try {
        const response = await fetch('../php/admin/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=logout',
            credentials: 'include'
        });

        const data = await response.json();
        if (data.success) {
            window.location.href = 'login.html';
        }
    } catch (error) {
        console.error('Logout failed:', error);
    }
}

// Show alert message
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 2rem;
        background: ${type === 'success' ? 'rgba(81, 207, 102, 0.9)' : 'rgba(255, 107, 107, 0.9)'};
        color: #fff;
        border-radius: 8px;
        z-index: 2000;
        animation: slideIn 0.3s ease;
    `;
    alertDiv.textContent = message;

    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 300);
    }, 3000);
}

// Preview image before upload
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }

        reader.readAsDataURL(input.files[0]);
    }
}

// Preview video before upload
function previewVideo(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const url = URL.createObjectURL(file);
        preview.src = url;
        preview.style.display = 'block';
        preview.onloadedmetadata = () => {
            // Optional: revoke object URL after some time or on change
        };
    }
}

// Close modals when clicking outside
window.onclick = function (event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}
