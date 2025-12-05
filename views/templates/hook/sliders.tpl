{*
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
 *}

{if isset($sliders) && is_array($sliders)}
    {foreach from=$sliders item=slider}
      <div class="homeslider-container" data-interval="{$slider.speed}" data-wrap="{$slider.loop}" data-pause="{$slider.pause_on_hover}">
        <ul class="rslides">
          {foreach from=$slider.slides item=slide}
            <li class="slide">
              {if !empty($slide.url)}<a href="{$slide.url}">{/if}
                {if $slide.video_url}
                    <video preload="none" class="lazy-video d-none d-md-inline-block" loop="" {if !empty($slide.image_url)}poster="{$slide.image_url}"{/if} data-autoplay-on-view="1">
                        <source type="video/mp4" data-src="{$slide.video_url}">
                    </video>
                {else}
                    <img src="{$slide.image_url}" alt="{$slide.legend|escape}" loading="lazy" class="img-fluid d-none d-md-inline-block"/>
                {/if}

                {if $slide.video_mobile_url}
                    <video preload="none" class="lazy-video d-md-none" loop="" {if !empty($slide.image_mobile_url)}poster="{$slide.image_mobile_url}"{/if} data-autoplay-on-view="1">
                        <source type="video/mp4" data-src="{$slide.video_mobile_url}">
                    </video>
                {else}
                    <img src="{$slide.image_mobile_url}" alt="{$slide.legend|escape}" loading="lazy" class="img-fluid d-md-none"/>
                {/if}

                {if !empty($slide.title) || !empty($slide.legend)}
                  <section class="caption caption-position-{$slide.content_position}">
                      {if !empty($slide.title)}<div class="caption-title">{$slide.title}</div>{/if}
                      {if !empty($slide.legend)}<div class="caption-legend">{$slide.legend}</div>{/if}
                  </section>
                {/if}
              {if !empty($slide.url)}</a>{/if}
            </li>
          {/foreach}
        </ul>
      </div>
    {/foreach}
{/if}
