            setTimeout(() => {
                if (!isProcessing) {
                    sendMessage();
                }
            }, 500);
        }

        /* Removed older generic function - Using the styled one below */

        // Create payment button
        function createPaymentButton(amount, currency) {
            const paymentContainer = document.createElement('div');
            paymentContainer.className = 'payment-container';

            paymentContainer.innerHTML = `
            <div class="payment-amount">${currency} ${amount}</div>
            <button class="payment-button" onclick="initiatePayment(${amount}, '${currency}')">
                <i class="fas fa-lock"></i>
                Pay Now with Razorpay
            </button>
            <div class="payment-info">
                Secure payment • All major cards & UPI accepted
            </div>
        `;

            return paymentContainer;
        }

        // Initiate payment
        async function initiatePayment(amount, currency) {
            try {
                // Get CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                // Create order in database first
                const createOrderResponse = await fetch('?ajax=1', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'create_order=1&csrf_token=' + encodeURIComponent(csrfToken)
                });

                const orderData = await createOrderResponse.json();

                if (orderData.success && orderData.redirect_url) {
                    // Redirect to payment page
                    window.location.href = orderData.redirect_url;
                } else {
                    throw new Error(orderData.message || 'Failed to create payment order');
                }
            } catch (error) {
                console.error('Payment initiation error:', error);
                alert('Error initiating payment: ' + error.message);
            }
        }

        // Calendar functionality
        let calendarPopup = null;
        let currentDateInput = null;
        let currentCalendar = {
            year: new Date().getFullYear(),
            month: new Date().getMonth(),
            day: new Date().getDate()
        };

        function createCalendarPopup() {
            const popup = document.createElement('div');
            popup.className = 'calendar-popup';
            popup.id = 'calendarPopup';

            popup.innerHTML = \`
            <div class="calendar" onclick="event.stopPropagation()">
                <div class="calendar-header">
                    <div class="calendar-nav">
                        <select class="calendar-select" id="calendarMonthSelect" onchange="changeCalendarMonthFromSelect()">
                            <option value="0">January</option>
                            <option value="1">February</option>
                            <option value="2">March</option>
                            <option value="3">April</option>
                            <option value="4">May</option>
                            <option value="5">June</option>
                            <option value="6">July</option>
                            <option value="7">August</option>
                            <option value="8">September</option>
                            <option value="9">October</option>
                            <option value="10">November</option>
                            <option value="11">December</option>
                        </select>
                        <select class="calendar-select" id="calendarYearSelect" onchange="changeCalendarYearFromSelect()">
                            \${generateYearOptions()}
                        </select>
                    </div>
                </div>
                <div class="calendar-weekdays">
                    <div class="weekday">Sun</div>
                    <div class="weekday">Mon</div>
                    <div class="weekday">Tue</div>
                    <div class="weekday">Wed</div>
                    <div class="weekday">Thu</div>
                    <div class="weekday">Fri</div>
                    <div class="weekday">Sat</div>
                </div>
                <div class="calendar-days" id="calendarDays"></div>
                <div class="date-format-hint">Format: YYYY/MM/DD</div>
                <div class="calendar-actions">
                    <button class="calendar-btn today" onclick="selectToday()">Today</button>
                    <button class="calendar-btn close" onclick="closeCalendar()">Close</button>
                </div>
            </div>
        \`;

            document.body.appendChild(popup);
            return popup;
        }

        function generateYearOptions() {
            const currentYear = new Date().getFullYear();
            let options = '';

            // Generate years from current year - 100 to current year + 100 (100 years before and after)
            for (let year = currentYear - 100; year <= currentYear + 100; year++) {
                options += \`<option value="\${year}">\${year}</option>\`;
            }

            return options;
        }

        function showCalendar(inputElement) {
            if (!calendarPopup) {
                calendarPopup = createCalendarPopup();
            }

            currentDateInput = inputElement;

            // Parse current date from input if available
            if (inputElement.value) {
                const parts = inputElement.value.split('/');
                if (parts.length === 3) {
                    currentCalendar.year = parseInt(parts[0]);
                    currentCalendar.month = parseInt(parts[1]) - 1;
                    currentCalendar.day = parseInt(parts[2]);
                }
            } else {
                // Set to today
                const today = new Date();
                currentCalendar.year = today.getFullYear();
                currentCalendar.month = today.getMonth();
                currentCalendar.day = today.getDate();
            }

            renderCalendar();
            calendarPopup.style.display = 'flex';
        }

        function closeCalendar() {
            if (calendarPopup) {
                calendarPopup.style.display = 'none';
            }
        }

        function renderCalendar() {
            if (!calendarPopup || !currentCalendar) return;

            const { year, month } = currentCalendar;
            const calendarDays = document.getElementById('calendarDays');
            const monthSelect = document.getElementById('calendarMonthSelect');
            const yearSelect = document.getElementById('calendarYearSelect');

            // Update select values
            if (monthSelect) monthSelect.value = month;
            if (yearSelect) yearSelect.value = year;

            const firstDay = new Date(year, month, 1);
            const startingDay = firstDay.getDay();

            const daysInMonth = new Date(year, month + 1, 0).getDate();

            const today = new Date();
            const todayStr = \`\${today.getFullYear()}/\${(today.getMonth() + 1).toString().padStart(2, '0')}/\${today.getDate().toString().padStart(2, '0')}\`;

            calendarDays.innerHTML = '';

            for (let i = 0; i < startingDay; i++) {
                const emptyDay = document.createElement('button');
                emptyDay.className = 'day empty';
                emptyDay.disabled = true;
                calendarDays.appendChild(emptyDay);
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const dayButton = document.createElement('button');
                dayButton.className = 'day';
                dayButton.textContent = day;

                const dateStr = \`\${year}/\${(month + 1).toString().padStart(2, '0')}/\${day.toString().padStart(2, '0')}\`;

                // Check if this is today
                if (dateStr === todayStr) {
                    dayButton.classList.add('today');
                }

                // Check if this is the selected day
                if (currentCalendar.day === day) {
                    dayButton.classList.add('selected');
                }

                dayButton.onclick = () => selectDate(day, month + 1, year);
                calendarDays.appendChild(dayButton);
            }
        }

        function changeCalendarMonthFromSelect() {
            const monthSelect = document.getElementById('calendarMonthSelect');
            if (monthSelect) {
                currentCalendar.month = parseInt(monthSelect.value);
                renderCalendar();
            }
        }

        function changeCalendarYearFromSelect() {
            const yearSelect = document.getElementById('calendarYearSelect');
            if (yearSelect) {
                currentCalendar.year = parseInt(yearSelect.value);
                renderCalendar();
            }
        }

        function selectDate(day, month, year) {
            const dateStr = \`\${year}/\${month.toString().padStart(2, '0')}/\${day.toString().padStart(2, '0')}\`;

            if (currentDateInput) {
                currentDateInput.value = dateStr;

                // Update calendar state
                currentCalendar.day = day;
                currentCalendar.month = month - 1;
                currentCalendar.year = year;

                const event = new Event('input', { bubbles: true });
                currentDateInput.dispatchEvent(event);

                currentDateInput.focus();
            }

            closeCalendar();
        }

        function selectToday() {
            const today = new Date();
            selectDate(today.getDate(), today.getMonth() + 1, today.getFullYear());
        }

        document.addEventListener('click', (e) => {
            if (calendarPopup && !calendarPopup.contains(e.target) && e.target !== currentDateInput && !e.target.classList.contains('calendar-icon')) {
                closeCalendar();
            }
        });

        function selectOption(value, labelElement) {
            // Update UI
            document.querySelectorAll('.select-option').forEach(el => {
                el.classList.remove('selected');
                const radio = el.querySelector('input[type="radio"]');
                if (radio) radio.checked = false;
            });

            labelElement.classList.add('selected');
            const radio = labelElement.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;

            // Set state variables
            currentSelectSelection = value;
            currentSelectLabel = labelElement.querySelector('.option-text').textContent;

            // Enable send button
            msgInput.value = currentSelectLabel;
            sendBtn.disabled = false;
        }

        // Typing indicator
        function showTypingIndicator() {
            typingIndicator.style.display = 'flex';
            chat.appendChild(typingIndicator);
            chat.scrollTop = chat.scrollHeight;
        }

        function hideTypingIndicator() {
            typingIndicator.style.display = 'none';
        }

        // Update progress display
        function updateProgressDisplay(data) {
            if (pBar && data.progress !== undefined) {
                pBar.style.width = data.progress + '%';
            }

            if (progressPercent && data.progress !== undefined) {
                progressPercent.textContent = data.progress + '%';
            }

            if (stepLabel && data.step_label) {
                stepLabel.textContent = data.step_label;
            }

            if (stepCount && data.step_count) {
                stepCount.textContent = data.step_count;
            }

            if (applicantCount && data.current_person && data.total_people) {
                applicantCount.textContent = \`Applicant \${data.current_person}/\${data.total_people}\`;
            }
        }
