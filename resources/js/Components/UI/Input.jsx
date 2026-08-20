export default function Input({
    label,
    type = 'text',
    value,
    onChange,
    error,
    className = '',
    required = false,
    ...props
}) {
    return (
        <div className="w-full">
            {label && (
                <label className="block text-sm font-medium text-gray-700 mb-1.5">
                    {label}
                    {required && <span className="text-red-500 ml-1">*</span>}
                </label>
            )}
            <input
                type={type}
                value={value}
                onChange={onChange}
                className={`
                    block w-full px-3 py-2.5 
                    text-gray-900 placeholder-gray-400
                    border rounded-lg
                    transition-colors duration-200
                    focus:outline-none focus:ring-2 focus:ring-offset-0
                    ${error
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                        : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                    }
                    ${className}
                `}
                {...props}
            />
            {error && (
                <p className="mt-1.5 text-sm text-red-600">{error}</p>
            )}
        </div>
    );
}
