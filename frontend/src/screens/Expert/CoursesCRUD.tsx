import React, { useState } from 'react';

export default function CoursesCRUD() {
  const [courses, setCourses] = useState([
    { id: 'CS101', name: 'برنامه‌نویسی وب', credits: 3, active: true },
    { id: 'CS201', name: 'هوش مصنوعی', credits: 3, active: true },
    { id: 'CS301', name: 'شبکه‌های کامپیوتری', credits: 3, active: false },
  ]);

  return (
    <div style={{ padding: 24 }}>
      <h2>مدیریت درس‌ها</h2>
      <button style={{ marginBottom: 16 }}>درس جدید</button>

      <table style={{ width: '100%', maxWidth: 700 }}>
        <thead>
          <tr style={{ background: '#f5f5f5' }}>
            <th style={{ padding: 12, textAlign: 'right' }}>کد</th>
            <th style={{ padding: 12, textAlign: 'right' }}>نام درس</th>
            <th style={{ padding: 12, textAlign: 'right' }}>واحد</th>
            <th style={{ padding: 12, textAlign: 'right' }}>وضعیت</th>
            <th style={{ padding: 12 }}>عملیات</th>
          </tr>
        </thead>
        <tbody>
          {courses.map((course, index) => (
            <tr key={index}>
              <td style={{ padding: 12 }}>{course.id}</td>
              <td style={{ padding: 12 }}>{course.name}</td>
              <td style={{ padding: 12 }}>{course.credits}</td>
              <td style={{ padding: 12 }}>
                <span style={{ color: course.active ? 'green' : 'gray' }}>
                  {course.active ? 'فعال' : 'غیرفعال'}
                </span>
              </td>
              <td style={{ padding: 12 }}>
                <button>ویرایش</button>
                <button style={{ marginLeft: 8, color: 'red' }}>حذف</button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}