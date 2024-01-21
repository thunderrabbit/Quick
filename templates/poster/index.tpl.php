<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>Posternator</title>
        <meta name="title" content="Posternator"/>
        <meta name="description" content=""/>
    </head>
    <body><!-- Posternator form area -->
        <div class="PosternatorWrapper">
            <div class="PosternatorLogo"><img src="/images/PosternatorLogo.png" alt="" /></div>
            <div class="PosternatorPanel">
                <div class="head"><h5 class="iUser">Posternator</h5></div>
<?php
                echo "<br>logged in!";
                echo "<br>Next steps:";
                echo "<br># Create template after logged in";
                echo "<br># Log in";
                echo "<br># See logged in template";
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
                                <input type="text" name="pass" class="validate[required]" id="req2" /></div>
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
