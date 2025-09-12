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
            
            // Clear any previous exam data when logging in
            Object.keys(sessionStorage).forEach(key => {
                if (key.startsWith('examTimeLeft_') || key.startsWith('examStartTime_') || 
                    key.startsWith('tabChangeCount_') || key.startsWith('examInitialized_') ||
                    key.startsWith('answer_')) {
                    sessionStorage.removeItem(key);
                }
            });
        });
    }
    
    // Exam timer functionality - COMPLETELY FIXED
    const timerElement = document.getElementById('examTimer');
    if (timerElement) {
        const timeLimit = parseInt(timerElement.dataset.time); 
        const studentId = document.body.dataset.studentId || 'unknown';
        
        // Create user-specific storage keys
        const timeLeftKey = `examTimeLeft_${studentId}`;
        const startTimeKey = `examStartTime_${studentId}`;
        const tabCountKey = `tabChangeCount_${studentId}`;
        const initializedKey = `examInitialized_${studentId}`;
        const currentStudentKey = `currentStudentId`;
        
        // Check if a different student was previously using this browser
        const previousStudentId = sessionStorage.getItem(currentStudentKey);
        if (previousStudentId && previousStudentId !== studentId) {
            // Clear all data from the previous student
            Object.keys(sessionStorage).forEach(key => {
                if (key.startsWith(`examTimeLeft_${previousStudentId}`) || 
                    key.startsWith(`examStartTime_${previousStudentId}`) || 
                    key.startsWith(`tabChangeCount_${previousStudentId}`) || 
                    key.startsWith(`examInitialized_${previousStudentId}`) ||
                    key.startsWith(`answer_${previousStudentId}_`)) {
                    sessionStorage.removeItem(key);
                }
            });
        }
        
        // Store the current student ID
        sessionStorage.setItem(currentStudentKey, studentId);
        
        // Get tab change count from sessionStorage or initialize to 0
        let tabChangeCount = parseInt(sessionStorage.getItem(tabCountKey)) || 0;
        
        // Check if this is the first time loading the exam page for this student
        const isFirstLoad = sessionStorage.getItem(initializedKey) !== 'true';
        
        if (isFirstLoad) {
            // This is the first load for this student, initialize everything fresh
            sessionStorage.setItem(timeLeftKey, timeLimit.toString());
            sessionStorage.setItem(startTimeKey, Date.now().toString());
            sessionStorage.setItem(initializedKey, 'true');
            sessionStorage.setItem(tabCountKey, '0');
            
            // Clear any previous answers for this student
            Object.keys(sessionStorage).forEach(key => {
                if (key.startsWith(`answer_${studentId}_`)) {
                    sessionStorage.removeItem(key);
                }
            });
        }
        
        // Check if we have a valid saved time
        const savedTimeLeft = parseInt(sessionStorage.getItem(timeLeftKey));
        const savedStartTime = parseInt(sessionStorage.getItem(startTimeKey));
        
        let timeLeft;
        
        if (savedTimeLeft !== null && savedStartTime !== null && !isNaN(savedTimeLeft) && !isNaN(savedStartTime)) {
            // Calculate remaining time considering page reloads
            const elapsedSeconds = Math.floor((Date.now() - savedStartTime) / 1000);
            timeLeft = Math.max(0, savedTimeLeft - elapsedSeconds);
        } else {
            // Fallback: Start fresh with the server-allocated time
            timeLeft = timeLimit;
            sessionStorage.setItem(timeLeftKey, timeLeft.toString());
            sessionStorage.setItem(startTimeKey, Date.now().toString());
        }
        
        const timerInterval = setInterval(function() {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                sessionStorage.removeItem(timeLeftKey);
                sessionStorage.removeItem(startTimeKey);
                sessionStorage.removeItem(tabCountKey);
                sessionStorage.removeItem(initializedKey);
                sessionStorage.removeItem(currentStudentKey);
                
                // Clear all answers for this student
                Object.keys(sessionStorage).forEach(key => {
                    if (key.startsWith(`answer_${studentId}_`)) {
                        sessionStorage.removeItem(key);
                    }
                });
                
                document.getElementById('examForm').submit();
                return;
            }
            
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Update sessionStorage every second for accuracy
            sessionStorage.setItem(timeLeftKey, timeLeft.toString());
            sessionStorage.setItem(startTimeKey, Date.now().toString());
            
            // Add warning class when time is low
            if (timeLeft < 300) {
                timerElement.classList.add('warning');
            }
            
            timeLeft--;
        }, 1000);
    }
    
    // Auto-save answers - FIXED to prevent cross-student data leakage
    const optionInputs = document.querySelectorAll('input[name^="question_"]');
    const studentId = document.body.dataset.studentId || 'unknown';
    
    // FIRST: Clear any answers that don't belong to the current student
    Object.keys(sessionStorage).forEach(key => {
        if (key.startsWith('answer_') && !key.startsWith(`answer_${studentId}_`)) {
            sessionStorage.removeItem(key);
        }
    });
    
    // SECOND: Now load answers only for current student
    optionInputs.forEach(input => {
        const questionId = input.name.split('_')[1];
        const answerKey = `answer_${studentId}_${questionId}`;
        const savedAnswer = sessionStorage.getItem(answerKey);
        
        // Only check the answer if it belongs to the current student
        if (savedAnswer && savedAnswer === input.value) {
            input.checked = true;
        }
        
        input.addEventListener('change', function() {
            const questionId = this.name.split('_')[1];
            const selectedOption = this.value;
            const answerKey = `answer_${studentId}_${questionId}`;
            
            // Save answer to sessionStorage with student-specific key
            sessionStorage.setItem(answerKey, selectedOption);
            
            // Send AJAX request to save the answer to server
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
    const studentIdForTab = document.body.dataset.studentId || 'unknown';
    const tabCountKey = `tabChangeCount_${studentIdForTab}`;
    let tabChangeCount = parseInt(sessionStorage.getItem(tabCountKey)) || 0;
    let warningModal = document.getElementById('warningModal');
    
    if (warningModal && tabChangeCount > 0) {
        document.getElementById('warningCount').textContent = tabChangeCount;
    }
    
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            tabChangeCount++;
            sessionStorage.setItem(tabCountKey, tabChangeCount.toString());
            
            if (tabChangeCount === 1) {
                if (warningModal) {
                    warningModal.style.display = 'flex';
                    document.getElementById('warningCount').textContent = tabChangeCount;
                }
            } else if (tabChangeCount >= 3) {
                sessionStorage.removeItem(tabCountKey);
                sessionStorage.removeItem(`examTimeLeft_${studentIdForTab}`);
                sessionStorage.removeItem(`examStartTime_${studentIdForTab}`);
                sessionStorage.removeItem(`examInitialized_${studentIdForTab}`);
                sessionStorage.removeItem(`currentStudentId`);
                
                Object.keys(sessionStorage).forEach(key => {
                    if (key.startsWith(`answer_${studentIdForTab}_`)) {
                        sessionStorage.removeItem(key);
                    }
                });
                
                document.getElementById('examForm').submit();
            } else {
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
            if (warningModal) {
                warningModal.style.display = 'none';
            }
        });
    }
    
    // Pagination
    const questionPages = document.querySelectorAll('.question-page');
    const pageButtons = document.querySelectorAll('.page-btn');
    
    if (questionPages.length > 0) {
        showPage(1);
        
        pageButtons.forEach(button => {
            button.addEventListener('click', function() {
                const pageNum = parseInt(this.dataset.page);
                showPage(pageNum);
            });
        });
    }

    // Additional JavaScript for form validation and UI enhancements
    const dobField = document.getElementById('dob');
    if (dobField) {
        const today = new Date();
        const maxDate = today.toISOString().split('T')[0];
        dobField.setAttribute('max', maxDate);
    }
    
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
    
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.admin-nav a');
    navLinks.forEach(link => {
        const linkPage = link.getAttribute('href');
        if (linkPage === currentPage || (currentPage === '' && linkPage === 'dashboard.php')) {
            link.classList.add('active');
        }
    });
    
    function showPage(pageNum) {
        questionPages.forEach(page => {
            page.style.display = 'none';
        });
        
        const pageElement = document.getElementById(`page-${pageNum}`);
        if (pageElement) {
            pageElement.style.display = 'block';
        }
        
        pageButtons.forEach(button => {
            if (parseInt(button.dataset.page) === pageNum) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
    }
});