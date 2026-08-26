export default function Badge({
    children,
    variant = 'default',
    size = 'md',
    className = ''
}) {
    const variants = {
        default: 'bg-gray-100 text-gray-800',
        primary: 'bg-indigo-100 text-indigo-800',
        success: 'bg-green-100 text-green-800',
        warning: 'bg-amber-100 text-amber-800',
        danger: 'bg-red-100 text-red-800',
        info: 'bg-blue-100 text-blue-800',
    };

    const sizes = {
        sm: 'px-2 py-0.5 text-xs',
        md: 'px-2.5 py-1 text-sm',
        lg: 'px-3 py-1.5 text-base',
    };

    return (
        <span className={`
            inline-flex items-center font-medium rounded-full
            ${variants[variant]}
            ${sizes[size]}
            ${className}
        `}>
            {children}
        </span>
    );
}

// StatusBadge Component - Pill badge dengan dot indikator berkedip
export function StatusBadge({ status, children, className = '', animated = false }) {
    const statusStyles = {
        planning: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        failed: 'bg-red-50 text-red-700 ring-1 ring-red-200',
        queued: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
        active: 'bg-green-50 text-green-700 ring-1 ring-green-200',
        completed: 'bg-gray-50 text-gray-700 ring-1 ring-gray-200',
    };

    const dotStyles = {
        planning: 'bg-emerald-500',
        failed: 'bg-red-500',
        queued: 'bg-amber-500',
        active: 'bg-green-500',
        completed: 'bg-gray-500',
    };

    return (
        <span
            className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${statusStyles[status] || statusStyles.completed
                } ${animated ? 'animate-pulse' : ''} ${className}`}
        >
            <span className={`inline-block w-2 h-2 rounded-full mr-2 ${dotStyles[status] || dotStyles.completed}`}></span>
            {children}
        </span>
    );
}
