import React from 'react';

const Skeleton = ({ className = '', style = {} }) => {
    return (
        <div
            className={`animate-pulse bg-gray-200 rounded-md ${className}`}
            style={style}
            aria-hidden="true"
        />
    );
};

export default Skeleton;
