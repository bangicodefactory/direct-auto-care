# PR — Add Customer model relationships and mutator

Linear issue: [IST-19](https://linear.app/bangicodefactory/issue/IST-19)

## What changed

Added Eloquent relationships (hasMany: vehicles, appointments, invoices) to the Customer model and implemented a phone number mutator that normalizes phone numbers to international format (+212).

## Why

Enables relational queries and data consistency across the application. The phone mutator ensures all customer phone numbers are stored in a standardized format for consistency and international compatibility.

## How to test

Manual verification of the Customer model relationships and phone mutator:

1. Create a customer via tinker: `Customer::create(['first_name' => 'Test', 'last_name' => 'User', 'phone' => '0612345678'])`
2. Verify phone is stored as `+212612345678` in database
3. Test relationship: `Customer.first()->vehicles` returns related vehicles
4. Test relationship: `Customer.first()->appointments` returns related appointments

## Definition of Done checklist

- [ ] PSR-12 + Pint pass (`composer lint`)
- [ ] ESLint + Prettier pass (`npm run lint`)
- [ ] Migrations run cleanly on fresh DB (`php artisan migrate:fresh --seed`)
- [ ] French validation messages, shown inline
- [ ] Authorization enforced at Policy level — tested as employee
- [ ] Works on mobile (iPhone 14 + Galaxy S21 DevTools), tablet, desktop
- [ ] All Linear acceptance criteria met (paste them here, check each)
- [ ] No console errors or Laravel deprecation warnings
- [ ] CI green

## Screenshots (mobile + desktop)

_Required for any UI change._

## Reviewers — focus on

_Highlight specific files or logic you want extra scrutiny on._
