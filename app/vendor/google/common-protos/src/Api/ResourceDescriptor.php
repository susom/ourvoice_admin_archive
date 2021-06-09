<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/resource.proto

namespace Google\Api;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A simple descriptor of a resource type.
 * ResourceDescriptor annotates a resource message (either by means of a
 * protobuf annotation or use in the service config), and associates the
 * resource's schema, the resource type, and the pattern of the resource name.
 * Example:
 *   message Topic {
 *     // Indicates this message defines a resource schema.
 *     // Declares the resource type in the format of {service}/{kind}.
 *     // For Kubernetes resources, the format is {api group}/{kind}.
 *     option (google.api.resource) = {
 *       type: "pubsub.googleapis.com/Topic"
 *       pattern: "projects/{project}/topics/{topic}"
 *     };
 *   }
 * Sometimes, resources have multiple patterns, typically because they can
 * live under multiple parents.
 * Example:
 *   message LogEntry {
 *     option (google.api.resource) = {
 *       type: "logging.googleapis.com/LogEntry"
 *       pattern: "projects/{project}/logs/{log}"
 *       pattern: "organizations/{organization}/logs/{log}"
 *       pattern: "folders/{folder}/logs/{log}"
 *       pattern: "billingAccounts/{billing_account}/logs/{log}"
 *     };
 *   }
 *
 * Generated from protobuf message <code>google.api.ResourceDescriptor</code>
 */
class ResourceDescriptor extends \Google\Protobuf\Internal\Message
{
    /**
     * The full name of the resource type. It must be in the format of
     * {service_name}/{resource_type_kind}. The resource type names are
     * singular and do not contain version numbers.
     * For example: `storage.googleapis.com/Bucket`
     * The value of the resource_type_kind must follow the regular expression
     * /[A-Z][a-zA-Z0-9]+/. It must start with upper case character and
     * recommended to use PascalCase (UpperCamelCase). The maximum number of
     * characters allowed for the resource_type_kind is 100.
     *
     * Generated from protobuf field <code>string type = 1;</code>
     */
    private $type = '';
    /**
     * Required. The valid pattern or patterns for this resource's names.
     * Examples:
     *   - "projects/{project}/topics/{topic}"
     *   - "projects/{project}/knowledgeBases/{knowledge_base}"
     * The components in braces correspond to the IDs for each resource in the
     * hierarchy. It is expected that, if multiple patterns are provided,
     * the same component name (e.g. "project") refers to IDs of the same
     * type of resource.
     *
     * Generated from protobuf field <code>repeated string pattern = 2;</code>
     */
    private $pattern;
    /**
     * Optional. The field on the resource that designates the resource name
     * field. If omitted, this is assumed to be "name".
     *
     * Generated from protobuf field <code>string name_field = 3;</code>
     */
    private $name_field = '';
    /**
     * Optional. The historical or future-looking state of the resource pattern.
     * Example:
     *   // The InspectTemplate message originally only supported resource
     *   // names with organization, and project was added later.
     *   message InspectTemplate {
     *     option (google.api.resource) = {
     *       type: "dlp.googleapis.com/InspectTemplate"
     *       pattern: "organizations/{organization}/inspectTemplates/{inspect_template}"
     *       pattern: "projects/{project}/inspectTemplates/{inspect_template}"
     *       history: ORIGINALLY_SINGLE_PATTERN
     *     };
     *   }
     *
     * Generated from protobuf field <code>.google.api.ResourceDescriptor.History history = 4;</code>
     */
    private $history = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $type
     *           The full name of the resource type. It must be in the format of
     *           {service_name}/{resource_type_kind}. The resource type names are
     *           singular and do not contain version numbers.
     *           For example: `storage.googleapis.com/Bucket`
     *           The value of the resource_type_kind must follow the regular expression
     *           /[A-Z][a-zA-Z0-9]+/. It must start with upper case character and
     *           recommended to use PascalCase (UpperCamelCase). The maximum number of
     *           characters allowed for the resource_type_kind is 100.
     *     @type string[]|\Google\Protobuf\Internal\RepeatedField $pattern
     *           Required. The valid pattern or patterns for this resource's names.
     *           Examples:
     *             - "projects/{project}/topics/{topic}"
     *             - "projects/{project}/knowledgeBases/{knowledge_base}"
     *           The components in braces correspond to the IDs for each resource in the
     *           hierarchy. It is expected that, if multiple patterns are provided,
     *           the same component name (e.g. "project") refers to IDs of the same
     *           type of resource.
     *     @type string $name_field
     *           Optional. The field on the resource that designates the resource name
     *           field. If omitted, this is assumed to be "name".
     *     @type int $history
     *           Optional. The historical or future-looking state of the resource pattern.
     *           Example:
     *             // The InspectTemplate message originally only supported resource
     *             // names with organization, and project was added later.
     *             message InspectTemplate {
     *               option (google.api.resource) = {
     *                 type: "dlp.googleapis.com/InspectTemplate"
     *                 pattern: "organizations/{organization}/inspectTemplates/{inspect_template}"
     *                 pattern: "projects/{project}/inspectTemplates/{inspect_template}"
     *                 history: ORIGINALLY_SINGLE_PATTERN
     *               };
     *             }
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Api\Resource::initOnce();
        parent::__construct($data);
    }

    /**
     * The full name of the resource type. It must be in the format of
     * {service_name}/{resource_type_kind}. The resource type names are
     * singular and do not contain version numbers.
     * For example: `storage.googleapis.com/Bucket`
     * The value of the resource_type_kind must follow the regular expression
     * /[A-Z][a-zA-Z0-9]+/. It must start with upper case character and
     * recommended to use PascalCase (UpperCamelCase). The maximum number of
     * characters allowed for the resource_type_kind is 100.
     *
     * Generated from protobuf field <code>string type = 1;</code>
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * The full name of the resource type. It must be in the format of
     * {service_name}/{resource_type_kind}. The resource type names are
     * singular and do not contain version numbers.
     * For example: `storage.googleapis.com/Bucket`
     * The value of the resource_type_kind must follow the regular expression
     * /[A-Z][a-zA-Z0-9]+/. It must start with upper case character and
     * recommended to use PascalCase (UpperCamelCase). The maximum number of
     * characters allowed for the resource_type_kind is 100.
     *
     * Generated from protobuf field <code>string type = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setType($var)
    {
        GPBUtil::checkString($var, True);
        $this->type = $var;

        return $this;
    }

    /**
     * Required. The valid pattern or patterns for this resource's names.
     * Examples:
     *   - "projects/{project}/topics/{topic}"
     *   - "projects/{project}/knowledgeBases/{knowledge_base}"
     * The components in braces correspond to the IDs for each resource in the
     * hierarchy. It is expected that, if multiple patterns are provided,
     * the same component name (e.g. "project") refers to IDs of the same
     * type of resource.
     *
     * Generated from protobuf field <code>repeated string pattern = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Required. The valid pattern or patterns for this resource's names.
     * Examples:
     *   - "projects/{project}/topics/{topic}"
     *   - "projects/{project}/knowledgeBases/{knowledge_base}"
     * The components in braces correspond to the IDs for each resource in the
     * hierarchy. It is expected that, if multiple patterns are provided,
     * the same component name (e.g. "project") refers to IDs of the same
     * type of resource.
     *
     * Generated from protobuf field <code>repeated string pattern = 2;</code>
     * @param string[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setPattern($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->pattern = $arr;

        return $this;
    }

    /**
     * Optional. The field on the resource that designates the resource name
     * field. If omitted, this is assumed to be "name".
     *
     * Generated from protobuf field <code>string name_field = 3;</code>
     * @return string
     */
    public function getNameField()
    {
        return $this->name_field;
    }

    /**
     * Optional. The field on the resource that designates the resource name
     * field. If omitted, this is assumed to be "name".
     *
     * Generated from protobuf field <code>string name_field = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setNameField($var)
    {
        GPBUtil::checkString($var, True);
        $this->name_field = $var;

        return $this;
    }

    /**
     * Optional. The historical or future-looking state of the resource pattern.
     * Example:
     *   // The InspectTemplate message originally only supported resource
     *   // names with organization, and project was added later.
     *   message InspectTemplate {
     *     option (google.api.resource) = {
     *       type: "dlp.googleapis.com/InspectTemplate"
     *       pattern: "organizations/{organization}/inspectTemplates/{inspect_template}"
     *       pattern: "projects/{project}/inspectTemplates/{inspect_template}"
     *       history: ORIGINALLY_SINGLE_PATTERN
     *     };
     *   }
     *
     * Generated from protobuf field <code>.google.api.ResourceDescriptor.History history = 4;</code>
     * @return int
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * Optional. The historical or future-looking state of the resource pattern.
     * Example:
     *   // The InspectTemplate message originally only supported resource
     *   // names with organization, and project was added later.
     *   message InspectTemplate {
     *     option (google.api.resource) = {
     *       type: "dlp.googleapis.com/InspectTemplate"
     *       pattern: "organizations/{organization}/inspectTemplates/{inspect_template}"
     *       pattern: "projects/{project}/inspectTemplates/{inspect_template}"
     *       history: ORIGINALLY_SINGLE_PATTERN
     *     };
     *   }
     *
     * Generated from protobuf field <code>.google.api.ResourceDescriptor.History history = 4;</code>
     * @param int $var
     * @return $this
     */
    public function setHistory($var)
    {
        GPBUtil::checkEnum($var, \Google\Api\ResourceDescriptor_History::class);
        $this->history = $var;

        return $this;
    }

}
