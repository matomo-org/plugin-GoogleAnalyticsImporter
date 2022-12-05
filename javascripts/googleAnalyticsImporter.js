(function () {
  document.addEventListener("DOMContentLoaded", function(event) {
    window.CoreHome.Matomo.on("matomoPageChange", checkForPendingImporters);
    window.CoreHome.Matomo.on("piwikPageChange", checkForPendingImporters);
  });
  checkForPendingImporters();
  function checkForPendingImporters(){
    let params = getAllUrlParams(window.location.href);

    if(!params.hasOwnProperty("date") || !params.hasOwnProperty("period") || !params.hasOwnProperty("idsite")){
      return;
    }

    let searchParams = {
      idSite : params.idsite,
      date : params.date,
      period : params.period,
      module: "GoogleAnalyticsImporter",
      action: "pendingImports"
    };

    //?module=CoreAdminHome&action=home&idSite=1&period=day&date=yesterday";
    (async () => {
      const response = await fetch('/index.php?' + new URLSearchParams(searchParams));
      const data = await response.json();
      if (data.displayPending){
        displayPendingNotification(data.availableDate);
      } else {
        hidePendingNotification();
      }
    })();
  }
})();

function hidePendingNotification(){
  var UI = require('piwik/UI');
  var notification = new UI.Notification();
  notification.remove('GoogleAnalyticsImporterPendingImportNotice');
}


function displayPendingNotification(availableDate){
  var UI = require('piwik/UI');
  var notification = new UI.Notification();
  if(availableDate !== ''){
    notification.show(_pk_translate("GoogleAnalyticsImporter_PendingGAImportReportNotificationSomeData",[availableDate]), {
      context: 'info',
      noclear: false,
      type: 'toast',
      id: 'GoogleAnalyticsImporterPendingImportNotice'
    });
  } else {
    notification.show(_pk_translate("GoogleAnalyticsImporter_PendingGAImportReportNotificationNoData"), {
      context: 'info',
      noclear: false,
      type: 'toast',
      id: 'GoogleAnalyticsImporterPendingImportNotice'
    });
  }
}

function getAllUrlParams(url) {
  // get query string from url (optional) or window
  // var queryString = url ? url.split('?')[1] : window.location.search.slice(1);
  var queryString = url ? url.split('?').pop() : window.location.search.slice(-1);

  var obj = {};
  if (queryString) {
    queryString = queryString.split('#')[0];
    var arr = queryString.split('&');

    for (var i = 0; i < arr.length; i++) {
      var a = arr[i].split('=');
      var paramName = a[0];
      var paramValue = typeof (a[1]) === 'undefined' ? true : a[1];
      paramName = paramName.toLowerCase();
      if (typeof paramValue === 'string') paramValue = paramValue.toLowerCase();
      if (paramName.match(/\[(\d+)?\]$/)) {
        var key = paramName.replace(/\[(\d+)?\]/, '');
        if (!obj[key]) obj[key] = [];
        if (paramName.match(/\[\d+\]$/)) {
          var index = /\[(\d+)\]/.exec(paramName)[1];
          obj[key][index] = paramValue;
        } else {
          obj[key].push(paramValue);
        }
      } else {
        if (!obj[paramName]) {
          obj[paramName] = paramValue;
        } else if (obj[paramName] && typeof obj[paramName] === 'string'){
          obj[paramName] = [obj[paramName]];
          obj[paramName].push(paramValue);
        } else {
          obj[paramName].push(paramValue);
        }
      }
    }
  }
  return obj;
}
