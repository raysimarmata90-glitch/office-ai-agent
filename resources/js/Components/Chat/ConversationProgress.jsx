import React from 'react';

/**
 * ConversationProgress Component
 * Menampilkan progress conversation dalam bentuk visual
 * 
 * @param {Object} props
 * @param {number} props.currentStep - Step saat ini
 * @param {number} props.totalSteps - Total steps
 * @param {Array} props.completedTopics - Topik yang sudah selesai
 */
export default function ConversationProgress({
    currentStep = 1,
    totalSteps = 5,
    completedTopics = []
}) {
    const progressPercentage = (currentStep / totalSteps) * 100;

    const topics = [
        { id: 1, name: 'Proyek', icon: '📋' },
        { id: 2, name: 'Objektif', icon: '🎯' },
        { id: 3, name: 'Target', icon: '✨' },
        { id: 4, name: 'Task', icon: '✅' },
        { id: 5, name: 'Estimasi', icon: '⏱️' },
    ];

    return (
        <div className="bg-white rounded-lg shadow-sm p-4 mb-4">
            {/* Progress Bar */}
            <div className="mb-4">
                <div className="flex items-center justify-between mb-2">
                    <span className="text-sm font-medium text-gray-700">Progress Conversation</span>
                    <span className="text-sm text-gray-500">{currentStep} of {totalSteps}</span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2">
                    <div
                        className="bg-indigo-600 h-2 rounded-full transition-all duration-500 ease-out"
                        style={{ width: `${progressPercentage}%` }}
                    ></div>
                </div>
            </div>

            {/* Topic Checklist */}
            <div className="space-y-2">
                <h4 className="text-xs font-semibold text-gray-600 uppercase tracking-wide">
                    Topik yang Dibahas
                </h4>
                <div className="grid grid-cols-2 gap-2">
                    {topics.map((topic) => {
                        const isCompleted = completedTopics.includes(topic.id);
                        const isCurrent = topic.id === currentStep;

                        return (
                            <div
                                key={topic.id}
                                className={`
                                    flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-all
                                    ${isCompleted ? 'bg-green-50 border-2 border-green-200' : ''}
                                    ${isCurrent ? 'bg-indigo-50 border-2 border-indigo-300 ring-2 ring-indigo-100' : ''}
                                    ${!isCompleted && !isCurrent ? 'bg-gray-50 border-2 border-gray-200' : ''}
                                `}
                            >
                                <span className="text-lg">{topic.icon}</span>
                                <span className={`
                                    flex-1 font-medium
                                    ${isCompleted ? 'text-green-700' : ''}
                                    ${isCurrent ? 'text-indigo-700' : ''}
                                    ${!isCompleted && !isCurrent ? 'text-gray-500' : ''}
                                `}>
                                    {topic.name}
                                </span>
                                {isCompleted && (
                                    <svg className="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                    </svg>
                                )}
                                {isCurrent && (
                                    <div className="w-2 h-2 bg-indigo-600 rounded-full animate-pulse"></div>
                                )}
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
