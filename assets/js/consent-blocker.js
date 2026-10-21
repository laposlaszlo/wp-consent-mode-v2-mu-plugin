/**
 * Consent Mode V2 - Script blocker
 * Activates <script type="text/plain" data-cmv2-category="..."> and
 * <iframe data-cmv2-category="..." data-cmv2-src="..."> once the matching
 * category has been consented to.
 */

(function () {
  'use strict';

  var CONFIG = window.CMV2_BLOCKER_CONFIG || {};
  var COOKIE = CONFIG.cookie || 'cmv2_state';
  var SELECTOR = 'script[type="text/plain"][data-cmv2-category], iframe[data-cmv2-category][data-cmv2-src]';
  var SKIP_ATTRS = ['type', 'data-cmv2-category', 'data-cmv2-type', 'data-cmv2-done'];

  function readConsent() {
    try {
      var match = document.cookie.match('(?:^|; )' + COOKIE + '=([^;]*)');
      if (!match) { return null; }
      var state = JSON.parse(decodeURIComponent(match[1]));
      var age = Math.floor(Date.now() / 1000) - parseInt(state.ts, 10);
      var ttl = (CONFIG.ttl_days || 180) * 86400;
      if (!state || state.version !== CONFIG.version || isNaN(age) || age > ttl) { return null; }
      return state.choices || {};
    } catch (e) {
      return null;
    }
  }

  function isAllowed(category, choices) {
    if (category === 'analytics') { return !!choices.analytics; }
    if (category === 'ads') { return !!choices.ads; }
    return false;
  }

  // Run in document order; an external script finishes loading before the next one starts,
  // so dependencies between blocked scripts keep working.
  function runQueue(queue) {
    var node = queue.shift();
    if (!node) { return; }
    node.setAttribute('data-cmv2-done', '1');

    if (node.tagName === 'IFRAME') {
      node.setAttribute('src', node.getAttribute('data-cmv2-src'));
      runQueue(queue);
      return;
    }

    var script = document.createElement('script');
    Array.prototype.forEach.call(node.attributes, function (attr) {
      if (SKIP_ATTRS.indexOf(attr.name) === -1) { script.setAttribute(attr.name, attr.value); }
    });
    var originalType = node.getAttribute('data-cmv2-type');
    if (originalType) { script.type = originalType; }

    if (node.hasAttribute('src')) {
      script.async = false;
      script.onload = script.onerror = function () { runQueue(queue); };
      node.parentNode.replaceChild(script, node);
    } else {
      script.text = node.text;
      node.parentNode.replaceChild(script, node);
      runQueue(queue);
    }
  }

  function activate(choices) {
    var queue = [];
    document.querySelectorAll(SELECTOR).forEach(function (node) {
      if (!node.getAttribute('data-cmv2-done') && isAllowed(node.getAttribute('data-cmv2-category'), choices)) {
        queue.push(node);
      }
    });
    runQueue(queue);
  }

  // Fired by the banner after every consent decision
  document.addEventListener('cmv2:consent', function (e) {
    activate((e && e.detail) || {});
  });

  var initial = CONFIG.allow_all ? { analytics: true, ads: true } : readConsent();
  if (initial) { activate(initial); }
})();
