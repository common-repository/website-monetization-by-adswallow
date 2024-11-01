<?php
/*
Plugin Name: Website Monetization by AdSwallow
Plugin URI: http://wordpress.org/plugins/website-monetization-by-adswallow/
Description: AdSwallow plugin helps you to make money from your blog or website! AdSwallow is an advertising network, specializing in JS monetizations. AdSwallow enables WordPress Publishers to easily install the AdSwallow unique advertising plugin to improve revenues for WordPress websites and blogs. AdSwallow automatically pays you for each person buying relevant brands and products you advertize with the help of this Plugin. This won't you take too much time, only a few steps, to start earning revenue.
Author: AdSwallow
Version: 1.0.2
Author URI: http://adswallow.com/
*/

class ADSWplugin {

  protected  $option_name = 'adsw_ad';
  protected  $srv_url = 'partners.adswallow.com';

  public function __construct() {
    add_action('wp_footer', array($this, 'add_script'));

    add_action('admin_menu', array($this, 'admin_menu'));
    add_action('admin_init', array($this, 'admin_init'));
  }

  function admin_menu() {
    add_options_page(
      'Website Monetization by AdSwallow',
      'Website Monetization by AdSwallow',
      'manage_options',
      $this->option_name . '_group',
      array($this, 'options_page')
    );
  }

  function admin_init() {
    register_setting($this->option_name . '_group', $this->get_option_key('id'), 'sanitize_text_field');
    register_setting($this->option_name . '_group', $this->get_option_key('mntz'), array($this, 'bool_sanitize'));

    add_action('update_option', array($this, 'update_option_action'), 10, 3);
    add_action('add_option', array($this, 'add_option_action'), 10, 2);
  }

  function bool_sanitize($input) {
    if (is_null($input)) return array();
    return array_map(function($v){ return (bool) $v; }, $input);
  }

  function update_option_action($option, $old_val, $val) {
    if ($option == $this->get_option_key('mntz')) {
      $this->update_path($option, $val);
    }
  }

  function add_option_action($option, $val) {
    if ($option == $this->get_option_key('mntz')) {
      $this->update_path($option, $val);
    }
  }

  function update_path($option, $val) {
    $source = $this->get_actual_source($val);
    if ($source) {
      $this->set_source_key($source);
    }
  }

  function add_script() {
    $opt = $this->get_option('id');
    $source_key = $this->get_source_key();
    if (!$source_key || !$opt) {
      return;
    }
      ?>
      <script>(function () {
          var s = document.createElement('script');
          s.src = '//<?php print $this->srv_url; ?>/static/<?php print $source_key; ?>?sid=<?php print $opt; ?>';
          document.body.appendChild(s);
        })();</script>
      <?php
  }

  private function get_source_key() {
    $source = $this->get_option('source_key');
    $expire = $this->get_option('source_key_expire', 0);
    if ($source && time() < $expire) {
      return $source;
    }
    $source = $this->get_actual_source();
    if ($source) {
      $this->set_source_key($source);
      return $source;
    }
    return FALSE;
  }

  private function set_source_key($source) {
    update_option($this->option_name . '_source_key', $source, TRUE);
    update_option($this->option_name . '_source_key_expire', time() + 86400, TRUE);
  }

  private function get_option($name) {
    return get_option($this->option_name . '_' . $name);
  }

  private function get_option_key($name) {
    return $this->option_name . '_' . $name;
  }

  private function get_default_mntz() {
    return array(
      'mntz_afflinks' => TRUE,
    );
  }

  private function get_monetizations_groups() {
    return array(
      'mntz' => array(
        'afflinks' =>'Afflinks',
        'intext' => 'InText',
        'img_banners_footer' => 'Image Banners in Footer',
        'img_banners_whitespace' => 'Image Banners Whitespace',
        'img_banners_inspace' => 'Image Banners Inspace',
        'popunder' => 'Popunder',
        'pricecomp' => 'Price comparison',
      ),

    );
  }

  private function get_tooltips() {
    return array(
      'afflinks' =>'The most effective and transparent monetization for extensions and websites, you\'ve ever seen. With Smart Links we analyze and convert an existing links on the website into their affiliate equivalent. User and third party monetizations friendly solution, which doesn\'t affecting the layout and general external look of any website. One of the best monetization method for browser extensions.',
      'intext' => 'We’ll bring an extra-value to any text content. Discover a true power of written word by inserting the most converting and profitable promotional links to your existing content with our monetization solution for extensions.',
      'img_banners_footer' => 'Pinned to the bottom of the webpage, footer banner won’t change the website layout in any way. This monetization works perfectly for any website all over the internet and delivers targeted promos for your audience.',
      'img_banners_whitespace' => 'You won’t need to solve a puzzle over the ad placement across your website. Let us find the very best spot and insert the banners from our Top Advertisers. Only few strings of JavaScript will start to bring the money from your website or browser add-on, once injected.<img src=\'' . plugin_dir_url( __FILE__ ) . 'img/resources/whitespace.png\'>',
      'img_banners_inspace' => 'You won’t need to solve a puzzle over the ad placement across your website. Let us find the very best spot and insert the banners from our Top Advertisers. Only few strings of JavaScript will start to bring the money from your website or browser add-on, once injected.<img src=\'' . plugin_dir_url( __FILE__ ) . 'img/resources/inspace.png\'>',
      'popunder' => 'Dozens of disturbing and irrelevant pops are past now. With help of our Plugin you’ll bring only non intrusive content-related advertisement in page background from pre-approved partners.',
      'pricecomp' => 'We\'ve connected with the industry leading Retailers to provide the best shoping experience. Fast and relevant search through the tons of products collects and drives the fairest results with the cheapest prices, depending on your user\'s activity. Use it to earn from extension like never before',
    );
  }

  private function get_subtitles() {
    return array(
    );
  }

  private function get_actual_source($mntz = NULL) {
    if (is_null($mntz)) {
      $mntz = $this->get_option('mntz', $this->get_default_mntz());
    }
    $mntz = array_keys($mntz);

    $response = file_get_contents('http://' . $this->srv_url . '/get-source?mntzs=' . implode('+', $mntz));
    $response = json_decode($response);

    return !empty($response->url) ? $response->url : FALSE;
  }


  function options_page() {
    wp_enqueue_script('jquery-ui-tooltip');
    wp_register_style('jquery-ui.css', plugin_dir_url( __FILE__ ) . 'css/vendor/jquery-ui.css');
    wp_enqueue_style('jquery-ui.css');
    wp_register_style('adsw.css', plugin_dir_url( __FILE__ ) . 'css/adsw.css');
    wp_enqueue_style('adsw.css');
    wp_register_script('adsw.js', plugin_dir_url( __FILE__ ) . 'js/adsw.js');
    wp_enqueue_script('adsw.js');

    $group_names = array(
      'mntz' => 'Select monetezations',
    );
    ?>
    <div class="wrap">
      <h2>Website Monetization by AdSwallow</h2>

      <form method="post" action="options.php">
        <?php settings_fields($this->option_name . '_group'); ?>
        <div class="fieldset-box fieldset-box-short">
          <h3>Activate monetization</h3>
          <div class="input-group">
            <div class="field-label">
              <p>SignIn or Login on <a target="_blank" href="http://partners.adswallow.com">adswallow.com</a> to get your <strong>Tracking ID</strong>.</p>
            </div>
            <div class="field-input"><a target="_blank" class="button-primary" href="http://partners.adswallow.com">Get Tracking ID</a></div>
            </div>
            <div class="input-group">
            <div class="field-label"><p>Enter your <strong>Tracking ID</strong> to the form.</p></div>
            <div class="field-input"><input type="text" name="<?php print $this->get_option_key('id'); ?>" value="<?php echo $this->get_option('id'); ?>" /></div>
            </div>
          <div class="input-group">
          <p>Do not hesitate to contact us if you have any questions. <a href="mailto:support@adswallow.com">support@adswallow.com</a></p>
            </div>
        </div>
        <?php $mntz = $this->get_option('mntz', $this->get_default_mntz()); ?>
        <?php $tooltips = $this->get_tooltips(); ?>
        <?php $subtitles = $this->get_subtitles(); ?>
        <?php foreach ($this->get_monetizations_groups() as $group_key => $group_val) : ?>
          <div class="fieldset-box">
            <h3><?php print $group_names[$group_key]; ?></h3>
            <?php foreach ($group_val as $mntz_key => $mntz_val) : ?>
              <div class="input-group">
                <div class="field-label"><?php print $mntz_val; ?></div>
                <div class="field-input">
                  <input type="checkbox" name="<?php print $this->get_option_key('mntz[' . $group_key . '_' . $mntz_key . ']') ?>" <?php print isset($mntz[$group_key . '_' . $mntz_key]) ? checked(1, $mntz[$group_key . '_' . $mntz_key]) : ''; ?>" />
                </div>
                <?php if (!empty($tooltips[$mntz_key])) : ?>
                  <span class="dashicons dashicons-info ad-tooltip" data-tooltip="<?php print $tooltips[$mntz_key]; ?>"></span>
                <?php endif; ?>
                <?php if (!empty($subtitles[$mntz_key])) : ?>
                  <div class="ad-subtitle"><?php print $subtitles[$mntz_key]; ?></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
        <p class="submit">
          <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
        </p>
      </form>
    </div>
  <?php }

}

$ADSWplugin = new ADSWplugin();

