# Accessibility Baseline

Mediarama targets WCAG 2.2 AA for the bundled public and administrative UI.

Foundation rules:

- semantic landmarks and headings;
- all interactive controls keyboard-operable;
- visible focus state must not be removed;
- controls with icon-only presentation require accessible names;
- navigation regions receive labels where ambiguity exists;
- images require appropriate alt handling; decorative images use empty alt;
- form fields require programmatic labels and associated error messages;
- status/error messages must not rely on color alone;
- sufficient text and UI-component contrast;
- dialogs/offcanvas components must preserve sensible focus behavior;
- media controls require keyboard access and labels;
- reduced-motion preferences must be respected for custom motion;
- automated checks complement, but do not replace, keyboard/screen-reader review.

UIkit accessibility behavior is used where provided, but Mediarama remains responsible for the final rendered semantics.

## Test baseline

Functional UI work should include:

1. keyboard-only navigation check;
2. heading/landmark inspection;
3. form label/error inspection;
4. automated accessibility scan in CI once browser testing is introduced;
5. manual screen-reader smoke test for major flows before release.
