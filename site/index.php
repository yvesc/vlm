<?php

    include_once("includes/header.inc");

?>

<?php
    if (!isLoggedIn()) {
?>
    <div id="whatisvlmbox" class="basic">
      <h1><?php echo getLocalizedString("homeofsailing"); ?></h1>
      <p><?php echo getLocalizedString("q1"); ?></p>
      <p><?php echo getLocalizedString("a1"); ?></p>

      <p><?php echo getLocalizedString("sh1"); ?>
      <a href="http://wiki.virtual-loup-de-mer.org/" target="_vlmwiki">
          <?php echo getLocalizedString("doc"); ?>
      </a>
      <?php echo getLocalizedString("sh2"); ?>
      <a href="http://wiki.virtual-loup-de-mer.org/index.php/Les_accessoires_et_outils_autour_de_VLM" target="_outils">
      <?php echo getLocalizedString("tools"); ?>
      </a></p>

      <p><?php echo getLocalizedString("sh3"); ?>
      <a href="http://www.virtual-winds.com/forum/index.php?showforum=276" target="_forum"><?echo getLocalizedString("forum")?></a>
      <?php echo getLocalizedString("sh4"); ?>
            <?php
            echo "<a href=\"javascript:popUp('" . CHAT_SERVER_URL . "/index.php?username=" . getLoginName() . "(" . getLoginId() . ")". "','chat')\">";
            echo getLocalizedString("tchat");
            ?>
      </a></p>


    </div>
<?php
    } else {
        $users = new Users(getLoginId());
        if ( $users->engaged != 0 ) {
            include("abandon_race.php");
        }
    }
?>    

    <div id="oneoneonebox" class="basic">
      <h1 class="info"><?php echo getLocalizedString("one-one-one"); ?></h1>
    </div>

    <div id="raceslistbox" class="basic">
      <?php include "includes/raceslist.inc"; ?>
    </div>

    <div id="time" class="basic">
      <?php
          lastUpdate($strings, $lang);
      ?>
    </div>

    <div id="userstatsbox" class="basic">

<?php
    // Nombre d'inscrits sur VLM
    $querynbu = "SELECT count(*) as nbu FROM users where idusers >0";
    $resultnbu = mysql_query($querynbu) or die("Query [$querynbu] failed \n");

    $row = mysql_fetch_array($resultnbu, MYSQL_ASSOC);
    printf( "<h4>" . getLocalizedString("nbplayers"). "</h4>", $row['nbu']);
?>
    </div>
    
    <div id="joinvlmbox"  class="basic">
      <h1 class="info">Rejoignez Virtual-Loup-De-Mer.Org, c'est gratuit !</h1>;
    </div>

<?php
    include_once("includes/footer.inc");
?>
