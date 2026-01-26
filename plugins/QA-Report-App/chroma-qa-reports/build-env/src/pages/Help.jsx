import React from 'react';
import { HelpCircle, Book, MessageCircle, ExternalLink } from 'lucide-react';

export function Help() {
    return (
        <div className="p-6 max-w-7xl mx-auto">
            <div className="mb-6">
                <h1 className="text-2xl font-bold text-gray-900">Help & Guide</h1>
                <p className="text-gray-600">Learn how to use the QA Reports system</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="bg-white rounded-xl border border-gray-200 p-6">
                    <Book className="w-10 h-10 text-primary-600 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">Getting Started</h3>
                    <p className="text-gray-500 mb-4">Learn the basics of creating and managing QA reports.</p>
                    <a href="#" className="text-primary-600 hover:text-primary-700 flex items-center gap-1">
                        View Guide <ExternalLink className="w-4 h-4" />
                    </a>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6">
                    <HelpCircle className="w-10 h-10 text-primary-600 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">FAQs</h3>
                    <p className="text-gray-500 mb-4">Find answers to commonly asked questions.</p>
                    <a href="#" className="text-primary-600 hover:text-primary-700 flex items-center gap-1">
                        Browse FAQs <ExternalLink className="w-4 h-4" />
                    </a>
                </div>

                <div className="bg-white rounded-xl border border-gray-200 p-6">
                    <MessageCircle className="w-10 h-10 text-primary-600 mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 mb-2">Contact Support</h3>
                    <p className="text-gray-500 mb-4">Need help? Reach out to our support team.</p>
                    <a href="mailto:support@chromaschools.com" className="text-primary-600 hover:text-primary-700 flex items-center gap-1">
                        Email Support <ExternalLink className="w-4 h-4" />
                    </a>
                </div>
            </div>
        </div>
    );
}

export default Help;
