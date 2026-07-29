import React from 'react';
import { motion } from 'framer-motion';

export default function SchedulerCExamFlip() {
  return (
    <div style={{ padding: 24 }}>
      <h2>فاز C - Flip Exam Schedule</h2>
      <motion.div
        whileHover={{ rotateY: 180 }}
        style={{ width: 200, height: 120, background: '#1976D2', color: 'white', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
      >
        Flip Card (Exam)
      </motion.div>
    </div>
  );
}