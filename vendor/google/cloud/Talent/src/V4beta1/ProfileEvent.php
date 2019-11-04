<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/cloud/talent/v4beta1/event.proto

namespace Google\Cloud\Talent\V4beta1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * An event issued when a profile searcher interacts with the application
 * that implements Cloud Talent Solution.
 *
 * Generated from protobuf message <code>google.cloud.talent.v4beta1.ProfileEvent</code>
 */
class ProfileEvent extends \Google\Protobuf\Internal\Message
{
    /**
     * Required. Type of event.
     *
     * Generated from protobuf field <code>.google.cloud.talent.v4beta1.ProfileEvent.ProfileEventType type = 1;</code>
     */
    private $type = 0;
    /**
     * Required. The [profile name(s)][google.cloud.talent.v4beta1.Profile.name]
     * associated with this client event.
     * The format is
     * "projects/{project_id}/tenants/{tenant_id}/profiles/{profile_id}",
     * for example, "projects/api-test-project/tenants/foo/profiles/bar".
     *
     * Generated from protobuf field <code>repeated string profiles = 2;</code>
     */
    private $profiles;
    /**
     * Optional. The [job name(s)][google.cloud.talent.v4beta1.Job.name]
     * associated with this client event. Leave it empty if the event isn't
     * associated with a job.
     * The format is
     * "projects/{project_id}/tenants/{tenant_id}/jobs/{job_id}", for
     * example, "projects/api-test-project/tenants/foo/jobs/1234".
     *
     * Generated from protobuf field <code>repeated string jobs = 6;</code>
     */
    private $jobs;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $type
     *           Required. Type of event.
     *     @type string[]|\Google\Protobuf\Internal\RepeatedField $profiles
     *           Required. The [profile name(s)][google.cloud.talent.v4beta1.Profile.name]
     *           associated with this client event.
     *           The format is
     *           "projects/{project_id}/tenants/{tenant_id}/profiles/{profile_id}",
     *           for example, "projects/api-test-project/tenants/foo/profiles/bar".
     *     @type string[]|\Google\Protobuf\Internal\RepeatedField $jobs
     *           Optional. The [job name(s)][google.cloud.talent.v4beta1.Job.name]
     *           associated with this client event. Leave it empty if the event isn't
     *           associated with a job.
     *           The format is
     *           "projects/{project_id}/tenants/{tenant_id}/jobs/{job_id}", for
     *           example, "projects/api-test-project/tenants/foo/jobs/1234".
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Cloud\Talent\V4Beta1\Event::initOnce();
        parent::__construct($data);
    }

    /**
     * Required. Type of event.
     *
     * Generated from protobuf field <code>.google.cloud.talent.v4beta1.ProfileEvent.ProfileEventType type = 1;</code>
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Required. Type of event.
     *
     * Generated from protobuf field <code>.google.cloud.talent.v4beta1.ProfileEvent.ProfileEventType type = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setType($var)
    {
        GPBUtil::checkEnum($var, \Google\Cloud\Talent\V4beta1\ProfileEvent_ProfileEventType::class);
        $this->type = $var;

        return $this;
    }

    /**
     * Required. The [profile name(s)][google.cloud.talent.v4beta1.Profile.name]
     * associated with this client event.
     * The format is
     * "projects/{project_id}/tenants/{tenant_id}/profiles/{profile_id}",
     * for example, "projects/api-test-project/tenants/foo/profiles/bar".
     *
     * Generated from protobuf field <code>repeated string profiles = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getProfiles()
    {
        return $this->profiles;
    }

    /**
     * Required. The [profile name(s)][google.cloud.talent.v4beta1.Profile.name]
     * associated with this client event.
     * The format is
     * "projects/{project_id}/tenants/{tenant_id}/profiles/{profile_id}",
     * for example, "projects/api-test-project/tenants/foo/profiles/bar".
     *
     * Generated from protobuf field <code>repeated string profiles = 2;</code>
     * @param string[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setProfiles($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->profiles = $arr;

        return $this;
    }

    /**
     * Optional. The [job name(s)][google.cloud.talent.v4beta1.Job.name]
     * associated with this client event. Leave it empty if the event isn't
     * associated with a job.
     * The format is
     * "projects/{project_id}/tenants/{tenant_id}/jobs/{job_id}", for
     * example, "projects/api-test-project/tenants/foo/jobs/1234".
     *
     * Generated from protobuf field <code>repeated string jobs = 6;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getJobs()
    {
        return $this->jobs;
    }

    /**
     * Optional. The [job name(s)][google.cloud.talent.v4beta1.Job.name]
     * associated with this client event. Leave it empty if the event isn't
     * associated with a job.
     * The format is
     * "projects/{project_id}/tenants/{tenant_id}/jobs/{job_id}", for
     * example, "projects/api-test-project/tenants/foo/jobs/1234".
     *
     * Generated from protobuf field <code>repeated string jobs = 6;</code>
     * @param string[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setJobs($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->jobs = $arr;

        return $this;
    }

}
