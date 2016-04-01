<?php

require_once("config.php");

$create_error = null;
$login_error = null;
$forgot_success = null;
$forgot_error = null;
$forgot_reset = false;
$forgot_token = null;
$forgot_email = null;
$forgot_id = null;

if (isset($_REQUEST['a']) && $_REQUEST['a'] == "logout") {
        if (isset($_SESSION['user']))
                unset($_SESSION['user']);
        header("Location: ".BASE_URL);
        exit();
}

if (isset($_REQUEST['a']) && $_REQUEST['a'] == "login") {
        $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : "";
        $password = isset($_REQUEST['password']) ? $_REQUEST['password'] : "";
        $pw = hash("sha512", SALT.$password);
        
        $user = $cpdo->fetchAssoc("SELECT * FROM `s_users` WHERE `email`=".$cpdo->quote($email)." AND `password`=".$cpdo->quote($pw)." AND (`failures`<5 OR `last_failure`<UTC_TIMESTAMP()-INTERVAL 5 MINUTE)");
        if (count($user) > 0) {
                $cpdo->query("UPDATE `s_users` SET `failures`=0,`last_failure`=UTC_TIMESTAMP() WHERE `email`=".$cpdo->quote($email)."");
                $_SESSION['user'] = $user[0];
                header("Location: ".BASE_URL);
                exit();
        } else {
                $cpdo->query("UPDATE `s_users` SET `failures`=`failures`+1,`last_failure`=UTC_TIMESTAMP() WHERE `email`=".$cpdo->quote($email)." AND `failures`<5");
                $check = $cpdo->lookup("SELECT COUNT(*) FROM `s_users` WHERE `email`=".$cpdo->quote($email)." AND `failures`>=5 AND `last_failure`>UTC_TIMESTAMP()-INTERVAL 5 MINUTE");
                $login_error = "The email address or password is incorrect.  Accounts are locked for five minutes after five failed attempts.".($check > 0 ? "<br><br>This account has been temporarily locked." : "");
        }
}

if (isset($_REQUEST['a']) && $_REQUEST['a'] == "create") {
        $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : "";
        $password = isset($_REQUEST['password']) ? $_REQUEST['password'] : "";
        
        $res = curlRequest("https://www.google.com/recaptcha/api/siteverify", "POST", array("secret"=>RECAPTCHA_SECRET_KEY,"response"=>$_REQUEST['g-recaptcha-response'],"remoteip"=>$_SERVER['REMOTE_ADDR']));
        $json = json_decode($res,true);

        if ($json['success']) {
                if (preg_match(EMAIL_RE, $email)) {
                        if (strlen($password) >= 8) {
                                $pw = hash("sha512", SALT.$password);
                                
                                $count = $cpdo->lookup("SELECT COUNT(*) FROM `s_users` WHERE `email`=".$cpdo->quote($email)."");
                                if ($count < 1) {
                                        $cpdo->query("INSERT INTO `s_users` (`email`,`password`) VALUES (".$cpdo->quote($email).",".$cpdo->quote($pw).")");
                                        $user = $cpdo->fetchAssoc("SELECT * FROM `s_users` WHERE `id`=".$cpdo->quote($cpdo->insertID()));
                                        $_SESSION['user'] = $user[0];
                                        header("Location: ".BASE_URL);
                                        exit();
                                } else
                                        $create_error = "An account with the specified email address already exists.";
                        } else
                                $create_error = "The password must contain at least eight characters.";
                } else
                        $create_error = "A valid email address is required.";
        } else
                $create_error = "There was an error with the captcha.";
        
}



define("ACCESS", isset($_SESSION['user']));

if (!ACCESS) {

        if (isset($_REQUEST['a']) && $_REQUEST['a'] == "forgot") {
                if (isset($_REQUEST['email'])) {
                        $res = curlRequest("https://www.google.com/recaptcha/api/siteverify", "POST", array("secret"=>RECAPTCHA_SECRET_KEY,"response"=>$_REQUEST['g-recaptcha-response'],"remoteip"=>$_SERVER['REMOTE_ADDR']));
                        $json = json_decode($res,true);

                        if ($json['success']) {
                                $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : "";
                                if (preg_match(EMAIL_RE, $email)) {
                                        $forgot_success = "If an account exists with this email address, you will receive an email with a password reset link.";
                                        $rand = randString(128);
                                        $res = $cpdo->fetchAssoc("SELECT * FROM `s_users` WHERE `email`=".$cpdo->quote($email)."");

                                        if (count($res) > 0) {
                                                $cpdo->query("INSERT INTO `s_forgot` (`user_id`,`token`,`expiration`) VALUES (".$cpdo->quote($res[0]['id']).",".$cpdo->quote($rand).",UTC_TIMESTAMP()+INTERVAL 24 HOUR)");
                                                $id = $cpdo->insertID();
                                                
                                                $mail = new PHPMailer();
                                                $mail->IsHtml(false);
                                                $mail->setFrom(SUPPORT_EMAIL);
                                                $mail->addAddress($res[0]['email']);
                                                $mail->Subject = "Password Reset";
                                                $mail->Body = "Click on the link below to reset your password.\n\n".BASE_URL."forgot?token=".$rand."&id=".$id;
                                                $mail->send();
                                        }
                                } else
                                        $forgot_error = "A valid email address is required.";
                        } else
                                $forgot_error = "There was an error with the captcha.";
                } else if (isset($_REQUEST['token'])) {
                        $forgot_token = isset($_REQUEST['token']) ? $_REQUEST['token'] : "";
                        $forgot_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : "";
                        
                        $res = $cpdo->fetchAssoc("SELECT * FROM `s_forgot` WHERE `token`=".$cpdo->quote($forgot_token)." AND `id`=".$cpdo->quote($forgot_id)." AND `expiration`>UTC_TIMESTAMP()");
                        if (count($res) > 0) {
                                $forgot_email = $cpdo->lookup("SELECT `email` FROM `s_users` WHERE `id`=".$cpdo->quote($res[0]['user_id']));
                                $forgot_reset = true;
                                
                                if (isset($_REQUEST['password'])) {
                                        $password = isset($_REQUEST['password']) ? $_REQUEST['password'] : "";
                                        if (strlen($password) >= 8) {
                                                $pw = hash("sha512", SALT.$password);
                                                $cpdo->query("UPDATE `s_users` SET `password`=".$cpdo->quote($pw)." WHERE `id`=".$cpdo->quote($res[0]['user_id']));
                                                $user = $cpdo->fetchAssoc("SELECT * FROM `s_users` WHERE `id`=".$cpdo->quote($res[0]['user_id']));
                                                $_SESSION['user'] = $user[0];
                                                header("Location: ".BASE_URL);
                                                exit();
                                        } else 
                                                $forgot_error = "The password must contain at least eight characters.";
                                }
                        } else
                                $forgot_error = "This is not a valid password reset link.  Reset links expire after 24 hours.  You can request another reset link below.";
                }
?><!DOCTYPE html>
<html>
<head>
        <meta charset='utf-8'>
        
        <title><?php echo TITLE; ?></title>
        <meta name='keywords' content="<?php echo KEYWORDS; ?>">
        <meta name='description' content="<?php echo DESCRIPTION; ?>">
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <link rel='shortcut icon' href='favicon.ico'>

        <script src='https://www.google.com/recaptcha/api.js'></script>
        
        <script src="<?php echo BASE_URL; ?>includes/jquery/jquery-2.2.1.min.js"></script>
        <link href='<?php echo BASE_URL; ?>includes/bootstrap/bootstrap.min.css' rel='stylesheet' type='text/css'>
        <link href='<?php echo BASE_URL; ?>includes/bootstrap/bootstrap-theme.min.css' rel='stylesheet' type='text/css'>
        <link href='<?php echo BASE_URL; ?>includes/bootstrap/animate.css' rel='stylesheet' type='text/css'>
        <link href='<?php echo BASE_URL; ?>includes/bootstrap/bootstrap-toggle.min.css' rel='stylesheet' type='text/css'>
        <script src="<?php echo BASE_URL; ?>includes/bootstrap/bootstrap.min.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/bootstrap/bootbox.min.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/bootstrap/bootstrap-notify.min.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/bootstrap/bootstrap-toggle.min.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/marked/marked.min.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/markdown/markdown.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/seedrandom/seedrandom.min.js"></script>
        
        
        
        <script src="<?php echo BASE_URL; ?>includes/ace/src/ace.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/ace/src/theme-chrome.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/ace/src/mode-javascript.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/ace/src/mode-markdown.js"></script>
        
        <script src="<?php echo BASE_URL; ?>includes/scripts/editor.js"></script>
        
        <link href='<?php echo BASE_URL; ?>includes/styles/styles.css' rel='stylesheet' type='text/css'>
        <script src="<?php echo BASE_URL; ?>includes/scripts/general.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/scripts/s.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/scripts/story.js"></script>
        
</head>
<body>
        <div id="bg"></div>

        <div id="container">
                <div id="forgot">
                        <form method="post" action="<?php echo BASE_URL."forgot"; ?>">
                                <h1>Forgot your password?</h1>
                                <?php if ($forgot_reset) { ?>
                                        <?php if ($forgot_error) { ?><div id="forgot-error"><?php echo $forgot_error; ?></div><?php } ?>
                                        <div><input type="hidden" name="token" value="<?php echo $forgot_token; ?>"></div>
                                        <div><input type="hidden" name="id" value="<?php echo $forgot_id; ?>"></div>
                                        <div><span>Email</span><input type="text" name="email" value="<?php echo $forgot_email; ?>" disabled></div>
                                        <div><span>Password</span><input type="password" name="password"></div>
                                        <div><span>&nbsp;</span><input type="submit" value="Reset Password"></div>
                                <?php } else if ($forgot_success) { ?>
                                        <div id="forgot-success"><?php echo $forgot_success; ?></div>
                                <?php } else { ?>
                                        <?php if ($forgot_error) { ?><div id="forgot-error"><?php echo $forgot_error; ?></div><?php } ?>
                                        <div><span>Email</span><input type="text" name="email"></div>
                                        <div class="g-recaptcha" data-sitekey="6Ld10xoTAAAAALkq-ZFyPktZAfiyMOqEqkp_63Sm"></div>
                                        <div><span>&nbsp;</span><input type="submit" value="Reset Password"></div>
                                <?php } ?>
                        </form>
                </div>
                
                <div class="cb"></div>
        </div>

</body>
</html><?php

                exit();
        } else {
?><!DOCTYPE html>
<html>
<head>
        <meta charset='utf-8'>
        
        <title><?php echo TITLE; ?></title>
        <meta name='keywords' content="<?php echo KEYWORDS; ?>">
        <meta name='description' content="<?php echo DESCRIPTION; ?>">
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <link rel='shortcut icon' href='favicon.ico'>

        <script src='https://www.google.com/recaptcha/api.js'></script>
        
        <script src="<?php echo BASE_URL; ?>includes/jquery/jquery-2.2.1.min.js"></script>
        <link href='<?php echo BASE_URL; ?>includes/bootstrap/bootstrap.min.css' rel='stylesheet' type='text/css'>
        <link href='<?php echo BASE_URL; ?>includes/bootstrap/bootstrap-theme.min.css' rel='stylesheet' type='text/css'>
        <link href='<?php echo BASE_URL; ?>includes/bootstrap/animate.css' rel='stylesheet' type='text/css'>
        <link href='<?php echo BASE_URL; ?>includes/bootstrap/bootstrap-toggle.min.css' rel='stylesheet' type='text/css'>
        <script src="<?php echo BASE_URL; ?>includes/bootstrap/bootstrap.min.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/bootstrap/bootbox.min.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/bootstrap/bootstrap-notify.min.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/bootstrap/bootstrap-toggle.min.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/marked/marked.min.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/markdown/markdown.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/seedrandom/seedrandom.min.js"></script>
        
        
        
        <script src="<?php echo BASE_URL; ?>includes/ace/src/ace.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/ace/src/theme-chrome.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/ace/src/mode-javascript.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/ace/src/mode-markdown.js"></script>
        
        <script src="<?php echo BASE_URL; ?>includes/scripts/editor.js"></script>
        
        <link href='<?php echo BASE_URL; ?>includes/styles/styles.css' rel='stylesheet' type='text/css'>
        <script src="<?php echo BASE_URL; ?>includes/scripts/general.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/scripts/s.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/scripts/story.js"></script>
        
<script>                
        window.onload = function(e) {
                s.story_id = 0;
                s.start_id = "start";
                s.cards = [{id:"start",text:
"# A New Story\n\n"+
"## by Marcel\n\n"+
"![swirl](https://pixabay.com/static/uploads/photo/2016/01/10/13/59/swirl-1131824_960_720.png)\n\n"+
">### {{random:If a little dreaming is dangerous, the cure for it is not to dream less but to dream more, to dream all the time.->The real voyage of discovery consists not in seeking new lands but seeing with new eyes.}}\n\n"+
"---\n\n"+
"{{caption:Click here to view a caption.->Let us be grateful to people who make us happy; they are the charming gardeners who make our souls blossom.}}\n\n"+
"---\n\n"+
"My **favorite** color is: {{cycle:color->Red->Orange->Yellow->Green->Blue->Indigo->Violet}}\n\n"+
"---\n\n"+
"{{sequence:val->Click here to continue.->Click again.->End of sequence.  [Proceed](proceed).}}\n\n"+
"---\n\n"+
"{{random:a->[Go to location one.](one)->[Go to location two.](two)}}\n\n"+
"---\n\n"+
"* item one\n"+
"* item two\n"+
"* item three\n"+
" * subitem one\n\n\n"+
"1. item one\n"+
"2. item one\n"+
"3. item one\n"+
" 1. subitem one\n\n\n"+
"    window.onload = function() {\n"+
"        window.alert(\"Hello, World!\");\n"+
"    };\n"



                ,code:""}];
                s.container = document.getElementById('story');
                s.animate = false;
                        
                story.start(s.start_id);
                
                var text_editor = ace.edit("experiment");
                text_editor.setTheme("ace/theme/chrome");
                var MarkdownMode = ace.require("ace/mode/markdown").Mode;
                text_editor.session.setMode(new MarkdownMode());
                        
                text_editor.setFontSize(14);
                text_editor.setValue(s.cards[0].text, 1);
                text_editor.focus();
                
                text_editor.on('change', function() {
                        s.cards[0].text = text_editor.getValue();
                        localStorage.setItem("random-"+s.story_id, Math.random());
                        localStorage.setItem("clicks-"+s.story_id, '[]');
                        story.start(s.start_id);
                });
        };
</script>
</head>
<body id="index">
        <div id="bg"></div>

        <div id="container">
                <div id="create">
                        <form method="post" action="<?php echo BASE_URL."create"; ?>">
                                <h1>Create an account.</h1>
                                <?php if ($create_error) { ?><div id="create-error"><?php echo $create_error; ?></div><?php } ?>
                                <div><span>Email</span><input type="text" name="email"></div>
                                <div><span>Password</span><input type="password" name="password"></div>
                                <div class="g-recaptcha" data-sitekey="6Ld10xoTAAAAALkq-ZFyPktZAfiyMOqEqkp_63Sm"></div>
                                <div><span>&nbsp;</span><input type="submit" value="Create Account"></div>
                        </form>
                </div>
                
                <div id="login">
                        <form method="post" action="<?php echo BASE_URL."login"; ?>">
                                <h1>Log in to <?php echo TITLE; ?>.</h1>
                                <?php if ($login_error) { ?><div id="login-error"><?php echo $login_error; ?></div><?php } ?>
                                <div><span>Email</span><input type="text" name="email"></div>
                                <div><span>Password</span><input type="password" name="password"></div>
                                <div><span>&nbsp;</span><input type="submit" value="Log In"></div>
                                <div><a href="<?php echo BASE_URL."forgot"; ?>">Forgot your password?</a></div>
                        </form>
                </div>
                
                <div class="cb"></div>
                <div style="margin-top:64px;margin-bottom:24px;border-bottom:1px solid #ccc;">Experiment below.</div>
                <div id="experiment"></div>
                <div id="story"></div>
                <div class="cb"></div>
        </div>

</body>
</html><?php

                exit();
        }
}

require_once("action.php");

?><!DOCTYPE html>
<html>
<head>
        <meta charset='utf-8'>
        
        <title><?php echo TITLE; ?></title>
        <meta name='keywords' content="<?php echo KEYWORDS; ?>">
        <meta name='description' content="<?php echo DESCRIPTION; ?>">
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <link rel='shortcut icon' href='favicon.ico'>

        <script src='https://www.google.com/recaptcha/api.js'></script>
        
        <script src="<?php echo BASE_URL; ?>includes/jquery/jquery-2.2.1.min.js"></script>
        <link href='<?php echo BASE_URL; ?>includes/bootstrap/bootstrap.min.css' rel='stylesheet' type='text/css'>
        <link href='<?php echo BASE_URL; ?>includes/bootstrap/bootstrap-theme.min.css' rel='stylesheet' type='text/css'>
        <link href='<?php echo BASE_URL; ?>includes/bootstrap/animate.css' rel='stylesheet' type='text/css'>
        <link href='<?php echo BASE_URL; ?>includes/bootstrap/bootstrap-toggle.min.css' rel='stylesheet' type='text/css'>
        <script src="<?php echo BASE_URL; ?>includes/bootstrap/bootstrap.min.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/bootstrap/bootbox.min.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/bootstrap/bootstrap-notify.min.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/bootstrap/bootstrap-toggle.min.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/marked/marked.min.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/markdown/markdown.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/seedrandom/seedrandom.min.js"></script>
        
        
        
        <script src="<?php echo BASE_URL; ?>includes/ace/src/ace.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/ace/src/theme-chrome.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/ace/src/mode-javascript.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/ace/src/mode-markdown.js"></script>
        
        <script src="<?php echo BASE_URL; ?>includes/scripts/editor.js"></script>
        
        <link href='<?php echo BASE_URL; ?>includes/styles/styles.css' rel='stylesheet' type='text/css'>
        <script src="<?php echo BASE_URL; ?>includes/scripts/general.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/scripts/s.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/scripts/story.js"></script>
        
        <script>
        var title = <?php echo json_encode(TITLE); ?>;
        window.onload = editor.init;
        </script>

</head>
<body id="editor">
        <div id="bg"></div>

        <div id="create-story">Create Story</div>
        <div id="load-story">Load Story</div>
        <div id="save-story">Save Story</div>
        <div id="render-story">Render Story</div>
        <div id="publish-story"><input type="checkbox" id="publish-toggle" data-toggle="toggle" data-size="mini" data-width="96" data-on="Published" data-off="Unpublished" data-onstyle="success" data-offstyle="danger"> <a href="" id="publish-link" target="_blank" style="position:relative;left:4px;top:4px;">LINK</a></div>
        <div id="rename-story">Rename Story</div>
        <div id="delete-story">Delete Story</div>
        <div id="logout">Logout</div>
        
        <div id="edit"><svg id="svg" style="height:100%;width:100%;" xmlns="http://www.w3.org/2000/svg"></svg><div id="edit-content"></div></div>
        
        <div id="render"></div>
        
        <div id="edit-card">
                <input id="edit-card-id">
                <div id="edit-card-text-heading">Story text in Markdown.</div>
                <div id="edit-card-code-heading">Optional JavaScript to execute prior.</div>
                <div class="cb"></div>
                <div id="edit-card-text"></div>
                <div id="edit-card-code"></div>
                <div class="cb"></div>
                <span id="edit-card-save">Save Card</span>
                <span id="edit-card-remove">Remove Card</span>
                <span id="edit-card-cancel">Cancel</span>
                <span id="edit-card-start">Set Start</span>
        </div>

</body>
</html>