// Replace with your actual Flutterwave API keys
        const FLUTTERWAVE_PUBLIC_KEY = "FLWPUBK_TEST-XXXXXXXXXXXXXXXXXXXXXX";
        const FLUTTERWAVE_SECRET_KEY = "FLWSECK_TEST-XXXXXXXXXXXXXXXXXXXXXX";
        const FLUTTERWAVE_ENCRYPTION_KEY = "XXXXXXXXXXXXXXXXXXXXXXXX";
        
        // Payment method switching
        const paymentMethods = document.querySelectorAll('.payment-method');
        const formSections = {
            card: document.getElementById('cardFormSection'),
            bank: document.getElementById('bankFormSection'),
            paypal: document.getElementById('paypalFormSection')
        };
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
                Object.values(formSections).forEach(section => section.classList.remove('active'));
                formSections[methodType].classList.add('active');
                
                // Show the corresponding preview section
                Object.values(previewSections).forEach(section => section.style.display = 'none');
                previewSections[methodType].style.display = 'flex';
                
                // Update flip button visibility
                document.getElementById('flipCardBtn').style.display = 
                    methodType === 'card' ? 'block' : 'none';
            });
        });
        
        // Card flip functionality
        document.getElementById('flipCardBtn').addEventListener('click', function() {
            document.getElementById('cardVisual').classList.toggle('flipped');
        });
        
        // Card form interactions
        document.getElementById('cardName').addEventListener('input', function() {
            const name = this.value || 'CARDHOLDER NAME';
            document.getElementById('previewCardName').textContent = name.toUpperCase();
        });
        
        document.getElementById('cardNumber').addEventListener('input', function() {
            // Format card number
            let value = this.value.replace(/\D/g, '');
            let formatted = '';
            
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) formatted += ' ';
                formatted += value[i];
            }
            
            this.value = formatted;
            document.getElementById('previewCardNumber').textContent = formatted || '#### #### #### ####';
        });
        
        document.getElementById('cardExpiry').addEventListener('input', function() {
            // Format expiration date
            let value = this.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            
            this.value = value;
            document.getElementById('previewCardExpiry').textContent = value || 'MM/YY';
        });
        
        document.getElementById('cardCvv').addEventListener('input', function() {
            const cvv = this.value;
            document.getElementById('previewCardCvv').textContent = cvv ? '•'.repeat(cvv.length) : '***';
        });
        
        document.getElementById('cardCvv').addEventListener('focus', function() {
            if (!document.getElementById('cardVisual').classList.contains('flipped')) {
                document.getElementById('cardVisual').classList.add('flipped');
            }
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
                // Add spaces every 4 characters for display
                masked = masked.replace(/(.{4})/g, '$1 ').trim();
            }
            document.getElementById('previewAccountNumber').textContent = 
                masked || '•••• •••• ••••';
        });
        
        const accountTypeRadios = document.querySelectorAll('input[name="accountType"]');
        accountTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    const accountTypeText = document.querySelector('#bankVisual div:last-child div:last-child');
                    accountTypeText.textContent = `Account Type: ${this.value.charAt(0).toUpperCase() + this.value.slice(1)}`;
                }
            });
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
        
        // Link Card Button with Flutterwave Integration
        document.getElementById('linkCardBtn').addEventListener('click', function() {
            const cardName = document.getElementById('cardName').value;
            const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
            const cardExpiry = document.getElementById('cardExpiry').value;
            const cardCvv = document.getElementById('cardCvv').value;
            const statusElement = document.getElementById('cardStatus');
            
            // Basic validation
            if (!cardName || !cardNumber || cardNumber.length < 16 || !cardExpiry || !cardCvv) {
                showStatus(statusElement, 'Please fill all card details correctly', 'error');
                return;
            }
            
            // Show processing status
            showStatus(statusElement, 'Verifying card with Flutterwave...', 'processing');
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            this.disabled = true;
            
            // Simulate Flutterwave API call (in a real app, this would be a server-side call)
            setTimeout(() => {
                // Simulate API response (success in this case)
                const isSuccess = Math.random() > 0.2; // 80% success rate for demo
                
                if (isSuccess) {
                    showStatus(statusElement, 'Card successfully verified with Flutterwave! Redirecting...', 'success');
                    this.innerHTML = '<i class="fas fa-check"></i> Verified Successfully!';
                    this.style.background = 'var(--success)';
                    this.style.borderColor = 'var(--success)';
                    
                    // Redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'dashboard.html';
                    }, 2000);
                } else {
                    showStatus(statusElement, 'Card verification failed. Please check your details and try again.', 'error');
                    this.innerHTML = '<i class="fas fa-lock"></i> Verify with Flutterwave';
                    this.disabled = false;
                }
            }, 2000);
        });
        
        // Show status message
        function showStatus(element, message, type) {
            element.textContent = message;
            element.className = 'status-message';
            element.classList.add(`status-${type}`);
            element.style.display = 'block';
        }
        
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
        
        // Initialize the page with card as default
        document.addEventListener('DOMContentLoaded', function() {
            // Show card preview by default
            previewSections.card.style.display = 'flex';
            previewSections.bank.style.display = 'none';
            previewSections.paypal.style.display = 'none';
            
            // Initialize form input styles
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 5px 15px rgba(37, 99, 235, 0.2)';
                });
                
                input.addEventListener('blur', function() {
                    this.style.transform = 'none';
                    this.style.boxShadow = 'none';
                });
            });
        });