# ACTOR: IT DEPARTMENT - Physical Handout Flow - V9 Shared Host

Not a system role with login (though IT staff may have Owner role), actor in physical world, same as V7 but PDF via dompdf Laravel.

## Flow Initial Distribution (New Intake 600 Students)
1. Owner bulk imports 600 via Excel in Owner dashboard /owner/users/bulk-import -> server validates transactional -> for each row generate temp 12 chars via Str::random, hash Argon2id via Hash::make, must_change_password=1, expires 7d, creates user
2. Generates ZIP of 600 envelope PDFs via dompdf + QR (simple-qrcode)
3. IT downloads ZIP, prints each PDF, folds, seals envelope with university stamp, writes Student Number outside
4. Student comes in-person with ID card to IT desk, IT verifies ID vs Student Number + Name
5. IT hands sealed envelope physically, student signs receipt logbook physical + IT can log in system via Owner password reset page reason "Initial handout signed"
6. Student goes home, logs in with temp, forced onboarding + change password (new password not same last 3 via PasswordHistory table)

## Flow Forgot Password
1. Student forgets, comes in-person with ID card
2. IT verifies ID, asks Owner (or IT staff with Owner role) to search Student ID in Owner dashboard Password Reset
3. Owner clicks Reset, enters reason "Forgot - in-person ID verified", generates new envelope PDF dompdf
4. IT prints, seals, hands, student signs
5. Old sessions revoked (delete sanctum tokens), must_change_password=1, expires 7d

## Security Requirements IT
- Envelopes sealed not transparent
- Temp passwords not spoken aloud, only sealed paper
- Printed PDFs not stored on IT computer after printing - download, print, delete file + empty recycle bin
- Receipt logbook physical locked
- IT staff must have Owner role to generate resets, or Admin request Owner
- AuditLog captures operator_id for each reset

## Edge Cases
- Student loses envelope before first login within 7 days expiry -> IT reprint? No, reset generates new temp old invalidated new expiry 7 days
- Student loses after expiry -> same reset flow new expiry
- Student claims never received -> Owner checks AuditLog when envelope generated + receipt logbook signed physical proof
- Bulk 600 ZIP size large 100MB, need printer 600 pages

## UI For IT Inside Owner Dashboard
- Bulk Download ZIP button
- Single Envelope PDF download per reset
- Receipt checkbox "دانشجو رسید را امضا کرد" optional to log in AuditLog details

END IT ACTOR V9
