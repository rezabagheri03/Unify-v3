import React, { useState } from 'react';

export default function FormsCalendar() {
  const [activeTab, setActiveTab] = useState<'forms' | 'calendar' | 'notice' | 'faq'>('forms');

  return (
    <div style={{ padding: 24 }}>
      <div style={{ display: 'flex', gap: 12, marginBottom: 20 }}>
        {['forms', 'calendar', 'notice', 'faq'].map(tab => (
          <button 
            key={tab} 
            onClick={() => setActiveTab(tab as any)}
            style={{ fontWeight: activeTab === tab ? 'bold' : 'normal' }}
          >
            {tab}
          </button>
        ))}
      </div>

      {activeTab === 'forms' && <div>فرم‌های دانشگاهی</div>}
      {activeTab === 'calendar' && <div>تقویم تحصیلی</div>}
      {activeTab === 'notice' && <div>تابلو اعلانات</div>}
      {activeTab === 'faq' && <div>سوالات متداول</div>}
    </div>
  );
}