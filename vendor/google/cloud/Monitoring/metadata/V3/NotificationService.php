<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/monitoring/v3/notification_service.proto

namespace GPBMetadata\Google\Monitoring\V3;

class NotificationService
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\Google\Api\Annotations::initOnce();
        \GPBMetadata\Google\Monitoring\V3\Notification::initOnce();
        \GPBMetadata\Google\Protobuf\GPBEmpty::initOnce();
        \GPBMetadata\Google\Protobuf\FieldMask::initOnce();
        \GPBMetadata\Google\Protobuf\Struct::initOnce();
        \GPBMetadata\Google\Protobuf\Timestamp::initOnce();
        \GPBMetadata\Google\Api\Client::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0ace1f0a2f676f6f676c652f6d6f6e69746f72696e672f76332f6e6f7469" .
            "6669636174696f6e5f736572766963652e70726f746f1214676f6f676c65" .
            "2e6d6f6e69746f72696e672e76331a27676f6f676c652f6d6f6e69746f72" .
            "696e672f76332f6e6f74696669636174696f6e2e70726f746f1a1b676f6f" .
            "676c652f70726f746f6275662f656d7074792e70726f746f1a20676f6f67" .
            "6c652f70726f746f6275662f6669656c645f6d61736b2e70726f746f1a1c" .
            "676f6f676c652f70726f746f6275662f7374727563742e70726f746f1a1f" .
            "676f6f676c652f70726f746f6275662f74696d657374616d702e70726f74" .
            "6f1a17676f6f676c652f6170692f636c69656e742e70726f746f22600a29" .
            "4c6973744e6f74696669636174696f6e4368616e6e656c44657363726970" .
            "746f727352657175657374120c0a046e616d6518042001280912110a0970" .
            "6167655f73697a6518022001280512120a0a706167655f746f6b656e1803" .
            "200128092297010a2a4c6973744e6f74696669636174696f6e4368616e6e" .
            "656c44657363726970746f7273526573706f6e736512500a136368616e6e" .
            "656c5f64657363726970746f727318012003280b32332e676f6f676c652e" .
            "6d6f6e69746f72696e672e76332e4e6f74696669636174696f6e4368616e" .
            "6e656c44657363726970746f7212170a0f6e6578745f706167655f746f6b" .
            "656e18022001280922370a274765744e6f74696669636174696f6e436861" .
            "6e6e656c44657363726970746f7252657175657374120c0a046e616d6518" .
            "032001280922790a204372656174654e6f74696669636174696f6e436861" .
            "6e6e656c52657175657374120c0a046e616d6518032001280912470a146e" .
            "6f74696669636174696f6e5f6368616e6e656c18022001280b32292e676f" .
            "6f676c652e6d6f6e69746f72696e672e76332e4e6f74696669636174696f" .
            "6e4368616e6e656c22780a1f4c6973744e6f74696669636174696f6e4368" .
            "616e6e656c7352657175657374120c0a046e616d65180520012809120e0a" .
            "0666696c74657218062001280912100a086f726465725f62791807200128" .
            "0912110a09706167655f73697a6518032001280512120a0a706167655f74" .
            "6f6b656e1804200128092285010a204c6973744e6f74696669636174696f" .
            "6e4368616e6e656c73526573706f6e736512480a156e6f74696669636174" .
            "696f6e5f6368616e6e656c7318032003280b32292e676f6f676c652e6d6f" .
            "6e69746f72696e672e76332e4e6f74696669636174696f6e4368616e6e65" .
            "6c12170a0f6e6578745f706167655f746f6b656e180220012809222d0a1d" .
            "4765744e6f74696669636174696f6e4368616e6e656c5265717565737412" .
            "0c0a046e616d65180320012809229c010a205570646174654e6f74696669" .
            "636174696f6e4368616e6e656c52657175657374122f0a0b757064617465" .
            "5f6d61736b18022001280b321a2e676f6f676c652e70726f746f6275662e" .
            "4669656c644d61736b12470a146e6f74696669636174696f6e5f6368616e" .
            "6e656c18032001280b32292e676f6f676c652e6d6f6e69746f72696e672e" .
            "76332e4e6f74696669636174696f6e4368616e6e656c223f0a2044656c65" .
            "74654e6f74696669636174696f6e4368616e6e656c52657175657374120c" .
            "0a046e616d65180320012809120d0a05666f726365180520012808223e0a" .
            "2e53656e644e6f74696669636174696f6e4368616e6e656c566572696669" .
            "636174696f6e436f646552657175657374120c0a046e616d651801200128" .
            "09226e0a2d4765744e6f74696669636174696f6e4368616e6e656c566572" .
            "696669636174696f6e436f646552657175657374120c0a046e616d651801" .
            "20012809122f0a0b6578706972655f74696d6518022001280b321a2e676f" .
            "6f676c652e70726f746f6275662e54696d657374616d70226f0a2e476574" .
            "4e6f74696669636174696f6e4368616e6e656c566572696669636174696f" .
            "6e436f6465526573706f6e7365120c0a04636f6465180120012809122f0a" .
            "0b6578706972655f74696d6518022001280b321a2e676f6f676c652e7072" .
            "6f746f6275662e54696d657374616d70223e0a205665726966794e6f7469" .
            "6669636174696f6e4368616e6e656c52657175657374120c0a046e616d65" .
            "180120012809120c0a04636f646518022001280932e7110a1a4e6f746966" .
            "69636174696f6e4368616e6e656c5365727669636512e5010a224c697374" .
            "4e6f74696669636174696f6e4368616e6e656c44657363726970746f7273" .
            "123f2e676f6f676c652e6d6f6e69746f72696e672e76332e4c6973744e6f" .
            "74696669636174696f6e4368616e6e656c44657363726970746f72735265" .
            "71756573741a402e676f6f676c652e6d6f6e69746f72696e672e76332e4c" .
            "6973744e6f74696669636174696f6e4368616e6e656c4465736372697074" .
            "6f7273526573706f6e7365223c82d3e493023612342f76332f7b6e616d65" .
            "3d70726f6a656374732f2a7d2f6e6f74696669636174696f6e4368616e6e" .
            "656c44657363726970746f727312d6010a204765744e6f74696669636174" .
            "696f6e4368616e6e656c44657363726970746f72123d2e676f6f676c652e" .
            "6d6f6e69746f72696e672e76332e4765744e6f74696669636174696f6e43" .
            "68616e6e656c44657363726970746f72526571756573741a332e676f6f67" .
            "6c652e6d6f6e69746f72696e672e76332e4e6f74696669636174696f6e43" .
            "68616e6e656c44657363726970746f72223e82d3e493023812362f76332f" .
            "7b6e616d653d70726f6a656374732f2a2f6e6f74696669636174696f6e43" .
            "68616e6e656c44657363726970746f72732f2a7d12bd010a184c6973744e" .
            "6f74696669636174696f6e4368616e6e656c7312352e676f6f676c652e6d" .
            "6f6e69746f72696e672e76332e4c6973744e6f74696669636174696f6e43" .
            "68616e6e656c73526571756573741a362e676f6f676c652e6d6f6e69746f" .
            "72696e672e76332e4c6973744e6f74696669636174696f6e4368616e6e65" .
            "6c73526573706f6e7365223282d3e493022c122a2f76332f7b6e616d653d" .
            "70726f6a656374732f2a7d2f6e6f74696669636174696f6e4368616e6e65" .
            "6c7312ae010a164765744e6f74696669636174696f6e4368616e6e656c12" .
            "332e676f6f676c652e6d6f6e69746f72696e672e76332e4765744e6f7469" .
            "6669636174696f6e4368616e6e656c526571756573741a292e676f6f676c" .
            "652e6d6f6e69746f72696e672e76332e4e6f74696669636174696f6e4368" .
            "616e6e656c223482d3e493022e122c2f76332f7b6e616d653d70726f6a65" .
            "6374732f2a2f6e6f74696669636174696f6e4368616e6e656c732f2a7d12" .
            "c8010a194372656174654e6f74696669636174696f6e4368616e6e656c12" .
            "362e676f6f676c652e6d6f6e69746f72696e672e76332e4372656174654e" .
            "6f74696669636174696f6e4368616e6e656c526571756573741a292e676f" .
            "6f676c652e6d6f6e69746f72696e672e76332e4e6f74696669636174696f" .
            "6e4368616e6e656c224882d3e4930242222a2f76332f7b6e616d653d7072" .
            "6f6a656374732f2a7d2f6e6f74696669636174696f6e4368616e6e656c73" .
            "3a146e6f74696669636174696f6e5f6368616e6e656c12df010a19557064" .
            "6174654e6f74696669636174696f6e4368616e6e656c12362e676f6f676c" .
            "652e6d6f6e69746f72696e672e76332e5570646174654e6f746966696361" .
            "74696f6e4368616e6e656c526571756573741a292e676f6f676c652e6d6f" .
            "6e69746f72696e672e76332e4e6f74696669636174696f6e4368616e6e65" .
            "6c225f82d3e493025932412f76332f7b6e6f74696669636174696f6e5f63" .
            "68616e6e656c2e6e616d653d70726f6a656374732f2a2f6e6f7469666963" .
            "6174696f6e4368616e6e656c732f2a7d3a146e6f74696669636174696f6e" .
            "5f6368616e6e656c12a1010a1944656c6574654e6f74696669636174696f" .
            "6e4368616e6e656c12362e676f6f676c652e6d6f6e69746f72696e672e76" .
            "332e44656c6574654e6f74696669636174696f6e4368616e6e656c526571" .
            "756573741a162e676f6f676c652e70726f746f6275662e456d7074792234" .
            "82d3e493022e2a2c2f76332f7b6e616d653d70726f6a656374732f2a2f6e" .
            "6f74696669636174696f6e4368616e6e656c732f2a7d12d5010a2753656e" .
            "644e6f74696669636174696f6e4368616e6e656c56657269666963617469" .
            "6f6e436f646512442e676f6f676c652e6d6f6e69746f72696e672e76332e" .
            "53656e644e6f74696669636174696f6e4368616e6e656c56657269666963" .
            "6174696f6e436f6465526571756573741a162e676f6f676c652e70726f74" .
            "6f6275662e456d707479224c82d3e493024622412f76332f7b6e616d653d" .
            "70726f6a656374732f2a2f6e6f74696669636174696f6e4368616e6e656c" .
            "732f2a7d3a73656e64566572696669636174696f6e436f64653a012a1280" .
            "020a264765744e6f74696669636174696f6e4368616e6e656c5665726966" .
            "69636174696f6e436f646512432e676f6f676c652e6d6f6e69746f72696e" .
            "672e76332e4765744e6f74696669636174696f6e4368616e6e656c566572" .
            "696669636174696f6e436f6465526571756573741a442e676f6f676c652e" .
            "6d6f6e69746f72696e672e76332e4765744e6f74696669636174696f6e43" .
            "68616e6e656c566572696669636174696f6e436f6465526573706f6e7365" .
            "224b82d3e493024522402f76332f7b6e616d653d70726f6a656374732f2a" .
            "2f6e6f74696669636174696f6e4368616e6e656c732f2a7d3a6765745665" .
            "72696669636174696f6e436f64653a012a12be010a195665726966794e6f" .
            "74696669636174696f6e4368616e6e656c12362e676f6f676c652e6d6f6e" .
            "69746f72696e672e76332e5665726966794e6f74696669636174696f6e43" .
            "68616e6e656c526571756573741a292e676f6f676c652e6d6f6e69746f72" .
            "696e672e76332e4e6f74696669636174696f6e4368616e6e656c223e82d3" .
            "e493023822332f76332f7b6e616d653d70726f6a656374732f2a2f6e6f74" .
            "696669636174696f6e4368616e6e656c732f2a7d3a7665726966793a012a" .
            "1aa901ca41196d6f6e69746f72696e672e676f6f676c65617069732e636f" .
            "6dd241890168747470733a2f2f7777772e676f6f676c65617069732e636f" .
            "6d2f617574682f636c6f75642d706c6174666f726d2c68747470733a2f2f" .
            "7777772e676f6f676c65617069732e636f6d2f617574682f6d6f6e69746f" .
            "72696e672c68747470733a2f2f7777772e676f6f676c65617069732e636f" .
            "6d2f617574682f6d6f6e69746f72696e672e7265616442b0010a18636f6d" .
            "2e676f6f676c652e6d6f6e69746f72696e672e763342184e6f7469666963" .
            "6174696f6e5365727669636550726f746f50015a3e676f6f676c652e676f" .
            "6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6d" .
            "6f6e69746f72696e672f76333b6d6f6e69746f72696e67aa021a476f6f67" .
            "6c652e436c6f75642e4d6f6e69746f72696e672e5633ca021a476f6f676c" .
            "655c436c6f75645c4d6f6e69746f72696e675c5633620670726f746f33"
        ), true);

        static::$is_initialized = true;
    }
}

