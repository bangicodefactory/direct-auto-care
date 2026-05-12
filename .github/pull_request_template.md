# PR — _<short title in present tense>_

Linear issue: IST-___ (link)

## What changed

_One paragraph: what does this PR do?_

## Why

_Link to the spec section or acceptance criterion this addresses._

## How to test

_Step-by-step manual verification path. Include mobile + desktop._

1.
2.
3.

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
