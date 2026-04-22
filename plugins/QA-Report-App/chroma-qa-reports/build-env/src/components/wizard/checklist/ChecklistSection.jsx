import React from 'react';
import ChecklistItem from '../ChecklistItem';
import { hasChecklistItemValue } from '@utils/checklistResponses';

const getEffectiveItemResponse = ( item, sectionResponses, allResponses ) => {
    const itemKey = item.key || item.id;
    const response = sectionResponses?.[ itemKey ] || {};

    if ( ! item?.shared_with ) {
        return response;
    }

    const sourceResponse = allResponses?.[ item.shared_with.section_key ]?.[ item.shared_with.item_key ] || {};

    return {
        ...response,
        rating: response.rating || sourceResponse.rating || '',
        notes:
            item.entry_mode === 'shared_exact'
                ? response.notes || sourceResponse.notes || ''
                : response.notes || '',
    };
};

const ChecklistSection = ( { section, responses, allResponses, onResponseChange, readOnly = false } ) => {
    // Calculate progress for this section
    const totalItems = section.items.length;
    const answeredItems = section.items.filter(
        ( item ) => hasChecklistItemValue( item, getEffectiveItemResponse( item, responses, allResponses ) )
    ).length;
    const progress = Math.round( ( answeredItems / totalItems ) * 100 );

    return (
        <div className="mb-8 animate-fade-in-up">
            { /* Section Header */ }
            <div className="flex items-center justify-between mb-4 sticky top-0 bg-cqa-brand-cream/90 backdrop-blur z-10 p-3 rounded-xl border border-cqa-brand-ink/10 shadow-sm">
                <h3 className="text-sm font-black text-cqa-brand-ink uppercase tracking-widest flex items-center gap-2">
                    <span className="w-1.5 h-6 bg-cqa-brand-secondary rounded-full"></span>
                    { section.name || section.title }
                    { section.tier === 2 && (
                        <span className="text-[10px] font-bold uppercase tracking-wide bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full">
                            Tier 2
                        </span>
                    ) }
                </h3>
                <div className="flex items-center gap-3">
                    <div className="flex flex-col items-end">
                        <span className="text-[9px] uppercase font-black text-cqa-brand-ink/40 leading-none mb-1">
                            Section Progress
                        </span>
                        <div className="flex items-center gap-2">
                            <div className="w-20 bg-cqa-brand-ink/5 h-1.5 rounded-full overflow-hidden border border-cqa-brand-ink/5">
                                <div
                                    className="bg-cqa-brand-secondary h-full transition-all duration-500 shadow-[0_0_8px_rgba(var(--brand-secondary-rgb),0.5)]"
                                    style={ { width: `${ progress }%` } }
                                ></div>
                            </div>
                            <span className="text-xs font-bold text-cqa-brand-ink/70">
                                { answeredItems }/{ totalItems }
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            { /* Items Grid */ }
            <div className="grid grid-cols-1 gap-6">
                { section.items.map( ( item ) => {
                    const itemKey = item.key || item.id;
                    return (
                        <ChecklistItem
                            key={ itemKey }
                            item={ item }
                            sectionKey={ section.key }
                            response={ responses[ itemKey ] || {} }
                            allResponses={ allResponses }
                            readOnly={ readOnly }
                            onChange={ ( itemId, response ) => onResponseChange( itemId, response, section.key ) }
                        />
                    );
                } ) }
            </div>
        </div>
    );
};

export default ChecklistSection;
