/**
 * @file
 * Attaches behaviors for thelog.
 */

(() => {

  'use strict';

  function unCamelCase(str) {
    return str
      // insert a space between lower & upper
      .replace(/([a-z])([A-Z])/g, '$1 $2')
      // space before last upper in a sequence followed by lower
      .replace(/\b([A-Z]+)([A-Z])([a-z])/, '$1 $2$3')
      // uppercase the first character
      .replace(/^./, function (str) { return str.toUpperCase(); })
  }

  Drupal.behaviors.thelog = {
    attach: function () {

      const projectedStats = document.querySelectorAll('.projected-stats');

      projectedStats.forEach(group => {
        const stats = JSON.parse(group.textContent);
        group.textContent = '';

        for (const key in stats) {
          if (Object.hasOwnProperty.call(stats, key)) {
            group.textContent += ` ${unCamelCase(key)}: ${stats[key]}`;
          }
        }

        group.classList.remove('projected-stats');
      })


    }
  };
})();
