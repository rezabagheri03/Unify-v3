import React from 'react';
import Dialog from '@mui/material/Dialog';
import DialogTitle from '@mui/material/DialogTitle';
import DialogContent from '@mui/material/DialogContent';
import DialogActions from '@mui/material/DialogActions';
import Button from '@mui/material/Button';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';
import { useState } from 'react';

/** P18 ConfirmationModal — requires typing a phrase for dangerous actions. */
export default function ConfirmationModal({
  open, title, message, requireText, onConfirm, onClose,
}: {
  open: boolean;
  title: string;
  message: string;
  requireText?: string;
  onConfirm: () => void;
  onClose: () => void;
}) {
  const [typed, setTyped] = useState('');
  const canConfirm = !requireText || typed.trim() === requireText;

  return (
    <Dialog open={open} onClose={onClose} fullWidth maxWidth="xs">
      <DialogTitle>{title}</DialogTitle>
      <DialogContent>
        <Typography variant="body2" sx={{ mb: requireText ? 2 : 0 }}>{message}</Typography>
        {requireText && (
          <TextField
            fullWidth size="small"
            label={`برای تأیید بنویسید: «${requireText}»`}
            value={typed}
            onChange={(e) => setTyped(e.target.value)}
          />
        )}
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose}>انصراف</Button>
        <Button color="error" variant="contained" disabled={!canConfirm} onClick={onConfirm}>
          تأیید
        </Button>
      </DialogActions>
    </Dialog>
  );
}
