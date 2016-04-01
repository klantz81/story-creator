<?php

if (isset($_REQUEST['a']) && $_REQUEST['a'] == "create") {
        $json = array(
                        "start_id"=>"Introduction",
                        "cards"=>array(
                                array(
                                        "id"=>"Introduction",
                                        "code"=>"",
                                        "text"=>"# The Beginning\n\n### by somebody\n\n![Glade](http://images.freeimages.com/images/previews/4cf/glade-1521047.jpg)\n\nYou find yourself in a glade.\n\n[Continue walking.](Walk)  \n[Explore the glade.](Explore)\n\n",
                                        "left"=>24,
                                        "top"=>24
                                ),
                                array(
                                        "id"=>"Walk",
                                        "code"=>"",
                                        "text"=>"It is beginning to get dark.  You decide to move on.\n\n",
                                        "left"=>24 + 100 + 150,
                                        "top"=>24
                                ),
                                array(
                                        "id"=>"Explore",
                                        "code"=>"",
                                        "text"=>"You explore the glade for hidden treasures.\n\n",
                                        "left"=>24 + 100 + 150,
                                        "top"=>24 + 100 + 150
                                ),
                        )
                );
        $title = isset($_REQUEST['title']) ? $_REQUEST['title'] : "";
        
        $cpdo->query("INSERT INTO `s_stories` (`user_id`,`title`,`json`,`last_load`) VALUES (".$cpdo->quote($_SESSION['user']['id']).",".$cpdo->quote($title).",".$cpdo->quote(json_encode($json)).",UTC_TIMESTAMP())");
        
        header("Content-type:text/javascript");
        echo json_encode(array("success"=>true,"id"=>$cpdo->insertID()));
        exit();
}

if (isset($_REQUEST['a']) && $_REQUEST['a'] == "load") {
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
        
        if ($id > 0)
                $json = $cpdo->lookup("SELECT `json` FROM `s_stories` WHERE `id`=".$cpdo->quote($id)." AND `user_id`=".$cpdo->quote($_SESSION['user']['id']));
        else {
                $id = $cpdo->lookup("SELECT `id` FROM `s_stories` WHERE `user_id`=".$cpdo->quote($_SESSION['user']['id'])." ORDER BY `last_load` DESC LIMIT 1");
                $json = $cpdo->lookup("SELECT `json` FROM `s_stories` WHERE `id`=".$cpdo->quote($id)." AND `user_id`=".$cpdo->quote($_SESSION['user']['id']));
        }

        $title = $cpdo->lookup("SELECT `title` FROM `s_stories` WHERE `id`=".$cpdo->quote($id)." AND `user_id`=".$cpdo->quote($_SESSION['user']['id']));
        $published = $cpdo->lookup("SELECT `published` FROM `s_stories` WHERE `id`=".$cpdo->quote($id)." AND `user_id`=".$cpdo->quote($_SESSION['user']['id']));
        
        $cpdo->query("UPDATE `s_stories` SET `last_load`=UTC_TIMESTAMP() WHERE `id`=".$cpdo->quote($id)." AND `user_id`=".$cpdo->quote($_SESSION['user']['id']));
        
        header("Content-type:text/javascript");
        echo json_encode(array("success"=>true,"json"=>$json,"id"=>$id,"title"=>$title,"published"=>$published));
        exit();
}

if (isset($_REQUEST['a']) && $_REQUEST['a'] == "save") {
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
        $cpdo->query("UPDATE `s_stories` SET `json`=".$cpdo->quote($_REQUEST['json'])." WHERE `id`=".$cpdo->quote($id)." AND `user_id`=".$cpdo->quote($_SESSION['user']['id']));
        
        header("Content-type:text/javascript");
        echo json_encode(array("success"=>true));
        exit();
}

if (isset($_REQUEST['a']) && $_REQUEST['a'] == "publish") {
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
        $cpdo->query("UPDATE `s_stories` SET `published`=".$cpdo->quote($_REQUEST['value'])." WHERE `id`=".$cpdo->quote($id)." AND `user_id`=".$cpdo->quote($_SESSION['user']['id']));
        
        header("Content-type:text/javascript");
        echo json_encode(array("success"=>true));
        exit();
}

if (isset($_REQUEST['a']) && $_REQUEST['a'] == "rename") {
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
        $cpdo->query("UPDATE `s_stories` SET `title`=".$cpdo->quote($_REQUEST['title'])." WHERE `id`=".$cpdo->quote($id)." AND `user_id`=".$cpdo->quote($_SESSION['user']['id']));
        
        header("Content-type:text/javascript");
        echo json_encode(array("success"=>true));
        exit();
}

if (isset($_REQUEST['a']) && $_REQUEST['a'] == "remove") {
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
        $cpdo->query("DELETE FROM `s_stories` WHERE `id`=".$cpdo->quote($id)." AND `user_id`=".$cpdo->quote($_SESSION['user']['id']));
        
        header("Content-type:text/javascript");
        echo json_encode(array("success"=>true));
        exit();
}

if (isset($_REQUEST['a']) && $_REQUEST['a'] == "list") {
        $list = $cpdo->fetchAssoc("SELECT `title`,`id` FROM `s_stories` WHERE `user_id`=".$cpdo->quote($_SESSION['user']['id'])." ORDER BY `title`");
        
        header("Content-type:text/javascript");
        echo json_encode(array("success"=>true,"list"=>$list));
        exit();
}


