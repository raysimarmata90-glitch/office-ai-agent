import React from 'react';

/**
 * QuickReplyTemplates Component
 * Template jawaban cepat untuk pertanyaan umum
 * 
 * @param {Object} props
 * @param {Function} props.onSelect - Callback ketika template dipilih
 */
export default function QuickReplyTemplates({ onSelect }) {
    const templates = [
        { icon: '👍', text: 'Ya', color: 'green' },
        { icon: '👎', text: 'Tidak', color: 'red' },
        { icon: '✅', text: 'Sudah sesuai', color: 'green' },
        { icon: '⏭️', text: 'Skip', color: 'gray' },
        { icon: '📝', text: 'Lainnya', color: 'blue' },
    ];

    return (
        <div className="flex flex-wrap gap-2">
            {templates.map((template, index) => (
                <button
                    key={index}
                    type="button"
                    onClick={() => onSelect(template.text)}
                    className={`
                        inline-flex items-center gap-2 px-3 py-2 rounded-full text-sm
                        border-2 transition-all
                        ${template.color === 'green' ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' : ''}
                        ${template.color === 'red' ? 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100' : ''}
                        ${template.color === 'gray' ? 'border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100' : ''}
                        ${template.color === 'blue' ? 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100' : ''}
                        focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
                    `}
                >
                    <span>{template.icon}</span>
                    <span>{template.text}</span>
                </button>
            ))}
        </div>
    );
}
