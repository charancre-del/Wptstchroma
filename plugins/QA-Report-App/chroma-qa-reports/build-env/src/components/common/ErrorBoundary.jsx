import React from 'react';
import { AlertTriangle, RefreshCcw } from 'lucide-react';

class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }

    componentDidCatch(error, errorInfo) {
        console.error('CQA React Error:', error, errorInfo);
    }

    handleReload = () => {
        window.location.reload();
    };

    render() {
        if (this.state.hasError) {
            return (
                <div className="flex h-screen items-center justify-center bg-brand-cream p-4">
                    <div className="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 border border-brand-ink/5 text-center">
                        <div className="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center text-red-600 mb-6">
                            <AlertTriangle size={32} />
                        </div>
                        <h2 className="text-2xl font-serif font-bold text-brand-ink mb-3">
                            Something went wrong
                        </h2>
                        <p className="text-brand-ink/60 mb-8">
                            We encountered an unexpected error. The application has been stopped to prevent data loss.
                        </p>

                        {this.state.error && (
                            <div className="bg-red-50 p-3 rounded-lg text-left mb-6 overflow-auto max-h-32 text-xs font-mono text-red-800 border border-red-100">
                                {this.state.error.toString()}
                            </div>
                        )}

                        <button
                            onClick={this.handleReload}
                            className="w-full flex items-center justify-center gap-2 px-6 py-3 bg-brand-ink text-white rounded-full hover:bg-brand-ink/80 transition-all font-bold"
                        >
                            <RefreshCcw size={18} />
                            Reload Application
                        </button>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}

export default ErrorBoundary;
