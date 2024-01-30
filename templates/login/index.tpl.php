<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>Login</title>
        <meta name="title" content="Login"/>
        <meta name="description" content=""/>
    </head>
    <body><!-- Login form area -->
        <div class="loginWrapper">
            <div class="loginLogo"><img src="/images/loginLogo.png" alt="" /></div>
            <div class="loginPanel">
                <div class="head"><h5 class="iUser">Login</h5></div>
<?php
                echo "<br>Not logged in!";
                echo "<br>Next steps:";
                echo "<br>O done: clean up login template";
                echo "<br>O done: Remove old code";
                echo "<br>O done: Squash foundation";
                echo "<br># Simulate logged in value";
                echo "<br># Create template after logged in";
                echo "<br># Log in";
                echo "<br># See logged in template";
?>
                <form action="" id="valid" class="mainForm" method="POST">
                    <fieldset>
                        <div class="loginRow noborder">
                            <label for="req1">Username:</label>
                            <div class="loginInput"><input type="text" name="email" class="validate[required]" id="req1" /></div>
                            <div class="fix"></div>
                        </div>

                        <div class="loginRow noborder">
                            <label for="req2">Password:</label>
                            <div class="loginInput"><input type="password" name="pass" class="validate[required]" id="req2" /></div>
                            <div class="fix"></div>
                        </div>
                        <div class="loginRow noborder">
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
