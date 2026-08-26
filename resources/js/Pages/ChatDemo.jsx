import React, { useState } from 'react';
import ChatInterface from '../Components/Chat/ChatInterface';
import ConversationProgress from '../Components/Chat/ConversationProgress';
import QuickReplyTemplates from '../Components/Chat/QuickReplyTemplates';

/**
 * ChatDemo Page
 * Demo page untuk menampilkan semua fitur chat interface
 * 
 * Gunakan page ini sebagai contoh implementasi atau untuk testing
 */
export default function ChatDemo() {
    const [showProgress, setShowProgress] = useState(true);
    const [showQuickReply, setShowQuickReply] = useState(true);

    // Sample data
    const sampleConversation = {
        id: 1,
        status: 'active',
        current_step: 3,
        completed_topics: [1, 2]
    };

    const sampleMessages = [
        {
            id: 1,
            sender_type: 'ai',
            content: 'Halo! Senang bertemu dengan Anda. Apa proyek yang sedang Anda kerjakan hari ini?',
            created_at: '2024-01-01T10:00:00Z'
        },
        {
            id: 2,
            sender_type: 'user',
            content: 'Saya sedang mengerjakan proposal untuk proyek Office AI Agent',
            created_at: '2024-01-01T10:01:00Z'
        },
        {
            id: 3,
            sender_type: 'ai',
            content: 'Bagus! Bisa Anda ceritakan lebih detail tentang objektif dari proyek Office AI Agent ini?',
            created_at: '2024-01-01T10:02:00Z'
        },
        {
            id: 4,
            sender_type: 'user',
            content: 'Objektifnya adalah membuat sistem AI yang dapat membantu karyawan dalam pekerjaan sehari-hari',
            created_at: '2024-01-01T10:03:00Z'
        },
        {
            id: 5,
            sender_type: 'ai',
            content: 'Apa jenis proposal yang ingin Anda buat?',
            created_at: '2024-01-01T10:04:00Z'
        }
    ];

    return (
        <div className="min-h-screen bg-gray-100">
            {/* Header */}
            <header className="bg-white shadow-sm border-b border-gray-200">
                <div className="max-w-7xl mx-auto px-6 py-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">
                                Chat Interface Demo
                            </h1>
                            <p className="text-sm text-gray-600 mt-1">
                                Interactive form-based chat dengan multiple choice dan custom input
                            </p>
                        </div>

                        {/* Toggle Controls */}
                        <div className="flex gap-4">
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={showProgress}
                                    onChange={(e) => setShowProgress(e.target.checked)}
                                    className="rounded text-indigo-600 focus:ring-indigo-500"
                                />
                                <span className="text-sm text-gray-700">Show Progress</span>
                            </label>

                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={showQuickReply}
                                    onChange={(e) => setShowQuickReply(e.target.checked)}
                                    className="rounded text-indigo-600 focus:ring-indigo-500"
                                />
                                <span className="text-sm text-gray-700">Quick Reply</span>
                            </label>
                        </div>
                    </div>
                </div>
            </header>

            {/* Main Content */}
            <div className="max-w-7xl mx-auto px-6 py-6">
                <div className="flex gap-6">
                    {/* Sidebar - Progress */}
                    {showProgress && (
                        <aside className="w-80 flex-shrink-0">
                            <ConversationProgress
                                currentStep={sampleConversation.current_step}
                                totalSteps={5}
                                completedTopics={sampleConversation.completed_topics}
                            />

                            {/* Features List */}
                            <div className="mt-4 bg-white rounded-lg shadow-sm p-4">
                                <h3 className="font-semibold text-gray-900 mb-3">
                                    ✨ Features
                                </h3>
                                <ul className="space-y-2 text-sm text-gray-600">
                                    <li className="flex items-start gap-2">
                                        <span className="text-green-600">✓</span>
                                        <span>Pilihan bernomor</span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <span className="text-green-600">✓</span>
                                        <span>Something else option</span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <span className="text-green-600">✓</span>
                                        <span>Progress indicator</span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <span className="text-green-600">✓</span>
                                        <span>Skip button</span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <span className="text-green-600">✓</span>
                                        <span>Direct reply input</span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <span className="text-green-600">✓</span>
                                        <span>Loading animations</span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <span className="text-green-600">✓</span>
                                        <span>Smooth transitions</span>
                                    </li>
                                    <li className="flex items-start gap-2">
                                        <span className="text-green-600">✓</span>
                                        <span>Auto-scroll to bottom</span>
                                    </li>
                                </ul>
                            </div>

                            {/* Quick Reply Templates */}
                            {showQuickReply && (
                                <div className="mt-4 bg-white rounded-lg shadow-sm p-4">
                                    <h3 className="font-semibold text-gray-900 mb-3">
                                        ⚡ Quick Reply
                                    </h3>
                                    <QuickReplyTemplates
                                        onSelect={(value) => console.log('Quick reply:', value)}
                                    />
                                </div>
                            )}
                        </aside>
                    )}

                    {/* Main Chat Area */}
                    <main className={`flex-1 ${showProgress ? '' : 'max-w-4xl mx-auto'}`}>
                        <div className="bg-white rounded-lg shadow-lg overflow-hidden" style={{ height: 'calc(100vh - 180px)' }}>
                            <ChatInterface
                                conversationId={sampleConversation.id}
                                initialMessages={sampleMessages}
                                isActive={sampleConversation.status === 'active'}
                            />
                        </div>

                        {/* Info Panel */}
                        <div className="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div className="flex items-start gap-3">
                                <svg className="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                </svg>
                                <div className="flex-1">
                                    <h4 className="font-semibold text-blue-900 mb-1">
                                        💡 Tips Penggunaan
                                    </h4>
                                    <ul className="text-sm text-blue-800 space-y-1">
                                        <li>• Klik nomor pilihan untuk langsung menjawab</li>
                                        <li>• Klik "Something else" untuk input custom</li>
                                        <li>• Gunakan tombol "Skip" untuk melewati pertanyaan</li>
                                        <li>• Atau ketik langsung di "Or reply directly..."</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    );
}
