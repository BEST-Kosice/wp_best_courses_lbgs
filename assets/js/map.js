    var map_container = document.getElementById("lbg_map");
    if (map_container) {
        var s = Snap(map_container);
        countries_add_effects('#1973BE', "#144173", s.selectAll("#Countries > g"));
        LBGs_add_effects("#FAA331", "#FFF0BE", s.selectAll("#LBGs > #current > a > path"));
        LBGs_add_effects("#91C3EB", "#C8EBFA", s.selectAll("#LBGs > #former > a > path"));
        LBGs_add_effects("#69CD28", "#BED746", s.selectAll("#LBGs > #observer > a > path"));
    }

    // Moved country code translation to backend, keeping this code just in case it is needed
	/*var countryNames = {
            'SE' : 'Švédsko', 'DK' : 'Dánsko', 'NO' : 'Nórsko', 'IS' : 'Island', 'FI' : 'Fínsko', 'RU' : 'Rusko', 
            'BE' : 'Belgicko', 'NL' : 'Holandsko', 'GB' : 'Veľká Británia', 'IE' : 'Írsko', 'FR' : 'Francúzsko', 
            'DE' : 'Nemecko', 'AT' : 'Rakúsko', 'CH' : 'Švajčiarsko', 'LI' : 'Lichtenštajnsko', 'LU' : 'Luxembursko',
            'PL' : 'Poľsko', 'LT' : 'Litva', 'LV' : 'Lotyšsko', 'EE' : 'Estónsko' , 'UA' : 'Ukrajina', 'BY' : 'Bielorusko',
            'HR' : 'Chorvátsko', 'SK' : 'Slovensko', 'CZ' : 'Česká republika', 'HU' : 'Maďarsko', 'SI' : 'Slovinsko', 
            'BA' : 'Bosna a Hercegovina', 'RS' : 'Srbsko', 'ME' : 'Čierna hora', 'MK' : 'Maccedónsko', 'AL' : 'Albánsko',
            'RO' : 'Rumunsko', 'BG' : 'Bulharsko', 'MD' : 'Moldavsko', 'AL' : 'Albánsko', 'GR' : 'Grécko', 'TR' : 'Turecko',
            'ES' : 'Španielsko', 'PT' : 'Portugalsko', 'IT' : 'Taliansko', 'MT' : 'Malta', 'AD' : 'Andora',
            'CY' : 'Cyprus', 'AM' : 'Arménsko', 'AZ' : 'Azerbajdžan', 'GE' : 'Gruzínsko', 'KZ' : 'Kazachstan'
    };*/
    
    var animationSpeed = 500;
    // Add mouseovver animation effects to countries
    function countries_add_effects(color, secondaryColor, countries){
        for (var idx = 0; idx < countries.length; idx++){
            countries[idx].attr({fill: '#1973BE'});
            (function(country){
                country.mouseover(function(){
                    country.animate({fill: "#144173"}, animationSpeed);
                });
                country.mouseout(function(){
                    country.animate({fill: '#1973BE'}, animationSpeed);
                });
            })(countries[idx]);
        }
    }

    // Add a text and a rectangle to represent the description box for the LBG, and add mouseover effects
    function LBGs_add_effects(color, secondaryColor, LBGs){
        for (var idx = 0; idx < LBGs.length; idx++){
            (function(lbg){
                var bbox = lbg.getBBox();
                var paper = lbg.paper;
                //var countrycode = lbg.node.id.substr(-2);
                var text = paper.text(bbox.x + 20, bbox.y + 30, lbg.node.id
                                      /*.replace(" " + countrycode, ", " + countryNames[countrycode])*/);
                var textbbox = text.getBBox();
                var rectangle;
                /* If part of the text were to be outside the viewport, shift the text 
                 * and therefore later the box that will contain the text to the left or up 
                 * by its width or height.
                 */
                
                if ((paper.getBBox().x2 - textbbox.x) < textbbox.width){
                    text.attr({x : ("-=" + text.getBBox().width)});
                    textbbox = text.getBBox();
                }
                if ((paper.getBBox().y2 - textbbox.y) < textbbox.height){
                    text.attr({y : ("-=" + text.getBBox().height)});
                    textbbox = text.getBBox();
                }
                
                // Create the rectangle, append to the svg code, add attributes
                rectangle = paper.rect(textbbox.x - 5, textbbox.y - 5, textbbox.width + 20, textbbox.height + 20, 3, 3);
                LBGs[LBGs.length - 1].after(rectangle);
                rectangle.after(text);
                text.attr({fill: "#000000", display: "none"});
                rectangle.attr({fill: "#ffffff", stroke: "#000000", strokewidth: "0.5px", display: "none"});
                // Add color to the current LBG
                lbg.attr({fill: color});
                
                // Onmouseover make the dot bigger and show the box with the text
                lbg.mouseover(function(){
                    lbg.animate({fill: secondaryColor, transform: 'translate(' 
                                 + ( -bbox.cx * (2.5 - 1)) + ',' + ( -bbox.cy * (2.5 - 1)) 
                                 + ') scale(2.5 2.5)'},
                                animationSpeed
                    );
                    rectangle.attr({display: "block"});
                    text.attr({display: "block"});
                });
                // Onmouseout make the dot small again and hide the box with the text
                lbg.mouseout(function(){
                    lbg.animate({fill: color, transform: 'translate(' 
                                 + ( -bbox.cx * (1 - 1)) + ',' + ( -bbox.cy * (1 - 1)) 
                                 + ') scale(1 1)'}, 
                                animationSpeed
                    );
                    rectangle.attr({display: "none"});
                    text.attr({display: "none"});
                });
            })(LBGs[idx]);
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




//TODO: Investigate use of <div> elements instead of the curent method as description boxes for LBGs.
//TODO: What to display when hovering over an LBG (where to put translation functions, frontend or backend)?
//TODO: Should the dots (town or LBG) also be clickable, how to connect data from the database (like homepage_url) into their svg elements?
