<?php


function presentaog_plugin_setup_menu() {

add_menu_page( 'Presenta Open Graph plugin', 'Presenta OG', 'manage_options', 'presenta-open-graph.php', 'presenta_render_plugin_setting_panel', plugins_url( '../img/icon_presenta.png', __FILE__ ), 66  );

}


