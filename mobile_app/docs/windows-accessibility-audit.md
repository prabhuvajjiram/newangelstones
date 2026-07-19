# Windows accessibility release audit

Microsoft Store's accessibility declaration is a release claim, not only a
code setting. Complete this checklist against the exact release candidate
before selecting **This product has been tested to meet accessibility
guidelines** in Partner Center.

## Test build

1. Download the `AngelGranites-Windows-Accessibility-Test` artifact from the
   same successful GitHub Actions run that produced the Store MSIX.
2. Extract the ZIP on a Windows 10 or Windows 11 x64 computer.
3. Launch `AngelGranites.exe`. Visual Studio is not required.
4. Record the commit, app version, Windows version, tester, and date below.

| Field | Result |
| --- | --- |
| Commit | |
| App version | |
| Windows version | |
| Tester and date | |

## Keyboard-only scenarios

Disconnect or avoid the mouse while completing every scenario.

- [ ] Press `Tab` and `Shift+Tab`; focus is always visible and follows the
      visual reading order.
- [ ] Press `Enter` or `Space` to activate every focused button, card, link,
      filter, and navigation item.
- [ ] Press `Ctrl+F` to open inventory search.
- [ ] Press `Alt+1`, `Alt+2`, `Alt+3`, and `Alt+4` to open Home, Colors, Stock,
      and Contact.
- [ ] Search for `AG-298` and `4-0 x 0-8 x 2-4`, apply Type, Color, and Location
      filters, and open a result with **View**.
- [ ] Save an inventory item, add it to the cart, adjust quantity, and reach the
      quote-request form.
- [ ] Open a flyer, a featured-product gallery, and a color gallery.
- [ ] Reach every Contact action, location, form field, and the Send button.
- [ ] Use arrow keys inside lists, page views, and selectable controls.
- [ ] Use `Esc` to close dialogs and return from temporary UI where supported.

## Narrator and UI Automation

- [ ] Start Narrator with `Windows+Ctrl+Enter`.
- [ ] Complete the four primary tabs and the stock/cart/quote flow using
      Narrator plus the keyboard.
- [ ] Narrator announces a useful name, role, value/state, and action for every
      interactive control.
- [ ] Images that convey product information have meaningful names; decorative
      branding and indicators are not repeated.
- [ ] Search-result counts, selected tabs, validation errors, loading states,
      and success messages are announced.
- [ ] Run Accessibility Insights for Windows **FastPass** on every primary tab.
- [ ] Resolve all automated failures and every high-priority UI Automation
      issue before release.
- [ ] Use Live Inspect (or Windows SDK Inspect) to confirm the UI Automation
      tree has no unnamed interactive elements.

## Visual accessibility

- [ ] Enable a Windows contrast theme before launching the app; text, focus,
      controls, and boundaries remain visible.
- [ ] Set Windows text size to 200%; no primary content or action is clipped,
      overlapped, or unreachable.
- [ ] Test 100%, 150%, and 200% display scaling.
- [ ] Use Magnifier to complete the main search and quote flows.
- [ ] Confirm text contrast is at least 4.5:1 and meaningful state is never
      communicated by color alone.
- [ ] Enable **Always show scrollbars** and confirm all scrollable content can
      be discovered and operated.

## Release decision

- [ ] All checks above passed on the exact Store candidate.
- [ ] Any exception has an owner, documented user impact, and release decision.
- [ ] Only now select the accessibility declaration in Partner Center.

If the MSIX changes after this audit, repeat the affected scenarios before
submission.
