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

namespace Google\Service\Monitoring;

class MonitoringQueryLanguageCondition extends \Google\Model
{
  public $duration;
  public $query;
  protected $triggerType = Trigger::class;
  protected $triggerDataType = '';

  public function setDuration($duration)
  {
    $this->duration = $duration;
  }
  public function getDuration()
  {
    return $this->duration;
  }
  public function setQuery($query)
  {
    $this->query = $query;
  }
  public function getQuery()
  {
    return $this->query;
  }
  /**
   * @param Trigger
   */
  public function setTrigger(Trigger $trigger)
  {
    $this->trigger = $trigger;
  }
  /**
   * @return Trigger
   */
  public function getTrigger()
  {
    return $this->trigger;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(MonitoringQueryLanguageCondition::class, 'Google_Service_Monitoring_MonitoringQueryLanguageCondition');
