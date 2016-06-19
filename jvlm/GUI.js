// On ready get started with vlm management

$(document).ready(
  function(){
    
    // Init maps
    OLInit();
    
    // Load translation strings
    InitLocale();
    
    // Init Menus()
    InitMenusAndButtons();
    
    
    // Init event handlers
    // Login button click event handler
    $("#LoginButton").click( 
      function()
      {
        OnLoginRequest();
      }
    );   

    // Do fixed heading
    $("#PM_Heading").click(
      function()
      {
        SendVLMBoatOrder(PM_Mode.HEADING,$("#PM_Heading").text)
      }

    );
    
    $("#logindlgButton").on ('click',
          function (e)
          {
            // Get localization key to figure out action
            var i=0;
            switch (e.currentTarget.attributes["I18n"].nodeValue)
            {
              case "login":
                if (_IsLoggedIn)
                {
                  $("#Menu").toggle();
                  Logout();
                }
                else
                {
                  $("#LoginForm").modal('show');
                  //OnLoginRequest();
                }

            }
            
          }
          
    
    );
    
    // Set BoatSelector as JQuery UI Selector 
    // Handle boat selector selection change
    //
    $("#BoatSelector").selectmenu();  
    $("#BoatSelector").on( "selectmenuselect", function(event,ui)
      {
        SetCurrentBoat(GetBoatFromIdu(ui.item.value));
      }
    );
     
    // CheckLogin
    CheckLogin();
    
     
  }  
);

function OLInit() {

   //Pour tenter le rechargement des tiles quand le temps de calcul est > au timeout
    OpenLayers.IMAGE_RELOAD_ATTEMPTS = 5;

    var default_latitude = 45.5;
    var default_longitude = -30.0;
    var default_zoom = 4;

    var layeroption = {
        //sphérique
        sphericalMercator: true,
        //FIXME: voir s'il y a des effets spécifiques à certains layers
        transitionEffect: "resize",
        //pour passer l'ante-meridien sans souci
        wrapDateLine: true
    };

    //MAP

    map = new OpenLayers.Map(
            "jVlmMap", //identifiant du div contenant la carte openlayer
            MapOptions);

    //NB: see config.js file. Le layer VLM peut utiliser plusieurs sous-domaine pour paralélliser les téléchargements des tiles.
    var urlArray = tilesUrlArray;

    var vlm = new OpenLayers.Layer.XYZ(
            "VLM Layer",
            urlArray,
            layeroption
    );

    /*//Les layers Bing
    //FIXME : roads... what for ;) ?
    var bingroad = new OpenLayers.Layer.Bing({
        key: bingApiKey,
        type: "Road",
        sphericalMercator: true,
        //FIXME: voir s'il y a des effets spécifiques au layer ?
        transitionEffect: "resize",
        //pour passer l'ante-meridien sans souci
        wrapDateLine: true
    });
    var bingaerial = new OpenLayers.Layer.Bing({
        key: bingApiKey,
        type: "Aerial",
        sphericalMercator: true,
        //FIXME: voir s'il y a des effets spécifiques au layer ?
        transitionEffect: "resize",
        //pour passer l'ante-meridien sans souci
        wrapDateLine: true

    });
    var binghybrid = new OpenLayers.Layer.Bing({
        key: bingApiKey,
        type: "AerialWithLabels",
        name: "Bing Aerial With Labels",
        sphericalMercator: true,
        //FIXME: voir s'il y a des effets spécifiques au layer ?
        transitionEffect: "resize",
        //pour passer l'ante-meridien sans souci
        wrapDateLine: true
    });

    //Layer Multimap, désactivé car fonctionnement erratique
    //var mm = new OpenLayers.Layer.MultiMap( "MultiMap", layeroption);

    //Le layer openlayer classique
    //FIXME: voir les types de layers
    var wms = new OpenLayers.Layer.WMS("OpenLayers WMS",
            "http://vmap0.tiles.osgeo.org/wms/vmap0",
            {layers: 'basic', sphericalMercator: true}
    );
    */
     
    //Le calque de vent made in Vlm
    var grib = new Gribmap.Layer("Gribmap", layeroption);
    //grib.setOpacity(0.9); //FIXME: faut il garder une transparence du vent ?
    
    /*
    //Layer Google Physical
    var gphy = new OpenLayers.Layer.Google(
            "Google Physical",
            {
                type: google.maps.MapTypeId.TERRAIN,
                sphericalMercator: true,
                transitionEffect: "resize",
                wrapDateLine: true
            }
    );

    //Layer Google Hybrid
    //FIXME: faut t il vraiment le conserver ?
    var ghyb = new OpenLayers.Layer.Google(
            "Google Hybrid",
            {
                type: google.maps.MapTypeId.HYBRID,
                numZoomLevels: 20,
                sphericalMercator: true,
                transitionEffect: "resize",
                wrapDateLine: true
            }
    );

    //Layer Google Satelit
    var gsat = new OpenLayers.Layer.Google(
            "Google Satellite",
            {
                type: google.maps.MapTypeId.SATELLITE,
                numZoomLevels: 22,
                sphericalMercator: true,
                transitionEffect: "resize",
                wrapDateLine: true
            }
    );
    */

    //La minimap utilise le layer VLM
    var vlmoverview = vlm.clone();

    //Et on ajoute tous les layers à la map.
    //map.addLayers([ VLMBoatsLayer,vlm, wms, bingroad, bingaerial, binghybrid, gphy, ghyb, gsat, grib]);
    map.addLayers([ VLMBoatsLayer,vlm, grib]);
    //map.addLayers([vlm, grib]); //FOR DEBUG

    //Controle l'affichage des layers
    //map.addControl(new OpenLayers.Control.LayerSwitcher());

    //Controle l'affichage de la position ET DU VENT de la souris
    map.addControl(new Gribmap.MousePosition({gribmap: grib}));

    //Affichage de l'échelle
    map.addControl(new OpenLayers.Control.ScaleLine());

    //Le Permalink
    //FIXME: éviter que le permalink soit masqué par la minimap ?
    map.addControl(new OpenLayers.Control.Permalink('permalink'));

    //FIXME: Pourquoi le graticule est il un control ?
    map.addControl(new OpenLayers.Control.Graticule());

    //Navigation clavier
    map.addControl(new OpenLayers.Control.KeyboardDefaults());

    //Le panel de vent
    map.addControl(new Gribmap.ControlWind());

    //Evite que le zoom molette surcharge le js du navigateur
    var nav = map.getControlsByClass("OpenLayers.Control.Navigation")[0];
    nav.handlers.wheel.cumulative = false;
    nav.handlers.wheel.interval = 100;

    //Minimap
    var ovmapOptions = {
        maximized: true,
        layers: [vlmoverview]
    }
    map.addControl(new OpenLayers.Control.OverviewMap(ovmapOptions));

    //Pour centrer quand on a pas de permalink dans l'url
    if (!map.getCenter()) {
        // Don't do this if argparser already did something...
        var lonlat = new OpenLayers.LonLat(default_longitude, default_latitude);
        lonlat.transform(MapOptions.displayProjection, MapOptions.projection);
        map.setCenter(lonlat, default_zoom);
    }
}


function InitMenusAndButtons()
{
  $( "#Menu" ).menu();
  $( "#Menu" ).hide();
  
  $( "input[type=submit],button" )
      .button()
      .click(function( event ) 
        {
          event.preventDefault();
        }
      );
  
  
  // Hide all progressbars
  HidePb("#PbLoginProgress");
  HidePb("#PbGetBoatProgress");
  HidePb("#PbGribLoginProgress");
}

function ClearBoatSelector()
{
  $("#BoatSelector").empty();
}

function AddBoatToSelector(boat, isfleet)
{
  var boatclass='';
  if (boat.Engaged && isfleet)
  {
    boatclass = 'RacingBoat';
  }
  else if (boat.Engaged)
  {
    boatclass = 'RacingBSBoat';
  }
  else if (isfleet)
  {
    boatclass = 'Boat';
  }
  else
  {
    boatclass = 'BSBoat';
  }
  
  $("#BoatSelector").append($('<option />',
                                { 
                                  value: boat.IdBoat,
                                  text: boat.BoatName,
                                }
                              )
                            )
                            
  $("option[value="+ boat.IdBoat +"]").toggleClass(false).addClass(boatclass);
}

function   ShowUserBoatSelector()
{
  //$("#BoatSelector").show();
}

function ShowBgLoad()
{
  $("#BgLoadProgress").css("display","block");
}

function HideBgLoad()
{
  $("#BgLoadProgress").css("display","block");
}

function ShowPb(PBName)
{
  $(PBName).show();
  LocalizeString();
}

function HidePb(PBName)
{
  $(PBName).hide();
}

function DisplayLoggedInMenus(LoggedIn)
{
  var LoggedInDisplay;
  var LoggedOutDisplay;
  if (LoggedIn)
  {
    LoggedInDisplay="block";
    LoggedOutDisplay="none";
  }
  else
  {
    LoggedInDisplay="none";
    LoggedOutDisplay="block";
  }
  $("ul[LoggedInNav='true']").css("display",LoggedInDisplay);
  $("ul[LoggedInNav='false']").css("display",LoggedOutDisplay);
  
  $("#BoatSelector").selectmenu("refresh");
}

function UpdateInMenuBoatInfo(Boat)
{
  var NorthSouth;
  var EastWest;
  
  // Update GUI for current player
  if (Boat.VLMInfo.LON >=0)
  {
    EastWest = "E";
  }
  else
  {
    EastWest = "W";
  }
  if (Boat.VLMInfo.LAT >=0)
  {
    NorthSouth = "N";
  }
  else
  {
    NorthSouth = "S";
  }
 
  var lon = new Coords(Boat.VLMInfo.LON/1000);
  var lat = new Coords(Boat.VLMInfo.LAT/1000);

  // Create field mapping array
  var BoatFieldMappings=[];
  BoatFieldMappings.push(["#BoatLon",lon.ToString() + ' ' + EastWest]);
  BoatFieldMappings.push(["#BoatLat",lat.ToString() + ' ' + NorthSouth]);
  BoatFieldMappings.push(["#BoatSpeed",Math.round(Boat.VLMInfo.BSP * 10)/10]);
  BoatFieldMappings.push(["#BoatHeading",Math.round(Boat.VLMInfo.HDG * 10)/10]);
  BoatFieldMappings.push(["#PM_Heading",Math.round(Boat.VLMInfo.HDG * 10)/10]);
  BoatFieldMappings.push(["#BoatAvg",Math.round(Boat.VLMInfo.AVG * 10)/10 ]);
  BoatFieldMappings.push(["#BoatDNM",Math.round(Boat.VLMInfo.DNM * 10)/10 ]);
  BoatFieldMappings.push(["#BoatLoch",Math.round(Boat.VLMInfo.LOC * 10)/10 ]);
  BoatFieldMappings.push(["#BoatOrtho",Math.round(Boat.VLMInfo.ORT * 10)/10 ]);
  BoatFieldMappings.push(["#BoatLoxo",Math.round(Boat.VLMInfo.LOX * 10)/10 ]);
  BoatFieldMappings.push(["#BoatVMG",Math.round(Boat.VLMInfo.VMG * 10)/10 ]);
  BoatFieldMappings.push(["#BoatWindSpeed",Math.round(Boat.VLMInfo.TWS * 10)/10 ]);
  BoatFieldMappings.push(["#BoatWindDirection",Math.round(Boat.VLMInfo.TWD * 10)/10 ]);
  BoatFieldMappings.push(["#BoatWindAngle",Math.round(Boat.VLMInfo.TWA * 10)/10 ]);

  // Loop all mapped fields to their respective location
  for (index in BoatFieldMappings)
  {
    $(BoatFieldMappings[index][0]).text(BoatFieldMappings[index][1]);
  }
 
  // Change color depênding on windangle
  var WindColor="red"
  if (Boat.VLMInfo.TWA <0)
  {
    WindColor="lime"
  }
  $("#BoatWindAngle").css("color",WindColor);

  // Get WindAngleImage
  var wHeading=Math.round(Boat.VLMInfo.TWD * 100)/100;
  var wSpeed=Math.round(Boat.VLMInfo.TWS * 100)/100;
  var BoatType=Boat.VLMInfo.POL;
  var BoatHeading=Math.round(Boat.VLMInfo.HDG*100)/100;

   $("#ImgWindAngle").attr('src','windangle.php?wheading='+wHeading+'&boatheading='+ BoatHeading +'&wspeed=2.00&roadtoend=180&boattype='+BoatType+"&jvlm="+Boat.VLMInfo.NOW);
   $("#ImgWindAngle").css("transform","rotate("+BoatHeading+"deg)");
   $("#DeckImage").css("transform","rotate("+BoatHeading+"deg)");
    
} 