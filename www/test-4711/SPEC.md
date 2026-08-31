# Test 4711 Showcase Specification

Status: APPROVED for implementation

## Purpose

`/test-4711/index.html` is a fullscreen NEROZON brand showcase intended to run unattended, including on a TV in the background. The page has no visible navigation, controls, footer, explanatory text, buttons, or other content.

## Visual baseline

- Reproduce the visual appearance of the public `www.nerozon.de` landing page as closely as practical for this showcase.
- The showcase fills the viewport and keeps the presentation centered horizontally and vertically.
- The normal/front state MUST use the existing DEV1 brand asset `/assets/brand/nerozon-mark.svg`. Do not recreate, redraw, or substitute the NEROZON mark.
- Preserve the dark, reduced NEROZON presentation and responsive sizing behavior of the public landing page unless the 3D presentation requires a small visual adjustment.

## Showcase object

The centered brand object has two intentional faces:

1. Front: the existing NEROZON brand asset.
2. Back: a premium metallic surface containing only the word `TRUST` in NEROZON blue. No additional logo or visible copy appears on the back.

The back should read as a deliberate high-quality metallic reverse of the brand object, not as a separate page element.

## Animation

- After page load, keep the front state still for a short, natural-looking introductory pause. The exact introductory delay may be chosen by the implementer to achieve a calm human viewing rhythm.
- Then perform one complete 360-degree spatial rotation around the vertical (Y) axis.
- One complete animated rotation sequence MUST last 5 seconds in total.
- Motion should accelerate and decelerate smoothly rather than appear mechanically linear.
- Around the 180-degree position, present the metallic `TRUST` back clearly and hold it for approximately 1 second; this hold is part of the 5-second animation duration.
- Finish each sequence exactly in the normal front-facing NEROZON state.
- After completion, keep the front state still for 8 seconds.
- Repeat the 5-second rotation plus 8-second still period indefinitely.
- Because the animation is the primary purpose of this dedicated showcase, it MUST continue even when the client requests reduced motion.

## Presentation quality

The intended impression is a premium, calm advertising display rather than a conventional web animation. Perspective, lighting, depth, metallic treatment, easing, and other implementation choices should support that intent without adding unrelated effects. Avoid particles, flashing, decorative copy, controls, or distracting secondary animation.

## Acceptance

The implementation is acceptable when all of the following are observable at `https://dev1.nerozon.de/test-4711/index.html`:

- fullscreen, centered, responsive showcase with no additional visible UI or content;
- front appearance uses `/assets/brand/nerozon-mark.svg`;
- visual baseline closely matches the public NEROZON landing page;
- object rotates spatially around its vertical axis through a complete 360 degrees;
- total rotation sequence is 5 seconds;
- reverse is metallic and shows only blue `TRUST`;
- `TRUST` is intentionally readable around the halfway point for about 1 second;
- animation returns cleanly to the normal front state;
- an 8-second stationary front state follows each rotation;
- sequence repeats indefinitely;
- reduced-motion preference does not suppress this showcase animation;
- no menu, footer, button, explanatory text, or unrelated visible element is present.

## Applicable project documentation

Root and local `RULES.md` plus the project documentation under `/docs/` remain applicable. This specification defines the concrete target behavior for `www/test-4711/` and does not prescribe low-level implementation technique.