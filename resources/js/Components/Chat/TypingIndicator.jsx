import React from 'react';

/**
 * TypingIndicator Component
 * Menampilkan animasi "AI is typing..."
 */
export default function TypingIndicator() {
    return (
        <div className="flex items-center space-x-2 text-gray-500 text-sm">
            <div className="flex space-x-1">
                <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0s' }}></div>
                <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0.2s' }}></div>
                <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0.4s' }}></div>
            </div>
            <span className="text-xs">AI is typing...</span>
        </div>
    );
}
