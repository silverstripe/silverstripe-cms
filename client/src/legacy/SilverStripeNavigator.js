(function() {
  function windowName(suffix) {
    const base = document.getElementsByTagName('base')[0].href.replace('http://','').replace(/\//g,'_').replace(/\./g,'_');
    return base + suffix;
  }

  function displayHide(elem) {
    const displayComputed = getComputedStyle(elem).display;
    elem.style.display = 'none';
    elem.dataset.__toggle_display = displayComputed;
  }

  function displayShow(elem) {
    elem.style.display = elem.dataset.__toggle_display ? elem.dataset.__toggle_display : 'block';
  }

  function displayToggle(elem) {
    const displayComputed = getComputedStyle(elem).display;
    if (displayComputed !== 'none') {
      displayHide(elem);
    } else {
      displayShow(elem);
    }
  }

  const newWindowLinks = document.querySelectorAll('#switchView a.newWindow');
  if (newWindowLinks.length > 0) {
    for (const link of newWindowLinks.values()) {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const w = window.open(link.href, windowName(link.target));
        w.focus();
        return false;
      });
    }
  }

  const popup = document.getElementById('SilverStripeNavigatorLinkPopup');
  if (popup) {
    const navigatorLink = document.getElementById('SilverStripeNavigatorLink');
    if (navigatorLink) {
      navigatorLink.addEventListener('click', function(e) {
        e.preventDefault();
        displayToggle(popup);
        return false;
      });
    }

    const closeLinks = popup.querySelectorAll('a.close');
    if (closeLinks.length > 0) {
      for (const link of closeLinks.values()) {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          displayHide(popup);
          return false;
        });
      }
    }

    const inputs = popup.querySelectorAll('input');
    if (inputs.length > 0) {
      for (const input of inputs.values()) {
        input.addEventListener('focus', function(e) {
          input.select();
        });
      }
    }
  }
})();
