<?php
  /**
   * @package 8digits
   * @version 0.3
   */
  /*
  Plugin Name: 8digits
  Plugin URI: http://wordpress.org/plugins/8digits/
  Description: Plugin for 8digits.com to integrate your woocommerce store with 8digits easily!
  Author: 8digits
  Version: 0.3
  Author URI: http://beta.8digits.com/
  */

  if(!defined('ABSPATH')) {
    exit;
  }

  define('ED_IS_WOO_ENABLED', in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))));


  if(!class_exists('EightDigits') && ED_IS_WOO_ENABLED) {

    /**
     *
     */
    final class EightDigits {

      /**
       * @var string
       */
      public static $version = '0.3';

      /**
       * @var EightDigits instance of class
       */
      private static $_instance = null;

      /**
       * @var null
       */
      private $pluginDir = null;

      /**
       * @var string
       */
      private $_extraCodeBefore = '';

      /**
       * @var string
       */
      private $_8digitsInterface = 'http://beta.8digits.com';

      /**
       * @var string
       */
      private $_8digitsStaticInterface = '//cdn.8digits.com';


      /**
       * Creates instance of EightDigits class
       *
       * @return EightDigits|null
       */
      public static function instance() {

        if(self::$_instance == null) {
          self::$_instance = new self();
        }

        return self::$_instance;
      }

      /**
       *
       */
      public function __construct() {
        $this->initialize();
        $this->buildMenu();
      }

      /**
       *
       */
      public function initialize() {
        $this->pluginDir = plugin_dir_path(__FILE__);

        /**
         * Adds 8digits tracking code to page
         */
        add_action('wp_footer', array($this, 'add8digitsCode'));

        /**
         * Product view
         */
        add_action('the_post', array($this, 'view'));

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
      }

      /**
       *
       */
      private function buildMenu() {
        add_action('admin_init', array($this, 'adminInit'));
        add_action('admin_menu', array($this, 'pluginMenu'));
      }

      /**
       *
       */
      public function activate() {

      }

      /**
       *
       */
      public function deactivate() {

      }

      /**
       *
       */
      public function adminInit() {
        add_settings_section(
          'eightdigits_setting_section',
          'Account Settings',
          array($this, 'accountSettingsSectionRenderer'),
          '8digits'
        );

        add_settings_field(
          'eightdigits_tracking_code',
          'Tracking Code',
          array($this, 'textFieldRenderer'),
          '8digits',
          'eightdigits_setting_section',
          array(
            'id'    => 'eightdigits_tracking_code',
            'label' => 'Type your Tracking Code here.'
          )
        );

          add_settings_field(
              'eightdigits_installation_notified',
              '',
              array($this, 'hiddenFieldRenderer'),
              '8digits',
              'eightdigits_setting_section',
              array(
                  'name'    => 'eightdigits_installation_notified',
                  'default' => '1'
              )
          );

        register_setting('8digits', 'eightdigits_tracking_code');
        register_setting('8digits', 'eightdigits_installation_notified');
      }

      /**
       *
       */
      public function pluginMenu() {
         add_menu_page('8digits', '8digits', 'manage_options', '8digits', array($this, 'optionsPage'));
      }

      /**
       *
       */
      public function optionsPage() {
        echo '<div class="wrap">';
        echo '<h2>8digits Options</h2>';
        echo '<form method="post" action="options.php">';
        settings_fields('8digits');
        do_settings_sections('8digits');
        submit_button();
        echo '</form>';
        echo '</div>';
      }

      /**
       * Renders section header for settings
       */
      public function accountSettingsSectionRenderer() {
          $trackingCode = get_option('eightdigits_tracking_code');

          $output = '';

          if (!($trackingCode)) {
              $output .= '<p>To activate 8digits, type in your tracking code and save changes.</p>';
              $output .= '<ul>';
              $output .= '<li>If you have not registered with 8digits yet please <a href="' . $this->_8digitsInterface . '/index/signup/woocommerce" target="_blank" class="button-primary">sign up now</a></li>';
              $output .= '<li>If you already have an account but you do not remember your tracking code please visit our <a href="' . $this->_8digitsInterface . '/index/login/woocommerceIntegration" target="_blank" class="button-primary">integration page</a></li>';
              $output .= '</ul>';
          } else {
              $output .= '<p><a href="' . $this->_8digitsInterface . '/index/login/woocommerceSolutions" target="_blank" class="button-primary">Run Campaigns</a></p>';
              $output .= '<p><a href="' . $this->_8digitsInterface . '/index/login/woocommerceDashboard" target="_blank" class="button-primary">Track Campaigns</a></p>';
          }

          /**
           *  Renders notification script
           *  This is for 8digits to be notified on new installations
           *  and offer support to find the best ways to run solutions
           *  for your site.
           */
          $notified = get_option('eightdigits_installation_notified');

          if (!($notified)) {

              $adminEmail = get_option('admin_email');
              $siteUrl = get_option('siteurl');

              $output .= <<<EOD
              <script type='text/javascript'>
                if(typeof jQuery!="undefined") {
                    addLoadEvent(function() {
                        jQuery.ajax({
                          url: '$this->_8digitsInterface/wordpress/installed',
                          method: 'POST',
                          dataType: 'jsonp',
                          data: {
                              siteurl: '$siteUrl',
                              email: '$adminEmail'
                          }
                        })
                    }());
                }
              </script>
EOD;

              update_option('eightdigits_installation_notified', true);
          }

          echo $output;
      }

      /**
       * Renders input box for option
       */
      public function textFieldRenderer() {
        $args  = func_get_args();
        $args = $args[0];

        $id    = $args['id'];
        $label = $args['label'];

        echo '<input name="' . $id . '" id="' . $id . '" type="text" value="' . get_option($id) . '" /> ' . $label;
      }


        /**
         * Renders hidden input box for option
         */
        public function hiddenFieldRenderer() {
            $args  = func_get_args();
            $args = $args[0];

            $name    = $args['name'];
            $defaultVal = $args['default'];
            $optVal = get_option($name);

            echo '<input name="' . $name . '" type="hidden" value="' . ($optVal ? $optVal : $defaultVal) . '" /> ';
        }

      /**
       * Adds 8digits integration code to footer. Also, renders scraping code to get called when 8digits JS SDK is ready.
       */
      public function add8digitsCode() {
        $trackingCode = get_option('eightdigits_tracking_code');

        $output = '';

        if($trackingCode) {

          if($this->_extraCodeBefore) {
            $output .= $this->_extraCodeBefore;
          }

          $version = self::$version;

          $output .= <<<EOD
          <script type='text/javascript'>
            var _trackingCode = '$trackingCode';
            (function() {
              var wa = document.createElement('script'); wa.type = 'text/javascript'; wa.async = true;
              wa.src = '$this->_8digitsStaticInterface/automation.js';
              var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(wa, s);
            })();
          </script>
          <!-- 8digits WooCommerce Plugin Version : $version -->
EOD;

        }

        echo $output;
      }

      /**
       * Creates scraping code according to page. Currently, we are handling cart, checkout and congrats pages.
       *
       * @param $post
       */
      public function view($post) {
        global $woocommerce;

        if(is_shop()) {

        } else if(is_product_category()) {

        } else if(is_product_tag()) {

        } else if(is_product()) {

        } else if(is_cart()) {
          $basketSize = $woocommerce->cart->total;

          $cartItems = $woocommerce->cart->get_cart();
          $cartItemsCount = $woocommerce->cart->get_cart_contents_count();

          $products  = array();

          foreach($cartItems AS $key => $item) {
            $product    = $item['data'];
            $products[] = $product->get_title() . ' - ' . $product->get_sale_price() . ' ' . $product->get_price_suffix() . ' - ' . $product->get_permalink();
          }

          $products = join("<br/>", $products);

          $this->_extraCodeBefore = <<<EOF
          <script type="text/javascript">
            function EightDigitsReady() {
              EightDigits.setAttributes({
                products: '$products',
                price: '$basketSize',
                itemCount: '$cartItemsCount'
              });

              setTimeout(function() {
                EightDigits.event({ key: 'CartDisplayed', noPath: true });
              }, 500);

            }
          </script>
EOF;

        } else if(is_checkout()) {
          $this->_extraCodeBefore = <<<EOF
          <script type="text/javascript">
            function EightDigitsReady() {
              EightDigits.event({ key: 'CheckoutDisplayed', noPath: true });
            }

            var attributeNamesMap = {
              'billing_first_name': 'firstName',
              'billing_last_name': 'lastName',
              'billing_company': 'company',
              'billing_email': 'email',
              'billing_phone': 'phone'
            };

            jQuery(function() {
              jQuery('.woocommerce-billing-fields').find('input[type="text"]').on('blur', function() {
                var el = jQuery(this);
                var id = el.attr('id');
                var value = el.val();

                if(attributeNamesMap.hasOwnProperty(id)) {
                  var attributeName = attributeNamesMap[id];
                  EightDigits.setAttribute({
                    name: attributeName,
                    value: value
                  });
                }


              })
            })

          </script>
EOF;
        } else if(is_account_page()) {

        } else if(is_order_received_page()) {
          $this->_extraCodeBefore = <<<EOF
          <script type="text/javascript">
            function EightDigitsReady() {
              EightDigits.event({ key: 'OrderReceivedDisplayed', noPath: true });
            }
          </script>
EOF;

        }

      }
    }

    EightDigits::instance();

  }



?>
