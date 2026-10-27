<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quick</title>
    <meta name="title" content="Quick" />
    <meta name="description" content="" />
    <link rel="stylesheet" href="/css/styles.css">

    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <script>
        $(function () {
            $("#dp").datepicker({
                dateFormat: "DD d MM yy"
            });
        });
    </script>
    <script>
    function wrapSelectedParagraphs(className) {
        const textarea = document.getElementById('content');
        const text = textarea.value;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;

        const before = text.substring(0, start);
        const selected = text.substring(start, end);
        const after = text.substring(end);

        const paragraphs = selected
            .split(/\n{2,}/)
            .map(p => `<p class="${className}">${p.trim()}</p>`);

        const newText = before + paragraphs.join("\n\n") + after;
        textarea.value = newText;

        // Don't reselect the new text because the buttons don't toggle the addition of <p> tags.
        // textarea.setSelectionRange(before.length, before.length + paragraphs.join("\n\n").length);
        // textarea.focus();
    }

    function pullLatestChanges() {
        const pullBtn = document.getElementById('pullBtn');
        const pullStatus = document.getElementById('pullStatus');

        // Disable button and show loading
        pullBtn.disabled = true;
        pullBtn.innerHTML = '⏳ Pulling...';
        pullStatus.innerHTML = '<em style="color: #666;">Checking for updates...</em>';

        // Make AJAX request
        fetch('/', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': <?= json_encode(csrf_token()) ?>,
            },
            body: 'action=pull'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                pullStatus.innerHTML = '<span style="color: #28a745;"></span>';
                pullStatus.firstChild.textContent = '✅ ' + data.message;
                pullBtn.style.background = '#6c757d';
                pullBtn.innerHTML = '🔄 Check Updates';

                // Optionally reload page after successful pull to refresh story word
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                pullStatus.innerHTML = '<span style="color: #dc3545;"></span>';
                pullStatus.firstChild.textContent = '❌ ' + data.message;
                pullBtn.style.background = '#dc3545';
                pullBtn.innerHTML = '🔄 Retry';
            }
        })
        .catch(error => {
            pullStatus.innerHTML = '<span style="color: #dc3545;"></span>';
            pullStatus.firstChild.textContent = '❌ Network error: ' + error.message;
            pullBtn.style.background = '#dc3545';
            pullBtn.innerHTML = '🔄 Retry';
        })
        .finally(() => {
            pullBtn.disabled = false;
        });
    }
    </script>
    <script>
    // Upload images from inside an entry (mg #706). The 📷 button opens badmin's
    // upload page as a popup; after uploading it posts the images back and closes,
    // and they go in at the cursor. badmin sends data, not markdown, so the line
    // inserted is always one Quick built from checked URLs.
    const BADMIN_ORIGIN = 'https://badmin.robnugen.com';
    const IMAGE_URL = /^\/\/b\.robnugen\.com\/[A-Za-z0-9_\/.-]+\.(jpeg|png|gif|webp)$/;
    const IMAGE_ALT = /^[a-z0-9 _-]*$/;
    let uploadPopup = null;
    let uploadSelection = null;

    function openUpload() {
        const textarea = document.getElementById('content');
        // The click takes focus from the textarea, so remember where the cursor was.
        uploadSelection = [textarea.selectionStart, textarea.selectionEnd];
        // Synchronously inside the click, or phones block the popup. No noopener:
        // badmin needs window.opener to send the images back.
        uploadPopup = window.open(BADMIN_ORIGIN + '/upload/?return=quick', 'badmin-upload');
    }

    window.addEventListener('message', function (event) {
        if (event.origin !== BADMIN_ORIGIN || uploadPopup === null || event.source !== uploadPopup) {
            return;
        }
        const data = event.data;
        if (!data || data.type !== 'quick-images' || data.v !== 1 || !Array.isArray(data.images)) {
            return;
        }
        const lines = [];
        for (const image of data.images) {
            if (!image || !IMAGE_URL.test(image.thumb) || !IMAGE_URL.test(image.full)
                || typeof image.alt !== 'string' || !IMAGE_ALT.test(image.alt)) {
                return;   // one bad entry and none go in
            }
            lines.push('[![' + image.alt + '](' + image.thumb + ')](' + image.full + ')');
        }
        if (lines.length === 0) {
            return;
        }
        const textarea = document.getElementById('content');
        const [start, end] = uploadSelection || [textarea.value.length, textarea.value.length];
        textarea.setRangeText(lines.join("\n"), start, end, 'end');   // as text, never HTML
        textarea.focus();
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
        uploadPopup = null;
        uploadSelection = null;
    });
    </script>
</head>

<body><!-- Quick form area -->
    <div class="PageWrapper">
        <div class="PageLogo"><img src="/images/QuickLogo.png" alt="" /></div>
        <div class="PagePanel">
            <?php
            echo "<br>logged in! <a href='/logout'>Log out</a>";

            // current time in JST timezone 24 hour format
            date_default_timezone_set(timezoneId: 'Asia/Tokyo');
            // date_default_timezone_set(timezoneId: 'Australia/Adelaide');
            $current_time = date("H:i");
            // current date in "Friday 2 February 2024" format
            $current_date = date("l j F Y T");

            // New entries get the browser's local time (see the script by the Date field);
            // JST is only the fallback, and what entries opened for editing are assumed to be in.
            $is_new_entry = !$entry_date && !$entry_time;
            $entry_date = $entry_date ?: $current_date;
            $entry_time = $entry_time ?: $current_time;

            if (isset($post_path)) {
                $post_path_html = htmlspecialchars($post_path);
                echo "<br>Post saved to <a target='journal' href='https://quick.robnugen.com/$post_path_html'>$post_path_html</a>";
            }
            if (isset($storyWordOutput)) {
                echo htmlspecialchars($storyWordOutput);
                echo "<br>File successfully added and pushed to git branch master";
            }
            if(isset($leNextStoryWord)) {
                echo "<br>Next story word: <strong>" . htmlspecialchars((string) $leNextStoryWord) . "</strong>";

                // Show pull button if updates are available
                if(isset($pullNeeded) && $pullNeeded) {
                    echo " <button id='pullBtn' onclick='pullLatestChanges()' style='background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;'>📥 Pull Updates</button>";
                } else {
                    echo " <button id='pullBtn' onclick='pullLatestChanges()' style='background: #6c757d; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;'>🔄 Check Updates</button>";
                }
                echo " <span id='pullStatus'></span>";
            }
            if (isset($gitLog)) {
                echo "<br>git log:<br>";
                echo "<pre>" . htmlspecialchars($gitLog) . "</pre>";
            }
            ?>
                <form action="/commit/" id="commit" class="mainForm" method="POST">
                    <?= csrf_field() ?>
                    <fieldset>
                        <div class="PageRow noborder">
                            <input type="submit" value="Commit Changes"
                                class="greyishBtn submitForm" />
                            <div class="fix"></div>
                        </div>
                        <?php if (isset($gitStatus)): ?>
                            <div class="PageRow noborder">
                                <label>Git Status is:</label>
                                <div class="PageInput">
                                    <pre><?php echo htmlspecialchars($gitStatus); ?></pre>
                                </div>
                                <div class="fix"></div>
                            </div>
                        <?php endif; ?>
                            <div class="fix"></div>
                        </div>
                    </fieldset>
                </form>

            <p><a href="https://quick.robnugen.com">https://quick.robnugen.com</a>
                <br><a href="https://quick.robnugen.com/list/">https://quick.robnugen.com/list/</a>
	        <br><a href="https://www.robnugen.com/journal/preformatted_journal_index_writer.pl">https://www.robnugen.com/journal/preformatted_journal_index_writer.pl</a>
                <br><a href="https://robnugen.com/journal">https://robnugen.com/journal</a>
                <br><a href="https://badmin.robnugen.com">https://badmin.robnugen.com</a>
            </p>

            <?php if (isset($recentEntries)): ?>
                <p>Recent entries for <strong><?php echo htmlspecialchars($recentYear); ?></strong>/<strong><?php echo htmlspecialchars($recentMonth); ?></strong></p>
                <?php if (!empty($recentEntries)): ?>
                    <ul>
                        <?php foreach ($recentEntries as $entry): ?>
                            <li>
                                <?php echo htmlspecialchars($entry['day'] . " " . $entry['monthWord'] . " - "); ?>
                                <a href="/parser.php?file=<?php echo urlencode($entry['path']); ?>">
                                    <?php echo htmlspecialchars($entry['title']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><em>No entries this month yet.</em></p>
                <?php endif; ?>
            <?php endif; ?>

            <form action="/poster/" id="valid" class="mainForm" method="POST">
                <?= csrf_field() ?>
                <fieldset>
                    <div class="PageRow noborder">
                        <input type="submit" value="Save" class="greyishBtn submitForm" />
                        <div class="fix"></div>
                    </div>
                    <div class="PageRow noborder">
                        <label for="dp">Date: <span id="dateLabel"><?php echo htmlspecialchars($entry_time) ?> <?php echo htmlspecialchars($entry_date) ?></span></label>
		      <small>
			<small>
			  <small>
			    New entries use this browser's timezone
		          </small>
		        </small>
		      </small>
                        <div class="PageInput">
                            <input type="text" name="time" value="<?php echo htmlspecialchars($entry_time) ?>" size="5" />
                            <input type="text" name="date" value="<?php echo htmlspecialchars($entry_date) ?>" size="35" id="dp" />
                            <input type="hidden" name="tz_offset" id="tz_offset" value="+09:00" />
                            <label for="debug">Debug:</label>
                            <input type="text" name="debug" value="0" size="5" />
                        </div>
                        <div class="fix"></div>
                    </div>

                    <div class="PageRow noborder">
                        <label for="title">Title:</label>
                        <div class="PageInput">
                            <input id="title" type="text" name="title" size="75" value="<?php echo htmlspecialchars($entry_title) ?>" />
                        </div>
                        <div class="fix"></div>
                    </div>

                    <div class="PageRow noborder">
                        <label for="tags">Tags:</label>
                        <div class="PageInput">
                            <input id="tags" type="text" name="tags" size="75" value="<?php echo htmlspecialchars($entry_tags) ?>" />
                        </div>
                        <div class="fix"></div>
                    </div>

                    <div class="PageRow noborder">
                        <label for="content">Content:</label>
                        <div class="PageInput contentWrapper">
                            <div class="quickTagSidebar">
                                <button
                                    type="button"
                                    class="ui-button ui-corner-all quickTagBtn"
                                    onclick="wrapSelectedParagraphs('note')"
                                    title="Tag as note">📝</button>
                                <button
                                    type="button"
                                    class="ui-button ui-corner-all quickTagBtn"
                                    onclick="wrapSelectedParagraphs('dream')"
                                    title="Tag as dream">💭</button>
                                <button
                                    type="button"
                                    class="ui-button ui-corner-all quickTagBtn"
                                    onclick="wrapSelectedParagraphs('lucid')"
                                    title="Tag as lucid dream">👁️</button>
                                <button
                                    type="button"
                                    class="ui-button ui-corner-all quickTagBtn"
                                    onclick="wrapSelectedParagraphs('sleepy')"
                                    title="Tag as sleepy">💤</button>
                                <button
                                    type="button"
                                    class="ui-button ui-corner-all quickTagBtn"
                                    onclick="wrapSelectedParagraphs('ai')"
                                    title="Tag as AI">🤖</button>
                                <button
                                    type="button"
                                    class="ui-button ui-corner-all quickTagBtn"
                                    onclick="wrapSelectedParagraphs('anger')"
                                    title="Tag as anger">😠</button>
                                <button
                                    type="button"
                                    class="ui-button ui-corner-all quickTagBtn"
                                    onclick="openUpload()"
                                    title="Upload images to badmin and insert them here">📷</button>
                            </div>
                            <textarea id="content" name="post_content" cols="75" rows="35"><?php echo htmlspecialchars($text); ?></textarea>
                        </div>
                        <div class="fix"></div>
                    </div>

                    <div class="PageRow noborder">
                        <input type="submit" value="Save" class="greyishBtn submitForm" />
                        <div class="fix"></div>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
    <div class="fix"></div>
    <script>
    // Keep the entry in localStorage while typing (mg #706). A phone may unload
    // this tab while badmin's photo picker is open; the draft brings the text
    // back. Restored only into an empty editor, and dropped once the entry saves.
    (function () {
        const KEY = 'quick-draft';
        const justSaved = <?= isset($post_path) ? 'true' : 'false' ?>;
        const fields = { title: document.getElementById('title'),
                         tags: document.getElementById('tags'),
                         content: document.getElementById('content') };
        let store = null;
        try { store = window.localStorage; } catch (e) { return; }
        if (!store || !fields.content) { return; }

        const forget = function () { try { store.removeItem(KEY); } catch (e) {} };
        if (justSaved) {
            forget();
        } else {
            let draft = null;
            try { draft = JSON.parse(store.getItem(KEY) || 'null'); } catch (e) {}
            if (draft && typeof draft.content === 'string' && draft.content.trim() !== ''
                && fields.content.value.trim() === '') {
                for (const name in fields) {
                    if (typeof draft[name] === 'string' && fields[name].value === '') {
                        fields[name].value = draft[name];
                    }
                }
                const note = document.createElement('p');
                note.textContent = 'Restored your unsaved draft. ';
                const discard = document.createElement('button');
                discard.type = 'button';
                discard.textContent = 'Discard it';
                discard.addEventListener('click', function () {
                    forget();
                    for (const name in fields) { fields[name].value = ''; }
                    note.remove();
                });
                note.append(discard);
                fields.content.form.before(note);
            }
        }

        let timer = null;
        const save = function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                try {
                    store.setItem(KEY, JSON.stringify({ title: fields.title.value,
                        tags: fields.tags.value, content: fields.content.value }));
                } catch (e) {}
            }, 500);
        };
        for (const name in fields) { fields[name].addEventListener('input', save); }
    })();
    </script>
    <?php if ($is_new_entry): ?>
    <script>
    // New entry: use the browser's local date, time and UTC offset instead of the server's JST.
    (function () {
        const now = new Date();
        const pad = function (n) { return String(n).padStart(2, '0'); };
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July',
            'August', 'September', 'October', 'November', 'December'];
        const time = pad(now.getHours()) + ':' + pad(now.getMinutes());
        const date = days[now.getDay()] + ' ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
        const minutes = -now.getTimezoneOffset();
        const offset = (minutes < 0 ? '-' : '+') + pad(Math.floor(Math.abs(minutes) / 60)) + ':' + pad(Math.abs(minutes) % 60);
        document.querySelector('#valid input[name="time"]').value = time;
        document.getElementById('dp').value = date;
        document.getElementById('tz_offset').value = offset;
        document.getElementById('dateLabel').textContent = time + ' ' + date + ' (UTC' + offset + ')';
    })();
    </script>
    <?php endif; ?>
</body>

</html>
