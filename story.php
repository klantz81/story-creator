<?php

require_once("config.php");

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;

if (isset($_REQUEST['editor']) && $_REQUEST['editor'] == "true" && ACCESS) {
        $res = $cpdo->fetchAssoc("SELECT * FROM `s_stories` WHERE `id`=".$cpdo->quote($id)." AND `user_id`=".$cpdo->quote($_SESSION['user']['id'])."");
        
} else {
        $res = $cpdo->fetchAssoc("SELECT * FROM `s_stories` WHERE `id`=".$cpdo->quote($id)." AND `published`>0");

}

?><!DOCTYPE html>
<html>
<head>
        <meta charset='utf-8'>
        
        <title><?php echo $res[0]['title']; ?></title>
        <meta name='keywords' content="">
        <meta name='description' content="">
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <link rel='shortcut icon' href='favicon.ico'>

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

        <link href='<?php echo BASE_URL; ?>includes/styles/styles.css' rel='stylesheet' type='text/css'>
        <script src="<?php echo BASE_URL; ?>includes/scripts/general.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/scripts/s.js"></script>
        <script src="<?php echo BASE_URL; ?>includes/scripts/story.js"></script>
        <script>
<?php if (count($res) > 0) { ?>
        var data = <?php echo $res[0]['json']; ?>;

        window.onload = function(e) {
                s.story_id = <?php echo $res[0]['id']; ?>;
                s.start_id = data.start_id;
                s.cards = data.cards;
                s.container = document.getElementById('story');
                        
                story.start(s.start_id);
        };
<?php } else { ?>
        window.onload = function(e) {
                document.getElementById('story').innerHTML = '<div style="padding:8px;">The requested document is not available.</div>';
        }
<?php } ?>
        </script>
</head>
<body>

<div id="dice"></div>
<style>
#dice { display:none; width:64px; height:64px; border:1px solid #999; background-color:#fff; border-radius:4px; position:absolute; left:32px; top:32px; box-shadow:0px 0px 8px 0 rgba(0,0,0,0.5) inset; }
#dice .dot { width:12px; height:12px; background-color:#333; border-radius:6px; position:absolute; margin-left:-6px; margin-top:-6px; }
</style>
<script>
var el = document.getElementById('dice');
function createDot(x, y) {
        var div = document.createElement('div');
        div.className = 'dot';
        div.style.left = x + '%';
        div.style.top = y + '%';
        el.appendChild(div);
}
function renderDie(value) {
        el.innerHTML = '';
        if (value == 6) {
                createDot(25, 25);
                createDot(25, 50);
                createDot(25, 75);
                createDot(75, 25);
                createDot(75, 50);
                createDot(75, 75);
        } else if (value == 5) {
                createDot(25, 25);
                createDot(25, 75);
                createDot(50, 50);
                createDot(75, 25);
                createDot(75, 75);
        } else if (value == 4) {
                createDot(25, 25);
                createDot(25, 75);
                createDot(75, 25);
                createDot(75, 75);
        } else if (value == 3) {
                createDot(25, 75);
                createDot(50, 50);
                createDot(75, 25);
        } else if (value == 2) {
                createDot(25, 75);
                createDot(75, 25);
        } else {
                createDot(50, 50);
        }
}
/*
var ival = setInterval(function() {
        renderDie(Math.floor(Math.random()*6) + 1);
}, 20);
*/
el.onclick = function(e) {
        clearInterval(ival);
};
</script>

<div id="story"></div>
<div id="footer"></div>
<div id="settings">Settings</div>
</body>
</html>