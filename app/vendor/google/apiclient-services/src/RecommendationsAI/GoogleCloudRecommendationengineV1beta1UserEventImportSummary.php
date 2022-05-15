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

namespace Google\Service\RecommendationsAI;

class GoogleCloudRecommendationengineV1beta1UserEventImportSummary extends \Google\Model
{
  public $joinedEventsCount;
  public $unjoinedEventsCount;

  public function setJoinedEventsCount($joinedEventsCount)
  {
    $this->joinedEventsCount = $joinedEventsCount;
  }
  public function getJoinedEventsCount()
  {
    return $this->joinedEventsCount;
  }
  public function setUnjoinedEventsCount($unjoinedEventsCount)
  {
    $this->unjoinedEventsCount = $unjoinedEventsCount;
  }
  public function getUnjoinedEventsCount()
  {
    return $this->unjoinedEventsCount;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudRecommendationengineV1beta1UserEventImportSummary::class, 'Google_Service_RecommendationsAI_GoogleCloudRecommendationengineV1beta1UserEventImportSummary');
