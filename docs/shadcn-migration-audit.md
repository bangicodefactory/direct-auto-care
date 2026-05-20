# shadcn Migration Audit

Inventory of raw HTML elements and legacy Breeze components in every page file. No refactoring in this PR — this is a checklist for module-by-module migration as Sprints 2-4 progress.

**Generated for IST-118. Update as pages are migrated.**

---

## Legend

| Symbol | Meaning |
|---|---|
| `raw` | Native HTML element (`<input>`, `<button>`, `<table>`, etc.) |
| `breeze` | Old Breeze wrapper (`TextInput`, `PrimaryButton`, `DangerButton`, etc.) |
| `shadcn` | Already using shadcn primitive — no action needed |
| `✅` | Fully migrated (update this doc when done) |

---

## Refactor priority order

Highest reuse first:

1. **Shared layout components** (`Sidebar`, `AuthenticatedLayout`, `BottomNav`, legacy button components)
2. **CustomerForm / VehicleForm** — reused across Create + Edit pages
3. **Invoices** — highest complexity + fiscal compliance
4. **Appointments** — customer-facing, most visible
5. **Customers** — high daily use
6. **Products / Inventory**
7. **Service Records**
8. **Users / Profile / Auth** — admin-only, lower traffic
9. **Welcome / Dashboard** — display-only, low element count

---

## Shared components (migrate first)

These live in `resources/js/Components/` and are used across many pages.

| File | Elements to migrate | shadcn target | Touches |
|---|---|---|---|
| `Components/TextInput.jsx` | `raw <input>` | `ui/input` | 1 |
| `Components/InputLabel.jsx` | `raw <label>` | `ui/label` | 1 |
| `Components/Checkbox.jsx` | `raw <input type="checkbox">` | `ui/checkbox` | 1 |
| `Components/PrimaryButton.jsx` | `raw <button>` | `ui/button` variant=default | 1 |
| `Components/SecondaryButton.jsx` | `raw <button>` | `ui/button` variant=secondary | 1 |
| `Components/DangerButton.jsx` | `raw <button>` | `ui/button` variant=destructive | 1 |
| `Components/Modal.jsx` | custom modal | `ui/dialog` | ~30 lines |
| `Components/Dropdown.jsx` | custom dropdown | `ui/dropdown-menu` | ~40 lines |
| `Components/CustomerForm.jsx` | `breeze` TextInput, InputLabel, raw select | `ui/input`, `ui/label`, `ui/select` | ~20 |
| `Components/VehicleForm.jsx` | `breeze` TextInput, InputLabel, raw select, raw textarea | `ui/input`, `ui/label`, `ui/select`, `ui/textarea` | ~25 |
| `Components/CustomerCombobox.jsx` | custom combobox | `ui/command` + `ui/popover` | ~30 |
| `Components/Sidebar.jsx` | raw `<a>` links | `NavLink` → shadcn-styled | ~10 |

---

## Auth pages

| File | Raw elements | Legacy components | shadcn target | Estimated touches |
|---|---|---|---|---|
| `Auth/Login.jsx` | `<form>`, `<label>` | `TextInput`, `PrimaryButton`, `Checkbox` | `ui/form`, `ui/input`, `ui/label`, `ui/button`, `ui/checkbox` | 8 |
| `Auth/Register.jsx` | `<form>` | `TextInput`, `InputLabel`, `PrimaryButton` | `ui/form`, `ui/input`, `ui/label`, `ui/button` | 8 |
| `Auth/ForgotPassword.jsx` | `<form>` | `TextInput`, `InputLabel`, `PrimaryButton` | `ui/form`, `ui/input`, `ui/label`, `ui/button` | 5 |
| `Auth/ResetPassword.jsx` | `<form>` | `TextInput`, `InputLabel`, `PrimaryButton` | `ui/form`, `ui/input`, `ui/label`, `ui/button` | 6 |
| `Auth/ConfirmPassword.jsx` | `<form>` | `TextInput`, `InputLabel`, `PrimaryButton` | `ui/form`, `ui/input`, `ui/label`, `ui/button` | 5 |
| `Auth/VerifyEmail.jsx` | `<form>` | `PrimaryButton`, `SecondaryButton` | `ui/button` | 3 |

---

## Dashboard

| File | Raw elements | Legacy components | shadcn target | Estimated touches |
|---|---|---|---|---|
| `Dashboard.jsx` | none | none | — | 0 |

---

## Customers

| File | Raw elements | Legacy components | shadcn target | Estimated touches |
|---|---|---|---|---|
| `Customers/Index.jsx` | `<button>`, `<select>`, `<table>` | — | `ui/button`, `ui/select`, `ui/table` | 12 |
| `Customers/Create.jsx` | none (uses `CustomerForm`) | `CustomerForm` | migrate `CustomerForm` shared component first | 0 (indirect) |
| `Customers/Edit.jsx` | none (uses `CustomerForm`) | `CustomerForm` | migrate `CustomerForm` shared component first | 0 (indirect) |
| `Customers/Show.jsx` | `<button>` | `PrimaryButton`, `DangerButton` | `ui/button` | 4 |

---

## Vehicles

| File | Raw elements | Legacy components | shadcn target | Estimated touches |
|---|---|---|---|---|
| `Vehicles/Index.jsx` | `<button>`, `<table>` | — | `ui/button`, `ui/table` | 8 |
| `Vehicles/Create.jsx` | none (uses `VehicleForm`) | `VehicleForm` | migrate `VehicleForm` shared component first | 0 (indirect) |
| `Vehicles/Edit.jsx` | none (uses `VehicleForm`) | `VehicleForm` | migrate `VehicleForm` shared component first | 0 (indirect) |
| `Vehicles/Show.jsx` | `<button>` | `PrimaryButton` | `ui/button` | 3 |

---

## Appointments

| File | Raw elements | Legacy components | shadcn target | Estimated touches |
|---|---|---|---|---|
| `Appointments/Index.jsx` | `<textarea>`, `<select>`, `<form>` | `TextInput`, `InputLabel` | `ui/textarea`, `ui/select`, `ui/form`, `ui/input`, `ui/label` | 15 |
| `Appointments/Create.jsx` | `<textarea>`, `<select>`, `<form>` | `TextInput`, `InputLabel` | `ui/textarea`, `ui/select`, `ui/form`, `ui/input`, `ui/label` | 14 |
| `Appointments/Edit.jsx` | `<textarea>`, `<select>`, `<form>` | `TextInput`, `InputLabel` | `ui/textarea`, `ui/select`, `ui/form`, `ui/input`, `ui/label` | 14 |
| `Appointments/Show.jsx` | `<button>` | `PrimaryButton`, `DangerButton` | `ui/button` | 4 |
| `Appointments/Today.jsx` | none | — | — | 0 |

---

## Invoices

| File | Raw elements | Legacy components | shadcn target | Estimated touches |
|---|---|---|---|---|
| `Invoices/Create.jsx` | `<button>`, `<textarea>`, `<select>`, `<table>`, `<form>` | `TextInput`, `InputLabel` | `ui/button`, `ui/textarea`, `ui/select`, `ui/table`, `ui/form`, `ui/input`, `ui/label` | 25 |
| `Invoices/Edit.jsx` | `<button>`, `<textarea>`, `<select>`, `<table>`, `<form>` | `TextInput`, `InputLabel` | same as Create | 25 |
| `Invoices/Index.jsx` | `<input>`, `<button>`, `<select>`, `<table>`, `<label>` | — | `ui/input`, `ui/button`, `ui/select`, `ui/table`, `ui/label` | 15 |
| `Invoices/Show.jsx` | `<button>`, `<table>`, `<a>` | — | `ui/button`, `ui/table` | 8 |

---

## Products

| File | Raw elements | Legacy components | shadcn target | Estimated touches |
|---|---|---|---|---|
| `Products/Index.jsx` | `<button>`, `<select>`, `<table>` | — | `ui/button`, `ui/select`, `ui/table` | 10 |
| `Products/Create.jsx` | `<select>`, `<form>` | `TextInput`, `InputLabel` | `ui/select`, `ui/form`, `ui/input`, `ui/label` | 8 |
| `Products/Edit.jsx` | `<select>`, `<form>` | `TextInput`, `InputLabel` | `ui/select`, `ui/form`, `ui/input`, `ui/label` | 8 |
| `Products/Show.jsx` | `<button>`, `<table>`, `<form>`, `<label>` | — | `ui/button`, `ui/table`, `ui/form`, `ui/label` | 8 |

---

## Service Records

| File | Raw elements | Legacy components | shadcn target | Estimated touches |
|---|---|---|---|---|
| `ServiceRecords/Create.jsx` | `<textarea>`, `<form>` | `TextInput`, `InputLabel` | `ui/textarea`, `ui/form`, `ui/input`, `ui/label` | 8 |
| `ServiceRecords/Edit.jsx` | `<textarea>`, `<form>` | `TextInput`, `InputLabel` | `ui/textarea`, `ui/form`, `ui/input`, `ui/label` | 8 |
| `ServiceRecords/Show.jsx` | `<button>` | `PrimaryButton` | `ui/button` | 3 |

---

## Reminders

| File | Raw elements | Legacy components | shadcn target | Estimated touches |
|---|---|---|---|---|
| `Reminders/Index.jsx` | `<input>`, `<button>`, `<select>`, `<table>`, `<label>` | — | `ui/input`, `ui/button`, `ui/select`, `ui/table`, `ui/label` | 12 |

---

## Profile

| File | Raw elements | Legacy components | shadcn target | Estimated touches |
|---|---|---|---|---|
| `Profile/Edit.jsx` | none (renders partials) | — | — | 0 (indirect) |
| `Profile/Partials/UpdateProfileInformationForm.jsx` | `<form>` | `TextInput`, `InputLabel`, `PrimaryButton` | `ui/form`, `ui/input`, `ui/label`, `ui/button` | 8 |
| `Profile/Partials/UpdatePasswordForm.jsx` | `<form>` | `TextInput`, `InputLabel`, `PrimaryButton` | `ui/form`, `ui/input`, `ui/label`, `ui/button` | 7 |
| `Profile/Partials/UpdateLocaleForm.jsx` | `<input>`, `<form>`, `<label>` | `PrimaryButton` | `ui/input`, `ui/form`, `ui/label`, `ui/button` | 5 |
| `Profile/Partials/DeleteUserForm.jsx` | `<form>` | `DangerButton`, `SecondaryButton`, `TextInput`, `InputLabel`, `Modal` | `ui/form`, `ui/input`, `ui/label`, `ui/button`, `ui/dialog` | 10 |

---

## Settings

| File | Raw elements | Legacy components | shadcn target | Estimated touches |
|---|---|---|---|---|
| `Settings/Index.jsx` | `<input>`, `<textarea>`, `<form>` | `TextInput`, `InputLabel`, `PrimaryButton` | `ui/input`, `ui/textarea`, `ui/form`, `ui/label`, `ui/button` | 10 |

---

## Users

| File | Raw elements | Legacy components | shadcn target | Estimated touches |
|---|---|---|---|---|
| `Users/Index.jsx` | `<button>`, `<table>` | — | `ui/button`, `ui/table` | 6 |
| `Users/Create.jsx` | `<select>`, `<form>` | `TextInput`, `InputLabel`, `PrimaryButton` | `ui/select`, `ui/form`, `ui/input`, `ui/label`, `ui/button` | 8 |
| `Users/Edit.jsx` | `<select>`, `<form>`, `<label>` | `TextInput`, `InputLabel`, `PrimaryButton` | `ui/select`, `ui/form`, `ui/input`, `ui/label`, `ui/button` | 8 |

---

## Other

| File | Raw elements | Legacy components | shadcn target | Estimated touches |
|---|---|---|---|---|
| `Welcome.jsx` | `<a>` links only | — | `ui/button` variant=link (optional) | 2 |
| `StyleGuide.jsx` | `<button>` | `PrimaryButton`, `SecondaryButton` | `ui/button` | 4 |

---

## Summary

| Category | Files | Total estimated touches |
|---|---|---|
| Shared components | 12 | ~140 |
| Auth pages | 6 | ~35 |
| Customers | 4 | ~16 |
| Vehicles | 4 | ~11 |
| Appointments | 5 | ~47 |
| Invoices | 4 | ~73 |
| Products | 4 | ~34 |
| Service Records | 3 | ~19 |
| Reminders | 1 | ~12 |
| Profile | 5 | ~30 |
| Settings | 1 | ~10 |
| Users | 3 | ~22 |
| Other | 2 | ~6 |
| **Total** | **54** | **~455** |

---

## Notes

- Pages using `CustomerForm` or `VehicleForm` show no raw elements because those shared components encapsulate them. Migrate the shared component and all pages using it migrate automatically.
- Auth pages use Breeze's default layout + old Breeze component wrappers. These should migrate last since they are lower traffic.
- Invoices have the highest complexity due to line-item tables and fiscal compliance fields — assign to a senior in Sprint 3.
