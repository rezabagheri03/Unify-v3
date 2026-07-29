import React, { useState } from 'react';

export default function ProfessorMessages() {
  const [message, setMessage] = useState('');
  const [lastSent, setLastSent] = useState<Date | null>(null);

  const canSend = !lastSent || (new Date().getTime() - lastSent.getTime()) > 10 * 60 * 1000;

  const sendBroadcast = () => {
    if (!message.trim() || !canSend) return;
    
    alert('پیام با موفقیت به کلاس ارسال شد');
    setLastSent(new Date());
    setMessage('');
  };

  return (
    <div style={{ padding: 24 }}>
      <h2>ارسال پیام به کلاس</h2>
      <p style={{ color: canSend ? 'green' : 'red' }}>
        {canSend ? 'می‌توانید پیام ارسال کنید' : 'لطفاً ۱۰ دقیقه صبر کنید'}
      </p>

      <textarea 
        value={message} 
        onChange={e => setMessage(e.target.value)}
        rows={5}
        style={{ width: '100%', maxWidth: 500, padding: 10 }}
        placeholder="متن پیام..."
      />
      
      <button 
        onClick={sendBroadcast} 
        disabled={!canSend || !message.trim()}
        style={{ marginTop: 12 }}
      >
        ارسال پیام
      </button>
    </div>
  );
}