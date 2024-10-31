<?php
/*
* Plugin Name: PRESENTA Open-Graph
* Plugin URI: https://www.presenta.cc/open-graph-wordpress-plugin
* Description: PRESENTA Open-Graph plugin generates social preview images and tags automatically for each post or page.
* Tags: social, social sharing, open graph, social preview, social image, twitter card, open graph
* Requires at least: 4.0
* Version: 1.1.8
* Author: PRESENTA
* Author URI: https://www.presenta.cc
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain: presenta-open-graph
* Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*

I18N part and textdomain and menu

*/

function presenta_open_graph_textdomain() {
	load_plugin_textdomain( 'presenta-open-graph', FALSE, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'presenta_open_graph_textdomain' );

include_once plugin_dir_path( __FILE__ ) . 'inc/menu_setup.php';

add_action('admin_menu', 'presentaog_plugin_setup_menu');



$PRESENTA_OG_SERVICE_URL = 'https://monthly.presenta.workers.dev/';
$PRESENTA_IS_LOCAL = $_SERVER["SERVER_NAME"] == 'localhost' || $_SERVER["SERVER_ADDR"] == '::1' || substr($_SERVER["SERVER_ADDR"], 0, 4) == '127.0.';

/*
  Function to generate the meta tags for public posts/pages
*/
function presenta_head_meta_data() {

  global $post, $PRESENTA_OG_SERVICE_URL;

  if(is_admin()) return;

  // for now, skip cat and tag pages
  if ( is_category() || is_tag() ) return;

  if (empty($post)) return;

  // get the templateId from settings
  $pTemplateID = get_option('presenta_plugin_template_id');

  // get the yoast user opt-in from settings
  $hasYoast = get_option('presenta_plugin_template_yoast');

  // no template set, no tags
  if (empty($pTemplateID)) return;

  $post_id   = $post->ID;
  $author_id = $post->post_author;
  
  $post_date    = get_the_modified_date();
  $post_author  = get_the_author_meta('display_name', $author_id);
  $post_title   = wp_strip_all_tags(get_the_title($post_id));
  $post_excerpt = wp_strip_all_tags(get_the_excerpt($post_id));
  $post_image   = get_the_post_thumbnail_url($post_id);
  $post_url     = get_permalink($post_id);

  $site_name = get_bloginfo('name');
  $site_url = site_url();

  if (!is_singular()){
    $post_title = $site_name;
  }

  /*
  // future option to offer fallback if the post doesn't have an image
  if(empty($post_image) && !empty($unsplash_topic)){
    $post_image = "https://source.unsplash.com/random/800x600/?sky";
  }
  */

  echo "\n" . '<!-- PRESENTA OG start -->' . "\n";



  // this block is skipped when you check the yoast checkbox
  if($hasYoast != '1'){
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($post_title) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($post_excerpt) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($post_url) . '">' . "\n";

    echo '<meta name="twitter:title" content="' . esc_attr($post_title) . '"  />' . "\n";
    echo '<meta name="twitter:site" content="' . esc_attr($site_name) . '"  />' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($post_excerpt) . '"  />' . "\n";
    echo '<meta name="twitter:url" content="' . esc_url($post_url) . '"  />' . "\n";
    echo '<meta name="twitter:creator" content="PRESENTA OG"  />' . "\n";
  }

  $jitService = preg_replace('/monthly/u', 'jit', $PRESENTA_OG_SERVICE_URL);
  $service = esc_url($jitService);
  $template = esc_attr($pTemplateID);
  $title = preg_replace('/\s+/u', '+', esc_attr($post_title));
  $subtitle = preg_replace('/\s+/u', '+', esc_attr($post_date));

  // mandatory tags
  echo '<meta name="twitter:card" content="summary_large_image"  />' . "\n";
  echo '<meta name="twitter:image" content="' . $service . $template . "?title=" . $title . "&subtitle=" . $subtitle . "&image=" . urlencode_deep($post_image) . '"  />' . "\n";
  echo '<meta name="twitter:image:src" content="' . $service . $template . "?title=" . $title . "&subtitle=" . $subtitle . "&image=" . urlencode_deep($post_image) . '"  />' . "\n";
  echo '<meta property="og:image"  content="' . $service . $template . "?title=" . $title . "&subtitle=" . $subtitle . "&image=" . urlencode_deep($post_image) . '"  />' . "\n";

  echo '<!-- PRESENTA OG end -->' . "\n\n";  
}
add_action('wp_head', 'presenta_head_meta_data', 1);








function presenta_plugin_add_settings_link ( $actions ) {
  $url = esc_url( add_query_arg(
		'page',
		'presenta-open-graph.php',
		get_admin_url() . 'admin.php'
	) );
	$link = "<a href='$url'>" . __( 'Settings' ) . '</a>';
	array_push(
		$actions,
		$link
	);
  return $actions;
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'presenta_plugin_add_settings_link' );








function modify_list_row_actions( $actions, $post ) {
  global $PRESENTA_IS_LOCAL;
  if($PRESENTA_IS_LOCAL) return $actions;
  $link = array(
    'og' => '<a href="https://opengraph.dev/panel?url='.get_permalink( $post->ID ).'" target="_blank">Test OG</a>'
  );
  $actions['og'] = $link['og'];
	return $actions;
}
add_filter( 'post_row_actions', 'modify_list_row_actions', 10, 2 );
add_filter( 'page_row_actions', 'modify_list_row_actions', 10, 2 );






/*
TODO: validate user provided options
*/
function presenta_plugin_options_validate(){
  // validate input
}

function presenta_plugin_section_callback(){
}

/*
This renders the input text for the templateID
*/
function presenta_plugin_template_id_callback(){
    $setting = get_option('presenta_plugin_template_id');
    ?>
    <input placeholder="i.e. xxxxxxxx:yyyyyyyyy" type="text" name="presenta_plugin_template_id" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
    <?php
}

/*
This renders the input text for the yoast pref
*/
function presenta_plugin_template_yoast_callback(){
  $setting = get_option('presenta_plugin_template_yoast');
  ?>
  <input type="text" name="presenta_plugin_template_yoast" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
  <?php
}

/*
Plugin registers
*/
function presenta_register_settings() {
    add_settings_section( 'presenta_plugin_section', 'Open-Graph image generator for WordPress', 'presenta_plugin_section_callback', 'presenta_plugin_options' );
    add_settings_field( 'presenta_plugin_template_id', 'Template ID', 'presenta_plugin_template_id_callback', 'presenta_plugin_options', 'presenta_plugin_section' );
    add_settings_field( 'presenta_plugin_template_yoast', 'Yoast Fix', 'presenta_plugin_template_yoast_callback', 'presenta_plugin_options', 'presenta_plugin_section' );
    
    register_setting( 'presenta_plugin_options', 'presenta_plugin_section' );
    register_setting( 'presenta_plugin_options', 'presenta_plugin_template_id' ); //, 'presenta_plugin_options_validate'
    register_setting( 'presenta_plugin_options', 'presenta_plugin_template_yoast' ); //, 'presenta_plugin_options_validate'
}
add_action( 'admin_init', 'presenta_register_settings' );

/*
Plugin interface
*/

function presenta_render_plugin_setting_panel(){
  global $PRESENTA_OG_SERVICE_URL;
  ?>
  <div class="wrap">


    <h1><?php echo __('PRESENTA Open-Graph', 'presenta-open-graph') ?></h1>
    <form action="options.php" method="post" class="presenta_form">
        <?php 
          //settings_errors();
          settings_fields( 'presenta_plugin_options' );
          do_settings_sections( 'presenta_plugin_options' ); 
          submit_button();
        ?>
    </form>

    <p><?php echo __('Choose the template you prefer the most, review the options below, then, Save Changes.', 'presenta-open-graph') ?></p>
    <p><input type="checkbox" id="presenta_yoast_fix" /><?php echo __(' I have Yoast or other SEO plugins active. This option forces PRESENTA handling only the image tag.', 'presenta-open-graph') ?></p>
    <!--<p><select>
      <option>-- Disabled --</option>
      <option>Sky</option>
      <option>Home</option>
      <option>Tech</option>
    </select> Choose the topic (or disable it) for image fallback (post/page without Featured image) picked randomly from Unsplash.</p>
    -->

    <div id="presenta_gallery_container">
      <div class="presenta_template">
        <div class="presenta_template_inner">
          <img src="<?php echo plugin_dir_url( __FILE__ ) . 'none.jpg'; ?>" />
        </div>
      </div>
    </div>
  </div>

    
    <script>
      
        // available templates
        const src = [
          {id: 'iZ5FRV1gys:7rxf7cZM0'},
          {id: 'iZ5FRV1gys:3SnGloPw8'},
          {id: 'iZ5FRV1gys:D0y46xMvB'},

          {id: 'iZ5FRV1gys:FHGqTU00F'}, 
          {id: 'iZ5FRV1gys:IndDb9a8W'}, 
          {id: 'iZ5FRV1gys:x3tbKnGaN'}, 
          {id: 'iZ5FRV1gys:OhbyAMYkC'},

          {id: 'iZ5FRV1gys:4m6auqrlW'}, 
          {id: 'iZ5FRV1gys:GpyuAoZVQ'}, 
          {id: 'iZ5FRV1gys:pp19I6Hli'}, 
          {id: 'iZ5FRV1gys:sUffKobpC'}, 
          {id: 'iZ5FRV1gys:5QtdEMpKr'}, 
          {id: 'iZ5FRV1gys:NcNv5CROK'}, 
          {id: 'iZ5FRV1gys:0SLGi9Tt6'}, 
          {id: 'iZ5FRV1gys:FgMJG5Lvu'}, 
          {id: 'iZ5FRV1gys:2O0PHKWMP'}, 
          {id: 'iZ5FRV1gys:I8T7SzD0K'}, 
          {id: 'iZ5FRV1gys:vtbvRxBCV'}, 
          {id: 'iZ5FRV1gys:2kYnVLqbM'}, 
          {id: 'iZ5FRV1gys:bTfrzgxUU'}, 
          {id: 'iZ5FRV1gys:sfe0xDycT'}, 
          {id: 'iZ5FRV1gys:B2VP2r9c5'}, 
          {id: 'iZ5FRV1gys:d1s3GuiGD'}, 
          {id: 'iZ5FRV1gys:1O4X6lAit'}
        ]

        // set the templateID in JS
        <?php $templateID = get_option('presenta_plugin_template_id'); ?>
        const actual = "<?php echo esc_attr($templateID); ?>"
    
        // set the yoast var in JS
        <?php $yoastFix = get_option('presenta_plugin_template_yoast'); ?>
        const checkYoast = document.querySelector('#presenta_yoast_fix')
        const hasYoast = "<?php echo esc_attr($yoastFix); ?>"
        if(hasYoast == '1') checkYoast.checked = true
        checkYoast.addEventListener('change', e => {
          const v = e.target.checked
          const field = document.querySelector('[name="presenta_plugin_template_yoast"]')
          field.value = v ? 1 : 0
        })

        const base = '<?php echo esc_url($PRESENTA_OG_SERVICE_URL); ?>'

        const wrapper = document.querySelector('#presenta_gallery_container')

        // build the gallery
        src.forEach((t,i) => {
          const el = document.createElement('div')
          el.classList.add('presenta_template')
          
          const inn = document.createElement('div')
          inn.classList.add('presenta_template_inner')
          el.append(inn)
          
          const img = document.createElement('img')
          img.setAttribute('src', base + t.id + '?title=Contrary to popular belief, Lorem Ipsum is not random text.&subtitle=January 1, 2022&image=' + encodeURIComponent('https://source.unsplash.com/800x500/?landscape'))
          inn.append(img)
          
          img.setAttribute('data-id', t.id)
          img.setAttribute('data-index', i+1)

          if(t.id == actual) img.classList.add('selected')
          
          wrapper.append(el)
        })

        // actual selection handler
        if(!actual){
          const first = wrapper.querySelector('.presenta_template_inner img:first-child')
          first.classList.add('selected')
        }


        // click handler on thumbnails
        wrapper.addEventListener('click', e => {
          const id = e.target.getAttribute('data-id')
          const index = e.target.getAttribute('data-index')
          const field = document.querySelector('[name="presenta_plugin_template_id"]')
          field.value = id
          
          const list = [...wrapper.querySelectorAll('.presenta_template_inner img')]
          list.forEach(d => {
            d.classList.remove('selected')
          })
          
          list[+index].classList.add('selected')

        })


    </script>
    <style>
      #presenta_gallery_container * {
        box-sizing: border-box;
      }
      #presenta_gallery_container{
        display:flex;
        flex-wrap: wrap;
        padding-right: 20px;
      }
      .presenta_template{
        width: 33.333333%;
      }
      .presenta_template_inner{
        padding:10px;
      }
      .presenta_template .selected{
        border:5px solid #1E66A8;
      }
      .presenta_template img{
        display:block;
        width:100%;
        height:auto;
        box-shadow: 0 0 10px #ccc;
      }
      .presenta_form table{
        display:none;
      }
    </style>
    <?php
}

/*
function presenta_add_settings_page() {
    add_options_page( 'PRESENTA OG Settings', 'PRESENTA OG', 'manage_options', 'presenta-og-plugin', 'presenta_render_plugin_setting_panel' );
}
add_action( 'admin_menu', 'presenta_add_settings_page' );
*/


