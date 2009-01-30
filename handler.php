<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2008. All Rights Reserved.
 *
 *
 *  This file is part of OBBLM.
 *
 *  OBBLM is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  OBBLM is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

session_start();
error_reporting(E_ALL);

require('header.php'); // Includes and constants.
$conn = mysql_up(false);

if (!isset($_GET['type'])) {
    fatal("Sorry. Don't know what to do. Please specify 'type' via GET.");
}
    
switch ($_GET['type'])
{
    /***************
     *  Message
     ***************/
    case 'msg':
        ?>
        <html>
            <head>
                <title>OBBLM message handler</title>
            </head>
            <script type="text/javascript">
                function done() {
                    top.opener.location.reload(true);
                    top.close();
                    return;
                }
            </script>
            <body style="font: 12px Tahoma;">
        <?php
        // Is coach an admin?
        if (!isset($_SESSION['logged_in']) || !is_object($coach = new Coach($_SESSION['coach_id'])) || !$coach->admin) {
            fatal("Only admins may use this feature.");
        }
        // Action specified?
        if (!isset($_GET['action'])) {
            fatal("Sorry. Don't know what to do. Please specify 'action' via GET.");
        }
        $title = '';
        $body = '';
        $msg = null;
        $status = false;
        
        switch ($_GET['action'])
        {
            case 'edit':
                $msg = new Message($_GET['msg_id']);
                $title = $msg->title;
                $body = $msg->message;
                // Fall-through!

            case 'new':
                if (isset($_POST['message']) && isset($_POST['title']) && !empty($_POST['message']) && !empty($_POST['title'])) {
                    if (get_magic_quotes_gpc()) {
                        $_POST['title']   = stripslashes($_POST['title']);
                        $_POST['message'] = stripslashes($_POST['message']);
                    }
                    if (is_object($msg)) {
                        status($status = $msg->edit($_POST['title'], $_POST['message']));
                    }
                    else {
                        status($status = Message::create(array('f_coach_id' => $_SESSION['coach_id'], 'title' => $_POST['title'], 'msg' => $_POST['message'])));
                    }
                    // When have been editing show the same text we submitted in the text fields again.
                    $title = $_POST['title'];
                    $body = $_POST['message'];
                }
                ?>
                <form method="POST">
                    Title:
                    <br>
                    <textarea rows="1" cols="60" name="title"><?php echo $title;?></textarea>
                    <br><br>
                    Message:
                    <br>
                    <textarea name="message" rows="13" cols="60"><?php echo $body;?></textarea>
                    <br><br>
                    <input type="submit" <?php echo ($status) ? 'value="Close window" OnClick="done(); return false;"' : 'value="Save"'?>>
                </form>
                <?php
                break;

            case 'delete':
                echo "<b>Delete message</b><br><br>\n";
                if (!isset($_GET['msg_id']) || !is_numeric($_GET['msg_id']) || !is_object($msg = new Message($_GET['msg_id']))) {
                    fatal("Sorry. I need a proper 'msg_id' GET field.");
                }
                status($msg->delete());
                ?>
                <input type='button' value='Close window' OnClick='done();'>
                <SCRIPT LANGUAGE="JavaScript">
                    done();
                    window.close();
                </SCRIPT>
                <?php
                break;

            default:
                fatal("Sorry. I don't know what the action '$_GET[type]' means.\n");
        }
        ?>
        </body>
        </html>
        <?php
        break;
        
    /***************
     *  GD-bracket
     ***************/
    case 'gdbracket':
        if (!isset($_GET['tour_id']) || !is_numeric($_GET['tour_id']) || !is_object($t = new Tour($_GET['tour_id']))) {
            fatal("Sorry, invalid tournament ID.");
        }

        // Make the K.O. bracket ready.
        $t->update();

        if (get_class($t->koObj) != 'KnockoutGD') {
            fatal("Sorry. GD-lib support is required to draw tournament bracket.");
        }

        // Create team ID <--> Name translation
        $dictionary = array();
        $query = "SELECT team_id, name FROM teams";
        $result = mysql_query($query);
        while ($row = mysql_fetch_assoc($result))
            $dictionary[$row['team_id']] = $row['name'];

        // Install translation.
        $t->koObj->renameCompets($dictionary);

        // Draw image.
        $im = $t->koObj->getImage($settings['league_name'] . ' Blood Bowl League');
        header('Content-type: image/png');
        imagepng($im);
        imagedestroy($im);
        break;

    /***************
     *  PDF-roster
     ***************/
    case 'roster':
        if (class_exists('FPDF') && class_exists('BB_PDF')) {
            fpdf_roster();
        }
        else {
            fatal("Sorry. FPDF support is required for this feature to work.");        
        }
        break;
        
    default:
        fatal("Sorry. I don't know what the type '$_GET[type]' means.\n");
}

mysql_close($conn); 

?>
