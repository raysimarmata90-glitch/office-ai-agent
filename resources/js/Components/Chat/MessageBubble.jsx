import React from 'react';

/**
 * MessageBubble Component
 * Menampilkan bubble pesan dari user atau AI
 * 
 * @param {Object} props
 * @param {string} props.content - Konten pesan
 * @param {boolean} props.isUser - Apakah pesan dari user
 * @param {string} props.timestamp - Waktu pesan
 * @param {boolean} props.isLoading - Apakah sedang loading (untuk AI response)
 */
export default function MessageBubble({ content, isUser, timestamp, isLoading = false }) {
    const formatTime = (time) => {
        if (!time) {
            const now = new Date();
            return now.getHours().toString().padStart(2, '0') + ':' +
                now.getMinutes().toString().padStart(2, '0');
        }
        return time;
    };

    return (
        <div
            className={`flex ${isUser ? 'justify-end' : 'justify-start'} animate-fade-in-up`}
            style={{ animation: 'fadeInUp 0.4s ease-out' }}
        >
            <div className={`flex items-start space-x-2 max-w-xl ${isUser ? 'flex-row-reverse space-x-reverse' : ''}`}>
                {/* Avatar */}
                <div className={`w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 ${isUser ? 'bg-indigo-600' : 'bg-gray-300'
                    }`}>
                    {isUser ? (
                        <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                        </svg>
                    ) : (
                        <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z" />
                            <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z" />
                        </svg>
                    )}
                </div>

                {/* Message Content */}
                <div className={`${isUser ? 'bg-indigo-600 text-white' : 'bg-white text-gray-900'
                    } rounded-lg px-4 py-3 shadow-sm`}>
                    {isLoading ? (
                        <div className="flex space-x-2 py-1">
                            <div className="w-2 h-2 bg-gray-400 rounded-full animate-pulse"
                                style={{ animationDelay: '0s' }}></div>
                            <div className="w-2 h-2 bg-gray-400 rounded-full animate-pulse"
                                style={{ animationDelay: '0.2s' }}></div>
                            <div className="w-2 h-2 bg-gray-400 rounded-full animate-pulse"
                                style={{ animationDelay: '0.4s' }}></div>
                        </div>
                    ) : (
                        <>
                            <p className="text-sm whitespace-pre-wrap">{content}</p>
                            <p className={`text-xs mt-1 ${isUser ? 'text-indigo-200' : 'text-gray-400'}`}>
                                {formatTime(timestamp)}
                            </p>
                        </>
                    )}
                </div>
            </div>

            <style jsx>{`
                @keyframes fadeInUp {
                    from {
                        opacity: 0;
                        transform: translateY(10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                @keyframes pulse {
                    0%, 100% {
                        opacity: 1;
                    }
                    50% {
                        opacity: 0.5;
                    }
                }

                .animate-pulse {
                    animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
                }
            `}</style>
        </div>
    );
}
