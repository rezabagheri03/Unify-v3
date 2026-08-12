import { render, screen, fireEvent } from '@testing-library/react';
import FileCard from '../components/FileCard';

describe('FileCard Component', () => {
  test('renders title, author and version correctly', () => {
    render(<FileCard id="r1" title="جزوه برنامه‌نویسی.pdf" author="P1001" version={2} mime="application/pdf" />);

    expect(screen.getByText(/جزوه برنامه‌نویسی.pdf/i)).toBeInTheDocument();
    expect(screen.getByText(/نسخه 2/)).toBeInTheDocument();
    expect(screen.getByText('PDF')).toBeInTheDocument();
  });

  test('renders professor badge when provided', () => {
    render(<FileCard id="r2" title="اسلایدهای درس.docx" author="P1001" badge_type="professor" mime="application/vnd.openxmlformats-officedocument.wordprocessingml.document" />);

    expect(screen.getByText('professor')).toBeInTheDocument();
    expect(screen.getByText('DOC')).toBeInTheDocument();
  });

  test('calls onDownload with the resource id when the download button is clicked', () => {
    const onDownload = jest.fn();
    render(<FileCard id="r3" title="خلاصه فصل ۱.pdf" onDownload={onDownload} />);

    fireEvent.click(screen.getByRole('button', { name: 'دانلود' }));
    expect(onDownload).toHaveBeenCalledWith('r3');
  });
});
