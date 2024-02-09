<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Login</title>
        <meta name="title" content="Login"/>
        <meta name="description" content=""/>
        <link rel="stylesheet" href="/css/styles.css">
    </head>
    <body><!-- Login form area -->
        <div class="PageWrapper">
            <div class="PageLogo"><img src="/images/loginLogo.png" alt="" /></div>
            <div class="PagePanel">
                <div class="head"><h5 class="iUser">Login</h5></div>
                <form action="" id="valid" class="mainForm" method="POST">
                    <fieldset>
                        <div class="PageRow noborder">
                            <label for="req1">Username:</label>
                            <div class="PageInput"><input type="text" name="email" class="validate[required]" id="req1" /></div>
                            <div class="fix"></div>
                        </div>

                        <div class="PageRow noborder">
                            <label for="req2">Password:</label>
                            <div class="PageInput"><input type="password" name="pass" class="validate[required]" id="req2" /></div>
                            <div class="fix"></div>
                        </div>
                        <div class="PageRow noborder">
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
