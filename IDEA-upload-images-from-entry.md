# Upload images from inside a Quick journal entry (badmin popup + postMessage)

## Goal
Write journal entries in Quick (quick.robnugen.com) and drop images into them without leaving the entry. Images still go to the image server via badmin; the text still goes to Quick, with markdown links to the images, and every commit keeps the reverse-story word as its message.

## Why this shape
- Quick is where I think about writing, so the interface starts there.
- Both sides are "heavy" in different ways: images are heavy in bytes (upload, resize, auto-crop on the image server); the journal is heavy in state (git repo, log, story file on the journal server). Each heavy job stays on its own server. Only a short markdown string moves between them, and it moves inside the browser.
- No CORS needed: neither page fetches from the other origin. Each page posts only to its own server; the two windows hand off the result with postMessage, which CORS doesn't govern. No server-to-server call, no shared login.

## Flow
1. Typing in Quick, cursor somewhere in the entry. Tap *Upload*.
2. Quick saves the cursor position (selectionStart / selectionEnd) — the textarea loses focus on click.
3. Quick calls window.open() on the badmin upload page *directly in the click handler* (no await or timer before it, or mobile popup blockers stop it). On a phone this opens a new tab.
4. badmin works as it does now: same-origin form post, its own login, resizing, auto-crop, up to 12 images.
5. On success badmin calls window.opener.postMessage(markdown, 'https://quick.robnugen.com') and closes itself.
6. Quick's listener checks event.origin is badmin's, inserts the string at the saved cursor with setRangeText(), and refocuses the textarea.
7. Post the entry as usual; Quick commits with the next story word.

## Quick side
- Upload button + saved cursor position
- message listener: accept only badmin's origin; insert as *text* into the textarea value, never as HTML
- Autosave the draft to localStorage (wrapped in try/catch) and restore it on load

## badmin side
- After the existing embed-code output, postMessage the markdown to Quick's exact origin (never '*'), then window.close()
- Fallback: always also show the markdown with a *Copy* button, for when the opener is gone
- Make the upload page comfortable on a phone (slots, category picker, camera/gallery picker)

## Things that bite
- Don't open the popup with noopener, and don't send a Cross-Origin-Opener-Policy header from either site: both sever window.opener.
- Use a popup, not an iframe modal: the popup is a normal top-level page, so badmin's login cookie just works, with no framing permissions or Safari storage rules to fight.
- *Phone:* iOS can unload the background Quick tab while the camera/photo picker is open. Quick reloads, the opener link is gone, and unsaved text would be lost. The localStorage draft + Copy button turn the worst case into one paste instead of a lost entry.
- *Phone:* don't run Quick as an iOS home-screen (standalone) app. window.open() then hands off to Safari as a separate app, and the opener link breaks. Use a normal browser tab; in-app browsers in other apps can misbehave the same way.
- Log in to badmin once on each device; its cookie handles later uploads.

## Open
- Whether badmin's upload page ends up living in badmint (the new framework) or stays in the current badmin. The hand-off is the same either way.
