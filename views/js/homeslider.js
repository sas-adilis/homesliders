/**
 * 2007-2020 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

jQuery(document).ready(function ($) {
  var homesliderConfig = {
    speed: 500,            // Integer: Speed of the transition, in milliseconds
    timeout: $('.homeslider-container').data('interval'), // Integer: Time between slide transitions, in milliseconds
    nav: true,             // Boolean: Show navigation, true or false
    random: false,          // Boolean: Randomize the order of the slides, true or false
    pause: $('.homeslider-container').data('pause'), // Boolean: Pause on hover, true or false
    maxwidth: "",           // Integer: Max-width of the slideshow, in pixels
    namespace: "homeslider",   // String: Change the default namespace used
    before: function(){},   // Function: Before callback
    after: function(){}     // Function: After callback
  };

  $(".rslides").responsiveSlides(homesliderConfig);


  $(function () {
    $('.homeslider-container .rslides').each(function () {
      var $slider = $(this);

      function playActiveSlideVideo() {
        // on met tout en pause
        $slider.find('video.hs-video').each(function () {
          this.pause();
        });

        // on cherche la slide active (à adapter selon la classe utilisée par ton plugin)
        var $active = $slider.find('li:visible').first();
        var video = $active.find('video.hs-video').get(0);

        if (video) {
          // on s'assure que la vidéo est bien muette pour les mobiles
          video.muted = true;
          var playPromise = video.play();
          if (playPromise && playPromise.catch) {
            playPromise.catch(function (e) {
              console.warn('Autoplay vidéo refusé :', e);
            });
          }
        }
      }

      // au chargement initial
      playActiveSlideVideo();

      // écoute les événements du slider si dispo
      // Exemple générique : Rslides déclenche souvent un "before" / "after" personnalisé
      $slider.on('after.rslides before.rslides', function () {
        playActiveSlideVideo();
      });
    });
  });
});
