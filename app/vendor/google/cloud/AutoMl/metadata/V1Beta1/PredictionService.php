<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/cloud/automl/v1beta1/prediction_service.proto

namespace GPBMetadata\Google\Cloud\Automl\V1Beta1;

class PredictionService
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\Google\Api\Annotations::initOnce();
        \GPBMetadata\Google\Api\Client::initOnce();
        \GPBMetadata\Google\Cloud\Automl\V1Beta1\AnnotationPayload::initOnce();
        \GPBMetadata\Google\Cloud\Automl\V1Beta1\DataItems::initOnce();
        \GPBMetadata\Google\Cloud\Automl\V1Beta1\Io::initOnce();
        \GPBMetadata\Google\Cloud\Automl\V1Beta1\Operations::initOnce();
        \GPBMetadata\Google\Longrunning\Operations::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0a920f0a34676f6f676c652f636c6f75642f6175746f6d6c2f7631626574" .
            "61312f70726564696374696f6e5f736572766963652e70726f746f121b67" .
            "6f6f676c652e636c6f75642e6175746f6d6c2e763162657461311a17676f" .
            "6f676c652f6170692f636c69656e742e70726f746f1a34676f6f676c652f" .
            "636c6f75642f6175746f6d6c2f763162657461312f616e6e6f746174696f" .
            "6e5f7061796c6f61642e70726f746f1a2c676f6f676c652f636c6f75642f" .
            "6175746f6d6c2f763162657461312f646174615f6974656d732e70726f74" .
            "6f1a24676f6f676c652f636c6f75642f6175746f6d6c2f76316265746131" .
            "2f696f2e70726f746f1a2c676f6f676c652f636c6f75642f6175746f6d6c" .
            "2f763162657461312f6f7065726174696f6e732e70726f746f1a23676f6f" .
            "676c652f6c6f6e6772756e6e696e672f6f7065726174696f6e732e70726f" .
            "746f22d4010a0e5072656469637452657175657374120c0a046e616d6518" .
            "0120012809123c0a077061796c6f616418022001280b322b2e676f6f676c" .
            "652e636c6f75642e6175746f6d6c2e763162657461312e4578616d706c65" .
            "5061796c6f616412470a06706172616d7318032003280b32372e676f6f67" .
            "6c652e636c6f75642e6175746f6d6c2e763162657461312e507265646963" .
            "74526571756573742e506172616d73456e7472791a2d0a0b506172616d73" .
            "456e747279120b0a036b6579180120012809120d0a0576616c7565180220" .
            "0128093a023801229a020a0f50726564696374526573706f6e7365123f0a" .
            "077061796c6f616418012003280b322e2e676f6f676c652e636c6f75642e" .
            "6175746f6d6c2e763162657461312e416e6e6f746174696f6e5061796c6f" .
            "616412470a1270726570726f6365737365645f696e70757418032001280b" .
            "322b2e676f6f676c652e636c6f75642e6175746f6d6c2e76316265746131" .
            "2e4578616d706c655061796c6f6164124c0a086d65746164617461180220" .
            "03280b323a2e676f6f676c652e636c6f75642e6175746f6d6c2e76316265" .
            "7461312e50726564696374526573706f6e73652e4d65746164617461456e" .
            "7472791a2f0a0d4d65746164617461456e747279120b0a036b6579180120" .
            "012809120d0a0576616c75651802200128093a02380122ba020a13426174" .
            "63685072656469637452657175657374120c0a046e616d65180120012809" .
            "124a0a0c696e7075745f636f6e66696718032001280b32342e676f6f676c" .
            "652e636c6f75642e6175746f6d6c2e763162657461312e42617463685072" .
            "6564696374496e707574436f6e666967124c0a0d6f75747075745f636f6e" .
            "66696718042001280b32352e676f6f676c652e636c6f75642e6175746f6d" .
            "6c2e763162657461312e4261746368507265646963744f7574707574436f" .
            "6e666967124c0a06706172616d7318052003280b323c2e676f6f676c652e" .
            "636c6f75642e6175746f6d6c2e763162657461312e426174636850726564" .
            "696374526571756573742e506172616d73456e7472791a2d0a0b50617261" .
            "6d73456e747279120b0a036b6579180120012809120d0a0576616c756518" .
            "02200128093a0238012296010a1242617463685072656469637452657375" .
            "6c74124f0a086d6574616461746118012003280b323d2e676f6f676c652e" .
            "636c6f75642e6175746f6d6c2e763162657461312e426174636850726564" .
            "696374526573756c742e4d65746164617461456e7472791a2f0a0d4d6574" .
            "6164617461456e747279120b0a036b6579180120012809120d0a0576616c" .
            "75651802200128093a02380132b4030a1150726564696374696f6e536572" .
            "7669636512a8010a0750726564696374122b2e676f6f676c652e636c6f75" .
            "642e6175746f6d6c2e763162657461312e50726564696374526571756573" .
            "741a2c2e676f6f676c652e636c6f75642e6175746f6d6c2e763162657461" .
            "312e50726564696374526573706f6e7365224282d3e493023c22372f7631" .
            "62657461312f7b6e616d653d70726f6a656374732f2a2f6c6f636174696f" .
            "6e732f2a2f6d6f64656c732f2a7d3a707265646963743a012a12a8010a0c" .
            "42617463685072656469637412302e676f6f676c652e636c6f75642e6175" .
            "746f6d6c2e763162657461312e4261746368507265646963745265717565" .
            "73741a1d2e676f6f676c652e6c6f6e6772756e6e696e672e4f7065726174" .
            "696f6e224782d3e4930241223c2f763162657461312f7b6e616d653d7072" .
            "6f6a656374732f2a2f6c6f636174696f6e732f2a2f6d6f64656c732f2a7d" .
            "3a6261746368507265646963743a012a1a49ca41156175746f6d6c2e676f" .
            "6f676c65617069732e636f6dd2412e68747470733a2f2f7777772e676f6f" .
            "676c65617069732e636f6d2f617574682f636c6f75642d706c6174666f72" .
            "6d42bd010a1f636f6d2e676f6f676c652e636c6f75642e6175746f6d6c2e" .
            "76316265746131421650726564696374696f6e5365727669636550726f74" .
            "6f50015a41676f6f676c652e676f6c616e672e6f72672f67656e70726f74" .
            "6f2f676f6f676c65617069732f636c6f75642f6175746f6d6c2f76316265" .
            "7461313b6175746f6d6cca021b476f6f676c655c436c6f75645c4175746f" .
            "4d6c5c56316265746131ea021e476f6f676c653a3a436c6f75643a3a4175" .
            "746f4d4c3a3a56316265746131620670726f746f33"
        ), true);

        static::$is_initialized = true;
    }
}
