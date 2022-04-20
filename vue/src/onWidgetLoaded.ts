/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import {
  Matomo,
  Periods,
  WidgetType,
  MatomoUrl,
  translate,
  parseDate,
} from 'CoreHome';

const UNSUPPORTED_REPORT_WIDGETS = [
  'Transitions.getTransitions',
  'UsersFlow.getUsersFlow',
  'UsersFlow.getUsersFlowPretty',
  'UsersFlow.getInteractionActions',
];

interface EventParams {
  parameters: NonNullable<WidgetType['parameters']>;
  element: HTMLElement;
}

const { $ } = window;

Matomo.on('widget:loaded', ({ parameters, element }: EventParams) => {
  const method = `${parameters.module}.${parameters.action}`;
  if (UNSUPPORTED_REPORT_WIDGETS.indexOf(method) === -1) {
    return;
  }

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  let { importedFromGoogleStartDate, importedFromGoogleEndDate } = Matomo as any;
  if (!importedFromGoogleStartDate || !importedFromGoogleEndDate) {
    return;
  }

  importedFromGoogleStartDate = parseDate(importedFromGoogleStartDate);
  importedFromGoogleEndDate = parseDate(importedFromGoogleEndDate);

  const period = MatomoUrl.parsed.value.period as string;
  const date = MatomoUrl.parsed.value.date as string;
  const currentPeriod = Periods.parse(period, date).getDateRange();

  const isInImportDateRange = !(
    importedFromGoogleStartDate.getTime() > currentPeriod[1].getTime()
    || importedFromGoogleEndDate.getTime() < currentPeriod[0].getTime()
  );
  if (!isInImportDateRange) {
    return;
  }

  const logDataRequired = translate('GoogleAnalyticsImporter_LogDataRequiredForReport');
  const helpText = `<br/> <div class="alert alert-info">${logDataRequired}</div>`;
  $(element).find('.card-content>div').append(helpText);
});
