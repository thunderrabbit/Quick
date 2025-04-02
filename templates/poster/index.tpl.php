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
    </script>
</head>

<body><!-- Quick form area -->
    <div class="PageWrapper">
        <div class="PageLogo"><img src="/images/QuickLogo.png" alt="" /></div>
        <div class="PagePanel">
            <div class="head">
                <h5 class="iUser">Quick</h5>
            </div>
            <?php
            // current time in JST timezone 24 hour format
            date_default_timezone_set('Asia/Tokyo');
            $current_time = date("H:i");
            // current date in "Friday 2 February 2024" format
            $current_date = date("l j F Y T");
            echo "<br>logged in! <a href='/logout'>Log out</a>";

            $entry_date = $entry_date ?: $current_date;
            $entry_time = $entry_time ?: $current_time;

            if (isset($post_path)) {
                echo "<br>Post saved to <a target='journal' href='https://quick.robnugen.com/$post_path'>$post_path</a>";
            }
            if (isset($storyWordOutput) && isset($newBranchName)) {
                echo $storyWordOutput;
                echo "<br>File successfully added and pushed to git branch <b>$newBranchName</b>";
            }
            if (isset($gitLog)) {
                echo "<br>git log:<br>";
                echo "<pre>$gitLog</pre>";
            }
            ?>
            <?php if ($show_deploy): ?>
                <form action="/deploy/" id="deploy" class="mainForm" method="POST">
                    <fieldset>
                        <div class="PageRow noborder">
                            <input type="submit" value="Deploy to <?php echo $newBranchName; ?>"
                                class="greyishBtn submitForm" />
                            <div class="fix"></div>
                        </div>
                        <label for="debug_deploy">Debug:</label>
                        <input id="debug_deploy" type="text" name="debug_deploy" value="0" size="5" />
                        <input type="hidden" name="branch" value="<?php echo $newBranchName; ?>" />
                    </fieldset>
                </form>
            <?php endif; // ($show_deploy): ?>

            <p><a href="https://quick.robnugen.com">https://quick.robnugen.com</a>
                <br><a href="https://robnugen.com/journal">https://robnugen.com/journal</a>
                <br><a href="https://badmin.robnugen.com">https://badmin.robnugen.com</a>
            </p>

            <form action="/poster/" id="valid" class="mainForm" method="POST">
                <fieldset>
                    <div class="PageRow noborder">
                        <input type="submit" value="Save" class="greyishBtn submitForm" />
                        <div class="fix"></div>
                    </div>
                    <div class="PageRow noborder">
                        <label for="dp">Date:</label>
                        <div class="PageInput">
                            <input type="text" name="time" value="<?php echo $entry_time ?>" size="5" />
                            <input type="text" name="date" value="<?php echo $entry_date ?>" size="35" id="dp" />
                            <label for="debug">Debug:</label>
                            <input type="text" name="debug" value="0" size="5" />
                        </div>
                        <div class="fix"></div>
                    </div>

                    <div class="PageRow noborder">
                        <label for="title">Title:</label>
                        <div class="PageInput">
                            <input id="title" type="text" name="title" size="75" value="<?php echo $entry_title ?>" />
                        </div>
                        <div class="fix"></div>
                    </div>

                    <div class="PageRow noborder">
                        <label for="tags">Tags:</label>
                        <div class="PageInput">
                            <input id="tags" type="text" name="tags" size="75" value="<?php echo $entry_tags ?>" />
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
                            </div>
                            <textarea id="content" name="post_content" cols="75" rows="35"><?php echo $text; ?></textarea>
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
</body>

</html>
