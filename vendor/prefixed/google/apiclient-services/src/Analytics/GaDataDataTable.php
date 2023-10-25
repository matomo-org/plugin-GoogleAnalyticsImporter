<?php

/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */
namespace Google\Service\Analytics;

class GaDataDataTable extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Collection
{
    protected $collection_key = 'rows';
    protected $colsType = \Google\Service\Analytics\GaDataDataTableCols::class;
    protected $colsDataType = 'array';
    protected $rowsType = \Google\Service\Analytics\GaDataDataTableRows::class;
    protected $rowsDataType = 'array';
    /**
     * @param GaDataDataTableCols[]
     */
    public function setCols($cols)
    {
        $this->cols = $cols;
    }
    /**
     * @return GaDataDataTableCols[]
     */
    public function getCols()
    {
        return $this->cols;
    }
    /**
     * @param GaDataDataTableRows[]
     */
    public function setRows($rows)
    {
        $this->rows = $rows;
    }
    /**
     * @return GaDataDataTableRows[]
     */
    public function getRows()
    {
        return $this->rows;
    }
}
// Adding a class alias for backwards compatibility with the previous class name.
class_alias(\Google\Service\Analytics\GaDataDataTable::class, 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Service_Analytics_GaDataDataTable');
