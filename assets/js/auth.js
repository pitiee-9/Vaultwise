function showSignIn() {
    document.getElementById('signup-form').classList.add('switch-form');
    document.getElementById('signin-form').classList.remove('switch-form');
}

function showSignUp() {
    document.getElementById('signin-form').classList.add('switch-form');
    document.getElementById('signup-form').classList.remove('switch-form');
}

// Check URL hash on page load
window.addEventListener('DOMContentLoaded', () => {
    if (window.location.hash === '#signin') {
        showSignIn();
    } else {
        showSignUp(); // default
    }
});


// Form switching functionality
        function showSignIn() {
            document.getElementById('signup-form').classList.add('switch-form');
            document.getElementById('signin-form').classList.remove('switch-form');
        }
        
        function showSignUp() {
            document.getElementById('signin-form').classList.add('switch-form');
            document.getElementById('signup-form').classList.remove('switch-form');
        }
        
        // Form submission handling
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Sign up successful! Redirecting to dashboard...');
            // In a real app, you would handle form submission to a server
        });
        
        document.getElementById('signinForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Sign in successful! Redirecting to dashboard...');
            // In a real app, you would handle form submission to a server
        });
        
        // Add focus effects to inputs
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-3px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'none';
            });
        });