import React, { useState } from 'react';
import { X, Building, MapPin, Award, Loader2 } from 'lucide-react';
import { useCreateSchool } from '@hooks/useQueries';

const AddSchoolModal = ( { isOpen, onClose } ) => {
    const schoolNameId = 'school-name';
    const schoolRegionId = 'school-region';
    const schoolTierId = 'school-tier';

    const [ formData, setFormData ] = useState( {
        name: '',
        region: '',
        tier: '1',
    } );

    const createSchool = useCreateSchool();

    const handleSubmit = async ( e ) => {
        e.preventDefault();

        if ( ! formData.name ) {
            return;
        }

        createSchool.mutate( formData, {
            onSuccess: () => {
                onClose();
                setFormData( { name: '', region: '', tier: '1' } );
            },
        } );
    };

    if ( ! isOpen ) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-fade-in">
            <div className="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-slide-up">
                { /* Header */ }
                <div className="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 className="font-serif font-bold text-xl text-gray-900 flex items-center gap-2">
                        <Building className="text-cqa-primary w-5 h-5" />
                        Add New School
                    </h3>
                    <button
                        onClick={ onClose }
                        className="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-200"
                    >
                        <X size={ 20 } />
                    </button>
                </div>

                <form onSubmit={ handleSubmit } className="p-6 space-y-4">
                    { /* Name */ }
                    <div className="space-y-1">
                        <label
                            htmlFor={ schoolNameId }
                            className="text-sm font-semibold text-gray-700 flex items-center gap-2"
                        >
                            <Building size={ 14 } className="text-gray-400" />
                            School Name <span className="text-red-500">*</span>
                        </label>
                        <input
                            id={ schoolNameId }
                            type="text"
                            required
                            placeholder="e.g. Springfield High School"
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cqa-primary focus:border-cqa-primary outline-none transition-all"
                            value={ formData.name }
                            onChange={ ( e ) => setFormData( { ...formData, name: e.target.value } ) }
                        />
                    </div>

                    { /* Region */ }
                    <div className="space-y-1">
                        <label
                            htmlFor={ schoolRegionId }
                            className="text-sm font-semibold text-gray-700 flex items-center gap-2"
                        >
                            <MapPin size={ 14 } className="text-gray-400" />
                            Region / Location
                        </label>
                        <input
                            id={ schoolRegionId }
                            type="text"
                            placeholder="e.g. Northeast District"
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cqa-primary focus:border-cqa-primary outline-none transition-all"
                            value={ formData.region }
                            onChange={ ( e ) => setFormData( { ...formData, region: e.target.value } ) }
                        />
                    </div>

                    { /* Tier */ }
                    <div className="space-y-1">
                        <label
                            htmlFor={ schoolTierId }
                            className="text-sm font-semibold text-gray-700 flex items-center gap-2"
                        >
                            <Award size={ 14 } className="text-gray-400" />
                            Initial Tier
                        </label>
                        <select
                            id={ schoolTierId }
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cqa-primary focus:border-cqa-primary outline-none bg-white"
                            value={ formData.tier }
                            onChange={ ( e ) => setFormData( { ...formData, tier: e.target.value } ) }
                        >
                            <option value="1">Tier 1 (Standard)</option>
                            <option value="2">Tier 2 (Focus)</option>
                            <option value="3">Tier 3 (Priority)</option>
                        </select>
                    </div>

                    { /* Actions */ }
                    <div className="pt-4 flex justify-end gap-3">
                        <button
                            type="button"
                            onClick={ onClose }
                            className="px-4 py-2 text-gray-600 font-medium hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={ createSchool.isLoading || ! formData.name }
                            className="px-6 py-2 bg-cqa-primary text-white font-bold rounded-lg hover:bg-indigo-700 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed shadow-md hover:shadow-lg"
                        >
                            { createSchool.isLoading ? (
                                <>
                                    <Loader2 className="animate-spin w-4 h-4" />
                                    Creating...
                                </>
                            ) : (
                                'Add School'
                            ) }
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default AddSchoolModal;
