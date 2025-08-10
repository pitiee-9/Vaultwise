// Toggle sidebar
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });
        
        // Mobile menu toggle
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Card type selection
        const cardTypes = document.querySelectorAll('.card-type');
        cardTypes.forEach(card => {
            card.addEventListener('click', function() {
                cardTypes.forEach(c => c.style.borderColor = 'var(--border)');
                this.style.borderColor = 'var(--accent)';
            });
        });
        
        // Animate feature cards on load
        document.addEventListener('DOMContentLoaded', function() {
            const featureCards = document.querySelectorAll('.feature-card');
            featureCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.5s ease';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 200 * index);
            });
            
            // Animate link card section
            const linkCard = document.querySelector('.link-card-section');
            linkCard.style.opacity = '0';
            setTimeout(() => {
                linkCard.style.opacity = '1';
                linkCard.style.transition = 'opacity 0.8s ease';
            }, 600);
        });


        // Payment method switching
        const paymentMethods = document.querySelectorAll('.payment-method');
        const formSections = document.querySelectorAll('.payment-form-section');
        const previewSections = {
            card: document.getElementById('cardVisual'),
            bank: document.getElementById('bankVisual'),
            paypal: document.getElementById('paypalVisual')
        };
        
        paymentMethods.forEach(method => {
            method.addEventListener('click', function() {
                const methodType = this.getAttribute('data-method');
                
                // Update active payment method UI
                paymentMethods.forEach(m => m.classList.remove('active'));
                this.classList.add('active');
                
                // Show the corresponding form section
                formSections.forEach(section => section.classList.remove('active'));
                document.getElementById(`${methodType}FormSection`).classList.add('active');
                
                // Show the corresponding preview section
                Object.values(previewSections).forEach(section => section.style.display = 'none');
                previewSections[methodType].style.display = 'block';
                
                // Update flip button visibility
                document.getElementById('flipCardBtn').style.display = 
                    methodType === 'card' ? 'block' : 'none';
            });
        });
        
        // Bank form interactions
        document.getElementById('bankName').addEventListener('change', function() {
            const bankName = this.options[this.selectedIndex].text;
            document.getElementById('previewBankName').textContent = 
                bankName || 'YOUR BANK';
        });
        
        document.getElementById('accountName').addEventListener('input', function() {
            document.getElementById('previewBankName').textContent = 
                this.value || 'YOUR BANK';
        });
        
        document.getElementById('accountNumber').addEventListener('input', function() {
            const value = this.value.replace(/\D/g, '');
            let masked = '';
            if (value.length > 0) {
                masked = value.slice(-4).padStart(value.length, '•');
            }
            document.getElementById('previewAccountNumber').textContent = 
                masked || '•••• •••• ••••';
        });
        
        // PayPal form interactions
        document.getElementById('paypalEmail').addEventListener('input', function() {
            document.getElementById('previewPaypalEmail').textContent = 
                this.value || 'your.email@example.com';
        });
        
        const paypalConnectionRadios = document.querySelectorAll('input[name="connectionType"]');
        paypalConnectionRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    const connectionText = document.querySelector('.paypal-visual div:last-child');
                    connectionText.textContent = this.value === 'express' 
                        ? 'Express Checkout Enabled' 
                        : 'Billing Agreement Active';
                }
            });
        });
        
        // Link Bank Button
        document.getElementById('linkBankBtn').addEventListener('click', function() {
            const bankName = document.getElementById('bankName').value;
            const accountName = document.getElementById('accountName').value;
            const routingNumber = document.getElementById('routingNumber').value;
            const accountNumber = document.getElementById('accountNumber').value;
            
            if (!bankName || !accountName || !routingNumber || routingNumber.length < 9 || !accountNumber) {
                alert('Please fill all bank details correctly');
                return;
            }
            
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-check"></i> Bank Linked Successfully!';
                this.style.background = 'var(--success)';
                this.style.borderColor = 'var(--success)';
                
                setTimeout(() => {
                    window.location.href = 'dashboard.html';
                }, 2000);
            }, 1500);
        });
        
        // Link PayPal Button
        document.getElementById('linkPayPalBtn').addEventListener('click', function() {
            const email = document.getElementById('paypalEmail').value;
            
            if (!email || !email.includes('@')) {
                alert('Please enter a valid PayPal email address');
                return;
            }
            
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Redirecting...';
            
            setTimeout(() => {
                this.innerHTML = '<i class="fab fa-paypal"></i> Connected Successfully!';
                this.style.background = '#009cde';
                
                setTimeout(() => {
                    window.location.href = 'dashboard.html';
                }, 2000);
            }, 1500);
        });