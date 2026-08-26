import React, { useState, useEffect } from 'react';

/**
 * QuickOptions Component
 * Menampilkan pilihan cepat untuk user dengan style mirip Claude Chat
 *
 * @param {Object} props
 * @param {string} props.question - Pertanyaan dari AI
 * @param {Function} props.onSelect - Callback ketika opsi dipilih
 * @param {number} props.currentStep - Step pertanyaan saat ini
 * @param {number} props.totalSteps - Total step pertanyaan
 * @param {Function} props.onSkip - Callback ketika skip diklik
 */
export default function QuickOptions({
    question,
    options: responseOptions = [],
    questionType,
    onSelect,
    currentStep = 1,
    totalSteps = 3,
    onSkip
}) {
    const [showCustomInput, setShowCustomInput] = useState(false);
    const [customValue, setCustomValue] = useState('');
    const [options, setOptions] = useState([]);
    const structuredQuestionTypes = [
        'objective', 'expectation', 'current_task', 'task_detail',
        'task_challenge', 'estimation', 'priority', 'other_project', 'confirmation',
        'project_selection'
    ];

    useEffect(() => {
        const detectedOptions = responseOptions.length > 0
            ? responseOptions
            : getOptionsForQuestion(question);
        setOptions(detectedOptions);
        setShowCustomInput(detectedOptions.length === 0 && !structuredQuestionTypes.includes(questionType));
    }, [question, questionType, responseOptions]);

    const getOptionsForQuestion = (content) => {
        const q = content.toLowerCase();

        // Pertanyaan pembuka proyek
        if (/proyek.*(kerjakan|dikerjakan).*hari ini/.test(q)) {
            return ['Proyek Baru', 'Lanjut Proyek Sebelumnya'];
        }

        // Jenis proposal
        if (/(jenis|tipe).*proposal|proposal.*(jenis|tipe)/.test(q)) {
            return [
                'Business Proposal',
                'Project Proposal',
                'Research Proposal',
                'Event Proposal',
                'Partnership Proposal',
                'Internal Company Proposal'
            ];
        }

        // Tujuan proposal
        if (/(tujuan|dibuat untuk|peruntukan).*proposal/.test(q)) {
            return [
                'Meminta approval',
                'Meminta budget',
                'Meminta resource',
                'Menawarkan solusi',
                'Mengajukan proyek',
                'Mengajukan improvement'
            ];
        }

        // Kompleksitas
        if (/kompleksitas|tingkat kompleks/.test(q)) {
            return ['Simple', 'Medium', 'Complex'];
        }

        // Audience
        if (/primary audience|target audience|audience|pihak.*membaca|target.*pembaca/.test(q)) {
            return [
                'Executive / Direksi',
                'Management / Manager',
                'Client',
                'Sponsor',
                'Investor',
                'Technical Team',
                'Internal Team',
                'Dosen / Akademik'
            ];
        }

        // Kedalaman
        if (/kedalaman|proposal depth|tingkat detail/.test(q)) {
            return ['Executive Level', 'Management Level', 'Operational / Technical Level'];
        }

        // Format output
        if (/format.*(keluaran|output)|format.*proposal/.test(q)) {
            return ['Markdown', 'Microsoft Word', 'PowerPoint', 'PDF'];
        }

        // Konfirmasi
        if (/sudah sesuai|sudah benar|konfirmasi|setuju/.test(q)) {
            return ['Iya', 'Tidak'];
        }

        // Proyek lain
        if (/proyek lain/.test(q)) {
            return ['Ya, ada proyek lain', 'Tidak ada proyek lain'];
        }

        // Prioritas
        if (/(prioritas|prioritas utama)/.test(q)) {
            return ['Ya, ini prioritas utama', 'Bukan prioritas utama'];
        }

        return [];
    };

    const handleOptionClick = (option) => {
        onSelect(option);
    };

    const handleCustomSubmit = (e) => {
        e.preventDefault();
        if (customValue.trim()) {
            onSelect(customValue.trim());
            setCustomValue('');
        }
    };

    if (options.length === 0 && !showCustomInput) {
        return null;
    }

    return (
        <div className="w-full max-w-4xl mx-auto">
            {/* Progress Bar */}
            {options.length > 0 && (
                <div className="mb-3 flex items-center justify-between text-sm text-gray-500">
                    <span>{currentStep} of {totalSteps}</span>
                    <button
                        type="button"
                        onClick={onSkip}
                        className="text-gray-600 hover:text-gray-800 transition-colors"
                    >
                        Skip
                    </button>
                </div>
            )}

            {/* Options */}
            {options.length > 0 && (
                <div className="space-y-2 mb-4">
                    {options.map((option, index) => (
                        <button
                            key={index}
                            type="button"
                            onClick={() => handleOptionClick(option)}
                            className="group relative flex items-start gap-3 w-full rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-left transition hover:border-indigo-500 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            style={{
                                animation: 'fadeInUp 0.3s ease-out',
                                animationDelay: `${index * 0.05}s`,
                                animationFillMode: 'both'
                            }}
                        >
                            <span className="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 text-gray-600 text-sm font-medium group-hover:bg-indigo-100 group-hover:text-indigo-700">
                                {index + 1}
                            </span>
                            <span className="flex-1 text-sm text-gray-800 group-hover:text-indigo-700 pt-0.5">
                                {option}
                            </span>
                            <svg
                                className="flex-shrink-0 w-5 h-5 text-gray-400 opacity-0 group-hover:opacity-100 group-hover:text-indigo-600 transition-opacity"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    ))}

                    {/* Something else option */}
                    {!structuredQuestionTypes.includes(questionType) && <button
                        type="button"
                        onClick={() => setShowCustomInput(true)}
                        className="group relative flex items-center gap-3 w-full rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-left transition hover:border-indigo-500 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        style={{
                            animation: 'fadeInUp 0.3s ease-out',
                            animationDelay: `${options.length * 0.05}s`,
                            animationFillMode: 'both'
                        }}
                    >
                        <svg
                            className="flex-shrink-0 w-5 h-5 text-gray-400 group-hover:text-indigo-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        <span className="flex-1 text-sm text-gray-600 group-hover:text-indigo-700">
                            Something else
                        </span>
                    </button>}
                </div>
            )}

            {/* Custom Input */}
            {showCustomInput && (
                <form onSubmit={handleCustomSubmit} className="flex items-center gap-3">
                    <div className="flex-1 relative">
                        <input
                            type="text"
                            value={customValue}
                            onChange={(e) => setCustomValue(e.target.value)}
                            placeholder="Or reply directly..."
                            className="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-gray-900 placeholder-gray-400"
                            autoFocus
                        />
                        <button
                            type="submit"
                            disabled={!customValue.trim()}
                            className="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-indigo-600 hover:text-indigo-700 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                    </div>
                </form>
            )}

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
            `}</style>
        </div>
    );
}
