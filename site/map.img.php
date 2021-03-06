<?php
    session_start();
    include_once("config.php");
    include_once("functions.php");
    include_once("mapfunctions.php");

    $boat= htmlentities(get_cgi_var('boat', ""));
    
    // Test si connect� ou pas.
    $idusers = getLoginId() ;
    //FIXME probablement pas optimum en tant qu'ordonnancement du test
    if ( empty($boat) || !($idusers == $boat || (isPlayerLoggedIn() && in_array($boat, getLoggedPlayerObject()->getManageableBoatIdList()) ) ) ) {
        // R�cup�ration des dimensions (x et y) : valeurs mini par d�faut = 250
        $x=500;
        $y=250;
    
        $im = @imagecreate($x, $y)
              or die("Cannot Initialize new GD image stream");
        $blanc = imagecolorallocate($im, 255, 255, 255);
        $noir = imagecolorallocate($im, 0, 0, 0);
    
        // Affichage d'un "-X-" au milieu de l'image
    
        imagestring($im, 5, 20, $y/2,  "You should not do that...your IP : " . getip() , $noir);
        imagestring($im, 5, 20, $y/2+20,  "Connected : ".$idusers ." is not BOAT=(".$boat.")" , $noir);
        imagestring($im, 3, 20, $y/2+40,  "Asking a map for a boat that is not yours changes the user's prefs" , $noir);
        imagestring($im, 3, 20, $y/2+60,  "SRV = " . SERVER_NAME , $noir);
    
        // Affichage de l'image
        header("Cache-Control: max-age=0");
        header("Content-type: image/png");
        imagepng($im);
        imagedestroy($im);
        exit;
    }

    $save= (htmlentities($_GET['save']) == 'on');

    $maptype = htmlentities(get_cgi_var('maptype', 'compas'));
    if ( in_array($maptype, Array("floatingcompas", "bothcompass", "compas", "none" ) ) ) {
        setUserPref($boat, "mapTools" , $maptype, $save);
    } else {
        setUserPref($boat, "mapTools" , "compas", $save);
    }
    $list= htmlentities($_GET['list']) ;
    
    $maparea= htmlentities(get_cgi_var('maparea', round(MAPAREA_MAX/2)));
    if ($maparea <MAPAREA_MIN ) $maparea=MAPAREA_MIN;
    if ($maparea >MAPAREA_MAX ) $maparea=MAPAREA_MAX;
    setUserPref($boat, "maparea" , $maparea, $save);

    $maille = htmlentities(get_cgi_var('maille', round(MAILLE_MAX/2)));
    if ($maille < MAILLE_MIN ) $maille = MAILLE_MIN;
    if ($maille > MAILLE_MAX ) $maille = MAILLE_MAX;
    setUserPref($boat, "mapMaille" , $maille, $save);

    $idraces= htmlentities(get_cgi_var('idraces')) ;
    //if ( $idraces == 20081109 ) $list = "myboat";

    $tracks = htmlentities(get_cgi_var('tracks', 'on')) ;

    $x = htmlentities(get_cgi_var('x', 800));
    $y = htmlentities(get_cgi_var('y', 600)) ;

    // Limitation de la taille de la carte pour pas p�ter le serveur
    if ( $x > MAX_MAP_X ) $x=MAX_MAP_X;
    setUserPref($boat, "mapX" , $x, $save);
  
    if ( $y > MAX_MAP_X ) $y=MAX_MAP_X;
    setUserPref($boat, "mapY" , $y, $save);
  
    $age= htmlentities(get_cgi_var('age', 2));
    setUserPref($boat, "mapAge" , $age, $save);
  
    $estime= htmlentities(get_cgi_var('estime', 30)) ;
    setUserPref($boat, "mapEstime" , $estime, $save);

    $proj= htmlentities(get_cgi_var('proj', 'on'));
    $text= htmlentities(get_cgi_var('text', 'right'));
    $windtext = htmlentities(get_cgi_var('windtext', 'on'));
    $drawtextwp = htmlentities(get_cgi_var('drawtextwp', 'on'));

    setUserPref($boat, "mapDrawtextwp" , $drawtextwp, $save);
    // Guess real map coordinates

?>
<html>
  <head>
    <title>VLM Map (<?php echo $boat; ?>)</title>
    <link rel="stylesheet" type="text/css" href="style/<?php echo getTheme(); ?>/style.css" />
    <script>
        clicEnCours = false;
        position_x = 250 ; 
        position_y = 150 ;
        netscape = false;
        if (navigator.appName.substring(0,8) == "Netscape") {
            netscape = true;
        }
        msiesix = /MSIE 6/i.test(navigator.userAgent);
  
        function DisplayPngByBrowser ( browser, img_path, width, height )
        {
            var png_path;
            if (msiesix) {
                document.write('<img id="dynimg" src="images/site/blank.gif" style="width:'+width+'px; height:'+height+'px; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\''+img_path+'\', sizingMethod=\'scale\');" />');
            } else if (netscape) {
                document.write('<img id="dynimg" src="'+img_path+'" />');
            } else {
                document.write('<img id="dynimg" src="'+img_path+'" />');
            }
        }

        function boutonPresse()
        {
            origine_x = x - position_x;
            origine_y = y - position_y;
            clicEnCours = true;
        }
  
        function boutonRelache()
        {
            clicEnCours = false;
        }
  
        function deplacementSouris(e)
        {
            x = (netscape) ? e.pageX : event.x + document.body.scrollLeft;
            y = (netscape) ? e.pageY : event.y + document.body.scrollTop;
          
            if (clicEnCours && document.getElementById) {
                position_x = x - origine_x;
                position_y = y - origine_y;
                document.getElementById("deplacable").style.left = position_x ;
                document.getElementById("deplacable").style.top = position_y ;
            }
        }
  
        //FIXME : Useless ?
        function previousTimestamp()
        {
            vts=document.control.vts.value;
            if ( vts >0 ) vts--;
            document.control.vts.value=vts;
            //showvts();
            for (ts=0; ts<=24 ; ts++) {
                vt=eval("ts" . ts);
                document.getElementById(vt).style.display = 'none' ;
            }
            vt=eval("ts" . document.control.vts.value);
            document.getElementById(vt).style.display = '' ;
        }

        function nextTimestamp()
        {
            vts=document.control.vts.value;
            if ( vts <24 ) vts++;
            document.control.vts.value=vts;
            showvts();
        }
/*
        if (netscape) {
            document.captureEvents(Event.MOUSEMOVE);
        }
*/
        document.onmousemove = deplacementSouris;
    </script>
  </head>
  <body id="mapbody">

    <?php
      // Sauvegarde des pr�f�rences
      setUserPref($boat, "mapOpponents" , $list, $save);

      $maplayers=htmlentities($_GET['maplayers']);
      if ( $maplayers == "" ) $maplayers = "multi";
      setUserPref($boat, "mapLayers" , $maplayers, $save);

      $mapcenter=htmlentities($_GET['mapcenter']);
      if ( $mapcenter == "" ) $mapcenter = "myboat";
      setUserPref($boat, "mapCenter" , $mapcenter, $save);

      // Centrage de la carte
      // Coordonn�es bateau
      $long= htmlentities($_GET['long']);
      $long = longitudeConstraintDegrees($long);
      
      $lat= htmlentities($_GET['lat']);

      // Coordonn�es WP
      $longwp=htmlentities($_GET['longwp']);
      $longwp = longitudeConstraintDegrees($longwp);

      $latwp= htmlentities($_GET['latwp']);

      // Si centrage sur la route, on moyenne long/longwp et lat/latwp
      // Sinon rien, long/lat sont le centre de la carte
      if ( $mapcenter == "roadtowp" ) {
          $centerwp = centerDualCoord($lat, $long, $latwp, $longwp);      
          $long = longitudeConstraintDegrees($centerwp['lon']);
          $lat = $centerwp['lat'];
      } else if ( $mapcenter == "mywp" ) {
          $long = $longwp;
          $lat  = $latwp;
      } // else lat/long = ceux qu'on a d�j� (position du bateau)


      $query_string_base="lat=". $lat . "&" . 
          "long=". $long . "&" . 
          "x=". $x . "&" . 
          "y=". $y . "&" . 
          "maparea=". $maparea . "&" .
          "maille=". $maille . "&" .
          "idraces=". $idraces . "&" .
          "drawtextwp=" .$drawtextwp . "&" . 
          "proj=". $proj  ;
        $query_string = $query_string_base . "&" . 
          "seacolor=e0e0f0". "&" . 
          "tracks=". $tracks . "&" . 
          "age=". $age . "&" . 
          "estime=". $estime . "&" . 
          "list=". $list . "&" . 
          "boat=". $boat . "&" . 
          "maptype=" . $maptype . "&" .
          "text=". $text ;

    // **** And now, draw the map **** 
          
      if ( $maplayers == "merged" ) {
          $URL_MAP=MAP_SERVER_URL . "/mercator.img.php?drawortho=yes&drawwind=0&" . $query_string  ;
          echo "<img src=\"$URL_MAP\">";
      } else {
          $URL_MAP=MAP_SERVER_URL . "/mercator.img.php?drawortho=yes&drawwind=-1&" . $query_string  ;

          // **** DRAW  WIND MAPS **** 
          $timestamp=0;

          $URL_TS=MAP_SERVER_URL . "/mercator.img.php?" ;
          $URL_TS.="drawgrid=no&drawmap=no&drawrace=no&drawscale=no";
          $URL_TS.="&drawpositions=no&drawlogos=no&drawlibelle=no&drawortho=no";
          $URL_TS.="&seacolor=transparent";
          $URL_TS.="&maptype=" . $maptype ;
          $URL_TS.="&". $query_string_base ;
          $URL_TS_BASE = $URL_TS;
          $URL_TS.="&drawwind=".$timestamp;

          echo "<div id=ts".$timestamp." style=\"top:10; left:10; position:absolute; background-image:url(".$URL_MAP."&seed=".intval(time()).");\">";
          echo "  <script language=\"javascript\">";
          echo "      var path_png = DisplayPngByBrowser(navigator.appName, ' " . $URL_TS . "', " . $x . ", " . $y.");";
          echo "  </script>";
          echo "</div>";
      ?>

      <div id="windcontrollayer" class="boxonmap">

        <script language="javascript">
            <?php echo "    var url_ts_base = '".$URL_TS_BASE."';"; ?>
            function enterOffset(event)
            {
                if (event && event.keyCode == 13) {  
                    updateOffset();
                }
            }

            function updateOffset()
            {
                var path = url_ts_base+'&drawwind='+document.getElementById('griboffset').value;
                document.getElementById('dynimg').src = path;
            }

            function nextOffset()
            {
                document.getElementById('griboffset').value++;
                updateOffset();
            }

            function prevOffset()
            {
                document.getElementById('griboffset').value--;
                updateOffset();
            }

        </script>
        <input id="prevgriboffset" size="2" value="-" class="controlonmap" type="button" onClick="javascript:prevOffset();" />
        <input id="griboffset"  class="controlonmap" type="text" maxlength="2" size="2" value="0" onKeyPress="javascript:enterOffset(event);"/>h
        <input id="nextgriboffset" size="2" value="+" class="controlonmap" type="button" onClick="javascript:nextOffset();" />

   <?php
      }

      // ****  Le compas deplacable en dernier, sinon il est dessous.. *** 
      // Que met t'on sur la carte ?
      if ( $maptype == "floatingcompas" || $maptype == "bothcompass" ) {
          echo "<div id=\"deplacable\" onMouseDown=\"boutonPresse()\" onMouseUp=\"boutonRelache()\"><img src=\"images/site/compas-transparent.gif\" /></div>";
      }
    ?>


  </body>
</html>
