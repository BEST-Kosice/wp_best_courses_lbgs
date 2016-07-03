function startgmaps(){
    { 
        var a = !1, b = $(window);
        $("body")
    }
    a || (startgooglemaps(), a = !0),
    setTimeout(function(){
        b.on("scroll", function(){
                b.scrollTop() > 0 && !a && (startgooglemaps(), a = !0)
        })
    }, 1e3)
}

function startgooglemaps(){
    window.google && google.maps ? initializeMap() : lazyLoadGoogleMap()
}

function initialize(){
    var a={
        zoom:17,
        scrollwheel:!1,
        mapTypeControl:!1,
        center:new google.maps.LatLng(48.7300311,21.244647100000066),
        styles:[
            {   featureType : "poi", elementType : "labels.text.fill", stylers : [ {color : "#747474"}, {lightness : "23"} ]   },
            {   featureType : "poi.attraction", elementType : "geometry.fill", stylers : [ {color : "#f38eb0"} ]   },
            {   featureType : "poi.government", elementType : "geometry.fill", stylers : [ {color : "#ced7db"} ]   },
            {   featureType : "poi.medical", elementType : "geometry.fill", stylers : [ {color : "#ffa5a8"} ]   },
            {   featureType : "poi.park", elementType : "geometry.fill", stylers : [ {color : "#c7e5c8"} ]   },
            {   featureType : "poi.place_of_worship", elementType : "geometry.fill", stylers : [ {color : "#d6cbc7"} ]   },
            {   featureType : "poi.school", elementType : "geometry.fill", stylers : [ {color : "#c4c9e8"} ]   },
            {   featureType : "poi.sports_complex", elementType : "geometry.fill", stylers : [ {color : "#b1eaf1"} ]   },
            {   featureType : "road", elementType : "geometry", stylers : [ {lightness : "100"} ]   },
            {   featureType : "road", elementType : "labels", stylers : [ {visibility : "off"}, {lightness : "100"} ]   },
            {   featureType : "road.highway", elementType : "geometry.fill", stylers : [ {color : "#ffd4a5"} ]   },
            {   featureType : "road.arterial", elementType : "geometry.fill", stylers : [ {color : "#ffe9d2"} ]   },
            {   featureType : "road.local", elementType : "all", stylers : [ {visibility : "simplified"} ]   },
            {   featureType : "road.local", elementType : "geometry.fill", stylers : [ {weight : "3.00"} ]   },
            {   featureType : "road.local", elementType : "geometry.stroke", stylers : [ {weight : "0.30"} ]   },
            {   featureType : "road.local", elementType : "labels.text", stylers : [ {visibility : "on"} ]   },
            {   featureType : "road.local", elementType : "labels.text.fill", stylers : [ {color : "#747474"}, {lightness : "36"} ]   },
            {   featureType : "road.local", elementType : "labels.text.stroke", stylers : [ {color : "#e9e5dc"}, {lightness : "30"} ]   },
            {   featureType : "transit.line", elementType : "geometry", stylers : [ {visibility : "on"}, {lightness : "100"} ]   },
            {   featureType : "water", elementType : "all", stylers : [ {color : "#d2e7f7"} ]   }
        ]
    },
    b=document.getElementById("map"),
    c=new google.maps.Map(b,a),
    d=(new google.maps.Marker(
            {
                position: new google.maps.LatLng(48.7300311,21.244647100000066),
                map: c,
                labelContent: "$425K",
                labelAnchor: new google.maps.Point(22,0),
                labelClass: "labels",
                labelStyle: {opacity:.75}
            }
        ),
        c.getCenter()
    );
         
    google.maps.event.addDomListener(window,"resize",function(){
            c.setCenter(d)
        }
    )
}

function lazyLoadGoogleMap(){
    $.getScript("http://maps.google.com/maps/api/js?sensor=true&callback=initializeMap").done(function(){}).fail(function(){})
}

function initializeMap(){
    initialize(params)
}










WebFontConfig={
    google:{
        families:["Ubuntu+Condensed::latin,latin-ext"]
    },
    custom:{
        families:["FontAwesome"],
        urls:["http://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css"]
    }
},
function(){
    var a=document.createElement("script");
    a.src=("https:"==document.location.protocol?"https":"http")+"://ajax.googleapis.com/ajax/libs/webfont/1.6.16/webfont.js",
        a.type="text/javascript",a.async="true";var b=document.getElementsByTagName("script")[0];
    b.parentNode.insertBefore(a,b)
}();
var bLazyslid=new Blazy(
    {
        loadInvisible:!0,
        selector:".b-lazy-slid",
        container:"#js-blazyslid"
    }
  ),
  bLazypartners=new Blazy(
    {
        selector:".b-lazy-slid",
        container:"#js-partners-carusel"
    }
  ),
  bLazy=new Blazy(
    {
        breakpoints:[
            {width:420,src:"data-src-small"}
        ],
        success:function(a){
            setTimeout(function(){
                var b=a.parentNode;
                b.className=b.className.replace(/\bloading\b/,"")}, 200)
        }
    }
);
$(document).ready(function(){
    "Daniel Demečko"!==$('a[rel="designer"]').html()&&$("body").empty(),
    $("#js-partners-carusel").carousel({interval:3e3}),
    console.log("Design and development http://demecko.com"),
    $("#newsletter").ajaxChimp(
        {
            url:"http://tuke.us13.list-manage.com/subscribe/post?u=610dc0e76119fdc2ddb7f785d&amp;id=262e3c2322",
            callback:function(){
                $("#newsletter button").parent().hide(),
                $("#newsletter input").hide(),
                $("#newsletter label").addClass("smaller"),
                $("#newsletter .col-md-8").removeClass("col-md-8"),
                $("#newsletter").delay(3e3).hide(500)
            }
        }
    )
});
var params;
document.getElementById("map")&&startgmaps();
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            