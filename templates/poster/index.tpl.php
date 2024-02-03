<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>Posternator</title>
        <meta name="title" content="Posternator"/>
        <meta name="description" content=""/>

        <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
        <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
        <script>
        $( function() {
            $( "#dp" ).datepicker({
                dateFormat: "DD d MM yy"
            });
        } );
        </script>
    </head>
    <body><!-- Posternator form area -->
        <div class="PosternatorWrapper">
            <div class="PosternatorLogo"><img src="/images/PosternatorLogo.png" alt="" /></div>
            <div class="PosternatorPanel">
                <div class="head"><h5 class="iUser">Posternator</h5></div>
<?php
                // current time in JST timezone 24 hour format
                date_default_timezone_set('Asia/Tokyo');
                $current_time = date("H:i");
                // current date in "Friday 2 February 2024" format
                $current_date = date("l j F Y T");

                echo "<br>logged in! <a href='/logout'>Log out</a>";
                echo "<br>Next steps:";
                echo "<br># Distinguish type of post .md or .html (default .md)";
                echo "<br># Add tags to posts";
                echo "<br># Add author (based on Config)";
                echo "<br># Move this to server where journal is hosted";
                echo "<br># Restore my code that grabbed entries via scp";
                echo "<br># Fix post saver to use correct format:";
                echo "<br># - frontmatter";
                echo "<br># - date";
                echo "<br># - content";
?>
                <form action="/poster/" id="valid" class="mainForm" method="POST">
                    <fieldset>
                        <div class="PosternatorRow noborder">
                            <input type="submit" value="Save" class="greyishBtn submitForm" />
                            <div class="fix"></div>
                        </div>
                        <div class="PosternatorRow noborder">
                            <label for="req2">Date:</label>
                            <div class="PosternatorInput">
                                <input type="text" name="time" value="<?php echo $current_time ?>" size="5" />
                                <input type="text" name="date" value="<?php echo $current_date ?>" size="35" id="dp" /></div>
                            <div class="fix"></div>
                        </div>

                        <div class="PosternatorRow noborder">
                            <label for="req1">Title:</label>
                            <div class="PosternatorInput">
                                <input type="text" name="title" size="97" value="" /></div>
                            <div class="fix"></div>
                        </div>

                        <div class="PosternatorRow noborder">
                            <label for="req2">Content:</label>
                            <div class="PosternatorInput">
                                <textarea name="post_content" cols="155" rows="35"></textarea>
                            </div>
                            <div class="fix"></div>
                        </div>
                        <div class="PosternatorRow noborder">
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
