    var map_container = document.getElementById("svg_map");
    if (map_container) {
        var s = Snap(map_container);
        var base_url = document.getElementById("assets_dir_url").href;
        Snap.load(base_url + "map-optimized.svg", onSVGLoaded ) ;
    }

    function addMouseoverAnimation(svgElement, mouseoverStyle, mouseoutStyle, animationSpeed){
        svgElement.node.onmouseover =
            function(){
                svgElement.animate(mouseoverStyle, animationSpeed);
            };
        svgElement.node.onmouseout =
            function(){
                svgElement.animate(mouseoutStyle, animationSpeed);
            };
    }


    function onSVGLoaded( data ){
        s.append( data );
        s.svg_map
        var countries = s.selectAll("#Countries > g");
        for (var idx = 0; idx < countries.length; idx++){
            countries[idx].attr({fill: '#1973BE'});
            addMouseoverAnimation(
              countries[idx],
              {fill: "#144173"},
              {fill: '#1973BE'},
              500
            );
        }
        var cities = s.selectAll("#Towns > path");
        for (var idx = 0; idx < cities.length; idx++){
            cities[idx].attr({fill: '#faa331'});
            var bbox = cities[idx].getBBox();

            console.log(bbox);
            addMouseoverAnimation(
              cities[idx],
              {fill: "#fff0be", transform: 'translate('+(-bbox.cx*(2-1))+','+(-bbox.cy*(2-1))+') scale(2 2)'},
              {fill: "#faa331", transform: 'translate('+(-bbox.cx*(1-1))+','+(-bbox.cy*(1-1))+') scale(1 1)'},
              500
            );
        }
    }
/*
Scaling algorithm courtesy of http://stackoverflow.com/a/24179513

var bbox=elementNode.getBBox();
var cx=bbox.x+(bbox.width/2),
    cy=bbox.y+(bbox.height/2);   // finding center of element
var scalex=1.5, scaley=1.5;    // your desired scale
var saclestr=scalex+','+scaley;
var tx=-cx*(scalex-1);
var ty=-cy*(scaley-1);
var translatestr=tx+','+ty;
elementNode.setAttribute('transform','translate('+translatestr+') scale('+saclestr+')');
*/




//TODO: Check map-optimized.svg for consistency, add id attributes to towns (which format should id use and what can it be used for?)
//TODO: Should the map display any info on hovering over a location (town or LBG, to be more specific) and if so, what to display?
//TODO: Should the dots (town or LBG) also be clickable, how to connect data from the database (like homepage_url) into their svg elements?
