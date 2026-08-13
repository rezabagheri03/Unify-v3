import React, { useEffect, useState } from 'react';
import TextField from '@mui/material/TextField';
import InputAdornment from '@mui/material/InputAdornment';
import SearchIcon from '@mui/icons-material/Search';

/** P18 SearchBar — 300ms debounced search input. */
export default function SearchBar({ value, onChange, placeholder = 'جستجو...' }: {
  value?: string;
  onChange: (v: string) => void;
  placeholder?: string;
}) {
  const [local, setLocal] = useState(value || '');

  useEffect(() => {
    const t = setTimeout(() => onChange(local), 300);
    return () => clearTimeout(t);
  }, [local]); // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <TextField
      fullWidth size="small"
      placeholder={placeholder}
      value={local}
      onChange={(e) => setLocal(e.target.value)}
      sx={{ mb: 2 }}
      InputProps={{
        startAdornment: (
          <InputAdornment position="start"><SearchIcon fontSize="small" /></InputAdornment>
        ),
      }}
    />
  );
}
