import React, { useEffect, useState } from 'react';
import { Save, Lock, Bot, Database, Check, Link2, RefreshCcw, Wrench, AlertCircle, CheckCircle2, Building2 } from 'lucide-react';
import {
    useSettings,
    useUpdateSettings,
    useSchools,
    useUpdateSchool,
    useMondayBoards,
    useMondayWorkspaces,
    useTestMondayConnection,
    useSetupMondayBoard,
} from '@hooks/useQueries';
import useUIStore from '@stores/useUIStore';

const Settings = () => {
    const { addToast } = useUIStore();
    const { data: settings, isLoading } = useSettings();
    const { data: schoolsResponse, isLoading: schoolsLoading } = useSchools( { per_page: 200 } );

    const updateSettingsMutation = useUpdateSettings();
    const updateSchoolMutation = useUpdateSchool();
    const mondayBoardsMutation = useMondayBoards();
    const mondayWorkspacesMutation = useMondayWorkspaces();
    const mondayTestMutation = useTestMondayConnection();
    const mondaySetupMutation = useSetupMondayBoard();

    const [ isSaved, setIsSaved ] = useState( false );
    const [ connectionStatus, setConnectionStatus ] = useState( null ); // { type: 'success'|'error', message }
    const [ formData, setFormData ] = useState( {
        google_client_id: '',
        google_client_secret: '',
        gemini_api_key: '',
        enable_ai: 'yes',
        monday_enabled: 'no',
        monday_api_token: '',
        monday_auto_sync_on_approval: 'yes',
        monday_default_status_label: 'Not Started',
        monday_workspace_id: '',
        monday_workspace_name: '',
    } );
    const [ schoolMappings, setSchoolMappings ] = useState( {} );

    const schools = schoolsResponse?.data || [];
    const boards = mondayBoardsMutation.data?.data || [];
    const workspaces = mondayWorkspacesMutation.data?.data || [];

    useEffect( () => {
        if ( settings ) {
            setFormData( {
                google_client_id: settings.google_client_id || '',
                google_client_secret: settings.google_client_secret || '',
                gemini_api_key: settings.gemini_api_key || '',
                enable_ai: settings.enable_ai || 'yes',
                monday_enabled: settings.monday_enabled || 'no',
                monday_api_token: settings.monday_api_token || '',
                monday_auto_sync_on_approval: settings.monday_auto_sync_on_approval || 'yes',
                monday_default_status_label: settings.monday_default_status_label || 'Not Started',
                monday_workspace_id: settings.monday_workspace_id || '',
                monday_workspace_name: settings.monday_workspace_name || '',
            } );
        }
    }, [ settings ] );

    useEffect( () => {
        if ( ! Array.isArray( schools ) ) {
            return;
        }

        const nextMappings = {};
        schools.forEach( ( school ) => {
            nextMappings[ school.id ] = {
                monday_board_id: school.monday_board_id || '',
                monday_board_name: school.monday_board_name || '',
                monday_status_column_id: school.monday_status_column_id || '',
                monday_priority_column_id: school.monday_priority_column_id || '',
                monday_date_column_id: school.monday_date_column_id || '',
                monday_notes_column_id: school.monday_notes_column_id || '',
                monday_person_column_id: school.monday_person_column_id || '',
                monday_default_person_id: school.monday_default_person_id || '',
            };
        } );
        setSchoolMappings( nextMappings );
    }, [ schools ] );

    const handleChange = ( e ) => {
        const { name, value, type, checked } = e.target;
        setFormData( ( prev ) => ( {
            ...prev,
            [ name ]: type === 'checkbox' ? ( checked ? 'yes' : 'no' ) : value,
        } ) );
        setIsSaved( false );
    };

    const handleSubmit = ( e ) => {
        e.preventDefault();
        updateSettingsMutation.mutate( formData, {
            onSuccess: () => {
                setIsSaved( true );
                addToast( { type: 'success', message: 'Settings saved successfully.' } );
                window.setTimeout( () => setIsSaved( false ), 3000 );
            },
            onError: ( error ) => {
                addToast( { type: 'error', message: error.message || 'Failed to save settings.' } );
            },
        } );
    };

    const persistSettingsForMonday = async () => {
        if ( ! formData.monday_api_token ) {
            throw new Error( 'Save or enter a monday.com API token first.' );
        }

        await updateSettingsMutation.mutateAsync( formData );
    };

    const handleTestMonday = async () => {
        setConnectionStatus( null );
        try {
            await persistSettingsForMonday();
            const result = await mondayTestMutation.mutateAsync();
            const userName = result?.data?.name || 'your account';
            const userEmail = result?.data?.email ? ` (${ result.data.email })` : '';
            setConnectionStatus( {
                type: 'success',
                message: `Connected as ${ userName }${ userEmail }`,
            } );
            addToast( {
                type: 'success',
                message: `Connected to monday.com as ${ userName }.`,
            } );
        } catch ( error ) {
            setConnectionStatus( {
                type: 'error',
                message: error.message || 'Connection failed.',
            } );
            addToast( { type: 'error', message: error.message || 'monday.com connection test failed.' } );
        }
    };

    const handleLoadWorkspaces = async () => {
        try {
            await persistSettingsForMonday();
            const result = await mondayWorkspacesMutation.mutateAsync();
            const count = result?.data?.length || 0;
            addToast( { type: 'success', message: `${ count } workspace${ count !== 1 ? 's' : '' } loaded.` } );
        } catch ( error ) {
            addToast( { type: 'error', message: error.message || 'Failed to load monday.com workspaces.' } );
        }
    };

    const handleWorkspaceChange = ( e ) => {
        const selectedId = e.target.value;
        const workspace = workspaces.find( ( ws ) => String( ws.id ) === selectedId );
        setFormData( ( prev ) => ( {
            ...prev,
            monday_workspace_id: selectedId,
            monday_workspace_name: workspace?.name || '',
        } ) );
    };

    const handleLoadBoards = async () => {
        try {
            await persistSettingsForMonday();
            const wsId = formData.monday_workspace_id || null;
            const result = await mondayBoardsMutation.mutateAsync( wsId );
            const count = result?.data?.length || 0;
            addToast( {
                type: 'success',
                message: `${ count } board${ count !== 1 ? 's' : '' } loaded${ wsId ? ' from selected workspace' : '' }.`,
            } );
        } catch ( error ) {
            addToast( { type: 'error', message: error.message || 'Failed to load monday.com boards.' } );
        }
    };

    const handleSchoolMappingChange = ( schoolId, field, value ) => {
        setSchoolMappings( ( prev ) => ( {
            ...prev,
            [ schoolId ]: {
                ...( prev[ schoolId ] || {} ),
                [ field ]: value,
            },
        } ) );
    };

    const handleSetupBoard = async ( school ) => {
        const mapping = schoolMappings[ school.id ] || {};
        if ( ! mapping.monday_board_id ) {
            addToast( { type: 'error', message: `Select a monday board for ${ school.name } first.` } );
            return;
        }

        try {
            const setupResult = await mondaySetupMutation.mutateAsync( {
                boardId: mapping.monday_board_id,
                ...mapping,
            } );
            const boardData = setupResult?.data || {};
            const nextSchoolData = {
                id: school.id,
                monday_board_id: boardData.board_id || mapping.monday_board_id,
                monday_board_name: boardData.board_name || mapping.monday_board_name,
                monday_status_column_id: boardData.mapping?.monday_status_column_id || '',
                monday_priority_column_id: boardData.mapping?.monday_priority_column_id || '',
                monday_date_column_id: boardData.mapping?.monday_date_column_id || '',
                monday_notes_column_id: boardData.mapping?.monday_notes_column_id || '',
                monday_person_column_id: boardData.mapping?.monday_person_column_id || '',
                monday_default_person_id: mapping.monday_default_person_id || '',
            };

            await updateSchoolMutation.mutateAsync( nextSchoolData );
            setSchoolMappings( ( prev ) => ( {
                ...prev,
                [ school.id ]: {
                    ...prev[ school.id ],
                    ...nextSchoolData,
                },
            } ) );

            addToast( {
                type: 'success',
                message: `${ school.name } is now mapped to monday.com and required columns are ready.`,
            } );
        } catch ( error ) {
            addToast( { type: 'error', message: error.message || `Failed to set up monday board for ${ school.name }.` } );
        }
    };

    if ( isLoading ) {
        return <div className="p-8 text-center text-gray-500">Loading settings...</div>;
    }

    return (
        <div className="p-6 max-w-6xl mx-auto space-y-6">
            <div className="mb-4">
                <h1 className="text-2xl font-bold text-gray-900">Plugin Settings</h1>
                <p className="text-gray-500 mt-1">Configure integrations, AI, and monday.com board syncing.</p>
            </div>

            <form onSubmit={ handleSubmit } className="space-y-6">
                <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
                        <div className="p-2 bg-blue-100 text-blue-600 rounded-lg">
                            <Database size={ 20 } />
                        </div>
                        <div>
                            <h2 className="font-semibold text-gray-900">Google Drive Integration</h2>
                            <p className="text-xs text-gray-500">Required for photo storage and report exports</p>
                        </div>
                    </div>
                    <div className="p-6 space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Client ID</label>
                            <input
                                type="text"
                                name="google_client_id"
                                value={ formData.google_client_id }
                                onChange={ handleChange }
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                placeholder="OAuth Client ID"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Client Secret</label>
                            <div className="relative">
                                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={ 16 } />
                                <input
                                    type="password"
                                    name="google_client_secret"
                                    value={ formData.google_client_secret }
                                    onChange={ handleChange }
                                    className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                    placeholder="OAuth Client Secret"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
                        <div className="p-2 bg-purple-100 text-purple-600 rounded-lg">
                            <Bot size={ 20 } />
                        </div>
                        <div>
                            <h2 className="font-semibold text-gray-900">AI Features</h2>
                            <p className="text-xs text-gray-500">Control Gemini-powered report generation.</p>
                        </div>
                    </div>
                    <div className="p-6 space-y-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-sm font-medium text-gray-900">Enable AI Summaries</h3>
                                <p className="text-xs text-gray-500">Allow AI to generate findings and POI.</p>
                            </div>
                            <label className="relative inline-flex items-center cursor-pointer">
                                <span className="sr-only">Enable AI summaries</span>
                                <input
                                    type="checkbox"
                                    name="enable_ai"
                                    checked={ formData.enable_ai === 'yes' }
                                    onChange={ handleChange }
                                    className="sr-only peer"
                                />
                                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                            </label>
                        </div>
                        { formData.enable_ai === 'yes' && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Gemini API Key</label>
                                <div className="relative">
                                    <Lock className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={ 16 } />
                                    <input
                                        type="password"
                                        name="gemini_api_key"
                                        value={ formData.gemini_api_key }
                                        onChange={ handleChange }
                                        className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all"
                                        placeholder="AI API Key"
                                    />
                                </div>
                            </div>
                        ) }
                    </div>
                </div>

                <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
                        <div className="p-2 bg-amber-100 text-amber-700 rounded-lg">
                            <Link2 size={ 20 } />
                        </div>
                        <div>
                            <h2 className="font-semibold text-gray-900">monday.com Integration</h2>
                            <p className="text-xs text-gray-500">Sync approved report POI items to school boards.</p>
                        </div>
                    </div>
                    <div className="p-6 space-y-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-sm font-medium text-gray-900">Enable monday Sync</h3>
                                <p className="text-xs text-gray-500">Approved reports can sync POI to monday boards.</p>
                            </div>
                            <label className="relative inline-flex items-center cursor-pointer">
                                <span className="sr-only">Enable monday sync</span>
                                <input
                                    type="checkbox"
                                    name="monday_enabled"
                                    checked={ formData.monday_enabled === 'yes' }
                                    onChange={ handleChange }
                                    className="sr-only peer"
                                />
                                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                            </label>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Admin API Token</label>
                            <div className="relative">
                                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={ 16 } />
                                <input
                                    type="password"
                                    name="monday_api_token"
                                    value={ formData.monday_api_token }
                                    onChange={ handleChange }
                                    className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all"
                                    placeholder="monday.com admin token"
                                />
                            </div>
                        </div>

                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-sm font-medium text-gray-900">Auto-sync on Approval</h3>
                                <p className="text-xs text-gray-500">Automatically sync POI when a report is approved.</p>
                            </div>
                            <label className="relative inline-flex items-center cursor-pointer">
                                <span className="sr-only">Auto-sync on approval</span>
                                <input
                                    type="checkbox"
                                    name="monday_auto_sync_on_approval"
                                    checked={ formData.monday_auto_sync_on_approval === 'yes' }
                                    onChange={ handleChange }
                                    className="sr-only peer"
                                />
                                <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                            </label>
                        </div>

                        <div className="rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            New monday items will start in <strong>{ formData.monday_default_status_label || 'Not Started' }</strong>.
                            Re-sync keeps monday Status and Notes intact, and removed POI items move to <strong>Removed from QA Sync</strong>.
                        </div>

                        { /* Step 1: Test Connection */ }
                        <div className="flex flex-wrap items-center gap-3">
                            <button
                                type="button"
                                onClick={ handleTestMonday }
                                disabled={ mondayTestMutation.isPending || ! formData.monday_api_token }
                                className="px-4 py-2 rounded-lg border border-amber-300 text-amber-800 hover:bg-amber-100 disabled:opacity-50 transition-colors"
                            >
                                { mondayTestMutation.isPending ? 'Testing...' : 'Test Connection' }
                            </button>
                            { connectionStatus && (
                                <span className={ `inline-flex items-center gap-1.5 text-sm font-medium ${
                                    connectionStatus.type === 'success' ? 'text-emerald-700' : 'text-red-600'
                                }` }>
                                    { connectionStatus.type === 'success'
                                        ? <CheckCircle2 size={ 16 } />
                                        : <AlertCircle size={ 16 } />
                                    }
                                    { connectionStatus.message }
                                </span>
                            ) }
                        </div>

                        { /* Step 2: Fetch Workspaces & Select */ }
                        <div className="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-3">
                            <div className="flex items-center gap-2 text-sm font-semibold text-gray-700">
                                <Building2 size={ 16 } />
                                Workspace
                            </div>
                            <div className="flex flex-wrap items-end gap-3">
                                <div className="flex-1 min-w-[200px]">
                                    <label className="block text-xs font-medium text-gray-600 mb-1">monday Workspace</label>
                                    <select
                                        value={ formData.monday_workspace_id || '' }
                                        onChange={ handleWorkspaceChange }
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm"
                                    >
                                        <option value="">
                                            { workspaces.length === 0
                                                ? 'Click "Fetch Workspaces" first'
                                                : 'Select workspace'
                                            }
                                        </option>
                                        { workspaces.map( ( ws ) => (
                                            <option key={ ws.id } value={ ws.id }>
                                                { ws.name }{ ws.kind === 'open' ? '' : ` (${ ws.kind })` }
                                            </option>
                                        ) ) }
                                    </select>
                                </div>
                                <button
                                    type="button"
                                    onClick={ handleLoadWorkspaces }
                                    disabled={ mondayWorkspacesMutation.isPending || ! formData.monday_api_token }
                                    className="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 disabled:opacity-50 flex items-center gap-2 text-sm transition-colors"
                                >
                                    <RefreshCcw size={ 14 } className={ mondayWorkspacesMutation.isPending ? 'animate-spin' : '' } />
                                    { mondayWorkspacesMutation.isPending ? 'Loading...' : 'Fetch Workspaces' }
                                </button>
                            </div>
                            { formData.monday_workspace_name && (
                                <p className="text-xs text-gray-500">
                                    Selected: <strong>{ formData.monday_workspace_name }</strong>
                                </p>
                            ) }
                        </div>

                        { /* Step 3: Fetch Boards */ }
                        <div className="flex flex-wrap items-center gap-3">
                            <button
                                type="button"
                                onClick={ handleLoadBoards }
                                disabled={ mondayBoardsMutation.isPending || ! formData.monday_api_token }
                                className="px-4 py-2 rounded-lg border border-amber-300 text-amber-800 hover:bg-amber-100 disabled:opacity-50 flex items-center gap-2 transition-colors"
                            >
                                <RefreshCcw size={ 14 } className={ mondayBoardsMutation.isPending ? 'animate-spin' : '' } />
                                { mondayBoardsMutation.isPending ? 'Loading Boards...' : 'Fetch Boards' }
                            </button>
                            { boards.length > 0 && (
                                <span className="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700">
                                    <CheckCircle2 size={ 16 } />
                                    { boards.length } board{ boards.length !== 1 ? 's' : '' } loaded
                                </span>
                            ) }
                        </div>
                    </div>
                </div>

                <div className="flex items-center justify-end gap-4">
                    { isSaved && (
                        <span className="text-green-600 text-sm font-medium flex items-center gap-2">
                            <Check size={ 16 } /> Saved successfully
                        </span>
                    ) }
                    <button
                        type="submit"
                        disabled={ updateSettingsMutation.isPending }
                        className="flex items-center gap-2 px-6 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        { updateSettingsMutation.isPending ? (
                            <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        ) : (
                            <Save size={ 18 } />
                        ) }
                        Save Changes
                    </button>
                </div>
            </form>

            <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div className="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
                    <div className="p-2 bg-emerald-100 text-emerald-700 rounded-lg">
                        <Wrench size={ 20 } />
                    </div>
                    <div>
                        <h2 className="font-semibold text-gray-900">School Board Mapping</h2>
                        <p className="text-xs text-gray-500">Attach each school to its monday board and create any missing columns.</p>
                    </div>
                </div>
                <div className="p-6 space-y-4">
                    { schoolsLoading ? (
                        <div className="text-sm text-gray-500">Loading schools...</div>
                    ) : schools.length === 0 ? (
                        <div className="text-sm text-gray-500">No schools found.</div>
                    ) : (
                        schools.map( ( school ) => {
                            const mapping = schoolMappings[ school.id ] || {};

                            return (
                                <div key={ school.id } className="rounded-xl border border-gray-200 p-4 space-y-3">
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 className="font-semibold text-gray-900">{ school.name }</h3>
                                            <p className="text-xs text-gray-500">
                                                Current board: { mapping.monday_board_name || 'Not mapped yet' }
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={ () => handleSetupBoard( school ) }
                                            disabled={
                                                mondaySetupMutation.isPending ||
                                                updateSchoolMutation.isPending ||
                                                ! mapping.monday_board_id
                                            }
                                            className="px-4 py-2 rounded-lg border border-emerald-300 text-emerald-800 hover:bg-emerald-50 disabled:opacity-50"
                                        >
                                            { mondaySetupMutation.isPending ? 'Setting Up...' : 'Set Up Board' }
                                        </button>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">monday Board</label>
                                            <select
                                                value={ mapping.monday_board_id || '' }
                                                onChange={ ( e ) => {
                                                    const board = boards.find( ( item ) => String( item.id ) === e.target.value );
                                                    handleSchoolMappingChange( school.id, 'monday_board_id', e.target.value );
                                                    handleSchoolMappingChange( school.id, 'monday_board_name', board?.name || '' );
                                                } }
                                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                            >
                                                <option value="">
                                                    { boards.length === 0 ? 'Fetch boards first' : 'Select board' }
                                                </option>
                                                { boards.map( ( board ) => (
                                                    <option key={ board.id } value={ board.id }>
                                                        { board.name }
                                                    </option>
                                                ) ) }
                                            </select>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Default monday Person ID</label>
                                            <input
                                                type="text"
                                                value={ mapping.monday_default_person_id || '' }
                                                onChange={ ( e ) =>
                                                    handleSchoolMappingChange( school.id, 'monday_default_person_id', e.target.value )
                                                }
                                                className="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                                placeholder="Optional person ID"
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3 text-xs text-gray-600">
                                        <div>Status: { mapping.monday_status_column_id || 'Pending setup' }</div>
                                        <div>Priority: { mapping.monday_priority_column_id || 'Pending setup' }</div>
                                        <div>Date: { mapping.monday_date_column_id || 'Pending setup' }</div>
                                        <div>Notes: { mapping.monday_notes_column_id || 'Pending setup' }</div>
                                        <div>Person: { mapping.monday_person_column_id || 'Pending setup' }</div>
                                    </div>
                                </div>
                            );
                        } )
                    ) }
                </div>
            </div>
        </div>
    );
};

export default Settings;

