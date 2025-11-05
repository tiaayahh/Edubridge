// Form validation and AJAX functionality
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Opportunity filtering
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            filterOpportunities(filter);
        });
    });

    // Certificate preview
    const certificateButtons = document.querySelectorAll('.preview-certificate');
    certificateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const certificateId = this.getAttribute('data-certificate-id');
            previewCertificate(certificateId);
        });
    });
});

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });

    // Email validation
    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            showFieldError(field, 'Please enter a valid email address');
            isValid = false;
        }
    });

    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showFieldError(field, message) {
    clearFieldError(field);
    field.style.borderColor = '#dc3545';
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '0.875rem';
    errorDiv.style.marginTop = '0.25rem';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.style.borderColor = '';
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

function filterOpportunities(filter) {
    const opportunities = document.querySelectorAll('.opportunity-card');
    opportunities.forEach(opportunity => {
        if (filter === 'all' || opportunity.getAttribute('data-type') === filter) {
            opportunity.style.display = 'block';
        } else {
            opportunity.style.display = 'none';
        }
    });
}

function previewCertificate(certificateId) {
    // Simulate certificate preview
    const modal = document.createElement('div');
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.backgroundColor = 'rgba(0,0,0,0.8)';
    modal.style.display = 'flex';
    modal.style.justifyContent = 'center';
    modal.style.alignItems = 'center';
    modal.style.zIndex = '1000';
    
    const certificateContent = `
        <div style="background: white; padding: 2rem; border-radius: 10px; text-align: center;">
            <h2>Certificate of Completion</h2>
            <p>Certificate ID: ${certificateId}</p>
            <p>This is a preview of your certificate</p>
            <button onclick="this.closest('.certificate-modal').remove()" class="btn btn-primary">Close</button>
        </div>
    `;
    
    modal.innerHTML = certificateContent;
    modal.className = 'certificate-modal';
    document.body.appendChild(modal);
}

// AJAX functions for dynamic content
async function applyForOpportunity(opportunityId) {
    try {
        const response = await fetch('includes/apply.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ opportunityId: opportunityId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Application submitted successfully!', 'success');
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        showNotification('Error submitting application', 'error');
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '1000';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Real-time form validation
document.addEventListener('input', function(e) {
    if (e.target.hasAttribute('data-validate')) {
        validateField(e.target);
    }
});

function validateField(field) {
    const value = field.value.trim();
    
    if (field.type === 'email' && value) {
        if (!isValidEmail(value)) {
            showFieldError(field, 'Please enter a valid email address');
        } else {
            clearFieldError(field);
        }
    }
    
    if (field.hasAttribute('minlength')) {
        const minLength = parseInt(field.getAttribute('minlength'));
        if (value.length < minLength) {
            showFieldError(field, `Minimum ${minLength} characters required`);
        } else {
            clearFieldError(field);
        }
    }
}