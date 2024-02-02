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
            $( "#datepicker" ).datepicker();
        } );
        </script>
    </head>
    <body><!-- Posternator form area -->
        <div class="PosternatorWrapper">
            <div class="PosternatorLogo"><img src="/images/PosternatorLogo.png" alt="" /></div>
            <div class="PosternatorPanel">
                <div class="head"><h5 class="iUser">Posternator</h5></div>
<?php
                echo "<br>logged in!";
                echo "<br>Next steps:";
                echo "<br># Post something and show it";
                echo "<br># Create a class to store posts";
                echo "<br># Increase size of textarea";
                echo "<br># Add a time picker    https://api.jqueryui.com/datepicker/";
                echo "<br># Restore my code that grabbed entries via scp";
                echo "<br># Move this to server where journal is hosted";
?>
                <form action="" id="valid" class="mainForm" method="POST">
                    <fieldset>
                        <div class="PosternatorRow noborder">
                            <label for="req1">Title:</label>
                            <div class="PosternatorInput">
                                <input type="text" name="email" class="validate[required]" id="req1" /></div>
                            <div class="fix"></div>
                        </div>

                        <div class="PosternatorRow noborder">
                            <label for="req2">Date:</label>
                            <div class="PosternatorInput">
                                <input type="text" name="date" id="datepicker"/></div>
                            <div class="fix"></div>
                        </div>

                        <div class="PosternatorRow noborder">
                            <label for="req2">Content:</label>
                            <div class="PosternatorInput">
                                <textarea>
                                </textarea>
                            </div>
                            <div class="fix"></div>
                        </div>
                        <div class="PosternatorRow noborder">
                            <input type="submit" value="Log me in" class="greyishBtn submitForm" />
                            <div class="fix"></div>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
        <div class="fix"></div>
    </body>
</html>
