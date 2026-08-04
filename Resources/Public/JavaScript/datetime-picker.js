/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import DocumentService from '@typo3/core/document-service.js';
import DateTimePicker from '@typo3/backend/date-time-picker.js';

// The core module only exposes initialize(), the scanning is done by FormEngine.
// The direct mail forms are not built by FormEngine, so they have to do it themselves.
// Scoped to our own fields on purpose - the query builder of the DB check module also
// renders t3js-datetimepicker fields, but with a localised value flatpickr cannot parse.
DocumentService.ready().then(() => {
  document.querySelectorAll('[data-dmail-datetimepicker]').forEach((element) => {
    DateTimePicker.initialize(element);
  });
});
