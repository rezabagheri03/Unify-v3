import { render, screen } from '@testing-library/react';
import { FileCard } from '../components/FileCard';

describe('FileCard Component', () => {
  test('renders title and size correctly', () => {
    render(<FileCard title="جزوه برنامه‌نویسی.pdf" size="2.4 MB" />);

    expect(screen.getByText(/جزوه برنامه‌نویسی.pdf/i)).toBeInTheDocument();
    expect(screen.getByText(/2.4 MB/i)).toBeInTheDocument();
  });

  test('renders professor badge when provided', () => {
    render(
      <FileCard 
        title="اسلایدهای درس.pptx" 
        size="1.8 MB" 
        badge="استاد" 
      />
    );

    expect(screen.getByText(/استاد/i)).toBeInTheDocument();
  });
});