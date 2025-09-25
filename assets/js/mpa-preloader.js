(function ($) {
  $(function () {
    var $preloader = $('#mpa-preloader');

    if (!$preloader.length) {
      return;
    }

    var showPreloader = function () {
      if ($preloader.hasClass('is-visible')) {
        return;
      }

      window.requestAnimationFrame(function () {
        $preloader.addClass('is-visible').attr('aria-hidden', 'false');
      });
    };

    var hidePreloader = function () {
      if (!$preloader.hasClass('is-visible')) {
        $preloader.attr('aria-hidden', 'true');
        return;
      }

      window.requestAnimationFrame(function () {
        $preloader.removeClass('is-visible').attr('aria-hidden', 'true');
      });
    };

    $(document).on('click', 'a', function (event) {
      var $link = $(this);
      var href = $link.attr('href') || '';

      if (
        $link.attr('data-no-preloader') === 'true' ||
        $link.is('[target="_blank"]') ||
        $link.is('[download]') ||
        event.ctrlKey ||
        event.metaKey ||
        event.shiftKey ||
        event.altKey ||
        href === '' ||
        href === '#' ||
        href.charAt(0) === '#' ||
        /^javascript:/i.test(href) ||
        ($link.prop('href') && !String($link.prop('href')).startsWith(window.location.origin))
      ) {
        return;
      }

      window.setTimeout(function () {
        if (event.isDefaultPrevented()) {
          hidePreloader();
          return;
        }

        showPreloader();
      }, 0);
    });

    $(window).on('load', hidePreloader);
  });
})(jQuery);
