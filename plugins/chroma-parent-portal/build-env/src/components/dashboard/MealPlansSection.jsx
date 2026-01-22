import React from 'react';
import LessonPlanSection from './LessonPlanSection';

const MealPlansSection = ({ items, onView, onDelete }) => {
    // Reusing the tab logic is efficient, quarters work same as months
    return (
        <LessonPlanSection items={items} type="meal" onView={onView} onDelete={onDelete} />
    );
};

export default MealPlansSection;
