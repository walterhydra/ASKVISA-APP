        // Message Sending function
        async function sendMessage() {
            const file = fileInput.files[0];
            let text = msgInput.value.trim();

            // Check if we have a select selection
            if (currentSelectSelection && text === '') {
                text = currentSelectSelection;
            }

            // Check if we're on a date input
            const dateInput = document.getElementById('dateInput');
            if (dateInput && dateInput.value.trim() !== '') {
                text = dateInput.value.trim();
            }

            if (isProcessing || (!text && !file)) return;

            // Show typing indicator
            showTypingIndicator();

            isProcessing = true;
            msgInput.disabled = true;
            sendBtn.disabled = true;

            const formData = new FormData();
            formData.append('message', text);

            // Add CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            formData.append('csrf_token', csrfToken);

            if (file) formData.append('image', file);

            // Add select selection if available
            if (currentSelectSelection && currentQuestionId) {
                formData.append('select_value', currentSelectSelection);
            }

            // Add user message to UI immediately with file preview
            if (text || file) {
                const userRow = document.createElement('div');
                userRow.className = 'flex flex-row-reverse gap-3 max-w-[85%] ml-auto slide-in';

                let attachmentHtml = '';
                let messageText = text || '';

                // Format select message for display
                if (currentSelectSelection) {
                    if (currentSelectLabel) {
                        messageText = \`Selected: \${currentSelectLabel}\`;
                    } else if (text === currentSelectSelection) {
                        messageText = \`Selected: \${text}\`;
                    }
                }

                if (file) {
                    const isPdf = file.type === "application/pdf";
                    const fileName = file.name;
                    const fileSize = (file.size / 1024).toFixed(2) + ' KB';

                    // Create object URL for preview
                    const objectUrl = URL.createObjectURL(file);

                    if (isPdf) {
                        attachmentHtml = \`
                        <div class="mt-3 text-left">
                            <div class="flex items-center gap-3 p-3 rounded-xl bg-black/40 border border-white/5 cursor-pointer hover:bg-black/60 transition-colors" onclick="openLightbox('\${objectUrl}', true)">
                                <span class="material-symbols-outlined text-primary text-2xl">picture_as_pdf</span>
                                <div>
                                    <h4 class="text-xs font-bold text-white w-48 truncate">\${fileName}</h4>
                                    <p class="text-[10px] text-slate-400">\${fileSize} • Click to view</p>
                                </div>
                            </div>
                        </div>
                    \`;
                    } else {
                        attachmentHtml = \`
                        <div class="mt-3 text-left">
                            <img src="\${objectUrl}" class="rounded-lg max-w-[200px] h-auto border border-white/20 cursor-pointer shadow-sm hover:opacity-90 transition-opacity" onclick="openLightbox(this.src)">
                        </div>
                    \`;
                    }

                    // If no text was entered, show "Uploaded file" as message
                    if (!text) {
                        messageText = isPdf ? "Uploaded PDF document" : "Uploaded image";
                    }
                }

                userRow.innerHTML = \`
                <div class="size-8 rounded-full bg-slate-800 flex items-center justify-center shrink-0 mt-1 border border-white/10 shadow-md">
                    <span class="material-symbols-outlined text-white text-xs">person</span>
                </div>
                <div class="space-y-2 text-right">
                    <div class="bg-primary text-white p-4 rounded-2xl rounded-tr-none shadow-md shadow-primary/20 inline-block text-left">
                        <p class="text-sm">\${escapeHtml(messageText)}</p>
                        \${attachmentHtml}
                    </div>
                    <span class="text-[10px] text-slate-400 px-1 block">\${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                </div>
            \`;
                chat.appendChild(userRow);
            }

            msgInput.value = '';
            clearPreview();
            currentSelectSelection = null;
            currentSelectLabel = null;
            currentQuestionId = null;

            // Deactivate all current selection options and skip buttons
            document.querySelectorAll('.select-option').forEach(option => {
                option.classList.add('disabled');
            });

            document.querySelectorAll('.skip-btn').forEach(btn => {
                btn.classList.add('disabled');
            });

            // Remove any date input
            const datePicker = document.querySelector('.date-picker-container');
            if (datePicker) {
                datePicker.remove();
            }

            // Remove any payment button
            const paymentContainer = document.querySelector('.payment-container');
            if (paymentContainer) {
                paymentContainer.remove();
            }

            chat.scrollTop = chat.scrollHeight;

            try {
                const response = await fetch('?ajax=1', { method: 'POST', body: formData });
                const data = await response.json();

                // Update CSRF token if provided
                if (data.csrf_token) {
                    document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.csrf_token);
                }

                // Hide typing indicator
                hideTypingIndicator();

                // Update progress display
                updateProgressDisplay(data);

                // Store order ID if available
                if (data.order_id) {
                    currentOrderId = data.order_id;
                }

                // Check if we need to show payment button
                // ALSO show if step is 'payment' (when returning from payment page)
                if ((data.show_payment_button && data.payment_amount > 0) || data.step === 'payment') {
                    // Add payment button to the bot response
                    const botRow = document.createElement('div');
                    botRow.className = 'flex gap-3 max-w-[85%] slide-in';

                    const formattedText = data.text.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');

                    botRow.innerHTML = \`
                    <div class="size-8 rounded-full bg-primary flex items-center justify-center shrink-0 mt-1 shadow-lg shadow-primary/20 bg-cover bg-center" style="background-image: url('assets/ask-visa-logo-final\\\\ red.png'); background-color: #1a0f0f;">
                    </div>
                    <div class="space-y-2 w-full">
                        <div class="bg-white dark:bg-primary/5 p-4 rounded-2xl rounded-tl-none border border-slate-200 dark:border-primary/10 shadow-sm glass-effect text-slate-200">
                            <p class="text-sm leading-relaxed">\${formattedText}</p>
                            <div class="bot-message-content"></div>
                        </div>
                        <span class="text-[10px] text-slate-400 px-1 block">\${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                    </div>
                \`;

                    chat.appendChild(botRow);

                    // Add payment button - use stored amount if returning
                    const paymentAmount = data.payment_amount || (data.step === 'payment' ? <?php echo $_SESSION['payment_amount'] ?? 0; ?> : 0);
                    const currency = data.currency || 'INR';

                    if (paymentAmount > 0) {
                        const paymentButton = createPaymentButton(paymentAmount, currency);
                        botRow.querySelector('.bot-message-content').appendChild(paymentButton);

                        // Update input placeholder
                        msgInput.placeholder = "Click the payment button above to proceed";
                        msgInput.disabled = true;
                        sendBtn.disabled = true;
                        chat.scrollTop = chat.scrollHeight;

                        // Return early since we handled this specially
                        isProcessing = false;
                        msgInput.disabled = true;
                        sendBtn.disabled = true;
                        return;
                    }
                }
                // Check if we need to show select dropdown
                else if (data.text && data.text.startsWith('json_select:') && data.show_select_dropdown) {
                    // Extract question ID and message
                    const parts = data.text.split(':');
                    const questionId = parts[1];
                    const actualMessage = parts.slice(2).join(':');

                    // Add bot response to UI
                    const botRow = document.createElement('div');
                    botRow.className = 'flex gap-3 max-w-[85%] slide-in';

                    const formattedText = formatBold(actualMessage);

                    botRow.innerHTML = \`
                    <div class="size-8 rounded-full bg-primary flex items-center justify-center shrink-0 mt-1 shadow-lg shadow-primary/20 bg-cover bg-center" style="background-image: url('assets/ask-visa-logo-final\\\\ red.png'); background-color: #1a0f0f;">
                    </div>
                    <div class="space-y-2 w-full">
                        <div class="bg-white dark:bg-primary/5 p-4 rounded-2xl rounded-tl-none border border-slate-200 dark:border-primary/10 shadow-sm glass-effect text-slate-200">
                            <p class="text-sm leading-relaxed">\${formattedText}</p>
                            <div class="bot-message-content"></div>
                        </div>
                        <span class="text-[10px] text-slate-400 px-1 block">\${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                    </div>
                \`;

                    chat.appendChild(botRow);

                    // Add select dropdown with options
                    if (data.select_options && data.select_options.length > 0) {
                        const selectDropdown = createSelectDropdown(questionId, data.select_options);
                        // Append to chat container directly to separate from bubble
                        selectDropdown.classList.add('bot-options-container');
                        selectDropdown.style.marginLeft = "45px"; // Align with text (avatar width approx)
                        selectDropdown.style.marginBottom = "20px";
                        chat.appendChild(selectDropdown);

                        // Update input placeholder
                        msgInput.placeholder = "Select an option above";
                        msgInput.disabled = true;
                        sendBtn.disabled = true;

                        chat.scrollTop = chat.scrollHeight;
                    }
                } else {
                    // Regular bot response
                    const botRow = document.createElement('div');
                    botRow.className = 'flex gap-3 max-w-[85%] slide-in';

                    const formattedText = formatBold(data.text);

                    botRow.innerHTML = \`
                    <div class="size-8 rounded-full bg-primary flex items-center justify-center shrink-0 mt-1 shadow-lg shadow-primary/20 bg-cover bg-center" style="background-image: url('assets/ask-visa-logo-final\\\\ red.png'); background-color: #1a0f0f;">
                    </div>
                    <div class="space-y-2 w-full">
                        <div class="bg-white dark:bg-primary/5 p-4 rounded-2xl rounded-tl-none border border-slate-200 dark:border-primary/10 shadow-sm glass-effect text-slate-200">
                            <p class="text-sm leading-relaxed">\${formattedText}</p>
                            <div class="bot-message-content"></div>
                        </div>
                        <span class="text-[10px] text-slate-400 px-1 block">\${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                    </div>
                \`;

                    // Add calendar for date questions
                    if (data.show_date_calendar) {
                        // Remove old date inputs' IDs so that getElementById finds the new one
                        document.querySelectorAll('#dateInput').forEach(el => el.removeAttribute('id'));
                        document.querySelectorAll('.date-input-wrapper').forEach(el => el.style.opacity = '0.7');

                        const datePickerContainer = document.createElement('div');
                        datePickerContainer.className = 'date-picker-container mt-3';

                        datePickerContainer.innerHTML = \`
                        <div class="relative glass border-primary/20 rounded-xl max-w-sm flex items-center overflow-hidden">
                            <input type="text" 
                                   class="flex-1 bg-transparent border-none text-white text-sm py-3 px-4 focus:ring-0 outline-none placeholder-slate-500" 
                                   placeholder="YYYY/MM/DD"
                                   id="dateInput"
                                   autocomplete="off">
                            <button class="bg-primary hover:bg-red-600 text-white px-4 py-3 flex items-center justify-center transition-colors shadow-lg" onclick="showCalendar(document.getElementById('dateInput'))">
                                <i class="fas fa-calendar-alt"></i>
                            </button>
                        </div>
                        <div class="text-[10px] text-slate-400 mt-1 pl-1">Click the calendar icon to pick a date</div>
                    \`;

                        botRow.querySelector('.bot-message-content').appendChild(datePickerContainer);

                        // Focus on date input
                        setTimeout(() => {
                            const dateInputEl = document.getElementById('dateInput');
                            if (dateInputEl) {
                                dateInputEl.focus();
                            }
                        }, 100);
                    }

                    // Add Skip Button if optional
                    if (data.show_skip_button) {
                        const skipBtnContainer = document.createElement('div');
                        skipBtnContainer.className = 'mt-3';
                        skipBtnContainer.innerHTML = \`<button class="text-xs bg-white/5 hover:bg-white/10 text-slate-300 border border-white/10 px-4 py-2 rounded-lg transition-colors flex items-center gap-2" onclick="sendSkip()">Skip optional step <i class="fas fa-forward"></i></button>\`;
                        botRow.querySelector('.bot-message-content').appendChild(skipBtnContainer);
                    }

                    chat.appendChild(botRow);

                    // Update file upload button
                    if (data.allow_upload) {
                        attachBtn.classList.remove('disabled');
                        attachBtn.classList.add('active');
                        fileInput.disabled = false;
                    } else {
                        attachBtn.classList.remove('active');
                        attachBtn.classList.add('disabled');
                        fileInput.disabled = true;
                        fileInput.value = "";
                    }
                }

            } catch (error) {
                console.error("Error sending message:", error);

                // Hide typing indicator on error
                hideTypingIndicator();

                // Show error message
                const errorRow = document.createElement('div');
                errorRow.className = 'flex gap-3 max-w-[85%] slide-in';
                errorRow.innerHTML = \`
                <div class="size-8 rounded-full bg-primary flex items-center justify-center shrink-0 mt-1 shadow-lg shadow-primary/20 bg-cover bg-center" style="background-image: url('assets/ask-visa-logo-final\\\\ red.png'); background-color: #1a0f0f;">
                </div>
                <div class="space-y-2 w-full">
                    <div class="bg-red-500/10 p-4 rounded-2xl rounded-tl-none border border-red-500/20 shadow-sm glass-effect text-red-200">
                        <p class="text-sm leading-relaxed">Sorry, an error occurred. Please try again.</p>
                    </div>
                    <span class="text-[10px] text-slate-400 px-1 block">\${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                </div>
            \`;
                chat.appendChild(errorRow);
            } finally {
                isProcessing = false;
                msgInput.disabled = false;
                sendBtn.disabled = false;
                msgInput.placeholder = "Type your response here...";
                msgInput.focus();
                chat.scrollTop = chat.scrollHeight;
            }
        }

        // Format bold text function for JS
        function formatBold(text) {
            return text.replace(/\\*\\*(.*?)\\*\\*/g, '<b>$1</b>');
        }

        function escapeHtml(text) {
            if (!text) return text;
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Theme Toggle Handler
        themeToggle.addEventListener('change', () => {
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
        });

        // Function to create select dropdown
        function createSelectDropdown(questionId, options) {
            const container = document.createElement('div');
            container.className = 'w-full max-w-sm pt-2 select-container flex flex-col gap-2';

            options.forEach(opt => {
                const optionDiv = document.createElement('div');
                optionDiv.className = 'select-option group bg-black/40 hover:bg-black/60 relative p-4 rounded-xl border border-white/5 cursor-pointer overflow-hidden transition-all hover:border-primary/50';

                // Create inner content structure for better styling
                // Check if label contains price/currency for formatting
                let labelText = opt.option_label || opt.label;
                let valueStr = opt.option_value || opt.value;

                // Try to split logic if it matches our standard format "Name - Currency Price"
                // Regex to match: Name - Currency Price
                // e.g. "Tourist Visa - INR 2500"
                const priceMatch = labelText.match(/^(.*?) - (.*?) (.*?)$/);

                if (priceMatch) {
                    optionDiv.innerHTML = \`
                    <div class="flex items-center justify-between">
                        <div class="font-semibold text-white group-hover:text-primary transition-colors text-sm">\${priceMatch[1]}</div>
                        <div class="font-mono text-primary font-bold bg-primary/10 px-2 py-1 rounded text-xs">\${priceMatch[2]} \${priceMatch[3]}</div>
                    </div>
                \`;
                } else {
                    optionDiv.innerHTML = \`<div class="font-semibold text-white group-hover:text-primary transition-colors text-sm">\${labelText}</div>\`;
                }

                optionDiv.dataset.value = valueStr;

                optionDiv.onclick = () => {
                    // Remove selected class from others
