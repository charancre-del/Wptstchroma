import React from 'react';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '@api/client';
import ChecklistSection from '@components/wizard/checklist/ChecklistSection';
import { ListChecks, AlertTriangle } from 'lucide-react';
import { useReportWizardStore } from '@stores/index';

const getLinkedSourceResponse = ( item, allResponses ) => {
    if ( ! item?.shared_with ) {
        return null;
    }

    return allResponses?.[ item.shared_with.section_key ]?.[ item.shared_with.item_key ] || null;
};

const getEffectiveItemResponse = ( item, sectionResponses, allResponses ) => {
    const itemKey = item.key || item.id;
    const response = sectionResponses?.[ itemKey ] || {};
    const sourceResponse = getLinkedSourceResponse( item, allResponses );

    if ( ! sourceResponse ) {
        return response;
    }

    return {
        ...response,
        rating: response.rating || sourceResponse.rating || '',
        notes: item.entry_mode === 'shared_exact' ? response.notes || sourceResponse.notes || '' : response.notes || '',
    };
};

const StepChecklist = ( { draft, readOnly = false } ) => {
    const responses = useReportWizardStore( ( s ) => s.responses );
    const setResponse = useReportWizardStore( ( s ) => s.setResponse );
    const setResponses = useReportWizardStore( ( s ) => s.setResponses );

    // Determine checklist type from draft or default to 'tier1'
    const checklistType = draft.report_type || 'tier1';
    // Fetch Checklist Definition
    const {
        data: checklistDef,
        isLoading,
        error,
    } = useQuery( {
        queryKey: [ 'checklist', checklistType ],
        queryFn: () => apiFetch( `checklists/${ checklistType }` ),
        staleTime: Infinity, // Definitions rarely change
    } );

    const handleResponseChange = React.useCallback(
        ( itemId, newResponse, sectionKey ) => {
            if ( readOnly ) {
                return;
            }
            setResponse( sectionKey, itemId, newResponse );
        },
        [ readOnly, setResponse ]
    );

    React.useEffect( () => {
        if ( readOnly || ! checklistDef?.sections?.length ) {
            return;
        }

        let nextResponses = null;

        checklistDef.sections.forEach( ( section ) => {
            section.items.forEach( ( item ) => {
                if ( ! item.shared_with ) {
                    return;
                }

                const itemKey = item.key || item.id;
                const sourceResponse =
                    responses?.[ item.shared_with.section_key ]?.[ item.shared_with.item_key ] || null;

                if ( ! sourceResponse?.rating ) {
                    return;
                }

                const currentResponse = responses?.[ section.key ]?.[ itemKey ] || {};
                const desiredResponse = {
                    ...currentResponse,
                    rating: sourceResponse.rating,
                };

                if ( item.entry_mode === 'shared_exact' && ! currentResponse.notes && sourceResponse.notes ) {
                    desiredResponse.notes = sourceResponse.notes;
                }

                const notesChanged =
                    item.entry_mode === 'shared_exact' &&
                    ( currentResponse.notes || '' ) !== ( desiredResponse.notes || '' );

                if ( ( currentResponse.rating || '' ) === desiredResponse.rating && ! notesChanged ) {
                    return;
                }

                if ( ! nextResponses ) {
                    nextResponses = { ...responses };
                }

                if ( ! nextResponses[ section.key ] || nextResponses[ section.key ] === responses[ section.key ] ) {
                    nextResponses[ section.key ] = { ...( responses[ section.key ] || {} ) };
                }

                nextResponses[ section.key ][ itemKey ] = desiredResponse;
            } );
        } );

        if ( nextResponses ) {
            setResponses( nextResponses );
        }
    }, [ checklistDef, readOnly, responses, setResponses ] );

    // Calculate Completion
    const completion = React.useMemo( () => {
        if ( ! checklistDef?.sections ) {
            return { total: 0, answered: 0, percent: 0 };
        }

        let total = 0;
        let answered = 0;

        checklistDef.sections.forEach( ( section ) => {
            const sectionResponses = responses[ section.key ] || {};
            total += section.items.length;
            section.items.forEach( ( item ) => {
                const effectiveResponse = getEffectiveItemResponse( item, sectionResponses, responses );
                if ( effectiveResponse?.rating ) {
                    answered++;
                }
            } );
        } );

        return { total, answered, percent: total > 0 ? Math.round( ( answered / total ) * 100 ) : 0 };
    }, [ checklistDef, responses ] );

    if ( isLoading ) {
        return (
            <div className="flex flex-col items-center justify-center py-20 text-gray-500">
                <span className="spinner h-8 w-8 mb-4 border-4 border-cqa-primary border-t-transparent rounded-full animate-spin"></span>
                <p>Loading checklist definition...</p>
            </div>
        );
    }

    if ( error ) {
        return (
            <div className="bg-red-50 p-6 rounded-lg border border-red-200 text-center">
                <AlertTriangle className="mx-auto text-red-500 mb-2" size={ 32 } />
                <h3 className="text-lg font-bold text-red-800">Failed to load checklist</h3>
                <p className="text-red-600 mb-4">Could not retrieve the checklist definition for { checklistType }.</p>
                <button
                    onClick={ () => window.location.reload() }
                    className="px-4 py-2 bg-white border border-red-300 text-red-700 rounded hover:bg-red-50"
                >
                    Retry
                </button>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            { /* Header / Stats */ }
            <div className="bg-white/95 backdrop-blur-sm p-4 rounded-lg border border-gray-200 shadow-sm flex items-center justify-between sticky top-8 z-20">
                <div>
                    <h2 className="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <ListChecks className="text-cqa-primary" />
                        { checklistDef.title || 'Inspection Checklist' }
                    </h2>
                    <p className="text-sm text-gray-500">
                        { completion.answered } of { completion.total } items rated
                    </p>
                </div>
                <div className="text-right">
                    <div className="text-2xl font-bold text-cqa-primary">{ completion.percent }%</div>
                    <div className="text-xs text-gray-400 uppercase tracking-wide">Complete</div>
                </div>
            </div>

            { /* Sections */ }
            <div>
                { checklistDef.sections?.map( ( section ) => (
                    <ChecklistSection
                        key={ section.key || section.id }
                        section={ section }
                        responses={ responses[ section.key ] || {} }
                        allResponses={ responses }
                        readOnly={ readOnly }
                        onResponseChange={ handleResponseChange }
                    />
                ) ) }
            </div>
        </div>
    );
};

export default StepChecklist;
