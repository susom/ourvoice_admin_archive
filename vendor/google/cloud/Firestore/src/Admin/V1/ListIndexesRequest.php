<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/firestore/admin/v1/firestore_admin.proto

namespace Google\Cloud\Firestore\Admin\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * The request for [FirestoreAdmin.ListIndexes][google.firestore.admin.v1.FirestoreAdmin.ListIndexes].
 *
 * Generated from protobuf message <code>google.firestore.admin.v1.ListIndexesRequest</code>
 */
class ListIndexesRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * A parent name of the form
     * `projects/{project_id}/databases/{database_id}/collectionGroups/{collection_id}`
     *
     * Generated from protobuf field <code>string parent = 1;</code>
     */
    private $parent = '';
    /**
     * The filter to apply to list results.
     *
     * Generated from protobuf field <code>string filter = 2;</code>
     */
    private $filter = '';
    /**
     * The number of results to return.
     *
     * Generated from protobuf field <code>int32 page_size = 3;</code>
     */
    private $page_size = 0;
    /**
     * A page token, returned from a previous call to
     * [FirestoreAdmin.ListIndexes][google.firestore.admin.v1.FirestoreAdmin.ListIndexes], that may be used to get the next
     * page of results.
     *
     * Generated from protobuf field <code>string page_token = 4;</code>
     */
    private $page_token = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $parent
     *           A parent name of the form
     *           `projects/{project_id}/databases/{database_id}/collectionGroups/{collection_id}`
     *     @type string $filter
     *           The filter to apply to list results.
     *     @type int $page_size
     *           The number of results to return.
     *     @type string $page_token
     *           A page token, returned from a previous call to
     *           [FirestoreAdmin.ListIndexes][google.firestore.admin.v1.FirestoreAdmin.ListIndexes], that may be used to get the next
     *           page of results.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Firestore\Admin\V1\FirestoreAdmin::initOnce();
        parent::__construct($data);
    }

    /**
     * A parent name of the form
     * `projects/{project_id}/databases/{database_id}/collectionGroups/{collection_id}`
     *
     * Generated from protobuf field <code>string parent = 1;</code>
     * @return string
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * A parent name of the form
     * `projects/{project_id}/databases/{database_id}/collectionGroups/{collection_id}`
     *
     * Generated from protobuf field <code>string parent = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setParent($var)
    {
        GPBUtil::checkString($var, True);
        $this->parent = $var;

        return $this;
    }

    /**
     * The filter to apply to list results.
     *
     * Generated from protobuf field <code>string filter = 2;</code>
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * The filter to apply to list results.
     *
     * Generated from protobuf field <code>string filter = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setFilter($var)
    {
        GPBUtil::checkString($var, True);
        $this->filter = $var;

        return $this;
    }

    /**
     * The number of results to return.
     *
     * Generated from protobuf field <code>int32 page_size = 3;</code>
     * @return int
     */
    public function getPageSize()
    {
        return $this->page_size;
    }

    /**
     * The number of results to return.
     *
     * Generated from protobuf field <code>int32 page_size = 3;</code>
     * @param int $var
     * @return $this
     */
    public function setPageSize($var)
    {
        GPBUtil::checkInt32($var);
        $this->page_size = $var;

        return $this;
    }

    /**
     * A page token, returned from a previous call to
     * [FirestoreAdmin.ListIndexes][google.firestore.admin.v1.FirestoreAdmin.ListIndexes], that may be used to get the next
     * page of results.
     *
     * Generated from protobuf field <code>string page_token = 4;</code>
     * @return string
     */
    public function getPageToken()
    {
        return $this->page_token;
    }

    /**
     * A page token, returned from a previous call to
     * [FirestoreAdmin.ListIndexes][google.firestore.admin.v1.FirestoreAdmin.ListIndexes], that may be used to get the next
     * page of results.
     *
     * Generated from protobuf field <code>string page_token = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setPageToken($var)
    {
        GPBUtil::checkString($var, True);
        $this->page_token = $var;

        return $this;
    }

}

