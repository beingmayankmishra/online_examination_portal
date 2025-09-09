// js/script.js
document.addEventListener('DOMContentLoaded', function() {
    // Login form validation
    const loginForm = document.getElementById('studentLoginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const enrollment = document.getElementById('enrollment').value;
            const dob = document.getElementById('dob').value;
            
            if (!enrollment.trim()) {
                e.preventDefault();
                alert('Please enter your enrollment number');
                return;
            }
            
            if (!dob) {
                e.preventDefault();
                alert('Please select your date of birth');
                return;
            }
        });
    }
    
    // Exam timer functionality
 const timerElement = document.getElementById('examTimer');
    if (timerElement) {
        const timeLimit = parseInt(timerElement.dataset.time); // Seconds
        let timeLeft = timeLimit;
        
        // Save timer state to sessionStorage
        if (!sessionStorage.getItem('examTimeLeft')) {
            sessionStorage.setItem('examTimeLeft', timeLeft);
            sessionStorage.setItem('examStartTime', Date.now());
        } else {
            // Calculate remaining time considering page reloads
            const savedTimeLeft = parseInt(sessionStorage.getItem('examTimeLeft'));
            const savedStartTime = parseInt(sessionStorage.getItem('examStartTime'));
            const elapsedSeconds = Math.floor((Date.now() - savedStartTime) / 1000);
            timeLeft = Math.max(0, savedTimeLeft - elapsedSeconds);
        }
        
        const timerInterval = setInterval(function() {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                sessionStorage.removeItem('examTimeLeft');
                sessionStorage.removeItem('examStartTime');
                document.getElementById('examForm').submit();
                return;
            }
            
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Update sessionStorage every 5 seconds
            if (timeLeft % 5 === 0) {
                sessionStorage.setItem('examTimeLeft', timeLeft);
                sessionStorage.setItem('examStartTime', Date.now());
            }
            
            // Add warning class when time is low
            if (timeLeft < 300) { // 5 minutes
                timerElement.classList.add('warning');
            }
            
            timeLeft--;
        }, 1000);
    }
    
    // Auto-save answers
    const optionInputs = document.querySelectorAll('input[name^="question_"]');
    optionInputs.forEach(input => {
        input.addEventListener('change', function() {
            const questionId = this.name.split('_')[1];
            const selectedOption = this.value;
            
            // Send AJAX request to save the answer
            fetch('save_answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `question_id=${questionId}&selected_option=${selectedOption}`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Failed to save answer');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });
    
    // Tab change detection
    let tabChangeCount = 0;
    let warningModal = document.getElementById('warningModal');
    
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // User switched tabs
            tabChangeCount++;
            
            if (tabChangeCount === 1) {
                // First warning
                if (warningModal) {
                    warningModal.style.display = 'flex';
                    document.getElementById('warningCount').textContent = tabChangeCount;
                }
            } else if (tabChangeCount >= 3) {
                // Auto-submit after 3 warnings
                document.getElementById('examForm').submit();
            } else {
                // Subsequent warnings
                if (warningModal) {
                    warningModal.style.display = 'flex';
                    document.getElementById('warningCount').textContent = tabChangeCount;
                }
            }
        }
    });
    
    // Close warning modal
    const closeWarningBtn = document.getElementById('closeWarning');
    if (closeWarningBtn) {
        closeWarningBtn.addEventListener('click', function() {
            warningModal.style.display = 'none';
        });
    }
    
    // Pagination
    const questionPages = document.querySelectorAll('.question-page');
    const pageButtons = document.querySelectorAll('.page-btn');
    
    if (questionPages.length > 0) {
        // Show first page initially
        showPage(1);
        
        // Add click event to page buttons
        pageButtons.forEach(button => {
            button.addEventListener('click', function() {
                const pageNum = parseInt(this.dataset.page);
                showPage(pageNum);
            });
        });
    }



    // Additional JavaScript for form validation and UI enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Prevent future dates in date of birth field
    const dobField = document.getElementById('dob');
    if (dobField) {
        const today = new Date();
        const maxDate = today.toISOString().split('T')[0];
        dobField.setAttribute('max', maxDate);
    }
    
    // Auto-hide messages after 5 seconds
    const messages = document.querySelectorAll('.error-message, .success-message');
    messages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            message.style.transition = 'opacity 0.5s ease-out';
            setTimeout(() => {
                message.remove();
            }, 500);
        }, 5000);
    });
    
    // Admin navigation active state
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.admin-nav a');
    navLinks.forEach(link => {
        const linkPage = link.getAttribute('href');
        if (linkPage === currentPage || (currentPage === '' && linkPage === 'dashboard.php')) {
            link.classList.add('active');
        }
    });
});
    
    function showPage(pageNum) {
        // Hide all pages
        questionPages.forEach(page => {
            page.style.display = 'none';
        });
        
        // Show selected page
        document.getElementById(`page-${pageNum}`).style.display = 'block';
        
        // Update active page button
        pageButtons.forEach(button => {
            if (parseInt(button.dataset.page) === pageNum) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
    }
});