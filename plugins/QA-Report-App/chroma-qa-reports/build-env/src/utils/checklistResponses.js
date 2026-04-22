export const parseEnrollmentNotes = ( notes = '' ) => {
    const text = typeof notes === 'string' ? notes : '';
    const enrolledMatch = text.match( /Enrolled:\s*([^\n\r]*)/i );
    const presentMatch = text.match( /Present:\s*([^\n\r]*)/i );

    return {
        enrolled: enrolledMatch?.[ 1 ]?.trim() || '',
        present: presentMatch?.[ 1 ]?.trim() || '',
    };
};

export const getEnrollmentValues = ( response = {} ) => {
    const parsed = parseEnrollmentNotes( response?.notes || '' );

    return {
        enrolled:
            response?.enrolled !== undefined && response?.enrolled !== null
                ? String( response.enrolled )
                : parsed.enrolled,
        present:
            response?.present !== undefined && response?.present !== null
                ? String( response.present )
                : parsed.present,
    };
};

export const serializeEnrollmentNotes = ( values = {} ) => {
    const enrolled = String( values.enrolled ?? '' ).trim();
    const present = String( values.present ?? '' ).trim();

    if ( enrolled === '' && present === '' ) {
        return '';
    }

    return [ `Enrolled: ${ enrolled }`, `Present: ${ present }` ].join( '\n' );
};

export const hasChecklistItemValue = ( item, response = {} ) => {
    if ( item?.type === 'enrollment' ) {
        const { enrolled, present } = getEnrollmentValues( response );
        return enrolled !== '' || present !== '';
    }

    return Boolean( response?.rating );
};
