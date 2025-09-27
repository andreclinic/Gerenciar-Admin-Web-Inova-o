(function ($) {
  $(function () {
    var $preloader = $('#mpa-preloader');
    var noop = function () {};
    var preloaderApi = {
      show: noop,
      hide: noop,
      isVisible: function () {
        return false;
      }
    };

    if (!$preloader.length) {
      window.MPA_PRELOADER = preloaderApi;
      return;
    }

    if (!$preloader.parent().is('body')) {
      $preloader.appendTo(document.body);
    }

    var isVisible = false;
    var raf = window.requestAnimationFrame || window.webkitRequestAnimationFrame || function (callback) {
      return window.setTimeout(callback, 16);
    };

    var showPreloader = function (force) {
      if (isVisible && !force) {
        return;
      }

      isVisible = true;
      $preloader.addClass('is-visible').attr('aria-hidden', 'false');
    };

    var hidePreloader = function () {
      if (!isVisible) {
        $preloader.attr('aria-hidden', 'true');
        return;
      }

      isVisible = false;
      $preloader.removeClass('is-visible').attr('aria-hidden', 'true');

      raf(function () {
        // Força repaint em navegadores mobile.
        void $preloader[0].offsetHeight;
      });
    };

    var ensureHiddenWhenPrevented = function (event) {
      window.setTimeout(function () {
        if (event.isDefaultPrevented()) {
          hidePreloader();
        }
      }, 0);
    };

    var getOrigin = function () {
      if (window.location.origin) {
        return window.location.origin;
      }

      return window.location.protocol + '//' + window.location.host;
    };

    var shouldHandleLink = function ($link, event) {
      var href = $link.attr('href') || '';
      var evt = event || {};

      if (
        $link.attr('data-no-preloader') === 'true' ||
        $link.is('[target="_blank"]') ||
        $link.is('[download]') ||
        evt.ctrlKey ||
        evt.metaKey ||
        evt.shiftKey ||
        evt.altKey ||
        href === '' ||
        href === '#' ||
        href.charAt(0) === '#' ||
        /^javascript:/i.test(href)
      ) {
        return false;
      }

      if ($link.hasClass('mpa-nav-item')) {
        var $container = $link.closest('.mpa-nav-item-container');
        if (
          $container.length &&
          $container.find('.mpa-submenu').length &&
          !$container.hasClass('expanded')
        ) {
          return false;
        }
      }

      var propHref = $link.prop('href');
      if (propHref) {
        var absoluteHref = String(propHref);
        if (absoluteHref.indexOf(getOrigin()) !== 0) {
          return false;
        }
      }

      return true;
    };

    $(document).on('click', 'a', function (event) {
      var $link = $(this);

      if (!shouldHandleLink($link, event)) {
        return;
      }

      showPreloader(true);
      ensureHiddenWhenPrevented(event);
    });

    $(document).on('submit', 'form', function (event) {
      if (event.isDefaultPrevented()) {
        return;
      }

      showPreloader(true);
      ensureHiddenWhenPrevented(event);
    });

    $(window).on('beforeunload pagehide', function () {
      showPreloader(true);
    });

    $(window).on('load pageshow', function () {
      hidePreloader();

      // Safari iOS mantém páginas em cache; garantir reset do overlay.
      isVisible = false;
    });

    preloaderApi = {
      show: function () {
        showPreloader(true);
      },
      hide: hidePreloader,
      isVisible: function () {
        return isVisible;
      },
      element: $preloader[0]
    };

    window.MPA_PRELOADER = preloaderApi;
  });
})(jQuery);
