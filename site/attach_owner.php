<?php
    include_once("includes/header.inc");
    include_once("players.class.php");
    include_once("config.php");

    requireLoggedPlayer("You have to be logged with the player credential to attach the boat.");
    if (getLoggedPlayerObject()->hasMaxBoats() ) {
        printErrorAndDie("Restriction", "You already reached the maximum boats per player");
    }

    $actionattach = get_cgi_var("claimownership");
    $boatpseudo = htmlentities(get_cgi_var("boatpseudo"));
    $boatpassword = get_cgi_var("boatpassword");

    function printAttachmentSummary($boatid = "", $boatpseudo = "") {
        $player = getLoggedPlayerObject();
        echo "<h2>".getLocalizedString("Attachment to this account")."</h2>";
        echo "<ul>";
            echo "<li>".getLocalizedString("email")." : ".$player->email."</li>";
            echo "<li>".getLocalizedString("playername")." : ".$player->playername."</li>";
        echo "</ul>";
        echo "<h2>".getLocalizedString("Boat to attach")."</h2>";
        echo "<ul>";
            echo "<li>".getLocalizedString("Boat id")." : ".$boatid."</li>";
            echo "<li>".getLocalizedString("Boat login")." : ".$boatpseudo."</li>";
        echo "</ul>";

    }

    function printFormRequest($boatpseudo = "", $boatpassword = "") {
        echo "<div id=\"attachboatbox\">";
        echo "<h2>".getLocalizedString("Here you can attach your boat to your player account. Please input your credentials.")."</h2>";
?>
        <form action="#" method="post" name="attachboat">
            <input size="25" maxlength="64" name="boatpseudo" value="<?php echo $boatpseudo; ?>" />
            <span class="texthelpers"><?php echo getLocalizedString("boatpseudo"); ?></span>
            <br />
            <input size="25" maxlength="15" name="boatpassword" value="<?php echo $boatpassword; ?>" />
            <span class="texthelpers"><?php echo getLocalizedString("boatpassword"); ?></span>
            <input type="hidden" name="claimownership" value="requested" />
            <br />
            <input type="submit" />
        </form> 
        </div>
<?php
    }

        
    /* At this point :
     * - player credentials are checked
     * - we are logged in as a player
     * - the boat is not already attached to your account
     */

    if ($actionattach == "requested") { //REQUESTED
        if ($idu = checkAccount($boatpseudo, $boatpassword)) {
            $users = getUserObject($idu);
            if ($users->getOwnerId() > 0) { //no way to reattach a boat
                $player = getPlayerObject($users->getOwnerId());
                echo "<div id=\"attachboatbox\">";
                echo "<p>";
                echo getLocalizedString("Current boat is already attached to the following player :")."&nbsp;";
                echo $player->htmlPlayername();
                echo "</p></div>";
                include_once("includes/footer.inc");
                exit();
            }
            echo "<h2>".getLocalizedString("Here is your request for attaching this boat")."&nbsp;:</h2>";
            echo "<div id=\"attachboatbox-request\">";
            printAttachmentSummary($users->idusers, $users->username);
?>
            <form action="#" method="post" name="attachboat">
                <input type="hidden" name="boatpseudo" value="<?php echo $boatpseudo; ?>"/>
                <input type="hidden" name="boatpassword" value="<?php echo $boatpassword; ?>"/>
                <input type="hidden" name="claimownership" value="confirmed"/>
                <input type="submit" value="<?php echo getLocalizedString("Confirm attachment request ?"); ?>" />
            </form> 
<?php
            echo "</div>";
        } else {
            echo "<h2>".getLocalizedString("Boat account is not valid.")."</h2>";
            printFormRequest($boatpseudo, $boatpassword);
        }
    } else if ($actionattach == "confirmed") { //CONFIRMED

        if ($idu = checkAccount($boatpseudo, $boatpassword)) {
            $users = getUserObject($idu);
            if ($users->setOwnerId(getPlayerId())) {
                echo "<div id=\"attachboatbox\">";
                echo '<h2>'.getLocalizedString("Attachment successful").'.</h2>';
                printAttachmentSummary($idu, $boatpseudo);
                echo "</div>";
            } else {
                echo "<h2>".getLocalizedString("It was not possible to attach this boat. Please report this error.")."</h2>";
                if ($users->error_status) {
                    print nl2br($users->error_string);
                }
                printFormRequest($boatpseudo, $boatpassword);
           }   
       }
    } else {
        printFormRequest($boatpseudo, $boatpassword);
    }
    include_once("includes/footer.inc");
  
?>
