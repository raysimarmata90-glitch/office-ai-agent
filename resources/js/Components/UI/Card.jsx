export default function Card({
    children,
    className = '',
    hover = false,
    onClick,
    padding = true,
}) {
    return (
        <div
            className={`
                bg-white rounded-xl border border-gray-200 shadow-sm
                ${hover ? 'transition-all duration-200 hover:shadow-md hover:border-gray-300' : ''}
                ${onClick ? 'cursor-pointer' : ''}
                ${padding ? 'p-6' : ''}
                ${className}
            `}
            onClick={onClick}
        >
            {children}
        </div>
    );
}

// MetricCard Component - Kartu KPI ringkas dengan angka mono bold & icon container
export function MetricCard({ title, value, icon, trend, trendValue, color = 'gray', className = '' }) {
    const colorStyles = {
        gray: 'bg-gray-100 text-gray-700',
        blue: 'bg-blue-100 text-blue-700',
        green: 'bg-green-100 text-green-700',
        emerald: 'bg-emerald-100 text-emerald-700',
        amber: 'bg-amber-100 text-amber-700',
        red: 'bg-red-100 text-red-700',
        indigo: 'bg-indigo-100 text-indigo-700',
    };

    return (
        <div
            className={`bg-white border border-gray-200 shadow-sm rounded-lg p-5 metric-card ${className}`}
        >
            <div className="flex items-start justify-between">
                <div className="flex-1">
                    <p className="text-sm font-medium text-gray-600 mb-1 font-sans">{title}</p>
                    <p className="text-3xl font-bold font-mono text-gray-900">{value}</p>
                    {trend && (
                        <div className="mt-2 flex items-center text-xs font-sans">
                            {trend === 'up' ? (
                                <span className="text-green-600 font-medium">
                                    <i className="fas fa-arrow-up mr-1"></i>
                                    {trendValue}
                                </span>
                            ) : (
                                <span className="text-red-600 font-medium">
                                    <i className="fas fa-arrow-down mr-1"></i>
                                    {trendValue}
                                </span>
                            )}
                        </div>
                    )}
                </div>
                <div className={`w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0 ${colorStyles[color]}`}>
                    <i className={`${icon} text-xl`}></i>
                </div>
            </div>
        </div>
    );
}
