import React, { useState, useEffect, useRef } from 'react';
import MessageBubble from './MessageBubble';
import QuickOptions from './QuickOptions';

/**
 * ChatInterface Component
 * Komponen utama untuk interface chat dengan form interaktif
 *
 * @param {Object} props
 * @param {number} props.conversationId - ID conversation
 * @param {Array} props.initialMessages - Pesan awal dari server
 * @param {boolean} props.isActive - Apakah conversation masih aktif
 */
export default function ChatInterface({ conversationId, initialMessages = [], isActive = true }) {
    const [messages, setMessages] = useState(initialMessages.map(message => ({
        ...message,
        options: message.options || message.metadata?.options || [],
        question_type: message.question_type || message.metadata?.question_type,
    })));
    const [isLoading, setIsLoading] = useState(false);
    const [currentQuestion, setCurrentQuestion] = useState('');
    const [currentStep, setCurrentStep] = useState(1);
    const messagesEndRef = useRef(null);

    useEffect(() => {
        scrollToBottom();
        // Update current question from last AI message
        const lastAiMessage = [...messages].reverse().find(m => m.sender_type === 'ai');
        if (lastAiMessage) {
            setCurrentQuestion(lastAiMessage.content);
        }
    }, [messages]);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    const sendMessage = async (content) => {
        if (!content.trim()) return;

        // Add user message immediately
        const userMessage = {
            sender_type: 'user',
            content: content,
            created_at: new Date().toISOString()
        };
        setMessages(prev => [...prev, userMessage]);
        setIsLoading(true);

        try {
            const response = await fetch(`/conversations/${conversationId}/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ message: content }),
            });

            const data = await response.json();

            if (data.success) {
                // Add AI response
                setTimeout(() => {
                    const aiMessage = {
                        sender_type: 'ai',
                        content: data.ai_response.content,
                        options: data.ai_response.options || [],
                        has_options: data.ai_response.has_options || false,
                        question_type: data.ai_response.question_type || data.ai_response.type,
                        created_at: new Date().toISOString()
                    };
                    setMessages(prev => [...prev, aiMessage]);
                    setCurrentQuestion(data.ai_response.content);
                    setCurrentStep(prev => prev + 1);
                    setIsLoading(false);
                }, 500);
            }
        } catch (error) {
            console.error('Error sending message:', error);
            setIsLoading(false);
            alert('Terjadi kesalahan saat mengirim pesan');
        }
    };

    const handleOptionSelect = (option) => {
        sendMessage(option);
    };

    const handleSkip = () => {
        sendMessage('Skip');
    };

    const latestAiMessage = [...messages].reverse().find(message => message.sender_type === 'ai');

    return (
        <div className="h-screen flex flex-col bg-gradient-to-b from-gray-50 to-white">
            {/* Messages Container */}
            <div className="flex-1 overflow-y-auto px-6 py-8 pb-24">
                <div className="max-w-4xl mx-auto space-y-4 mt-12">
                    {messages.map((message, index) => (
                        <MessageBubble
                            key={index}
                            content={message.content}
                            isUser={message.sender_type === 'user'}
                            timestamp={message.created_at}
                        />
                    ))}

                    {/* Loading Indicator */}
                    {isLoading && (
                        <MessageBubble
                            content=""
                            isUser={false}
                            isLoading={true}
                        />
                    )}

                    <div ref={messagesEndRef} />
                </div>
            </div>

            {/* Input Area */}
            {isActive ? (
                <div className="px-6 py-6 pb-8 border-t border-gray-200 bg-white">
                    <QuickOptions
                        question={currentQuestion}
                        options={latestAiMessage?.options || []}
                        questionType={latestAiMessage?.question_type}
                        onSelect={handleOptionSelect}
                        currentStep={currentStep}
                        totalSteps={10}
                        onSkip={handleSkip}
                    />
                </div>
            ) : (
                <div className="px-6 py-6 pb-8">
                    <div className="max-w-4xl mx-auto text-center text-yellow-800 bg-yellow-50 rounded-lg py-4">
                        <svg className="inline-block w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                        </svg>
                        Percakapan ini sudah selesai.
                    </div>
                </div>
            )}
        </div>
    );
}
