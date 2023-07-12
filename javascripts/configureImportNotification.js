(function () {
  var notificationID = 'ConfigureGAImportNotification';
  document.addEventListener("DOMContentLoaded", function (event) {
    window.CoreHome.Matomo.on("matomoPageChange", showNotification);
    window.CoreHome.Matomo.on("piwikPageChange", showNotification);
  });
  checkForConfigureImporterDisplay();

  function checkForConfigureImporterDisplay() {
    if (!piwik.idSite) {
      return;
    }
    let searchParams = {
      idSite: piwik.idSite,
      module: "GoogleAnalyticsImporter",
      action: "displayConfigureImportNotification"
    };

    (async () => {
      const response = await fetch(window.piwik.piwik_url + 'index.php?' + new URLSearchParams(searchParams));
      const data = await response.json();
      if (data.showNotification && data.configureURL) {
        showNotification(data.configureURL);
      } else {
        hideNotification();
      }
    })();
  }

  function hideNotification() {
    var UI = require('piwik/UI');
    var notification = new UI.Notification();
    notification.remove(notificationID);
  }

  function showNotification(url) {
    var UI = require('piwik/UI');
    var notification = new UI.Notification();
    notification.show(_pk_translate('GoogleAnalyticsImporter_ConfigureImportNotificationMessage', ['<a href="' + url + '" target="_blank" rel="noreferrer noopener">', piwik.piwik_url, '</a>']), {
      context: 'info',
      id: notificationID
    });
  }
})();
