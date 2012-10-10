var prefixes = new Array();

function f_selector_clicked(selector_index) {

    for (var i=0; i<this.num_panes; i++) {
        var sel_id = this.prefix + '_selector_' + (i+1);
        var selector = document.getElementById(sel_id);
        var pane_id = this.prefix + '_pane_' + (i+1);
        var pane = document.getElementById(pane_id);

        if (i == selector_index-1) {
            selector.className = 'tabs-selected'
            pane.style.display = 'block';
        }
        else {
            selector.className = 'tabs';
            pane.style.display = 'none';
        }
    }
}


// CONSTRUCTOR
function tabs_group(prefix, num_panes) {
    this.prefix = prefix;
    prefixes[prefixes.length] = prefix;
    this.num_panes = num_panes;
    this.selector_clicked = f_selector_clicked;
    this.selector_clicked(1);  // init
}
