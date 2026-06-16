/**
 * `react-big-calendar` (ADR-002) ships no types and `@types/react-big-calendar`
 * is not installed (a dependency addition needs sign-off). Declare the modules
 * so imports resolve; the calendar surface is untyped (`any`) for now. Adopting
 * `@types/react-big-calendar` later would restore type-safety on the Calendar API.
 */
declare module 'react-big-calendar';
declare module 'react-big-calendar/lib/addons/dragAndDrop';
