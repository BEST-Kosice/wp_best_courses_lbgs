(function() {
    tinymce.create('tinymce.plugins.Wptuts', {
        init : function(ed, url) {
            ed.addButton('events', {
                text: '+ BEST Events',
                icon: false,
                cmd : 'events',
            });

            ed.addCommand('events', function() {
                var selected_text = ed.selection.getContent();
                var return_text = '';
                return_text = '[best_events]';
                ed.execCommand('mceInsertContent', 0, return_text);
            });

            ed.addButton('lbgs', {
                text: '+ BEST LBGS',
                icon: false,
                cmd : 'lbgs',
            });

            ed.addCommand('lbgs', function() {
                var selected_text = ed.selection.getContent();
                var return_text = '';
                return_text = '[best_lbgs]';
                ed.execCommand('mceInsertContent', 0, return_text);
            });
        },
        // ... Hidden code
    });
    // Register plugin
    tinymce.PluginManager.add( 'wptuts', tinymce.plugins.Wptuts );
})();
