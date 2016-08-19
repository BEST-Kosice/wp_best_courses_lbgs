/* initialize a stacktable (creates a mobile-friendly version of the table,
 * so basically creates a second table, but without sorting options).
 */

 (function($) {

 	// $ Works! You can test it with next line if you like
 	// console.log($);
    $('.js-best-events').stacktable();

 })( jQuery );

/* for mobile devices, the default date interval styling within
 * the table cell 'od-do' ([date] - [date]) is asked for, but
 * for larger screens, a styling of ([date]<br>-<br>[date]) is more appropriate.
 */
var tables = document.getElementsByClassName("large-only")

Object.keys(tables).forEach(function(key) {
    styling_dates(tables[key]);
});

function styling_dates(table) {
    if (table) {
        var rowArray = table.tBodies[0].rows;
        var length = rowArray.length;
        var cellIndex = table.tHead.rows[0].cells['dates'].cellIndex;
        var cell;
        for (var i = 0; i < length; i++){
            cell = rowArray[i].cells[cellIndex];
            cell.innerHTML = cell.innerHTML.replace(/ - /, '<br>-<br>');
        }
    }
}

// sortable - add a custom 'interval' type to properly sort our column 'od-do' (a date interval sorted according to start date).
window.Sortable.setupTypes([
    window.Sortable.types[0],   //numeric
    window.Sortable.types[1],   //date
    //our custom type, note, it must be before the alpha (string) type, otherwise, the data will be sorted as strings.
    {
        name: 'interval',
        defaultSortDirection: 'ascending',
        match: function(a) {
            /* dont't ask why, but Date.parse has some problem
             * when interpreting dates in the form DD.MM.YYYY.
             * It interprets day as month and vice versa,
             * but using the format MM.DD.YYYY works just fine,
             * so we need to switch month and day before parsing.
             */
            return !isNaN(Date.parse(a.replace(/\n-\n.*/g, '').replace(/([0-9]{1,2}).([0-9]{1,2})/, '$2.$1')));
        },
        comparator: function(a) {
            return Date.parse(a.replace(/\n-\n.*/g, '').replace(/([0-9]{1,2}).([0-9]{1,2})/, '$2.$1')) || 0;
        }
    },
    window.Sortable.types[2]    //alpha (string data)
]);


/*
 * This is the jQuery selector for the mobile-friendly version of the table
 * TODO: What next should we try to do with that table (add sorting options,
 * change layout or style in ways not possible with CSS, ...).
 */
 // (function($) {
 //
 //    // $ Works! You can test it with next line if you like
 //    // console.log($);;
 //    $('.small-only')[0].tBodies[0]);
 //
 // })( jQuery );
