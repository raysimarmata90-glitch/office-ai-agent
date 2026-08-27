@extends('layouts.app')

@section('title', 'Project Chat - Structured Flow')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Project Tracking Chat</h1>
                    <p class="mt-1 text-sm text-gray-500">Flow terstruktur untuk tracking progress pekerjaan</p>
                </div>
                <button onclick="resetSession()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Reset Chat
                </button>
            </div>
        </div>

        <!-- Progress Steps -->
        <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-gray-600" id="step-label">Step 1 of 6</span>
                        <span class="text-xs font-medium text-gray-600" id="step-name">Select Client</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 16.67%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Container -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <!-- Messages -->
            <div id="chat-messages" class="h-96 overflow-y-auto p-6 space-y-4">
                <!-- Initial message will be loaded here -->
            </div>

            <!-- Input Area -->
            <div class="border-t border-gray-200 p-4">
                <form id="chat-form" class="flex gap-3">
                    <!-- Options Container (for select type) -->
                    <div id="options-container" class="hidden w-full"></div>
                    
                    <!-- Text Input Container (for text type) -->
                    <div id="text-input-container" class="hidden flex-1 flex gap-3">
                        <input 
                            type="text" 
                            id="message-input"
                            class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Ketik jawaban Anda..."
                        />
                        <button 
                            type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Session Info -->
        <div id="session-info" class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4 hidden">
            <h3 class="text-sm font-semibold text-blue-900 mb-2">Session Summary</h3>
            <div id="session-summary" class="text-sm text-blue-800 space-y-1"></div>
        </div>
    </div>
</div>

<script>
let currentSessionId = null;
let currentStep = 'select_client';
let currentQuestionType = 'select';

// Initialize chat
document.addEventListener('DOMContentLoaded', function() {
    initSession();
});

// Initialize session
async function initSession() {
    try {
        const response = await fetch('/project-chat/init', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();
        
        if (data.success) {
            currentSessionId = data.session_id;
            currentStep = data.current_step;
            currentQuestionType = data.question_type;
            
            // Display AI message
            addMessage('ai', data.message);
            
            // Display input based on question type
            displayInput(data.question_type, data.options);
            
            // Update progress
            updateProgress(data.current_step);
        }
    } catch (error) {
        console.error('Error initializing session:', error);
        addMessage('ai', 'Maaf, terjadi kesalahan. Silakan refresh halaman.');
    }
}

// Send message
document.getElementById('chat-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    let message = '';
    
    if (currentQuestionType === 'select') {
        const selectedOption = document.querySelector('input[name="option"]:checked');
        if (!selectedOption) {
            alert('Silakan pilih salah satu opsi');
            return;
        }
        message = selectedOption.value;
    } else {
        message = document.getElementById('message-input').value.trim();
        if (!message) {
            alert('Silakan masukkan jawaban Anda');
            return;
        }
    }
    
    // Display user message
    addMessage('user', message);
    
    // Clear input
    if (currentQuestionType === 'text') {
        document.getElementById('message-input').value = '';
    }
    
    // Send to server
    try {
        const response = await fetch('/project-chat/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                message: message,
                session_id: currentSessionId
            })
        });

        const data = await response.json();
        
        if (data.success) {
            // Display AI response
            addMessage('ai', data.message);
            
            if (data.session_completed) {
                // Show summary
                displaySummary(data.summary);
                // Hide input
                document.getElementById('options-container').classList.add('hidden');
                document.getElementById('text-input-container').classList.add('hidden');
            } else {
                // Update current state
                currentStep = data.current_step;
                currentQuestionType = data.question_type;
                
                // Display new input
                displayInput(data.question_type, data.options);
                
                // Update progress
                updateProgress(data.current_step);
            }
        } else {
            addMessage('ai', data.message || 'Terjadi kesalahan. Silakan coba lagi.');
            
            // Re-display options if validation failed
            if (data.options) {
                displayInput(currentQuestionType, data.options);
            }
        }
    } catch (error) {
        console.error('Error sending message:', error);
        addMessage('ai', 'Maaf, terjadi kesalahan. Silakan coba lagi.');
    }
});

// Add message to chat
function addMessage(sender, content) {
    const messagesContainer = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `flex ${sender === 'user' ? 'justify-end' : 'justify-start'}`;
    
    const bubble = document.createElement('div');
    bubble.className = `max-w-3/4 rounded-lg px-4 py-3 ${
        sender === 'user' 
            ? 'bg-blue-600 text-white' 
            : 'bg-gray-100 text-gray-900'
    }`;
    
    // Parse markdown-style bold
    const formattedContent = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    bubble.innerHTML = formattedContent;
    
    messageDiv.appendChild(bubble);
    messagesContainer.appendChild(messageDiv);
    
    // Scroll to bottom
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Display input based on question type
function displayInput(type, options = []) {
    const optionsContainer = document.getElementById('options-container');
    const textInputContainer = document.getElementById('text-input-container');
    
    if (type === 'select' && options && options.length > 0) {
        // Show options as radio buttons
        optionsContainer.classList.remove('hidden');
        textInputContainer.classList.add('hidden');
        
        let html = '<div class="space-y-2">';
        options.forEach((option, index) => {
            const value = typeof option === 'object' ? option.value : option;
            const label = typeof option === 'object' ? option.label : option;
            const category = typeof option === 'object' ? option.category_label : null;
            
            html += `
                <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                    <input type="radio" name="option" value="${value}" class="mr-3 text-blue-600 focus:ring-blue-500">
                    <div class="flex-1">
                        ${category ? `<span class="text-xs text-gray-500 block">${category}</span>` : ''}
                        <span class="text-sm">${label}</span>
                    </div>
                </label>
            `;
        });
        html += '</div>';
        html += '<button type="submit" class="w-full mt-3 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Lanjutkan</button>';
        
        optionsContainer.innerHTML = html;
    } else {
        // Show text input
        optionsContainer.classList.add('hidden');
        textInputContainer.classList.remove('hidden');
    }
}

// Update progress bar
function updateProgress(step) {
    const steps = {
        'select_client': { num: 1, total: 6, name: 'Select Client', percent: 16.67 },
        'select_deliverable': { num: 2, total: 6, name: 'Select Deliverable', percent: 33.33 },
        'objective_as_is': { num: 3, total: 6, name: 'Objective As-Is', percent: 50 },
        'timeline_validation': { num: 4, total: 6, name: 'Timeline', percent: 66.67 },
        'task_inquiry': { num: 5, total: 6, name: 'More Tasks?', percent: 83.33 },
        'percentage_allocation': { num: 6, total: 6, name: 'Percentage', percent: 100 },
        'completed': { num: 6, total: 6, name: 'Completed', percent: 100 }
    };
    
    const stepInfo = steps[step] || steps['select_client'];
    
    document.getElementById('step-label').textContent = `Step ${stepInfo.num} of ${stepInfo.total}`;
    document.getElementById('step-name').textContent = stepInfo.name;
    document.getElementById('progress-bar').style.width = `${stepInfo.percent}%`;
}

// Display session summary
function displaySummary(summary) {
    const sessionInfo = document.getElementById('session-info');
    const sessionSummary = document.getElementById('session-summary');
    
    let html = '';
    html += `<p><strong>Client:</strong> ${summary.client || '-'}</p>`;
    html += `<p><strong>Project:</strong> ${summary.project || '-'}</p>`;
    html += `<p><strong>Deliverable:</strong> ${summary.deliverable || '-'} <span class="text-xs">(${summary.deliverable_category})</span></p>`;
    html += `<p><strong>Objective:</strong> ${summary.objective || '-'}</p>`;
    html += `<p><strong>Estimasi:</strong> ${summary.estimated_days} hari</p>`;
    html += `<p><strong>Progress:</strong> ${summary.completion_percentage}%</p>`;
    
    sessionSummary.innerHTML = html;
    sessionInfo.classList.remove('hidden');
}

// Reset session
async function resetSession() {
    if (!confirm('Apakah Anda yakin ingin memulai session baru?')) {
        return;
    }
    
    try {
        await fetch('/project-chat/reset', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        // Clear messages
        document.getElementById('chat-messages').innerHTML = '';
        
        // Hide summary
        document.getElementById('session-info').classList.add('hidden');
        
        // Reinitialize
        initSession();
    } catch (error) {
        console.error('Error resetting session:', error);
        alert('Gagal reset session. Silakan refresh halaman.');
    }
}
</script>
@endsection
