# Πίνακας Πρόσβασης (Stage 2 Foundation)

## Ρόλοι

- `registrar` (Καταχωρητής)
- `expert` (Εμπειρογνώμονας)
- `administrator` (Διαχειριστής)

## Εφαρμοσμένοι κανόνες στη βάση Stage 2

- Μόνο authenticated χρήστες έχουν πρόσβαση στο `/`.
- Μόνο `administrator` έχει πρόσβαση στα:
  - `/admin/users`
  - `/admin/invitations`
- Τα routes `/login` και `/POST /login` είναι μόνο για guest sessions.
- Όλα τα POST routes προστατεύονται με CSRF middleware.

## Επόμενο βήμα Stage 3

Θα ενεργοποιηθούν granular ownership policies (registrar/expert/admin) στα modules:
- accidents
- roads
- vehicles
- attachments
- flags
- audit logs UI
